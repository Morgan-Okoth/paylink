@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Dashboard</h4>
    <div>
        <a href="{{ route('invoices.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Invoice
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card revenue">
            <div class="card-body">
                <div class="text-muted small">Total Revenue</div>
                <div class="h3 mb-0">KES {{ number_format($stats['total_revenue'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card paid">
            <div class="card-body">
                <div class="text-muted small">Paid Invoices</div>
                <div class="h3 mb-0">{{ $stats['paid_invoices'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card pending">
            <div class="card-body">
                <div class="text-muted small">Pending Amount</div>
                <div class="h3 mb-0">KES {{ number_format($stats['pending_amount'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card overdue">
            <div class="card-body">
                <div class="text-muted small">Overdue</div>
                <div class="h3 mb-0">{{ $stats['overdue_invoices'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card table-card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Recent Invoices</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(auth()->user()->invoices()->with('customer')->latest()->limit(5)->get() as $invoice)
                            <tr>
                                <td>
                                    <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                                </td>
                                <td>{{ $invoice->customer->name }}</td>
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
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No invoices yet. <a href="{{ route('invoices.create') }}">Create one</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('invoices.index') }}" class="text-decoration-none">View all invoices</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card table-card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('invoices.create') }}" class="btn btn-outline-primary">
                        <i class="bi bi-receipt me-2"></i> Create Invoice
                    </a>
                    <a href="{{ route('customers.create') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-person-plus me-2"></i> Add Customer
                    </a>
                    <a href="{{ route('settings') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-gear me-2"></i> M-Pesa Settings
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card table-card mt-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">Summary</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Customers</span>
                    <strong>{{ $stats['total_customers'] }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Invoices</span>
                    <strong>{{ $stats['total_invoices'] }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Pending</span>
                    <strong>{{ $stats['pending_invoices'] }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection