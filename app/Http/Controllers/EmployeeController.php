<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Notifications\NewEmployeeCredentialsNotification;
use App\Notifications\EmailChangeNotification;
use Illuminate\Support\Facades\Log;


class EmployeeController extends Controller
{
    public function index()
    {
        $employees = User::where('role', 'karyawan')->get();
        return view('employees.index', compact('employees'));
    }
    
    public function create()
    {
        return view('employees.create');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|regex:/^[A-Za-z\s]+$/',
            'email' => 'required|string|email|max:255|unique:users',
        ]);
        
        // Generate random password
        $password = $this->generateRandomPassword();
        
        // Create new employee
        $employee = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
            'role' => 'karyawan',
            'password_change_required' => true, // Flag to force password change on first login
        ]);
        
        // Send notification with credentials
        try {
            $employee->notify(new NewEmployeeCredentialsNotification($password));
        } catch (\Exception $e) {
            // Log error but continue
            Log::error('Failed to send notification to new employee: ' . $e->getMessage());
            // Continue execution without returning here
        }
        
        return redirect()->route('employees.index')
            ->with('success', 'Karyawan berhasil ditambahkan. Kredensial login telah dikirim ke email karyawan.');
    }
    
    public function edit($id)
    {
        $employee = User::where('role', 'karyawan')->where('user_id', $id)->firstOrFail();
        return view('employees.edit', compact('employee'));
    }
    
    public function update(Request $request, $id)
    {
        $employee = User::where('role', 'karyawan')->where('user_id', $id)->firstOrFail();
        
        $request->validate([
            'name' => 'required|regex:/^[A-Za-z\s]+$/',
            'email' => 'required|string|email|max:255|unique:users,email,' . $employee->user_id . ',user_id',
        ]);
        
        $employee->name = $request->name;
        
        // Check if email is being changed
        $emailChanged = $employee->email != $request->email;
        if ($emailChanged) {
            $oldEmail = $employee->email;
            $newEmail = $request->email;
            
            // Update email in database
            $employee->email = $newEmail;
            
            // Send notification to both old and new email addresses
            try {
                // Send to old email
                $employee->email = $oldEmail; // Temporarily set back to old email
                $employee->notify(new EmailChangeNotification($oldEmail, $newEmail));
                
                // Send to new email
                $employee->email = $newEmail; // Set to new email
                $employee->notify(new EmailChangeNotification($oldEmail, $newEmail));
                
                // Log success
                Log::info('Email change notification sent to both ' . $oldEmail . ' and ' . $newEmail);
            } catch (\Exception $e) {
                // Log error but continue
                Log::error('Failed to send email change notification: ' . $e->getMessage());
            }
        }
        
        $employee->save();
        
        $successMessage = 'Data karyawan berhasil diupdate';
        if ($emailChanged) {
            $successMessage .= '. Notifikasi perubahan email telah dikirim.';
        }
        
        return redirect()->route('employees.index')
            ->with('success', $successMessage);
    }
    
    public function destroy($id)
    {
        $employee = User::where('role', 'karyawan')->where('user_id', $id)->firstOrFail();
        $employee->delete();
        
        return redirect()->route('employees.index')
            ->with('success', 'Karyawan berhasil dihapus');
    }
    
    /**
     * Generate random password with 8 characters
     * 
     * @return string
     */
    private function generateRandomPassword()
    {
        // Generate password containing random uppercase, lowercase, numbers and special characters
        $uppercase = chr(rand(65, 90));
        $lowercase = chr(rand(97, 122));
        $number = rand(0, 9);
        $special = ['!', '@', '#', '$', '%', '^', '&', '*'][rand(0, 7)];
        
        // Generate 4 more random characters
        $remaining = Str::random(4);
        
        // Combine all characters and shuffle
        $password = str_shuffle($uppercase . $lowercase . $number . $special . $remaining);
        
        return $password;
    }
}