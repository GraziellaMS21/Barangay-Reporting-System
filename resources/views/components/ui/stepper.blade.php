@props([
    'steps' => [],
    'current' => 1,
])

<div {{ $attributes->merge(['class' => 'w-full my-4']) }}>
    <nav aria-label="Progress">
        <ol role="list" class="flex items-center justify-between border-b border-slate-200 pb-4">
            @foreach($steps as $idx => $step)
                @php
                    $stepNum = $idx + 1;
                    $isCompleted = $stepNum < (int)$current;
                    $isCurrent = $stepNum === (int)$current;
                    $stepLabel = is_array($step) ? ($step['label'] ?? "Step $stepNum") : $step;
                @endphp
                <li class="relative flex-1 text-center {{ ! $loop->last ? 'pr-2' : '' }}">
                    <div class="flex flex-col items-center group">
                        <span @class([
                            'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-colors',
                            'bg-blue-600 text-white shadow-sm' => $isCompleted || $isCurrent,
                            'bg-slate-100 text-slate-500 border border-slate-300' => ! $isCompleted && ! $isCurrent,
                        ])>
                            @if($isCompleted)
                                <i class="fa-solid fa-check text-xs"></i>
                            @else
                                {{ $stepNum }}
                            @endif
                        </span>
                        <span @class([
                            'mt-2 text-xs font-medium truncate max-w-[100px] sm:max-w-none',
                            'text-blue-700 font-semibold' => $isCurrent,
                            'text-slate-700' => $isCompleted,
                            'text-slate-400' => ! $isCompleted && ! $isCurrent,
                        ])>
                            {{ $stepLabel }}
                        </span>
                    </div>
                </li>
            @endforeach
        </ol>
    </nav>
</div>
