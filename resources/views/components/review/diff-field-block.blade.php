@props([
    'label',
    'fieldName',
    'residentText' => null,
    'aiText' => null,
    'currentValue' => null,
    'provenance' => null,
    'aiStatus' => 'completed',
    'required' => false,
])

@php
    $hasAi = filled($aiText) && trim((string)$aiText) !== '' && trim((string)$aiText) !== 'Not stated in the narrative';
    $hasResident = filled($residentText) && trim((string)$residentText) !== '';
    $val = $currentValue ?? ($hasAi ? $aiText : ($hasResident ? $residentText : ''));
    
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
    
    $differs = $hasAi && $hasResident && trim((string)$aiText) !== trim((string)$residentText);
    $bothValue = ($hasAi && $hasResident && $differs) ? trim($residentText) . "\n\n[AI Extracted Particulars]:\n" . trim($aiText) : ($hasAi ? $aiText : $residentText);
    $blockId = 'diff_block_' . md5($fieldName);
@endphp

<div id="{{ $blockId }}" class="p-5 rounded-xl border border-slate-200 bg-white shadow-xs space-y-4 transition-all hover:border-slate-300">
    <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2">
        <div class="flex items-center gap-2">
            <label for="textarea_{{ $fieldName }}" class="text-xs font-bold uppercase tracking-wider text-slate-800">
                {{ $label }} @if($required) <span class="text-red-600">*</span> @endif
            </label>
            @if($differs)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                    Differs
                </span>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <span id="badge_{{ $fieldName }}">
                <x-ui.provenance-badge :provenance="$provenance" />
            </span>
        </div>
    </div>

    <!-- Active Textarea -->
    <div>
        <textarea id="textarea_{{ $fieldName }}"
                  name="{{ $fieldName }}"
                  rows="4"
                  @if($required) required @endif
                  oninput="updateProvenance('{{ $fieldName }}', 'staff_edited')"
                  placeholder="{{ $aiStatus === 'failed' ? 'AI unavailable. Retry or fill in manually.' : ($hasAi ? '' : 'Not stated in the narrative') }}"
                  class="w-full text-xs text-slate-900 leading-relaxed font-sans rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 p-3 transition-colors">{{ $val }}</textarea>
        <input type="hidden" id="prov_{{ $fieldName }}" name="field_provenance[{{ $fieldName }}]" value="{{ $provenance ?? 'resident' }}">
    </div>

    <!-- Read-only Reference Lines (AI & Resident) -->
    <div class="space-y-1.5 text-[11px] bg-slate-50 p-2.5 rounded-lg border border-slate-100">
        <div class="text-slate-600">
            <span class="font-bold text-purple-700">AI:</span> 
            <span class="{{ $hasAi ? 'text-slate-800' : 'text-slate-400 italic' }}">{{ $hasAi ? Str::limit($aiText, 180) : 'None' }}</span>
        </div>
        <div class="text-slate-600">
            <span class="font-bold text-blue-700">Resident:</span> 
            <span class="{{ $hasResident ? 'text-slate-800' : 'text-slate-400 italic' }}">{{ $hasResident ? Str::limit($residentText, 180) : 'None' }}</span>
        </div>
    </div>

    <!-- Action Buttons & Comparison Drawer Toggle -->
    <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-100">
        @if($differs)
            <button type="button" 
                    onclick="toggleDiff('diff_drawer_{{ $fieldName }}')" 
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                <i class="fa-solid fa-code-compare"></i>
                <span>Compare Full Text</span>
            </button>
        @else
            <div></div>
        @endif

        <div class="flex items-center gap-1.5 text-[11px]">
            <button type="button"
                    @if($hasAi)
                        onclick="setFieldValue('{{ $fieldName }}', {{ json_encode($aiText) }}, 'ai')"
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
                        onclick="setFieldValue('{{ $fieldName }}', {{ json_encode($residentText) }}, 'resident')"
                        class="px-2 py-1 rounded text-blue-700 bg-blue-50 hover:bg-blue-100 font-semibold border border-blue-200 transition-colors"
                    @else
                        disabled
                        title="Resident provided no text"
                        class="px-2 py-1 rounded text-slate-400 bg-slate-100 font-medium border border-slate-200 cursor-not-allowed opacity-60"
                    @endif>
                Use Resident
            </button>

            <button type="button"
                    @if($hasAi && $hasResident && $differs)
                        onclick="setFieldValue('{{ $fieldName }}', {{ json_encode($bothValue) }}, 'staff_edited')"
                        class="px-2 py-1 rounded text-emerald-700 bg-emerald-50 hover:bg-emerald-100 font-semibold border border-emerald-200 transition-colors"
                        title="Combine Resident original and AI extraction"
                    @else
                        disabled
                        title="Requires differing values from both AI and Resident"
                        class="px-2 py-1 rounded text-slate-400 bg-slate-100 font-medium border border-slate-200 cursor-not-allowed opacity-60"
                    @endif>
                Use Both
            </button>
        </div>
    </div>

    @if($differs)
        <div id="diff_drawer_{{ $fieldName }}" class="hidden grid grid-cols-1 md:grid-cols-2 gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs">
            <div class="space-y-1">
                <span class="block font-bold text-slate-600 uppercase text-[10px]">Resident Original Text</span>
                <p class="p-2.5 bg-white rounded border border-slate-200 text-slate-700 whitespace-pre-wrap leading-relaxed">{{ $residentText }}</p>
            </div>
            <div class="space-y-1">
                <span class="block font-bold text-purple-700 uppercase text-[10px]">AI Extracted Text</span>
                <p class="p-2.5 bg-white rounded border border-purple-200 text-slate-800 whitespace-pre-wrap leading-relaxed">{{ $aiText }}</p>
            </div>
        </div>
    @endif
</div>
