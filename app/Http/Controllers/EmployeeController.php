<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed'
        ]);
        
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'karyawan'
        ]);
        
        return redirect()->route('employees.index')
            ->with('success', 'Karyawan berhasil ditambahkan');
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $employee->user_id . ',user_id',
            'password' => 'nullable|string|min:8|confirmed'
        ]);
        
        $employee->name = $request->name;
        $employee->email = $request->email;
        
        if ($request->filled('password')) {
            $employee->password = Hash::make($request->password);
        }
        
        $employee->save();
        
        return redirect()->route('employees.index')
            ->with('success', 'Data karyawan berhasil diupdate');
    }
    
    public function destroy($id)
    {
        $employee = User::where('role', 'karyawan')->where('user_id', $id)->firstOrFail();
        $employee->delete();
        
        return redirect()->route('employees.index')
            ->with('success', 'Karyawan berhasil dihapus');
    }
}