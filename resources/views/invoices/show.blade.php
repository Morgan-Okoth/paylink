@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('invoices.index') }}" class="text-decoration-none text-muted">&larr; Back to Invoices</a>
        <h4 class="mb-0 mt-2">{{ $invoice->invoice_number }}</h4>
    </div>
    <div>
        <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Invoice Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="text-muted small">Amount</div>
                        <div class="h4">KES {{ number_format($invoice->amount, 2) }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Due Date</div>
                        <div class="h5">{{ $invoice->due_date->format('M d, Y') }}</div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <div class="text-muted small">Customer</div>
                        <div class="h5">{{ $invoice->customer->name }}</div>
                        <div class="text-muted">{{ $invoice->customer->phone_number }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Created</div>
                        <div class="h5">{{ $invoice->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
                @if($invoice->notes)
                <hr>
                <div class="text-muted small">Notes</div>
                <p class="mb-0">{{ $invoice->notes }}</p>
                @endif
                @if($invoice->paid_at)
                <hr>
                <div class="text-muted small">Paid On</div>
                <p class="mb-0">{{ $invoice->paid_at->format('M d, Y H:i') }}</p>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        @if($invoice->isPending() || $invoice->isOverdue())
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Request Payment</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">Send M-Pesa payment request to {{ $invoice->customer->phone_number }}</p>
                <form action="{{ route('invoices.payment', $invoice) }}" method="POST" class="payment-form">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-phone"></i> Send STK Push
                    </button>
                </form>
            </div>
        </div>
        @endif
        
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Payment History</h5>
            </div>
            <div class="card-body p-0">
                @forelse($invoice->payments as $payment)
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between">
                        <span class="status-badge status-{{ $payment->status == 'completed' ? 'paid' : 'pending' }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                        <span class="text-muted small">{{ $payment->created_at->format('M d H:i') }}</span>
                    </div>
                    @if($payment->mpesa_receipt_number)
                    <div class="small mt-1">Receipt: {{ $payment->mpesa_receipt_number }}</div>
                    @endif
                </div>
                @empty
                <div class="p-3 text-muted text-center">No payment requests</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
var form = document.querySelector('.payment-form');
if (form) {
    form.addEventListener('submit', function(e) {
        if (!confirm('Send M-Pesa payment request to {{ $invoice->customer->phone_number }} for KES {{ number_format($invoice->amount, 2) }}?')) {
            e.preventDefault();
        }
    });
}
</script>
@endsection