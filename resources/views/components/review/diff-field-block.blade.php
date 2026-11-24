@props([
    'label',
    'fieldName',
    'residentText' => null,
    'aiText' => null,
    'currentValue' => null,
    'provenance' => 'ai',
])

@php
    $val = $currentValue ?? $aiText ?? $residentText ?? '';
    $differs = filled($aiText) && filled($residentText) && trim((string)$aiText) !== trim((string)$residentText);
@endphp

<div x-data="{
    current: @js($val),
    provenance: @js($provenance),
    showDiff: false,
    editing: false,
    aiText: @js($aiText ?? ''),
    resText: @js($residentText ?? ''),
    useAi() {
        this.current = this.aiText;
        this.provenance = 'ai';
        this.editing = false;
        $dispatch('field-reviewed', { field: @js($fieldName) });
    },
    useResident() {
        this.current = this.resText;
        this.provenance = 'resident';
        this.editing = false;
        $dispatch('field-reviewed', { field: @js($fieldName) });
    },
    markAccepted() {
        $dispatch('field-reviewed', { field: @js($fieldName) });
    }
}"
x-init="markAccepted()"
class="p-5 rounded-xl border border-slate-200 bg-white shadow-xs space-y-4 transition-all hover:border-slate-300">
    <div class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2">
        <div class="flex items-center gap-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">
                {{ $label }}
            </h3>
            @if($differs)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                    Differs
                </span>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <template x-if="provenance === 'ai'">
                <x-ui.provenance-badge provenance="ai" />
            </template>
            <template x-if="provenance === 'resident'">
                <x-ui.provenance-badge provenance="resident" />
            </template>
            <template x-if="provenance === 'staff_edited'">
                <x-ui.provenance-badge provenance="staff_edited" />
            </template>
        </div>
    </div>

    <!-- Active Field Content / Editor -->
    <div>
        <template x-if="!editing">
            <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-900 leading-relaxed whitespace-pre-wrap font-sans">
                <span x-text="current || 'No information provided'"></span>
            </div>
        </template>
        <template x-if="editing">
            <div>
                <textarea x-model="current" 
                          @input="provenance = 'staff_edited'; $dispatch('field-reviewed', { field: @js($fieldName) })"
                          rows="4" 
                          class="w-full text-xs rounded-lg border-blue-500 focus:ring-blue-500 p-3 bg-white border leading-relaxed font-sans"></textarea>
            </div>
        </template>
    </div>

    <!-- Hidden form inputs -->
    <input type="hidden" name="{{ $fieldName }}" :value="current">
    <input type="hidden" name="field_provenance[{{ $fieldName }}]" :value="provenance">

    <!-- Comparison & Diff Drawer -->
    @if($differs)
        <div>
            <button type="button" 
                    @click="showDiff = !showDiff" 
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                <i class="fa-solid" :class="showDiff ? 'fa-chevron-up' : 'fa-code-compare'"></i>
                <span x-text="showDiff ? 'Hide Comparison' : 'Compare Resident Narrative vs AI Extraction'"></span>
            </button>

            <div x-show="showDiff" x-cloak class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs">
                <div class="space-y-1">
                    <span class="block font-bold text-slate-600 uppercase text-[10px]">Resident Original Text</span>
                    <p class="p-2.5 bg-white rounded border border-slate-200 text-slate-700 whitespace-pre-wrap leading-relaxed">{{ $residentText }}</p>
                </div>
                <div class="space-y-1">
                    <span class="block font-bold text-purple-700 uppercase text-[10px]">AI Structured Extraction</span>
                    <p class="p-2.5 bg-purple-50/50 rounded border border-purple-200 text-purple-900 whitespace-pre-wrap leading-relaxed">{{ $aiText }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Action Toolbar -->
    <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-3 text-xs">
        <span class="text-slate-500 text-[11px]">Select source or edit directly</span>
        <div class="flex items-center gap-1.5">
            @if($aiText)
                <button type="button" 
                        @click="useAi()" 
                        class="px-2.5 py-1.5 rounded-lg text-purple-700 bg-purple-50 hover:bg-purple-100 font-semibold border border-purple-200 transition-colors">
                    <i class="fa-solid fa-wand-magic-sparkles mr-1 text-[10px]"></i> Use AI
                </button>
            @endif
            @if($residentText)
                <button type="button" 
                        @click="useResident()" 
                        class="px-2.5 py-1.5 rounded-lg text-slate-700 bg-slate-100 hover:bg-slate-200 font-semibold border border-slate-200 transition-colors">
                    <i class="fa-solid fa-user mr-1 text-[10px]"></i> Use Resident
                </button>
            @endif
            <button type="button" 
                    @click="editing = !editing; if(editing) provenance = 'staff_edited'" 
                    class="px-2.5 py-1.5 rounded-lg text-blue-700 bg-blue-50 hover:bg-blue-100 font-semibold border border-blue-200 transition-colors">
                <i class="fa-solid fa-pen-to-square mr-1 text-[10px]"></i>
                <span x-text="editing ? 'Save Edit' : 'Edit Text'"></span>
            </button>
        </div>
    </div>
</div>
