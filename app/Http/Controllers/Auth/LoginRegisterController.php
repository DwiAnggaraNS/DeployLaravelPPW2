<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Jobs\SendMailJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;


class LoginRegisterController extends Controller
{

    public function register()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:250',
            'email' => 'required|email|max:250|unique:users',
            'password' => 'required|confirmed|min:8',
            'isAdmin' => 'required|boolean'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'isAdmin' => $request->isAdmin
        ]);

        // Data untuk email
         $data = [
            'name' => $user->name,
            'email' => $user->email,
            'registration_date' => Carbon::now()->toFormattedDateString()
        ];
        
        // Dispatch job untuk mengirim email
        dispatch(new SendMailJob($data));

        $credentials = $request->only('email', 'password');
        Auth::attempt($credentials);
        $request->session()->regenerate();
        return redirect()->route('dashboard')
            ->withSuccess('You have successfully registered & logged in!');
    }

    public function login()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' =>'required|email|max:250',
            'password' => 'required|min:8'
        ]);

        if (Auth::attempt($credentials)){
            $request->session()->regenerate();
            return redirect()->route('dashboard')
                ->withSuccess('You have successfully logged in!');
        }

        return back()->withErrors(['email' => 'Invalid email or password'])->onlyInput('email');
    }

    public function dashboard()
    {
        if (Auth::check()) {
            return view('auth.dashboard');
        }

        return redirect()->route('login')->withErrors([
            'email' => 'Please login t access the dashboard'
        ])->onlyInput('email');
    }
    public function adminpage()
    {
        return view('auth.adminpage');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')
            ->withSuccess('You have successfully logged out!');
    }

}