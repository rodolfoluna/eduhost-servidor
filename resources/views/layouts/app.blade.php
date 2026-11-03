<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EduHost - Hosting Académico y LMS')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        code, pre, .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
    @stack('styles')
</head>
<body class="min-h-full flex flex-col text-slate-800">

    @auth
    <header class="bg-slate-900 text-white shadow-md border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-8">
                <a href="{{ url('/') }}" class="flex items-center space-x-2.5">
                    <span class="bg-indigo-600 text-white p-2 rounded-lg font-bold text-lg leading-none shadow-sm">EH</span>
                    <div>
                        <span class="font-bold text-lg tracking-tight text-white">EduHost</span>
                        <span class="hidden sm:inline-block text-xs bg-slate-800 text-indigo-300 px-2 py-0.5 rounded-full ml-1 border border-slate-700">
                            {{ auth()->user()->isTeacher() ? 'Panel Profesor' : 'Mini-cPanel Alumno' }}
                        </span>
                    </div>
                </a>

                <nav class="hidden md:flex items-center space-x-1 text-sm font-medium">
                    @if(auth()->user()->isTeacher())
                        <a href="{{ route('teacher.dashboard') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('teacher.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('teacher.groups.index') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('teacher.groups.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            Grupos
                        </a>
                        <a href="{{ route('teacher.students.index') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('teacher.students.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            Alumnos (30)
                        </a>
                        <a href="{{ route('teacher.activities.index') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('teacher.activities.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            Actividades y Prácticas
                        </a>
                    @else
                        <a href="{{ route('student.dashboard') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('student.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            Mis Prácticas y Sitios Web
                        </a>
                        <a href="{{ route('student.databases.index') }}" class="px-3 py-2 rounded-md {{ request()->routeIs('student.databases.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            Mis Bases de Datos (MariaDB)
                        </a>
                    @endif
                    <a href="{{ config('eduhost.adminer_url', '/db-manager/') }}" target="_blank" class="px-3 py-2 rounded-md text-emerald-300 hover:bg-slate-800 hover:text-emerald-200 flex items-center gap-1">
                        <span>Adminer DB</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </nav>
            </div>

            <div class="flex items-center space-x-4">
                <div class="text-right hidden sm:block">
                    <div class="text-sm font-medium text-white">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-slate-400 font-mono">
                        {{ auth()->user()->isTeacher() ? 'Administrador' : '/~' . auth()->user()->username . '/' }}
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs bg-slate-800 hover:bg-rose-600 text-slate-200 hover:text-white px-3 py-2 rounded-md border border-slate-700 transition">
                        Salir
                    </button>
                </form>
            </div>
        </div>
    </header>
    @endauth

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if(session('success'))
            <div class="mb-6 rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center justify-between">
                <span><strong>¡Listo!</strong> {{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                <strong>Aviso:</strong> {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white border-t border-slate-200 py-4 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-500">
            <div>
                <strong>EduHost</strong> — Plataforma de Alojamiento Académico (PHP {{ PHP_VERSION }} · MariaDB · Laravel 11)
            </div>
            <div class="mt-2 sm:mt-0">
                Servidor del Instituto · Publicado con Tailscale Funnel
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

