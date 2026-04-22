<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use App\Services\DarajaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        $query = $user->invoices()->with('customer');
        
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate(15);
        
        $stats = [
            'total' => $user->invoices()->count(),
            'paid' => $user->invoices()->paid()->count(),
            'pending' => $user->invoices()->pending()->count(),
            'overdue' => $user->invoices()->overdue()->count(),
            'revenue' => $user->invoices()->paid()->sum('amount'),
            'pending_amount' => $user->invoices()->dueForPayment()->sum('amount'),
        ];
        
        return view('invoices.index', compact('invoices', 'stats'));
    }

    public function create(): View
    {
        $customerId = request('customer_id');
        $customers = Auth::user()->customers()->orderBy('name')->get();
        
        return view('invoices.create', compact('customers', 'customerId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'due_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $invoice = Invoice::create([
            'user_id' => Auth::id(),
            'customer_id' => $request->customer_id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'status' => Invoice::STATUS_PENDING,
            'notes' => $request->notes,
        ]);
        
        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice created successfully');
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);
        
        $invoice->load('customer', 'payments');
        
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorize('update', $invoice);
        
        $customers = Auth::user()->customers()->orderBy('name')->get();
        
        return view('invoices.edit', compact('invoice', 'customers'));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);
        
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'due_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $invoice->update($request->only(['customer_id', 'amount', 'due_date', 'notes']));
        
        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);
        
        if (!$invoice->isPending()) {
            return redirect()->back()
                ->with('error', 'Only pending invoices can be deleted');
        }
        
        $invoice->delete();
        
        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully');
    }

    public function requestPayment(Invoice $invoice): RedirectResponse
    {
        $this->authorize('view', $invoice);
        
        if (!$invoice->isPending() && !$invoice->isOverdue()) {
            return redirect()->back()
                ->with('error', 'Invoice is already paid');
        }
        
        $user = Auth::user();
        $customer = $invoice->customer;
        
        if (empty($customer->phone_number)) {
            return redirect()->back()
                ->with('error', 'Customer has no phone number');
        }
        
        try {
            $daraja = new DarajaService($user);
            
            $result = $daraja->initiateStkPush([
                'amount' => $invoice->amount,
                'phone_number' => $customer->phone_number,
                'callback_url' => config('app.url') . '/api/mpesa/callback',
                'account_reference' => $invoice->invoice_number,
                'transaction_desc' => "Payment for Invoice {$invoice->invoice_number}",
            ]);
            
            if ($result['success']) {
                $invoice->update([
                    'mpesa_checkout_request_id' => $result['checkout_request_id'],
                ]);
                
                $invoice->payments()->create([
                    'user_id' => $user->id,
                    'checkout_request_id' => $result['checkout_request_id'],
                    'merchant_request_id' => $result['merchant_request_id'],
                    'amount' => $invoice->amount,
                    'phone_number' => $customer->phone_number,
                    'status' => Payment::STATUS_PENDING,
                ]);
                
                return redirect()->back()
                    ->with('success', 'Payment request sent to ' . $customer->phone_number);
            }
            
            return redirect()->back()
                ->with('error', $result['customer_message'] ?? 'Failed to initiate payment');
            
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', 'Payment error: ' . $e->getMessage());
        }
    }
}