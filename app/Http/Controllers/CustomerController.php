<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        $query = $user->customers();
        
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }
        
        $customers = $query->orderBy('name')
            ->paginate(15);
        
        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email,NULL,id,user_id,' . Auth::id(),
            'phone_number' => 'required|string|min:9|max:12',
            'address' => 'nullable|string|max:500',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $user = Auth::user();
        
        $phoneNumber = preg_replace('/[^0-9]/', '', $request->phone_number);
        if (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = '254' . substr($phoneNumber, 1);
        }
        
        Customer::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $phoneNumber,
            'address' => $request->address,
        ]);
        
        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully');
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);
        
        $customer->load(['invoices' => function ($query) {
            $query->latest()->limit(10)->get();
        }]);
        
        $stats = [
            'total_invoices' => $customer->invoices()->count(),
            'total_paid' => $customer->invoices()->paid()->sum('amount'),
            'total_pending' => $customer->getTotalPendingAmount(),
        ];
        
        return view('customers.show', compact('customer', 'stats'));
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);
        
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email,' . $customer->id . ',id,user_id,' . Auth::id(),
            'phone_number' => 'required|string|min:9|max:12',
            'address' => 'nullable|string|max:500',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $phoneNumber = preg_replace('/[^0-9]/', '', $request->phone_number);
        if (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = '254' . substr($phoneNumber, 1);
        }
        
        $customer->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $phoneNumber,
            'address' => $request->address,
        ]);
        
        return redirect()->route('customers.show', $customer)
            ->with('success', 'Customer updated successfully');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);
        
        if ($customer->invoices()->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete customer with existing invoices');
        }
        
        $customer->delete();
        
        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully');
    }
}