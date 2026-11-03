@extends('layouts.app')

@section('title', 'Iniciar Sesión - EduHost')

@section('content')
<div class="min-h-[75vh] flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg border border-slate-200 p-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 text-white font-bold text-2xl shadow-md mb-3">
                EH
            </div>
            <h1 class="text-2xl font-bold text-slate-900">EduHost Académico</h1>
            <p class="text-sm text-slate-500 mt-1">
                Plataforma de Actividades y Hosting Web (PHP, MariaDB y Laravel)
            </p>
        </div>

        <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Matrícula o Correo Electrónico
                </label>
                <input type="text" name="login" value="{{ old('login') }}" required autofocus
                    placeholder="Ej. 2026001 o profesor@instituto.edu"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Contraseña
                </label>
                <input type="password" name="password" required
                    placeholder="••••••••"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 outline-none">
            </div>

            <div class="flex items-center justify-between text-xs text-slate-600">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600">
                    <span>Mantener sesión iniciada</span>
                </label>
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow-sm transition text-sm">
                Entrar al Servidor
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-100 text-xs text-slate-500 space-y-1">
            <div class="font-semibold text-slate-700">Accesos iniciales de prueba:</div>
            <div>• <strong>Profesor:</strong> <code>profesor@instituto.edu</code> / <code>password123</code></div>
            <div>• <strong>Alumno Demo:</strong> Matrícula <code>2026001</code> / <code>alumno123</code></div>
        </div>
    </div>
</div>
@endsection

