<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return Auth::user()->isTeacher()
                ? redirect()->route('teacher.dashboard')
                : redirect()->route('student.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Permitir iniciar sesión con Correo Electrónico o con Matrícula (username)
        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (!Auth::user()->is_active) {
                Auth::logout();
                return back()->withErrors(['login' => 'Tu cuenta de hosting está suspendida. Consulta a tu profesor.']);
            }

            return Auth::user()->isTeacher()
                ? redirect()->route('teacher.dashboard')
                : redirect()->route('student.dashboard');
        }

        return back()->withErrors([
            'login' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

