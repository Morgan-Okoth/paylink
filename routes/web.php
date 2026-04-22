<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MpesaCallbackController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        
        $stats = [
            'total_invoices' => $user->invoices()->count(),
            'paid_invoices' => $user->invoices()->paid()->count(),
            'pending_invoices' => $user->invoices()->pending()->count(),
            'overdue_invoices' => $user->invoices()->overdue()->count(),
            'total_revenue' => $user->invoices()->paid()->sum('amount'),
            'pending_amount' => $user->invoices()->dueForPayment()->sum('amount'),
            'total_customers' => $user->customers()->count(),
        ];
        
        return view('dashboard', compact('stats'));
    })->name('dashboard');
    
    Route::get('/settings', [AuthController::class, 'showSettings'])->name('settings');
    Route::put('/settings', [AuthController::class, 'updateSettings']);
    Route::put('/password', [AuthController::class, 'updatePassword'])->name('password.update');
    
    Route::resource('customers', CustomerController::class);
    
    Route::resource('invoices', InvoiceController::class);
    
    Route::post('/invoices/{invoice}/payment', [InvoiceController::class, 'requestPayment'])
        ->name('invoices.payment');
});

Route::prefix('api/mpesa')->group(function () {
    Route::post('/callback', [MpesaCallbackController::class, 'handleCallback']);
    Route::post('/validate', [MpesaCallbackController::class, 'handleValidation']);
    Route::post('/timeout', [MpesaCallbackController::class, 'handleTimeout']);
});