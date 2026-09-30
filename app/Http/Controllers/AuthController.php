<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function setup(): View|RedirectResponse
    {
        if (User::exists()) {
            return redirect()->route('login');
        }

        return view('auth.setup');
    }

    public function storeSetup(Request $request): RedirectResponse
    {
        abort_if(User::exists(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        $user = User::create($data + ['role' => 'admin']);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function loginForm(): View|RedirectResponse
    {
        if (! User::exists()) {
            return redirect()->route('setup');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        if (! User::exists()) {
            return redirect()->route('setup');
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Los datos de acceso no son válidos.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
