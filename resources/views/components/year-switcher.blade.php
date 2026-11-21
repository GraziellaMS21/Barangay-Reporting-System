@php
    $context = app(\App\Services\FiscalYearContext::class);
    $viewing = $context->viewing();
    $years = \App\Models\FiscalYear::orderByDesc('year')->get();
@endphp

<form action="{{ route('year.switch') }}" method="POST" class="inline-flex items-center">
    @csrf
    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 hover:bg-slate-200/80 rounded-lg text-xs font-semibold text-slate-700 border border-slate-200 transition-colors">
        <i class="fa-regular fa-calendar text-blue-600"></i>
        <select name="fiscal_year_id" onchange="this.form.submit()" class="bg-transparent border-none text-xs font-semibold text-slate-800 focus:ring-0 cursor-pointer p-0 pr-4">
            <option value="" @selected($viewing === null)>All Fiscal Years</option>
            @foreach($years as $fy)
                <option value="{{ $fy->id }}" @selected($viewing && $viewing->id === $fy->id)>
                    {{ $fy->label }} {{ $fy->status === 'active' ? '(Active)' : '' }}
                </option>
            @endforeach
        </select>
    </div>
</form>
