<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\User;

class PasswordResetController extends Controller
{
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        
        $status = Password::sendResetLink(
            $request->only('email')
        );
        
        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => 'Tautan reset password berhasil dikirim.'])
            : back()->withErrors(['email' => ($status)]);
    }

    public function showResetPasswordForm($token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => request()->email
        ]);
    }

    public function resetPassword(Request $request)
    {
        // Validasi input
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

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
                ->withErrors(['email' => ($status)]);
        }
    }
}