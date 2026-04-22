@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<h4 class="mb-4">Settings</h4>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Business Profile</h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                
                <form method="POST" action="{{ route('settings') }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Your Name</label>
                            <input type="text" name="name" class="form-control" required value="{{ old('name', $user->name) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Business Name</label>
                            <input type="text" name="business_name" class="form-control" required value="{{ old('business_name', $user->business_name) }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" required value="{{ old('phone_number', $user->phone_number) }}">
                    </div>
                    
                    <h6 class="text-muted mt-4 mb-3">M-Pesa Credentials</h6>
                    <p class="small text-muted">Get these from <a href="https://developer.safaricom.co.ke" target="_blank">Safaricom Developer Portal</a></p>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Shortcode</label>
                            <input type="text" name="mpesa_shortcode" class="form-control" required value="{{ old('mpesa_shortcode', $user->mpesa_shortcode) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Passkey</label>
                            <input type="text" name="mpesa_passkey" class="form-control" required value="{{ old('mpesa_passkey', $user->mpesa_passkey) }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Consumer Key</label>
                            <input type="text" name="mpesa_consumer_key" class="form-control" required value="{{ old('mpesa_consumer_key', $user->mpesa_consumer_key) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Consumer Secret</label>
                            <input type="text" name="mpesa_consumer_secret" class="form-control" required value="{{ old('mpesa_consumer_secret', $user->mpesa_consumer_secret) }}">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary">Update Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">API Callback URL</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted">Use this URL in Safaricom Developer Portal</p>
                <code class="d-block p-2 bg-light rounded">{{ config('app.url') }}/api/mpesa/callback</code>
                <hr>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger w-100">Logout</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection