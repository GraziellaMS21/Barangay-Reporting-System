@php
    $linkedCase = \App\Models\BarangayCase::where('source_report_id', $report->id)->first();
@endphp

@if($linkedCase)
    <a href="{{ route('admin.cases.show', $linkedCase) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition-colors">
        <i class="fa-solid fa-gavel"></i>
        <span>Docketed: {{ $linkedCase->case_number }}</span>
    </a>
@elseif(auth()->user()?->hasPermission('cases.create'))
    @if(!empty($formId))
        <button type="submit" form="{{ $formId }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-300 hover:bg-slate-200 transition-colors" title="Docket this complaint under Katarungang Pambarangay">
            <i class="fa-solid fa-scale-balanced"></i>
            <span>Convert to Blotter Case</span>
        </button>
    @else
        <form action="{{ route('admin.reports.convert-to-case', $report) }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-300 hover:bg-slate-200 transition-colors" title="Docket this complaint under Katarungang Pambarangay">
                <i class="fa-solid fa-scale-balanced"></i>
                <span>Convert to Blotter Case</span>
            </button>
        </form>
    @endif
@endif
