@props([
    'label',
    'fieldName',
    'residentValue' => null,
    'aiValue' => null,
    'currentValue' => null,
    'provenance' => 'ai',
])

@php
    $val = $currentValue ?? $aiValue ?? $residentValue ?? '';
    $differs = filled($aiValue) && filled($residentValue) && trim((string)$aiValue) !== trim((string)$residentValue);
@endphp

<div x-data="{
    current: @js($val),
    provenance: @js($provenance),
    editing: false,
    aiVal: @js($aiValue ?? ''),
    resVal: @js($residentValue ?? ''),
    useAi() {
        this.current = this.aiVal;
        this.provenance = 'ai';
        this.editing = false;
        $dispatch('field-reviewed', { field: @js($fieldName) });
    },
    useResident() {
        this.current = this.resVal;
        this.provenance = 'resident';
        this.editing = false;
        $dispatch('field-reviewed', { field: @js($fieldName) });
    },
    markAccepted() {
        $dispatch('field-reviewed', { field: @js($fieldName) });
    }
}" 
x-init="markAccepted()"
class="p-4 rounded-xl border border-slate-200 bg-white shadow-xs space-y-3 transition-all hover:border-slate-300">
    <div class="flex items-center justify-between gap-2">
        <label class="text-xs font-bold uppercase tracking-wider text-slate-700">
            {{ $label }}
        </label>
        <div class="flex items-center gap-1.5">
            @if($differs)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                    Differs
                </span>
            @endif
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

    <!-- Display / Edit Field -->
    <div>
        <template x-if="!editing">
            <div class="p-2.5 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-900 font-medium break-words">
                <span x-text="current || 'Not specified'"></span>
            </div>
        </template>
        <template x-if="editing">
            <input type="text" 
                   x-model="current" 
                   @input="provenance = 'staff_edited'; $dispatch('field-reviewed', { field: @js($fieldName) })"
                   class="w-full text-xs rounded-lg border-blue-500 focus:ring-blue-500 p-2.5 bg-white border">
        </template>
    </div>

    <!-- Hidden form input for submission -->
    <input type="hidden" name="{{ $fieldName }}" :value="current">
    <input type="hidden" name="field_provenance[{{ $fieldName }}]" :value="provenance">

    <!-- Value Source Comparison & Action Chips -->
    <div class="pt-1 border-t border-slate-100 flex items-center justify-between gap-2 text-[11px]">
        <div class="text-slate-500 truncate max-w-[200px]" title="Resident value">
            @if($residentValue)
                <span class="font-semibold text-slate-600">Resident:</span> {{ $residentValue }}
            @endif
        </div>
        <div class="flex items-center gap-1 shrink-0">
            @if($aiValue)
                <button type="button" 
                        @click="useAi()" 
                        class="px-2 py-1 rounded text-purple-700 bg-purple-50 hover:bg-purple-100 font-semibold border border-purple-200 transition-colors"
                        title="Use AI Suggestion">
                    Use AI
                </button>
            @endif
            @if($residentValue)
                <button type="button" 
                        @click="useResident()" 
                        class="px-2 py-1 rounded text-slate-700 bg-slate-100 hover:bg-slate-200 font-semibold border border-slate-200 transition-colors"
                        title="Use Resident Value">
                    Use Resident
                </button>
            @endif
            <button type="button" 
                    @click="editing = !editing; if(editing) provenance = 'staff_edited'" 
                    class="px-2 py-1 rounded text-blue-700 bg-blue-50 hover:bg-blue-100 font-semibold border border-blue-200 transition-colors">
                <span x-text="editing ? 'Done' : 'Edit'"></span>
            </button>
        </div>
    </div>
</div>
