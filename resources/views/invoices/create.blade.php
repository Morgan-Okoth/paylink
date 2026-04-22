@extends('layouts.app')

@section('title', 'Create Invoice')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">New Invoice</h4>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                
                <form method="POST" action="{{ route('invoices.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }} ({{ $customer->phone_number }})
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">or <a href="{{ route('customers.create') }}">add a new customer</a></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (KES)</label>
                        <input type="number" name="amount" class="form-control" min="1" step="0.01" required value="{{ old('amount') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-control" required value="{{ old('due_date') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create Invoice</button>
                        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection