@extends('layouts.app')

@section('title', 'Entregas de Actividad - ' . $activity->title)

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <a href="{{ route('teacher.activities.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">← Volver a Actividades</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $activity->title }}</h1>
        <p class="text-sm text-slate-500">Grupo: <strong>{{ $activity->group->name }} ({{ $activity->group->code }})</strong> · Tipo: <strong>{{ $activity->typeLabel() }}</strong></p>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm text-left">
        <thead class="bg-slate-50 text-slate-600 text-xs uppercase border-b border-slate-200">
            <tr>
                <th class="px-5 py-3">Alumno / Matrícula</th>
                <th class="px-5 py-3">Estado de Entrega</th>
                <th class="px-5 py-3">Sitio Desplegado</th>
                <th class="px-5 py-3">Base de Datos Vinculada</th>
                <th class="px-5 py-3">Calificación</th>
                <th class="px-5 py-3 text-right">Revisión</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($activity->group->students as $student)
                @php
                    $project = $student->projects->first();
                @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3.5">
                        <div class="font-semibold text-slate-900">{{ $student->name }}</div>
                        <div class="text-xs font-mono text-slate-500">{{ $student->username }}</div>
                    </td>
                    <td class="px-5 py-3.5">
                        @if(!$project)
                            <span class="px-2.5 py-0.5 rounded-full text-xs bg-slate-100 text-slate-500">Sin iniciar</span>
                        @elseif($project->status === 'graded')
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Calificado</span>
                        @elseif($project->status === 'submitted')
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Entregado</span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-xs bg-blue-50 text-blue-700">En progreso (Borrador)</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 font-mono text-xs">
                        @if($project)
                            <a href="{{ $project->publicUrl() }}" target="_blank" class="text-indigo-600 hover:underline">
                                /~{{ $student->username }}/{{ $project->folder_slug }}/ ↗
                            </a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 font-mono text-xs text-slate-600">
                        {{ $project?->studentDatabase?->db_name ?? '—' }}
                    </td>
                    <td class="px-5 py-3.5 font-bold">
                        {{ $project && $project->score !== null ? $project->score . ' / ' . $activity->max_score : '—' }}
                    </td>
                    <td class="px-5 py-3.5 text-right">
                        @if($project)
                            <a href="{{ route('teacher.projects.inspect', $project) }}"
                                class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white">
                                Abrir Super-Visor (Código + BD)
                            </a>
                        @else
                            <span class="text-xs text-slate-400">No ha creado proyecto</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

