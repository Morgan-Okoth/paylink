@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h4><i class="bi bi-wallet2"></i> PayLink</h4>
                        <p class="text-muted mb-0">Create your business account</p>
                    </div>
                    
                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    
                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        <h6 class="text-muted mb-3">Account Details</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Your Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Business Name</label>
                                <input type="text" name="business_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone_number" class="form-control" placeholder="0712345678" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>
                        </div>
                        
                        <h6 class="text-muted mb-3 mt-4">M-Pesa Credentials</h6>
                        <p class="small text-muted">Get these from <a href="https://developer.safaricom.co.ke" target="_blank">Safaricom Developer Portal</a></p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Shortcode</label>
                                <input type="text" name="mpesa_shortcode" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Consumer Key</label>
                                <input type="text" name="mpesa_consumer_key" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Consumer Secret</label>
                                <input type="text" name="mpesa_consumer_secret" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Passkey</label>
                                <input type="text" name="mpesa_passkey" class="form-control" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mt-3">Create Account</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <a href="{{ route('login') }}">Already have an account?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection