<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->user_id.',user_id'],
        ]);
        
        // Update profile
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();
        
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
            'new_password' => ['required', 'min:8', 'confirmed'],
        ]);
        
        $user = User::find(Auth::user()->user_id);
        
        // Periksa apakah password lama benar
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password lama salah']);
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