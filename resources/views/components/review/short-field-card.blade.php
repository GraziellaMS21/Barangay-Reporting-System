@props([
    'label',
    'fieldName',
    'residentValue' => null,
    'aiValue' => null,
    'currentValue' => null,
    'provenance' => null,
    'aiStatus' => 'completed', // completed, failed, pending
])

@php
    $hasAi = filled($aiValue) && trim((string)$aiValue) !== '' && trim((string)$aiValue) !== 'Not stated in the narrative';
    $hasResident = filled($residentValue) && trim((string)$residentValue) !== '';
    $val = $currentValue ?? ($hasAi ? $aiValue : ($hasResident ? $residentValue : ''));
    
    // Provenance badge logic
    if (empty($provenance)) {
        if ($aiStatus === 'failed') {
            $provenance = $hasResident ? 'resident' : null;
        } elseif ($hasAi) {
            $provenance = 'ai';
        } elseif ($hasResident) {
            $provenance = 'resident';
        } else {
            $provenance = 'not_provided';
        }
    }
    
    $differs = $hasAi && $hasResident && trim((string)$aiValue) !== trim((string)$residentValue);
    $bothValue = ($hasAi && $hasResident && $differs) ? trim($residentValue) . ' (AI Note: ' . trim($aiValue) . ')' : ($hasAi ? $aiValue : $residentValue);
    $cardId = 'field_card_' . md5($fieldName);
@endphp

<div id="{{ $cardId }}" class="p-4 rounded-xl border border-slate-200 bg-white shadow-xs space-y-3 transition-all hover:border-slate-300">
    <div class="flex items-center justify-between gap-2">
        <label for="input_{{ $fieldName }}" class="text-xs font-bold uppercase tracking-wider text-slate-700">
            {{ $label }}
        </label>
        <div class="flex items-center gap-1.5">
            @if($differs)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                    Differs
                </span>
            @endif
            <span id="badge_{{ $fieldName }}">
                <x-ui.provenance-badge :provenance="$provenance" />
            </span>
        </div>
    </div>

    <!-- Editable Input Field -->
    <div>
        <input type="text"
               id="input_{{ $fieldName }}"
               name="{{ $fieldName }}"
               value="{{ $val }}"
               oninput="updateProvenance('{{ $fieldName }}', 'staff_edited')"
               placeholder="{{ $aiStatus === 'failed' ? 'AI unavailable. Retry or fill in manually.' : ($hasAi ? '' : 'Not stated in the narrative') }}"
               class="w-full text-xs font-medium text-slate-900 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 p-2.5 transition-colors">
        <input type="hidden" id="prov_{{ $fieldName }}" name="field_provenance[{{ $fieldName }}]" value="{{ $provenance ?? 'resident' }}">
    </div>

    <!-- Read-only Reference Lines (AI & Resident) -->
    <div class="space-y-1 text-[11px] bg-slate-50 p-2 rounded border border-slate-100">
        <div class="text-slate-600 truncate" title="{{ $hasAi ? $aiValue : 'None' }}">
            <span class="font-bold text-purple-700">AI:</span> 
            <span class="{{ $hasAi ? 'text-slate-800' : 'text-slate-400 italic' }}">{{ $hasAi ? $aiValue : 'None' }}</span>
        </div>
        <div class="text-slate-600 truncate" title="{{ $hasResident ? $residentValue : 'None' }}">
            <span class="font-bold text-blue-700">Resident:</span> 
            <span class="{{ $hasResident ? 'text-slate-800' : 'text-slate-400 italic' }}">{{ $hasResident ? $residentValue : 'None' }}</span>
        </div>
    </div>

    <!-- Action Buttons [Use AI] [Use Resident] [Use Both] -->
    <div class="pt-1 flex items-center justify-end gap-1.5 text-[11px]">
        <button type="button"
                @if($hasAi)
                    onclick="setFieldValue('{{ $fieldName }}', {{ json_encode($aiValue) }}, 'ai')"
                    class="px-2 py-1 rounded text-purple-700 bg-purple-50 hover:bg-purple-100 font-semibold border border-purple-200 transition-colors"
                @else
                    disabled
                    title="AI found no data for this field"
                    class="px-2 py-1 rounded text-slate-400 bg-slate-100 font-medium border border-slate-200 cursor-not-allowed opacity-60"
                @endif>
            Use AI
        </button>

        <button type="button"
                @if($hasResident)
                    onclick="setFieldValue('{{ $fieldName }}', {{ json_encode($residentValue) }}, 'resident')"
                    class="px-2 py-1 rounded text-blue-700 bg-blue-50 hover:bg-blue-100 font-semibold border border-blue-200 transition-colors"
                @else
                    disabled
                    title="Resident provided no structured data"
                    class="px-2 py-1 rounded text-slate-400 bg-slate-100 font-medium border border-slate-200 cursor-not-allowed opacity-60"
                @endif>
            Use Resident
        </button>

        <button type="button"
                @if($hasAi && $hasResident && $differs)
                    onclick="setFieldValue('{{ $fieldName }}', {{ json_encode($bothValue) }}, 'staff_edited')"
                    class="px-2 py-1 rounded text-emerald-700 bg-emerald-50 hover:bg-emerald-100 font-semibold border border-emerald-200 transition-colors"
                    title="Combine Resident original and AI note"
                @else
                    disabled
                    title="Requires differing values from both AI and Resident"
                    class="px-2 py-1 rounded text-slate-400 bg-slate-100 font-medium border border-slate-200 cursor-not-allowed opacity-60"
                @endif>
            Use Both
        </button>
    </div>
</div>
