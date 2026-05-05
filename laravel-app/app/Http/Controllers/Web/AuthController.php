<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();
            $token = Auth::user()->issueToken();
            $request->session()->put('api_token', $token);
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:100',
            'name'         => 'required|string|max:100',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:8|confirmed',
        ]);

        $tenant = Tenant::create([
            'name' => $request->company_name,
            'slug' => Str::slug($request->company_name) . '-' . Str::lower(Str::random(6)),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'role'      => 'admin',
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $token = $user->issueToken();
        $request->session()->put('api_token', $token);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
