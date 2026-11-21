<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-xl border border-slate-200 bg-white']) }}>
    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        @if(isset($header))
            <thead class="bg-slate-50 font-semibold text-slate-700">
                {{ $header }}
            </thead>
        @endif
        <tbody class="divide-y divide-slate-100 text-slate-800">
            {{ $slot }}
        </tbody>
        @if(isset($footer))
            <tfoot class="bg-slate-50 border-t border-slate-200 font-medium text-slate-600">
                {{ $footer }}
            </tfoot>
        @endif
    </table>
</div>
