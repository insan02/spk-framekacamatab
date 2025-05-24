<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Notifications\OwnerEmailChangeNotification;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    // Menampilkan halaman profil
    public function show()
    {
        $user = Auth::user(); // Ambil data user yang sedang login
        return view('profile.index', compact('user'));
    }
    
    // Menampilkan halaman edit profil
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit-profile', compact('user'));
    }
    
    // Menyimpan profil yang diperbarui
    public function update(Request $request)
    {
        $user = User::find(Auth::user()->user_id);
        
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 
                'string', 
                'email', 
                'max:255',
                'unique:users,email,'.$user->user_id.',user_id',
                'regex:/^[\w.]+@gmail\.com$/' // Memastikan format email adalah @gmail.com
            ],
        ], [
            'email.regex' => 'Format email harus menggunakan domain @gmail.com'
        ]);
        
        // Cek apakah email berubah
        $emailChanged = $user->email != $request->email;
        $oldEmail = $user->email;
        
        // Update profile
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();
        
        // Jika email berubah dan user adalah owner, kirim notifikasi
        if ($emailChanged && $user->role === 'owner') {
            try {
                // Kirim notifikasi ke email lama
                $user->email = $oldEmail; // Temporarily set back to old email
                $user->notify(new OwnerEmailChangeNotification($oldEmail, $request->email));
                
                // Kirim notifikasi ke email baru
                $user->email = $request->email; // Set back to new email
                $user->notify(new OwnerEmailChangeNotification($oldEmail, $request->email));
                
                // Log notifikasi yang berhasil
                Log::info('Owner email change notification sent to both ' . $oldEmail . ' and ' . $request->email);
                
                return redirect()->route('profile')->with('success', 'Profil berhasil diperbarui dan notifikasi perubahan email telah dikirim.');
            } catch (\Exception $e) {
                // Log error tapi tetap lanjutkan
                Log::error('Failed to send owner email change notification: ' . $e->getMessage());
                
                return redirect()->route('profile')->with('success', 'Profil berhasil diperbarui tetapi gagal mengirim notifikasi perubahan email.');
            }
        }
        
        return redirect()->route('profile')->with('success', 'Profil berhasil diperbarui');
    }

    // Menampilkan halaman edit password
    public function editPassword()
{
    return view('profile.edit');
}

// Menyimpan password yang diperbarui
public function updatePassword(Request $request)
{
    $request->validate([
        'current_password' => ['required'],
        'new_password' => [
            'required',
            'min:8',
            'confirmed',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
        ],
    ], [
        'new_password.regex' => 'Password harus mengandung minimal 8 karakter dengan kombinasi huruf besar, huruf kecil, dan angka.',
        'new_password.min' => 'Password minimal harus 8 karakter.',
        'new_password.confirmed' => 'Konfirmasi password tidak cocok.',
        'current_password.required' => 'Password lama harus diisi.'
    ]);
    
    $user = User::find(Auth::user()->user_id);
    
    // Periksa apakah password lama benar
    if (!Hash::check($request->current_password, $user->password)) {
        return back()->withErrors(['current_password' => 'Password lama salah']);
    }
    
    // Periksa apakah password baru sama dengan password lama
    if (Hash::check($request->new_password, $user->password)) {
        return back()->withErrors(['new_password' => 'Password baru tidak boleh sama dengan password lama']);
    }
    
    // Update password baru
    $user->password = Hash::make($request->new_password);
    $user->save();
    
    // Tambahkan pesan sukses ke session
    $message = 'Password berhasil diperbarui, silakan login ulang ke aplikasi';
    
    // Logout user
    Auth::logout();
    
    // Invalidate session dan regenerate CSRF token
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    
    // Redirect ke halaman login dengan pesan sukses
    return redirect()->route('login')->with('success', $message);
}
}