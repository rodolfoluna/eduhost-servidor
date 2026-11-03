@extends('layouts.app')

@section('title', 'Workspace de Proyecto: ' . $project->name)

@section('content')
<!-- Cabecera del Workspace del Alumno -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <a href="{{ route('student.dashboard') }}" class="text-indigo-600 font-semibold hover:underline">← Mis Sitios</a>
            <span>/</span>
            <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-semibold">{{ $project->typeLabel() }}</span>
            <span>/</span>
            <span class="font-semibold text-slate-700">{{ $project->statusLabel() }}</span>
        </div>
        <h1 class="text-xl font-bold text-slate-900">{{ $project->name }}</h1>
        <div class="text-xs font-mono text-indigo-600 mt-0.5">
            URL Pública: <a href="{{ $project->publicUrl() }}" target="_blank" class="hover:underline font-semibold">{{ $project->publicUrl() }} ↗</a>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ $project->publicUrl() }}" target="_blank"
            class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2.5 rounded-lg text-sm shadow-sm">
            <span>🌐 Ver Mi Sitio Funcionando</span>
            <span>↗</span>
        </a>
        <a href="{{ route('student.databases.index') }}"
            class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white font-medium px-3.5 py-2.5 rounded-lg text-sm">
            <span>🗄️ Mis Bases de Datos</span>
        </a>
    </div>
</div>

