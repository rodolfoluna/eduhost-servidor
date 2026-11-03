@extends('layouts.app')

@section('title', 'Panel del Profesor - EduHost')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Panel de Control del Profesor</h1>
        <p class="text-sm text-slate-500">Supervisión de Hosting Académico, Grupos, Alumnos y Entregas de Código.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('teacher.students.index') }}" class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-3.5 py-2 rounded-lg text-sm font-medium shadow-sm">
            + Registrar Alumnos
        </a>
        <a href="{{ route('teacher.activities.index') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm">
            + Nueva Actividad / Práctica
        </a>
    </div>
</div>

<!-- Tarjetas de Estadísticas del Servidor -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <div class="text-xs font-medium text-slate-500 uppercase">Grupos</div>
        <div class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['groups_count'] }}</div>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <div class="text-xs font-medium text-slate-500 uppercase">Alumnos Activos</div>
        <div class="text-2xl font-bold text-indigo-600 mt-1">{{ $stats['students_count'] }}</div>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <div class="text-xs font-medium text-slate-500 uppercase">Actividades</div>
        <div class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['activities_count'] }}</div>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <div class="text-xs font-medium text-slate-500 uppercase">Sitios Alojados</div>
        <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['projects_count'] }}</div>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <div class="text-xs font-medium text-slate-500 uppercase">Por Calificar</div>
        <div class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['pending_reviews'] }}</div>
    </div>
    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm">
        <div class="text-xs font-medium text-slate-500 uppercase">BDs MariaDB</div>
        <div class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['databases_count'] }}</div>
    </div>
</div>

<!-- Tabla de Proyectos y Entregas Recientes -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <h2 class="font-bold text-slate-800">Últimos Sitios y Prácticas de Alumnos en el Servidor</h2>
        <span class="text-xs text-slate-500">Haz clic en "Revisar Código y BD" para abrir el Super-Visor</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase border-b border-slate-200">
                <tr>
                    <th class="px-5 py-3">Alumno / Matrícula</th>
                    <th class="px-5 py-3">Proyecto / Ruta Pública</th>
                    <th class="px-5 py-3">Tecnología</th>
                    <th class="px-5 py-3">Base de Datos</th>
                    <th class="px-5 py-3">Estado / Nota</th>
                    <th class="px-5 py-3 text-right">Acciones del Profesor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($recentSubmissions as $project)
                <tr class="hover:bg-slate-50/80">
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-slate-900">{{ $project->user->name }}</div>
                        <div class="text-xs text-slate-500 font-mono">
                            {{ $project->user->username }} · {{ $project->user->group?->code ?? 'Sin grupo' }}
                        </div>
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="font-medium text-slate-800">{{ $project->name }}</div>
                        <a href="{{ $project->publicUrl() }}" target="_blank" class="text-xs text-indigo-600 hover:underline font-mono">
                            /~{{ $project->user->username }}/{{ $project->folder_slug }}/ ↗
                        </a>
                    </td>
                    <td class="px-5 py-3.5">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border
                            {{ $project->project_type === 'laravel' ? 'bg-rose-50 text-rose-700 border-rose-200' : ($project->project_type === 'php_mariadb' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200') }}">
                            {{ $project->typeLabel() }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5 font-mono text-xs text-slate-600">
                        {{ $project->studentDatabase?->db_name ?? '—' }}
                    </td>
                    <td class="px-5 py-3.5">
                        @if($project->status === 'graded')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                Calificado: {{ $project->score }}/100
                            </span>
                        @elseif($project->status === 'submitted')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                Entregado · Por Revisar
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                En desarrollo
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 text-right space-x-2">
                        <a href="{{ $project->publicUrl() }}" target="_blank"
                            class="inline-flex items-center px-2.5 py-1.5 rounded-md text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700">
                            Ver Sitio ↗
                        </a>
                        <a href="{{ route('teacher.projects.inspect', $project) }}"
                            class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">
                            Revisar Código y BD
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                        Aún no hay proyectos subidos por los alumnos.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

