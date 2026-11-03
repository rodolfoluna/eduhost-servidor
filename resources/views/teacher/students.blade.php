@extends('layouts.app')

@section('title', 'Gestión de Alumnos y Cuentas de Hosting - EduHost')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Alta Individual de Alumno -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-base font-bold text-slate-900 mb-1">Registrar Alumno Individual</h2>
        <p class="text-xs text-slate-500 mb-4">Crea automáticamente su carpeta web y su usuario aislado de MariaDB.</p>

        <form action="{{ route('teacher.students.store') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Grupo</label>
                <select name="group_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->code }} - {{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Matrícula / Usuario (será su URL /~matricula/)</label>
                <input type="text" name="username" required placeholder="Ej. 2026002 o maria_lopez"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Nombre Completo</label>
                <input type="text" name="name" required placeholder="Ej. María López Hernández"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" required placeholder="alumno@instituto.edu"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Contraseña Inicial</label>
                    <input type="text" name="password" required value="alumno123"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
                </div>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded-lg text-sm">
                Crear Cuenta de Hosting y Alumno
            </button>
        </form>
    </div>

    <!-- Importación Masiva (CSV para los 30 Alumnos) -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-base font-bold text-slate-900 mb-1">Importación Rápida en Lote (Carga tus 30 Alumnos en 1 Clic)</h2>
        <p class="text-xs text-slate-500 mb-3">
            Pega una línea por alumno con el formato: <code>matricula,nombre completo,correo,contraseña</code>
        </p>

        <form action="{{ route('teacher.students.import') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Asignar al Grupo:</label>
                <select name="group_id" required class="w-full sm:w-80 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->code }} - {{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <textarea name="csv_lines" rows="6" required
                    placeholder="2026002,María López,maria@instituto.edu,alumno123&#10;2026003,Carlos Ramírez,carlos@instituto.edu,alumno123&#10;2026004,Ana Torres,ana@instituto.edu,alumno123"
                    class="w-full rounded-lg border border-slate-300 p-3 text-xs font-mono bg-slate-50"></textarea>
            </div>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-5 py-2 rounded-lg text-sm shadow-sm">
                Importar y Aprovisionar Cuentas en el Servidor
            </button>
        </form>
    </div>
</div>

<!-- Tabla de Alumnos y Credenciales de Hosting -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <h2 class="font-bold text-slate-800">Directorio de Alumnos y Cuentas MariaDB ({{ $students->count() }})</h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3">Matrícula / URL</th>
                    <th class="px-4 py-3">Alumno / Email</th>
                    <th class="px-4 py-3">Grupo</th>
                    <th class="px-4 py-3">Credenciales MariaDB</th>
                    <th class="px-4 py-3">Proyectos / BDs</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($students as $st)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-xs">
                        <div class="font-bold text-indigo-700">{{ $st->username }}</div>
                        <div class="text-slate-400">/~{{ $st->username }}/</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-semibold text-slate-900">{{ $st->name }}</div>
                        <div class="text-xs text-slate-500">{{ $st->email }}</div>
                    </td>
                    <td class="px-4 py-3 text-xs font-medium">
                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700">{{ $st->group?->code ?? '—' }}</span>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs">
                        <div>User: <strong class="text-slate-800">{{ $st->db_user }}</strong></div>
                        <div>Pass: <code class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-700">{{ $st->db_password_plain }}</code></div>
                    </td>
                    <td class="px-4 py-3 text-xs">
                        <div><strong>{{ $st->projects->count() }}</strong> sitios web</div>
                        <div class="text-slate-500"><strong>{{ $st->studentDatabases->count() }}</strong> bases de datos</div>
                    </td>
                    <td class="px-4 py-3">
                        @if($st->is_active)
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Activo</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">Suspendido</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <form action="{{ route('teacher.students.toggle', $st) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-xs px-2.5 py-1 rounded border border-slate-300 hover:bg-slate-100">
                                {{ $st->is_active ? 'Suspender' : 'Reactivar' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-slate-500">No hay alumnos registrados todavía.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

