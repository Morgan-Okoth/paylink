@extends('layouts.app')

@section('title', $customer->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('customers.index') }}" class="text-decoration-none text-muted">&larr; Back to Customers</a>
        <h4 class="mb-0 mt-2">{{ $customer->name }}</h4>
    </div>
    <div>
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil"></i> Edit
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Contact Info</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Phone</div>
                    <div>{{ $customer->phone_number }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Email</div>
                    <div>{{ $customer->email ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-muted small">Address</div>
                    <div>{{ $customer->address ?? '-' }}</div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary btn-sm w-100 mb-2">
                    <i class="bi bi-receipt"></i> Create Invoice
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Invoices</h5>
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="text-muted small">Total</div>
                        <strong>{{ $stats['total_invoices'] }}</strong>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Paid</div>
                        <strong class="text-success">KES {{ number_format($stats['total_paid'], 2) }}</strong>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Pending</div>
                        <strong class="text-warning">KES {{ number_format($stats['total_pending'], 2) }}</strong>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->invoices as $invoice)
                            <tr>
                                <td>
                                    <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                                </td>
                                <td>KES {{ number_format($invoice->amount, 2) }}</td>
                                <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                                <td>
                                    <span class="status-badge status-{{ $invoice->status }}">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    No invoices yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection