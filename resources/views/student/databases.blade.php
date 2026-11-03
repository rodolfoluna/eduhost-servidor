@extends('layouts.app')

@section('title', 'Mis Bases de Datos MariaDB - EduHost')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">🗄️ Mis Bases de Datos MariaDB</h1>
        <p class="text-sm text-slate-500">Crea tus bases de datos para tus prácticas de PHP + PDO/MySQLi y proyectos de Laravel.</p>
    </div>
    <a href="{{ config('eduhost.adminer_url', '/db-manager/') }}?server=127.0.0.1&username={{ $student->db_user }}"
        target="_blank"
        class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2.5 rounded-lg text-sm shadow-sm">
        <span>Abrir Gestor Visual Adminer (Tipo phpMyAdmin)</span>
        <span>↗</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Crear Nueva Base de Datos -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-base font-bold text-slate-900 mb-1">Crear Base de Datos</h2>
        <p class="text-xs text-slate-500 mb-4">
            Límite permitido: {{ $student->studentDatabases->count() }} de {{ config('eduhost.max_databases_per_student', 5) }} bases de datos.
        </p>

        <form action="{{ route('student.databases.store') }}" method="POST" class="space-y-3.5">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nombre de la Base de Datos</label>
                <div class="flex items-center">
                    <span class="bg-slate-100 border border-r-0 border-slate-300 rounded-l-lg px-2.5 py-2 text-xs font-mono text-slate-600">
                        {{ $student->dbPrefix() }}
                    </span>
                    <input type="text" name="db_suffix" required placeholder="tienda"
                        class="w-full rounded-r-lg border border-slate-300 px-3 py-2 text-sm font-mono">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Descripción / Práctica</label>
                <input type="text" name="description" placeholder="Ej. Base de datos para Práctica 2 CRUD"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">
            </div>

            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg text-sm">
                + Crear Base de Datos en MariaDB
            </button>
        </form>
    </div>

    <!-- Credenciales y Snippet de Conexión PDO / .env -->
    <div class="lg:col-span-2 bg-slate-900 text-slate-100 rounded-xl border border-slate-800 shadow-sm p-6">
        <h2 class="text-sm font-bold text-indigo-400 uppercase tracking-wider mb-3">
            Tus Credenciales de Conexión en el Servidor
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4 text-xs font-mono">
            <div class="bg-slate-800 p-3 rounded-lg border border-slate-700">
                <div class="text-slate-400 text-[10px] uppercase">Servidor (Host)</div>
                <div class="text-emerald-300 font-bold mt-0.5">127.0.0.1</div>
            </div>
            <div class="bg-slate-800 p-3 rounded-lg border border-slate-700">
                <div class="text-slate-400 text-[10px] uppercase">Puerto</div>
                <div class="text-white font-bold mt-0.5">3306</div>
            </div>
            <div class="bg-slate-800 p-3 rounded-lg border border-slate-700">
                <div class="text-slate-400 text-[10px] uppercase">Usuario MySQL</div>
                <div class="text-amber-300 font-bold mt-0.5">{{ $student->db_user }}</div>
            </div>
            <div class="bg-slate-800 p-3 rounded-lg border border-slate-700">
                <div class="text-slate-400 text-[10px] uppercase">Contraseña</div>
                <div class="text-rose-300 font-bold mt-0.5">{{ $student->db_password_plain }}</div>
            </div>
        </div>

        <div class="text-xs text-slate-400 mb-1">Código listo para copiar en tus proyectos de PHP Puro (PDO):</div>
        @php
            $sampleDb = $student->studentDatabases->first()?->db_name ?? ($student->dbPrefix() . 'tienda');
        @endphp
        <pre class="bg-slate-950 text-slate-200 p-3.5 rounded-lg text-xs font-mono overflow-x-auto border border-slate-800">&lt;?php
$pdo = new PDO(
    "mysql:host=127.0.0.1;dbname={{ $sampleDb }};charset=utf8mb4",
    "{{ $student->db_user }}",
    "{{ $student->db_password_plain }}",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);</pre>
    </div>
</div>

<!-- Lista de Bases de Datos del Alumno e Importador .SQL -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
    <div class="px-6 py-4 border-b border-slate-200">
        <h2 class="font-bold text-slate-900">Mis Bases de Datos Activas ({{ $student->studentDatabases->count() }})</h2>
    </div>

    <div class="divide-y divide-slate-200">
        @forelse($student->studentDatabases as $db)
            <div class="p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-bold text-base text-indigo-700">{{ $db->db_name }}</span>
                        <a href="{{ route('student.databases.index', ['db_id' => $db->id]) }}"
                            class="text-xs px-2.5 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium">
                            Inspeccionar Tablas
                        </a>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $db->description ?: 'Base de datos MariaDB' }}</p>
                </div>

                <!-- Formulario Importar .SQL + Abrir en Adminer + Eliminar -->
                <div class="flex flex-wrap items-center gap-3">
                    <form action="{{ route('student.databases.import', $db) }}" method="POST" enctype="multipart/form-data"
                        class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                        @csrf
                        <input type="file" name="sql_file" accept=".sql,.txt" required
                            class="text-xs text-slate-600 w-48 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-indigo-50 file:text-indigo-700">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-3 py-1.5 rounded">
                            Importar .SQL
                        </button>
                    </form>

                    <a href="{{ config('eduhost.adminer_url', '/db-manager/') }}?server=127.0.0.1&username={{ $student->db_user }}&db={{ $db->db_name }}"
                        target="_blank"
                        class="text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-semibold px-3 py-2 rounded-lg">
                        Abrir en Adminer ↗
                    </a>

                    <form action="{{ route('student.databases.destroy', $db) }}" method="POST"
                        onsubmit="return confirm('¿Seguro que deseas eliminar la base de datos {{ $db->db_name }} y todas sus tablas?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs bg-rose-50 hover:bg-rose-100 text-rose-700 px-3 py-2 rounded-lg font-medium">
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-500 text-sm">
                Aún no has creado ninguna base de datos. Usa el formulario de arriba para crear la primera.
            </div>
        @endforelse
    </div>
</div>

<!-- Vista Rápida de Tablas de la BD Seleccionada -->
@if($dbInspection)
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
    <h3 class="font-bold text-slate-900 mb-3">
        Vista Rápida de Tablas en <code class="text-indigo-600">{{ $dbInspection['database'] }}</code>
    </h3>
    <div class="flex flex-wrap gap-2 mb-4">
        @forelse($dbInspection['tables'] as $tbl)
            <a href="{{ route('student.databases.index', ['db_id' => $selectedDbId, 'table' => $tbl]) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-mono {{ $dbInspection['active_table'] === $tbl ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                ⊞ {{ $tbl }}
            </a>
        @empty
            <p class="text-xs text-slate-500">Esta base de datos aún no tiene tablas. Importa un archivo <code>.sql</code> arriba o ábrela en Adminer.</p>
        @endforelse
    </div>

    @if($dbInspection['active_table'] && count($dbInspection['rows']) > 0)
        <div class="overflow-x-auto border border-slate-200 rounded-lg">
            <table class="w-full text-xs text-left font-mono">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        @foreach(array_keys((array) $dbInspection['rows'][0]) as $colName)
                            <th class="px-3 py-2 border-b">{{ $colName }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($dbInspection['rows'] as $row)
                        <tr>
                            @foreach((array) $row as $val)
                                <td class="px-3 py-1.5">{{ is_null($val) ? 'NULL' : $val }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endif
@endsection

