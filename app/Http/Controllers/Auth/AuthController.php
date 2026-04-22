<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $credentials = $request->only('email', 'password');
        
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            
            return redirect()->intended('/dashboard')
                ->with('success', 'Welcome back!');
        }
        
        return redirect()->back()
            ->with('error', 'Invalid credentials')
            ->withInput();
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|min:9|max:12',
            'password' => 'required|string|min:8|confirmed',
            'mpesa_shortcode' => 'required|string|max:10',
            'mpesa_consumer_key' => 'required|string|max:100',
            'mpesa_consumer_secret' => 'required|string|max:200',
            'mpesa_passkey' => 'required|string|max:200',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $user = User::create([
            'name' => $request->name,
            'business_name' => $request->business_name,
            'email' => $request->email,
            'phone_number' => preg_replace('/[^0-9]/', '', $request->phone_number),
            'password' => $request->password,
            'mpesa_shortcode' => $request->mpesa_shortcode,
            'mpesa_consumer_key' => $request->mpesa_consumer_key,
            'mpesa_consumer_secret' => $request->mpesa_consumer_secret,
            'mpesa_passkey' => $request->mpesa_passkey,
        ]);
        
        Auth::login($user);
        
        return redirect('/dashboard')
            ->with('success', 'Account created successfully!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login')
            ->with('success', 'Logged out successfully');
    }

    public function showSettings(): View
    {
        $user = Auth::user();
        
        return view('auth.settings', compact('user'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'business_name' => 'required|string|max:255',
            'phone_number' => 'required|string|min:9|max:12',
            'mpesa_shortcode' => 'required|string|max:10',
            'mpesa_consumer_key' => 'required|string|max:100',
            'mpesa_consumer_secret' => 'required|string|max:200',
            'mpesa_passkey' => 'required|string|max:200',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $user->update([
            'name' => $request->name,
            'business_name' => $request->business_name,
            'phone_number' => preg_replace('/[^0-9]/', '', $request->phone_number),
            'mpesa_shortcode' => $request->mpesa_shortcode,
            'mpesa_consumer_key' => $request->mpesa_consumer_key,
            'mpesa_consumer_secret' => $request->mpesa_consumer_secret,
            'mpesa_passkey' => $request->mpesa_passkey,
        ]);
        
        return redirect()->back()
            ->with('success', 'Settings updated successfully');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $user = Auth::user();
        
        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()
                ->with('error', 'Current password is incorrect')
                ->withInput();
        }
        
        $user->update([
            'password' => $request->password,
        ]);
        
        return redirect()->back()
            ->with('success', 'Password updated successfully');
    }
}