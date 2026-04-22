@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Invoices</h4>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Invoice
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card text-center py-3">
            <div class="text-muted small">Total</div>
            <div class="h4 mb-0">{{ $stats['total'] }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center py-3 stat-card paid">
            <div class="text-muted small">Paid</div>
            <div class="h4 mb-0">{{ $stats['paid'] }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center py-3 stat-card pending">
            <div class="text-muted small">Pending</div>
            <div class="h4 mb-0">{{ $stats['pending'] }}</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center py-3 stat-card overdue">
            <div class="text-muted small">Overdue</div>
            <div class="h4 mb-0">{{ $stats['overdue'] }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center py-3">
            <div class="text-muted small">Revenue</div>
            <div class="h4 mb-0">KES {{ number_format($stats['revenue'], 2) }}</div>
        </div>
    </div>
</div>

<div class="card table-card">
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
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
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
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('invoices.show', $invoice) }}">View</a></li>
                                    @if($invoice->isPending() || $invoice->isOverdue())
                                    <li>
                                        <form action="{{ route('invoices.payment', $invoice) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item">Send Payment</button>
                                        </form>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            No invoices yet. <a href="{{ route('invoices.create') }}">Create one</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        {{ $invoices->links() }}
    </div>
</div>
@endsection