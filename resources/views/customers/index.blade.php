@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Customers</h4>
    <a href="{{ route('customers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Customer
    </a>
</div>

<form method="GET" class="mb-4">
    <div class="input-group" style="max-width: 400px;">
        <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone..." value="{{ request('search') }}">
        <button type="submit" class="btn btn-outline-secondary">Search</button>
        @if(request('search'))
        <a href="{{ route('customers.index') }}" class="btn btn-outline-danger">Clear</a>
        @endif
    </div>
</form>

<div class="card table-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Total Invoices</th>
                        <th>Pending</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td>
                            <a href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a>
                        </td>
                        <td>{{ $customer->email ?? '-' }}</td>
                        <td>{{ $customer->phone_number }}</td>
                        <td>{{ $customer->invoices()->count() }}</td>
                        <td>KES {{ number_format($customer->getTotalPendingAmount(), 2) }}</td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('customers.show', $customer) }}">View</a></li>
                                    <li><a class="dropdown-item" href="{{ route('customers.edit', $customer) }}">Edit</a></li>
                                    @if(!$customer->invoices()->exists())
                                    <li>
                                        <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="delete-form d-none">
                                            @csrf @method('DELETE')
                                        </form>
                                        <a class="dropdown-item text-danger delete-btn" href="#">Delete</a>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            No customers yet. <a href="{{ route('customers.create') }}">Add one</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        {{ $customers->links() }}
    </div>
</div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('.delete-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Delete this customer?')) {
            this.closest('form').submit();
        }
    });
});
</script>
@endsection