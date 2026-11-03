<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Verifica que el usuario autenticado tenga el rol requerido ('teacher' o 'student')
     * y que su cuenta de hosting esté activa.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta de hosting ha sido suspendida temporalmente por el profesor.',
            ]);
        }

        if ($user->role !== $role) {
            return $user->role === 'teacher'
                ? redirect()->route('teacher.dashboard')
                : redirect()->route('student.dashboard');
        }

        return $next($request);
    }
}

