<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebAuthController extends Controller
{
    private function dashboardRouteByRole(string $role): string
    {
        return match ($role) {
            'admin' => 'admin.dashboard',
            'siswa' => 'siswa.dashboard',
            default => 'guru.dashboard',
        };
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route($this->dashboardRouteByRole((string) Auth::user()->role));
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route($this->dashboardRouteByRole((string) Auth::user()->role));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
