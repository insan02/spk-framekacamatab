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

    $user = User::find(Auth::id()); // Ubah Auth::user() ke User::find(Auth::id())

    // Periksa apakah password lama benar
    if (!Hash::check($request->current_password, $user->password)) {
        return back()->withErrors(['current_password' => 'Password lama salah']);
    }

    // Update password baru
    $user->password = Hash::make($request->new_password);
    $user->save();

    return redirect()->route('profile')->with('success', 'Password berhasil diperbarui');
}


}
