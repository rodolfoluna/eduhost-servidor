@extends('layouts.app')

@section('title', 'Actividades y Prácticas - EduHost')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Crear Nueva Actividad -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 h-fit">
        <h2 class="text-lg font-bold text-slate-900 mb-1">Asignar Nueva Actividad</h2>
        <p class="text-xs text-slate-500 mb-4">Define el tipo de proyecto que los alumnos deberán desplegar en el servidor.</p>

        <form action="{{ route('teacher.activities.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Grupo Destino</label>
                <select name="group_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->code }} - {{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Título de la Práctica</label>
                <input type="text" name="title" required placeholder="Ej. Práctica 3: Sistema de Ventas con Laravel"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Tecnología Requerida</label>
                <select name="project_type" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="php_pure">PHP Puro (Scripts y Formularios)</option>
                    <option value="php_mariadb">PHP Puro + Base de Datos MariaDB (PDO / MySQLi)</option>
                    <option value="laravel">Framework Laravel 11 + MariaDB (MVC / Migraciones)</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Fecha Límite</label>
                    <input type="datetime-local" name="due_date"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Puntaje Máximo</label>
                    <input type="number" name="max_score" value="100" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Instrucciones para el Alumno</label>
                <textarea name="instructions" rows="5" required
                    placeholder="Describe los requisitos funcionales, tablas de base de datos necesarias y criterios de evaluación..."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg text-sm">
                Publicar Actividad
            </button>
        </form>
    </div>

    <!-- Lista de Actividades -->
    <div class="lg:col-span-2 space-y-4">
        @forelse($activities as $act)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $act->typeBadgeColor() }}">
                        {{ $act->typeLabel() }}
                    </span>
                    <span class="text-xs font-mono bg-slate-100 text-slate-700 px-2 py-0.5 rounded">
                        {{ $act->group->code }}
                    </span>
                </div>
                <div class="text-xs text-slate-500">
                    Fecha límite: <strong>{{ $act->due_date ? $act->due_date->format('d/m/Y H:i') : 'Abierta' }}</strong>
                </div>
            </div>

            <h3 class="text-lg font-bold text-slate-900">{{ $act->title }}</h3>
            <p class="text-sm text-slate-600 whitespace-pre-line mt-2 bg-slate-50 p-3.5 rounded-lg border border-slate-100">{{ $act->instructions }}</p>

            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                <div class="text-xs font-medium text-slate-600">
                    📦 <strong>{{ $act->projects_count }}</strong> proyectos desplegados por alumnos en esta práctica
                </div>
                <a href="{{ route('teacher.activities.submissions', $act) }}"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-lg shadow-sm">
                    Ver Entregas del Grupo ({{ $act->projects_count }}) →
                </a>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500">
            No has creado actividades aún.
        </div>
        @endforelse
    </div>
</div>
@endsection

