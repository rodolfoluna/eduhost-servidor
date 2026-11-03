@props(['items', 'level' => 0])

<ul class="{{ $level > 0 ? 'pl-3 border-l border-slate-700 ml-2 space-y-0.5' : 'space-y-0.5' }}">
    @foreach($items as $item)
        @if($item['type'] === 'dir')
            <li>
                <details {{ $level === 0 ? 'open' : '' }} class="group">
                    <summary class="flex items-center justify-between px-2 py-1 rounded cursor-pointer hover:bg-slate-800 text-xs text-slate-300 select-none">
                        <span class="flex items-center gap-1.5 truncate">
                            <span class="text-amber-400">📁</span>
                            <span class="font-medium">{{ $item['name'] }}/</span>
                            @if(!empty($item['is_vendor']))
                                <span class="text-[10px] bg-slate-800 text-slate-400 px-1 rounded">sistema</span>
                            @endif
                        </span>
                    </summary>
                    @if(!empty($item['children']))
                        <x-file-tree :items="$item['children']" :level="$level + 1" />
                    @endif
                </details>
            </li>
        @else
            <li class="flex items-center justify-between group rounded hover:bg-slate-800 px-2 py-1">
                <button type="button"
                    onclick="loadFileInMonaco('{{ addslashes($item['path']) }}')"
                    class="flex-1 text-left flex items-center gap-1.5 text-xs text-slate-300 hover:text-white truncate">
                    <span class="text-indigo-400">📄</span>
                    <span class="truncate font-mono">{{ $item['name'] }}</span>
                </button>
                <span class="text-[10px] text-slate-500 font-mono ml-2">{{ $item['size_kb'] }}K</span>
            </li>
        @endif
    @endforeach
</ul>

