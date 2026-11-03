@extends('layouts.app')

@section('title', 'Super-Visor de Proyecto: ' . $project->name . ' - ' . $project->user->name)

@section('content')
<!-- Cabecera del Super-Visor -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="{{ route('teacher.dashboard') }}" class="text-indigo-600 font-semibold hover:underline">← Panel Profesor</a>
            <span>/</span>
            <span>Alumno: <strong class="text-slate-800">{{ $project->user->name }} ({{ $project->user->username }})</strong></span>
            <span>/</span>
            <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-semibold">{{ $project->typeLabel() }}</span>
        </div>
        <h1 class="text-xl font-bold text-slate-900">{{ $project->name }}</h1>
        <p class="text-xs font-mono text-slate-500 mt-0.5">
            Ruta en servidor: <code>{{ $project->absolutePath() }}</code>
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ $project->publicUrl() }}" target="_blank"
            class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2.5 rounded-lg text-sm shadow-sm">
            <span>🌐 Abrir Sitio Web del Alumno en Vivo</span>
            <span>↗</span>
        </a>
        <a href="{{ config('eduhost.adminer_url', '/db-manager/') }}?server=127.0.0.1&username={{ $project->user->db_user }}&db={{ $project->studentDatabase?->db_name }}"
            target="_blank"
            class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white font-medium px-3.5 py-2.5 rounded-lg text-sm">
            <span>🗄️ Abrir BD en Adminer</span>
        </a>
    </div>
</div>

<!-- Bloque Superior: Calificación del Profesor y Notas del Alumno -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-2">Detalles de la Entrega</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs mb-3">
            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                <div class="text-slate-500">Actividad Asociada</div>
                <div class="font-semibold text-slate-800 mt-0.5">{{ $project->activity?->title ?? 'Proyecto Libre' }}</div>
            </div>
            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                <div class="text-slate-500">Credenciales MariaDB del Alumno</div>
                <div class="font-mono font-semibold text-slate-800 mt-0.5">
                    {{ $project->user->db_user }} / {{ $project->user->db_password_plain }}
                </div>
            </div>
            <div class="bg-slate-50 p-3 rounded-lg border border-slate-100">
                <div class="text-slate-500">URL Pública del Proyecto</div>
                <a href="{{ $project->publicUrl() }}" target="_blank" class="font-mono font-semibold text-indigo-600 hover:underline mt-0.5 block truncate">
                    {{ $project->publicUrl() }}
                </a>
            </div>
        </div>
        @if($project->student_notes)
            <div class="text-xs bg-amber-50 border border-amber-200 text-amber-900 p-3 rounded-lg">
                <strong>Comentario del Alumno:</strong> {{ $project->student_notes }}
            </div>
        @endif
    </div>

    <!-- Formulario de Calificación -->
    <div class="bg-white rounded-xl border border-indigo-200 shadow-sm p-5">
        <h2 class="text-sm font-bold text-indigo-950 uppercase tracking-wider mb-3">Evaluar y Calificar Práctica</h2>
        <form action="{{ route('teacher.projects.grade', $project) }}" method="POST" class="space-y-3">
            @csrf
            <div class="flex items-center gap-3">
                <div class="w-32">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Calificación (0-100)</label>
                    <input type="number" step="0.5" min="0" max="100" name="score"
                        value="{{ old('score', $project->score ?? 100) }}" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-base font-bold text-indigo-700">
                </div>
                <div class="flex-1 text-xs text-slate-500 pt-4">
                    Estado actual: <strong class="text-slate-800">{{ $project->statusLabel() }}</strong>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Retroalimentación para el Alumno</label>
                <textarea name="teacher_feedback" rows="2" placeholder="Excelente estructura MVC y manejo de PDO..."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">{{ old('teacher_feedback', $project->teacher_feedback) }}</textarea>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded-lg text-xs shadow-sm">
                Guardar Calificación y Retroalimentación
            </button>
        </form>
    </div>
</div>

