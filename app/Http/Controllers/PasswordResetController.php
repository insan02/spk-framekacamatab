<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PasswordResetController extends Controller
{
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        // Validasi email harus dari gmail.com
        $request->validate([
            'email' => [
                'required',
                'email',
                'exists:users,email',
                'regex:/^[^\s@]+@gmail\.com$/'
            ]
        ], [
            'email.regex' => 'Alamat email harus menggunakan @gmail.com'
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => 'Tautan reset password berhasil dikirim.'])
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPasswordForm($token)
    {
        // Cek apakah token masih valid
        $email = request()->email;
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        // Jika record tidak ditemukan atau token tidak cocok, artinya token tidak valid
        if (!$resetRecord || !Hash::check($token, $resetRecord->token)) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Token reset password tidak valid atau sudah kadaluarsa. Silakan minta tautan reset password baru.']);
        }

        // Cek apakah token sudah kadaluarsa (umumnya 60 menit)
        $createdAt = \Carbon\Carbon::parse($resetRecord->created_at);
        if ($createdAt->addMinutes(15)->isPast()) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Token reset password sudah kadaluarsa. Silakan minta tautan reset password baru.']);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email
        ]);
    }

    public function resetPassword(Request $request)
    {
        // Validasi input dengan aturan password yang lebih ketat
        $request->validate([
            'token' => 'required',
            'email' => [
                'required',
                'email',
                'exists:users,email',
                'regex:/^[^\s@]+@gmail\.com$/'
            ],
            'password' => [
                'required',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
            ],
        ], [
            'email.regex' => 'Alamat email harus menggunakan @gmail.com',
            'password.regex' => 'Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka',
            'password.min' => 'Password harus minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak sesuai'
        ]);

        // Cek apakah token masih valid
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        // Jika record tidak ditemukan atau token tidak cocok, artinya token tidak valid
        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['token' => 'Token reset password tidak valid atau sudah kadaluarsa. Silakan minta tautan reset password baru.']);
        }

        // Cek apakah token sudah kadaluarsa (umumnya 60 menit)
        $createdAt = \Carbon\Carbon::parse($resetRecord->created_at);
        if ($createdAt->addMinutes(15)->isPast()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['token' => 'Token reset password sudah kadaluarsa. Silakan minta tautan reset password baru.']);
        }

        // Coba reset password dengan custom callback untuk menangani primary key user_id
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // Pastikan kita update password pengguna dengan benar
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        // Tampilkan hasil
        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Password Anda berhasil direset, Silakan Login menggunakan password baru!');
        } else {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }
    }
}