<!-- Fila de Herramientas de Despliegue: Subir .ZIP, Subir Archivo Suelto y Entregar al Profesor -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- 1. Subir Proyecto en .ZIP -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h2 class="text-sm font-bold text-slate-900 mb-1">📦 1. Subir y Descomprimir Proyecto (.ZIP)</h2>
        <p class="text-xs text-slate-500 mb-3">
            Sube tu práctica comprimida en <code>.zip</code>. Si es Laravel, no necesitas incluir <code>vendor/</code>.
        </p>

        <form action="{{ route('student.projects.upload-zip', $project) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <input type="file" name="zip_file" accept=".zip" required
                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">

            <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                <input type="checkbox" name="clear_existing" value="1" class="rounded border-slate-300 text-indigo-600">
                <span>Limpiar archivos anteriores antes de descomprimir</span>
            </label>

            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded-lg text-xs shadow-sm">
                Subir y Extraer .ZIP en el Servidor
            </button>
        </form>
    </div>

    <!-- 2. Subir Archivo Individual -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h2 class="text-sm font-bold text-slate-900 mb-1">📄 2. Subir Archivo Suelto</h2>
        <p class="text-xs text-slate-500 mb-3">Sube un archivo individual (ej. <code>index.php</code>, <code>conexion.php</code>, imagen o CSS).</p>

        <form action="{{ route('student.projects.upload-file', $project) }}" method="POST" enctype="multipart/form-data" class="space-y-2.5">
            @csrf
            <div>
                <input type="text" name="target_dir" placeholder="Subcarpeta destino (opcional, ej: public/css)"
                    class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-mono">
            </div>
            <input type="file" name="single_file" required
                class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700">
            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2 rounded-lg text-xs">
                Subir Archivo
            </button>
        </form>
    </div>

    <!-- 3. Entregar Práctica al Profesor -->
    <div class="bg-white rounded-xl border border-emerald-200 shadow-sm p-5">
        <h2 class="text-sm font-bold text-emerald-950 mb-1">✅ 3. Marcar Práctica como Entregada</h2>
        <p class="text-xs text-slate-500 mb-3">Indica qué base de datos usaste para que tu profesor pueda calificarla.</p>

        <form action="{{ route('student.projects.submit', $project) }}" method="POST" class="space-y-2.5">
            @csrf
            <div>
                <select name="student_database_id" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-mono">
                    <option value="">— Sin Base de Datos / Seleccionar BD —</option>
                    @foreach($project->user->studentDatabases as $db)
                        <option value="{{ $db->id }}" {{ $project->student_database_id === $db->id ? 'selected' : '' }}>
                            {{ $db->db_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <input type="text" name="student_notes" value="{{ old('student_notes', $project->student_notes) }}"
                    placeholder="Notas para el profesor (ej. Usuario admin: admin@test.com / 123456)"
                    class="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-xs">
            </div>
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 rounded-lg text-xs shadow-sm">
                {{ $project->status === 'submitted' ? 'Actualizar Entrega al Profesor' : 'Enviar Práctica para Calificación' }}
            </button>
        </form>
    </div>
</div>

<!-- CONSOLA 1-CLIC PARA PROYECTOS LARAVEL -->
@if($project->project_type === 'laravel')
<div class="bg-slate-900 text-white rounded-xl border border-slate-800 shadow-md p-5 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
        <div>
            <h2 class="text-sm font-bold text-rose-400 uppercase tracking-wider">⚡ Consola 1-Clic para Laravel (Composer & Artisan)</h2>
            <p class="text-xs text-slate-400">Ejecuta las tareas de despliegue de tu proyecto Laravel en el servidor con un clic.</p>
        </div>
        <span class="text-xs font-mono bg-slate-800 text-slate-300 px-2.5 py-1 rounded">
            BD Vinculada: {{ $project->studentDatabase?->db_name ?? 'Selecciona una arriba en el paso 3' }}
        </span>
    </div>

    <div class="flex flex-wrap gap-2 mb-4">
        @php
            $laravelButtons = [
                'env_autoconfig'   => ['label' => '1. Autoconfigurar .env (URL + MariaDB)', 'color' => 'bg-indigo-600 hover:bg-indigo-500'],
                'composer_install' => ['label' => '2. Composer Install', 'color' => 'bg-rose-600 hover:bg-rose-500'],
                'key_generate'     => ['label' => '3. Artisan Key:Generate', 'color' => 'bg-slate-800 hover:bg-slate-700'],
                'migrate'          => ['label' => '4. Artisan Migrate', 'color' => 'bg-emerald-600 hover:bg-emerald-500'],
                'migrate_fresh'    => ['label' => 'Migrate:Fresh --seed', 'color' => 'bg-amber-600 hover:bg-amber-500'],
                'storage_link'     => ['label' => 'Artisan Storage:Link', 'color' => 'bg-slate-800 hover:bg-slate-700'],
                'optimize_clear'   => ['label' => 'Limpiar Caché (Optimize:Clear)', 'color' => 'bg-slate-800 hover:bg-slate-700'],
                'route_list'       => ['label' => 'Ver Rutas (route:list)', 'color' => 'bg-slate-800 hover:bg-slate-700'],
            ];
        @endphp

        @foreach($laravelButtons as $actionKey => $btn)
            <form action="{{ route('workspace.laravel.cmd', $project) }}" method="POST">
                @csrf
                <input type="hidden" name="action" value="{{ $actionKey }}">
                <button type="submit" class="text-xs font-semibold px-3 py-2 rounded-lg text-white transition {{ $btn['color'] }}">
                    {{ $btn['label'] }}
                </button>
            </form>
        @endforeach
    </div>

    @if(session('console_output'))
        <div class="mt-3">
            <div class="text-xs font-mono text-emerald-400 mb-1">$ {{ session('console_command') }}</div>
            <pre class="text-xs font-mono bg-slate-950 text-slate-200 p-4 rounded-lg border border-slate-800 overflow-x-auto max-h-60">{{ session('console_output') }}</pre>
        </div>
    @endif
</div>
@endif

<!-- GESTOR DE ARCHIVOS Y EDITOR DE CÓDIGO WEB (MONACO EDITOR) -->
<div class="bg-slate-900 rounded-xl border border-slate-800 shadow-lg overflow-hidden mb-8">
    <div class="bg-slate-950 px-4 py-3 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="text-xs font-bold uppercase tracking-wider text-indigo-400">💻 Editor Web de Código (Monaco Editor)</span>
            <span id="current-file-badge" class="text-xs font-mono bg-slate-800 text-slate-200 px-2.5 py-1 rounded border border-slate-700">
                Selecciona un archivo a la izquierda
            </span>
        </div>
        <div class="flex items-center gap-2">
            <span id="save-status" class="text-xs text-emerald-400 hidden">¡Cambios guardados!</span>
            <button type="button" onclick="promptNewFile()" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1.5 rounded border border-slate-700">
                + Crear Archivo / Carpeta
            </button>
            <button type="button" onclick="saveCurrentFile()" class="text-xs bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-4 py-1.5 rounded shadow">
                💾 Guardar Archivo (Ctrl+S)
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 min-h-[540px]">
        <div class="lg:col-span-3 bg-slate-900 border-r border-slate-800 p-3 overflow-y-auto max-h-[540px]">
            <div class="text-[11px] font-semibold uppercase text-slate-400 mb-2 px-2">
                Mis Archivos (/~{{ $project->user->username }}/{{ $project->folder_slug }}/)
            </div>
            @if(count($fileTree) > 0)
                <x-file-tree :items="$fileTree" />
            @else
                <div class="text-xs text-slate-500 p-2">Sube tu .ZIP o crea tu primer archivo.</div>
            @endif
        </div>

        <div class="lg:col-span-9 relative">
            <div id="monaco-container" class="w-full h-[540px]"></div>
        </div>
    </div>
</div>

@if($project->project_type === 'laravel')
<div class="bg-slate-900 text-slate-200 rounded-xl p-5 border border-slate-800">
    <h3 class="text-sm font-bold text-rose-400 uppercase mb-2">Visor de Errores Laravel (storage/logs/laravel.log)</h3>
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
            value: "// Haz clic en un archivo a la izquierda para editarlo o usa '+ Crear Archivo' arriba.\n",
            language: 'php',
            theme: 'vs-dark',
            automaticLayout: true,
            fontSize: 13
        });

        monacoEditor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, function () {
            saveCurrentFile();
        });

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
            if (!silent) alert('Error al cargar el archivo.');
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
            alert(data.error || 'Error al guardar.');
        }
    }

    function promptNewFile() {
        const name = prompt('Nombre o ruta del nuevo archivo (ej. index.php, includes/db.php, estilo.css):');
        if (!name) return;
        currentFilePath = name.trim();
        document.getElementById('current-file-badge').textContent = currentFilePath + ' (nuevo - presiona Guardar)';
        monacoEditor.setValue("<?php\n// Archivo: " + currentFilePath + "\n");
    }
</script>
@endpush

