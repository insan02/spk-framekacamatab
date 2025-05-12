<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\ActivityLogService; // Added ActivityLogService

class CustomerController extends Controller
{
    /**
     * Display a listing of the customers.
     */
    public function index()
    {
        $customers = Customer::orderBy('name')->paginate(10);
        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/'
            ],
            'phone' => [
                'required',
                'string',
                'min:11',
                'max:15',
                'regex:/^[0-9]+$/'
            ],
            'address' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $existingCustomer = Customer::where('phone', $request->phone)->first();
        if ($existingCustomer) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['phone' => 'No Hp tersebut sudah ada.']);
        }

        $customer = Customer::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        // Log activity for customer creation
        ActivityLogService::log(
            'create',
            'customer',
            $customer->customer_id,
            null,
            $customer->toArray(),
            'Membuat pelanggan baru: ' . $customer->name
        );

        return redirect()->route('penilaian.index')
            ->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/'
            ],
            'phone' => [
                'required',
                'string',
                'min:11',
                'max:15',
                'regex:/^[0-9]+$/'
            ],
            'address' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Cek manual untuk nama duplikat (kecuali untuk record saat ini)
        $existingCustomer = Customer::where('phone', $request->phone)
                            ->where('customer_id', '!=', $customer->customer_id)
                            ->first();
        
        if ($existingCustomer) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['phone' => 'No HP tersebut sudah ada.']);
        }

        // Store old data for comparison and logging
        $oldData = $customer->toArray();
        $oldName = $customer->name;
        
        // Check if there are any changes
        if ($oldName === $request->name && 
            $customer->phone === $request->phone && 
            $customer->address === $request->address) {
            // No changes, redirect without update and log
            return redirect()->route('penilaian.index')
                ->with('info', 'Tidak ada perubahan data pada pelanggan');
        }

        try {
            $customer->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);
            
            // Simplified description for the activity log
            $description = "Mengubah data pelanggan: {$customer->name}";

            // Log activity for customer update
            ActivityLogService::log(
                'update',
                'customer',
                $customer->customer_id,
                $oldData,
                $customer->toArray(),
                $description
            );

            return redirect()->route('penilaian.index')
                ->with('success', 'Pelanggan berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui pelanggan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer)
    {
        try {
            $customerName = $customer->name;
            $customerData = $customer->toArray();
            
            // Check if customer has related records
            // Add any relation checks here if needed, similar to KriteriaController
            
            $customer->delete();
            
            // Log activity for customer deletion
            ActivityLogService::log(
                'delete',
                'customer',
                $customer->customer_id,
                $customerData,
                null,
                'Menghapus pelanggan: ' . $customerName
            );

            return redirect()->route('penilaian.index')
                ->with('success', 'Pelanggan berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('penilaian.index')
                ->with('error', "Gagal menghapus pelanggan '{$customer->name}': " . $e->getMessage());
        }
    }

    /**
     * Get customer data via AJAX request.
     */
    public function getCustomerData(Customer $customer)
    {
        return response()->json([
            'customer' => $customer
        ]);
    }

    /**
     * Search for customers by name or phone.
     */
    public function search(Request $request)
    {
        $query = $request->input('q');
        
        if (empty($query)) {
            return response()->json(['customers' => []]);
        }
        
        $customers = Customer::where('name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('address', 'like', "%{$query}%")
            ->get();
            
        return response()->json(['customers' => $customers]);
    }

    /**
     * Get customer details by ID.
     */
    public function getCustomerDetails($id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);
    }
}