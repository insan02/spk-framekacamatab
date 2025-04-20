<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Customer::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return redirect()->route('customers.index')
            ->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    /**
     * Store a new customer via AJAX.
     */
    public function storeAjax(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/'
            ],
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for similar customers by name (if force is not set)
        if (!$request->force) {
            $similarCustomers = Customer::where('name', 'like', $request->name)
                ->where(function($query) use ($request) {
                    $query->where('phone', '!=', $request->phone)
                        ->orWhere('address', '!=', $request->address);
                })
                ->get();

            if ($similarCustomers->count() > 0) {
                return response()->json([
                    'success' => false,
                    'similar_customers' => $similarCustomers
                ]);
            }
        }

        // Create new customer
        $customer = Customer::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return response()->json([
            'success' => true,
            'customer' => $customer
        ]);
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
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $customer->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return redirect()->route('customers.index')
            ->with('success', 'Pelanggan berhasil diperbarui.');
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Pelanggan berhasil dihapus.');
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