<!-- VISOR Y EDITOR DE CÓDIGO EN NAVEGADOR (MONACO EDITOR - VS CODE WEB) -->
<div class="bg-slate-900 rounded-xl border border-slate-800 shadow-lg overflow-hidden mb-8">
    <div class="bg-slate-950 px-4 py-3 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="text-xs font-bold uppercase tracking-wider text-indigo-400">💻 Visor y Editor de Código (Monaco Editor)</span>
            <span id="current-file-badge" class="text-xs font-mono bg-slate-800 text-slate-200 px-2.5 py-1 rounded border border-slate-700">
                Selecciona un archivo del árbol izquierdo
            </span>
        </div>
        <div class="flex items-center gap-2">
            <span id="save-status" class="text-xs text-emerald-400 hidden">¡Guardado!</span>
            <button type="button" onclick="promptNewFile()" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1.5 rounded border border-slate-700">
                + Nuevo Archivo
            </button>
            <button type="button" onclick="saveCurrentFile()" class="text-xs bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-1.5 rounded shadow">
                💾 Guardar Cambios en el Servidor (Ctrl+S)
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 min-h-[540px]">
        <!-- Árbol de Archivos del Alumno -->
        <div class="lg:col-span-3 bg-slate-900 border-r border-slate-800 p-3 overflow-y-auto max-h-[540px]">
            <div class="text-[11px] font-semibold uppercase text-slate-400 mb-2 px-2">
                Archivos en /~{{ $project->user->username }}/{{ $project->folder_slug }}/
            </div>
            @if(count($fileTree) > 0)
                <x-file-tree :items="$fileTree" />
            @else
                <div class="text-xs text-slate-500 p-2">Carpeta vacía.</div>
            @endif
        </div>

        <!-- Lienzo de Monaco Editor -->
        <div class="lg:col-span-9 relative">
            <div id="monaco-container" class="w-full h-[540px]"></div>
        </div>
    </div>
</div>

