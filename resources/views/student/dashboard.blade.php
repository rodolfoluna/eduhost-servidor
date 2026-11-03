@extends('layouts.app')

@section('title', 'Mi Panel de Hosting y Actividades - EduHost')

@section('content')
<!-- Banner de Información de Hosting del Alumno (Estilo cPanel) -->
<div class="bg-slate-900 text-white rounded-2xl p-6 mb-8 shadow-md border border-slate-800">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-center">
        <div class="lg:col-span-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-indigo-400">Mi Cuenta de Hosting Académico</span>
            <h1 class="text-2xl font-bold mt-1">{{ $student->name }}</h1>
            <p class="text-xs text-slate-300 mt-1">
                Grupo: <strong>{{ $student->group?->name ?? 'General' }}</strong> ·
                Espacio Web Raíz: <code class="bg-slate-800 text-indigo-300 px-2 py-0.5 rounded">/~{{ $student->username }}/</code>
            </p>
        </div>

        <div class="bg-slate-800/90 rounded-xl p-3.5 border border-slate-700 text-xs font-mono space-y-1">
            <div class="text-slate-400 font-sans font-semibold uppercase text-[10px]">Credenciales MariaDB (PDO / .env)</div>
            <div>DB_HOST: <span class="text-emerald-300">127.0.0.1</span></div>
            <div>DB_USER: <span class="text-amber-300">{{ $student->db_user }}</span></div>
            <div>DB_PASS: <span class="text-rose-300">{{ $student->db_password_plain }}</span></div>
        </div>

        <div class="bg-slate-800/90 rounded-xl p-3.5 border border-slate-700 text-xs">
            <div class="flex justify-between font-medium mb-1">
                <span class="text-slate-300">Uso de Disco</span>
                <span class="font-mono text-indigo-300">{{ $diskUsedMb }} MB / {{ $student->disk_quota_mb }} MB</span>
            </div>
            <div class="w-full bg-slate-700 h-2 rounded-full overflow-hidden mb-2">
                <div class="bg-indigo-500 h-full" style="width: {{ min(100, round(($diskUsedMb / max(1, $student->disk_quota_mb)) * 100)) }}%"></div>
            </div>
            <div class="flex justify-between text-[11px] text-slate-400">
                <span>BDs MariaDB: {{ $student->studentDatabases->count() }}/{{ config('eduhost.max_databases_per_student', 5) }}</span>
                <a href="{{ route('student.databases.index') }}" class="text-indigo-400 hover:underline font-semibold">Gestionar BDs →</a>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Columna Izquierda: Crear Nuevo Sitio Web / Proyecto -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-base font-bold text-slate-900 mb-1">🚀 Desplegar Nuevo Sitio / Práctica</h2>
            <p class="text-xs text-slate-500 mb-4">Crea el espacio para tu sitio en PHP Puro, PHP+MariaDB o Laravel.</p>

            <form action="{{ route('student.projects.store') }}" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Actividad a Entregar (Opcional)</label>
                    <select name="activity_id" id="activity-selector" onchange="autoFillFromActivity(this)"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
                        <option value="">— Proyecto Libre / Personal —</option>
                        @foreach($activities as $act)
                            <option value="{{ $act->id }}" data-slug="{{ $act->slug }}" data-type="{{ $act->project_type }}" data-title="{{ $act->title }}">
                                {{ $act->title }} ({{ $act->typeLabel() }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nombre del Proyecto</label>
                    <input type="text" name="name" id="proj-name" required placeholder="Ej. Sistema de Inventario"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Carpeta en URL (sin espacios)</label>
                    <div class="flex items-center">
                        <span class="bg-slate-100 border border-r-0 border-slate-300 rounded-l-lg px-2.5 py-2 text-xs font-mono text-slate-500">
                            /~{{ $student->username }}/
                        </span>
                        <input type="text" name="folder_slug" id="proj-slug" required placeholder="mi-practica-1"
                            class="w-full rounded-r-lg border border-slate-300 px-3 py-2 text-sm font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tipo de Tecnología</label>
                    <select name="project_type" id="proj-type" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="php_pure">PHP Puro</option>
                        <option value="php_mariadb">PHP + Base de Datos MariaDB</option>
                        <option value="laravel">Framework Laravel 11</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Vincular Base de Datos MariaDB (Opcional)</label>
                    <select name="student_database_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs font-mono">
                        <option value="">— Ninguna por ahora —</option>
                        @foreach($student->studentDatabases as $db)
                            <option value="{{ $db->id }}">{{ $db->db_name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg text-sm shadow-sm">
                    Crear Espacio Web y Abrir Editor
                </button>
            </form>
        </div>
    </div>

    <!-- Columna Derecha (2 cols): Mis Sitios Alojados y Actividades Asignadas -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Mis Sitios Web Alojados -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="font-bold text-slate-900">🌐 Mis Sitios Web Alojados en el Servidor ({{ $student->projects->count() }})</h2>
            </div>

            <div class="divide-y divide-slate-200">
                @forelse($student->projects as $project)
                <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                {{ $project->typeLabel() }}
                            </span>
                            <h3 class="font-bold text-slate-900 text-base">{{ $project->name }}</h3>
                            @if($project->status === 'graded')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                    Calificación: {{ $project->score }}/100
                                </span>
                            @elseif($project->status === 'submitted')
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                    Entregado al Profesor
                                </span>
                            @endif
                        </div>

                        <div class="mt-1 text-xs font-mono text-indigo-600">
                            <a href="{{ $project->publicUrl() }}" target="_blank" class="hover:underline">
                                {{ $project->publicUrl() }} ↗
                            </a>
                        </div>

                        @if($project->activity)
                            <div class="text-xs text-slate-500 mt-1">
                                Práctica vinculada: <strong>{{ $project->activity->title }}</strong>
                            </div>
                        @endif

                        @if($project->teacher_feedback)
                            <div class="mt-2 text-xs bg-emerald-50 border border-emerald-200 text-emerald-900 p-2.5 rounded-lg">
                                <strong>Retroalimentación del Profesor:</strong> {{ $project->teacher_feedback }}
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ $project->publicUrl() }}" target="_blank"
                            class="px-3 py-2 rounded-lg border border-slate-300 hover:bg-slate-100 text-xs font-medium text-slate-700">
                            Abrir Sitio ↗
                        </a>
                        <a href="{{ route('student.projects.workspace', $project) }}"
                            class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold shadow-sm">
                            Subir Archivos / Editar Código
                        </a>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-slate-500 text-sm">
                    Aún no has creado ningún sitio web. Usa el formulario de la izquierda para lanzar tu primer proyecto.
                </div>
                @endforelse
            </div>
        </div>

        <!-- Actividades del Grupo -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="font-bold text-slate-900">📚 Actividades Asignadas por el Profesor</h2>
            </div>
            <div class="divide-y divide-slate-200">
                @forelse($activities as $act)
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-1">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $act->typeBadgeColor() }}">
                                {{ $act->typeLabel() }}
                            </span>
                            <span class="text-xs text-slate-500">
                                Entrega límite: <strong>{{ $act->due_date ? $act->due_date->format('d/m/Y H:i') : 'Sin fecha límite' }}</strong>
                            </span>
                        </div>
                        <h3 class="font-bold text-slate-900 mt-1">{{ $act->title }}</h3>
                        <p class="text-xs text-slate-600 whitespace-pre-line mt-2 bg-slate-50 p-3 rounded-lg border border-slate-100">{{ $act->instructions }}</p>
                    </div>
                @empty
                    <div class="p-6 text-center text-slate-500 text-sm">No hay actividades pendientes en tu grupo.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function autoFillFromActivity(select) {
    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) return;
    document.getElementById('proj-name').value = opt.getAttribute('data-title');
    document.getElementById('proj-slug').value = opt.getAttribute('data-slug');
    document.getElementById('proj-type').value = opt.getAttribute('data-type');
}
</script>
@endpush
@endsection

