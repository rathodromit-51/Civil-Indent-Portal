<?php

namespace App\Http\Controllers;

use App\Contracts\Authenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle incoming authentication request.
     */
    public function store(Request $request, Authenticator $authenticator): RedirectResponse
    {
        $credentials = $request->validate([
            'cpf' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $cpf = trim($credentials['cpf']);
        $password = $credentials['password'];

        $user = $authenticator->authenticate($cpf, $password);

        if (! $user) {
            return back()->withInput($request->only('cpf', 'remember'))->withErrors([
                'cpf' => 'The provided CPF credentials do not match our records or the account is inactive.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out successfully.');
    }
}