<!-- INSPECTOR EN VIVO DE BASE DE DATOS MARIADB DEL ALUMNO -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="font-bold text-slate-900">🗄️ Inspector de Base de Datos MariaDB del Alumno</h2>
            <p class="text-xs text-slate-500">Revisa las tablas, llaves primarias, tipos de datos y registros creados por el alumno sin salir de EduHost.</p>
        </div>

        <form method="GET" action="{{ route('teacher.projects.inspect', $project) }}" class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-600">Base de Datos:</label>
            <select name="db_id" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-mono">
                @forelse($project->user->studentDatabases as $db)
                    <option value="{{ $db->id }}" {{ ($dbInspection['database'] ?? '') === $db->db_name ? 'selected' : '' }}>
                        {{ $db->db_name }}
                    </option>
                @empty
                    <option value="">El alumno no ha creado BDs</option>
                @endforelse
            </select>
        </form>
    </div>

    <div class="p-6">
        @if(!$dbInspection)
            <div class="text-sm text-slate-500 text-center py-6">
                Este alumno aún no tiene bases de datos registradas en MariaDB.
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Lista de Tablas -->
                <div class="border-r border-slate-200 pr-4">
                    <div class="text-xs font-bold uppercase text-slate-500 mb-2">
                        Tablas en {{ $dbInspection['database'] }} ({{ count($dbInspection['tables']) }})
                    </div>
                    <div class="space-y-1">
                        @forelse($dbInspection['tables'] as $tbl)
                            <a href="{{ route('teacher.projects.inspect', ['project' => $project->id, 'table' => $tbl]) }}"
                                class="block px-3 py-2 rounded-lg text-xs font-mono {{ $dbInspection['active_table'] === $tbl ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                                ⊞ {{ $tbl }}
                            </a>
                        @empty
                            <div class="text-xs text-slate-400">Sin tablas creadas aún.</div>
                        @endforelse
                    </div>
                </div>

                <!-- Estructura y Registros de la Tabla Activa -->
                <div class="lg:col-span-3 space-y-6 overflow-x-auto">
                    @if($dbInspection['active_table'])
                        <div>
                            <h3 class="text-xs font-bold uppercase text-slate-600 mb-2">
                                Estructura de Columnas: <code class="text-indigo-600">{{ $dbInspection['active_table'] }}</code>
                            </h3>
                            <table class="w-full text-xs text-left border border-slate-200 rounded-lg overflow-hidden">
                                <thead class="bg-slate-100 text-slate-700">
                                    <tr>
                                        <th class="px-3 py-2">Columna</th>
                                        <th class="px-3 py-2">Tipo</th>
                                        <th class="px-3 py-2">Nulo</th>
                                        <th class="px-3 py-2">Llave</th>
                                        <th class="px-3 py-2">Extra</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 font-mono">
                                    @foreach($dbInspection['columns'] as $col)
                                        <tr>
                                            <td class="px-3 py-1.5 font-bold text-slate-900">{{ $col->Field }}</td>
                                            <td class="px-3 py-1.5 text-indigo-700">{{ $col->Type }}</td>
                                            <td class="px-3 py-1.5">{{ $col->Null }}</td>
                                            <td class="px-3 py-1.5">{{ $col->Key }}</td>
                                            <td class="px-3 py-1.5 text-slate-500">{{ $col->Extra }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <h3 class="text-xs font-bold uppercase text-slate-600 mb-2">
                                Registros Almacenados (Primeras 50 filas: {{ count($dbInspection['rows']) }})
                            </h3>
                            @if(count($dbInspection['rows']) > 0)
                                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                                    <table class="w-full text-xs text-left">
                                        <thead class="bg-slate-100 text-slate-700 font-mono">
                                            <tr>
                                                @foreach(array_keys((array) $dbInspection['rows'][0]) as $colName)
                                                    <th class="px-3 py-2 border-b">{{ $colName }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 font-mono">
                                            @foreach($dbInspection['rows'] as $row)
                                                <tr class="hover:bg-slate-50">
                                                    @foreach((array) $row as $val)
                                                        <td class="px-3 py-1.5 truncate max-w-xs">{{ is_null($val) ? 'NULL' : $val }}</td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-xs text-slate-500 bg-slate-50 p-4 rounded-lg border border-slate-200">
                                    La tabla <code>{{ $dbInspection['active_table'] }}</code> no tiene filas insertadas todavía.
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@if($project->project_type === 'laravel')
<!-- Consola y Logs de Laravel para el Profesor -->
<div class="bg-slate-900 text-slate-200 rounded-xl p-5 border border-slate-800">
    <h3 class="text-sm font-bold text-rose-400 uppercase mb-2">Logs del Proyecto Laravel (storage/logs/laravel.log)</h3>
    <pre class="text-xs font-mono bg-slate-950 p-4 rounded-lg overflow-x-auto max-h-64 text-slate-300">{{ $laravelLogs }}</pre>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.47.0/min/vs/loader.min.js"></script>
<script>
    let monacoEditor = null;
    let currentFilePath = '';
    const readUrl = "{{ route('workspace.file.read', $project) }}";
    const saveUrl = "{{ route('workspace.file.save', $project) }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.47.0/min/vs' }});
    require(['vs/editor/editor.main'], function () {
        monacoEditor = monaco.editor.create(document.getElementById('monaco-container'), {
            value: "// Selecciona cualquier archivo PHP, Blade, SQL, HTML o JS del árbol izquierdo para inspeccionar o editar el código del alumno.\n",
            language: 'php',
            theme: 'vs-dark',
            automaticLayout: true,
            fontSize: 13,
            minimap: { enabled: true }
        });

        // Atajo Ctrl+S / Cmd+S para guardar directo en el servidor
        monacoEditor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, function () {
            saveCurrentFile();
        });

        // Intentar abrir index.php o routes/web.php por defecto
        loadFileInMonaco("{{ $project->project_type === 'laravel' ? 'routes/web.php' : 'index.php' }}", true);
    });

    async function loadFileInMonaco(path, silent = false) {
        try {
            const res = await fetch(readUrl + '?path=' + encodeURIComponent(path));
            const data = await res.json();
            if (!res.ok) {
                if (!silent) alert(data.error || 'No se pudo abrir el archivo.');
                return;
            }
            currentFilePath = data.path;
            document.getElementById('current-file-badge').textContent = data.path + ' (' + data.size_kb + ' KB)';
            const model = monaco.editor.createModel(data.content, data.language);
            monacoEditor.setModel(model);
        } catch (err) {
            if (!silent) alert('Error al leer archivo del servidor.');
        }
    }

    async function saveCurrentFile() {
        if (!currentFilePath) {
            alert('Primero selecciona o crea un archivo.');
            return;
        }
        const content = monacoEditor.getValue();
        const res = await fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ path: currentFilePath, content: content })
        });
        const data = await res.json();
        if (res.ok) {
            const badge = document.getElementById('save-status');
            badge.classList.remove('hidden');
            setTimeout(() => badge.classList.add('hidden'), 2500);
        } else {
            alert(data.error || 'Error al guardar el archivo.');
        }
    }

    function promptNewFile() {
        const name = prompt('Ruta del nuevo archivo (ej. index.php, conexion.php o resources/views/hola.blade.php):');
        if (!name) return;
        currentFilePath = name.trim();
        document.getElementById('current-file-badge').textContent = currentFilePath + ' (nuevo)';
        monacoEditor.setValue("<?php\n\n");
    }
</script>
@endpush

