@extends('layouts.app')

@section('title', 'Gestión de Grupos - EduHost')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Formulario Nuevo Grupo -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 h-fit">
        <h2 class="text-lg font-bold text-slate-900 mb-1">Crear Nuevo Grupo</h2>
        <p class="text-xs text-slate-500 mb-4">Organiza a tus alumnos por materia o semestre.</p>

        <form action="{{ route('teacher.groups.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nombre del Grupo / Materia</label>
                <input type="text" name="name" required placeholder="Ej. 5A - Desarrollo Web PHP y MariaDB"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Código Único</label>
                    <input type="text" name="code" required placeholder="Ej. 5A-WEB"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Semestre</label>
                    <input type="text" name="semester" placeholder="Ej. 2026-2"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Descripción</label>
                <textarea name="description" rows="3" placeholder="Notas o criterios del curso..."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg text-sm">
                Guardar Grupo
            </button>
        </form>
    </div>

    <!-- Lista de Grupos -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="font-bold text-slate-800">Grupos Registrados ({{ $groups->count() }})</h2>
        </div>
        <div class="divide-y divide-slate-200">
            @forelse($groups as $group)
            <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded bg-indigo-100 text-indigo-800 font-mono text-xs font-bold">{{ $group->code }}</span>
                        <h3 class="font-bold text-slate-900">{{ $group->name }}</h3>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">{{ $group->description ?: 'Sin descripción' }} · Semestre: {{ $group->semester ?: 'Actual' }}</p>
                    <div class="flex items-center gap-4 mt-2 text-xs font-medium text-slate-600">
                        <span>👥 {{ $group->students_count }} Alumnos</span>
                        <span>📚 {{ $group->activities_count }} Actividades asignadas</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('teacher.students.index', ['group_id' => $group->id]) }}"
                        class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-medium hover:bg-slate-100">
                        Ver Alumnos
                    </a>
                    <form action="{{ route('teacher.groups.destroy', $group) }}" method="POST" onsubmit="return confirm('¿Eliminar este grupo?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-medium">
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-slate-500 text-sm">No hay grupos creados aún.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

