<div class="space-y-4">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-900 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold">
                <i class="fa-solid fa-inbox"></i>
            </div>
            <div>
                <p class="font-bold">Citizen Complaint Intake Queue</p>
                <p class="text-xs text-blue-700 mt-0.5">Dispute complaint requests submitted online by residents awaiting verification and official docketing.</p>
            </div>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-200 text-blue-900">
            {{ $pendingIntakes?->total() ?? 0 }} Pending
        </span>
    </div>

    @if(!$pendingIntakes || $pendingIntakes->isEmpty())
        <div class="p-12 text-center bg-white border border-slate-200 rounded-xl">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-lg">
                <i class="fa-solid fa-check"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-800">All clear! No pending intakes</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">New dispute requests submitted by residents through the public complaint portal will appear here.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700 table-fixed">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4 w-[14%]">Submitted</th>
                            <th class="py-3 px-4 w-[22%]">Complainant</th>
                            <th class="py-3 px-4 w-[22%]">Respondent</th>
                            <th class="py-3 px-4 w-[26%]">Nature & 5Ws</th>
                            <th class="py-3 px-4 w-[16%] text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($pendingIntakes as $intake)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-3 px-4 align-top">
                                    <div class="font-bold text-slate-900">{{ $intake->submitted_at?->diffForHumans() }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $intake->submitted_at?->format('M j, Y g:i A') }}</div>
                                    @if($intake->case_type_suggestion)
                                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                            {{ $intake->case_type_suggestion }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 align-top">
                                    <div class="font-bold text-slate-900">{{ $intake->complainant_name }}</div>
                                    @if($intake->complainant_alias)
                                        <div class="text-[10px] text-slate-500">"{{ $intake->complainant_alias }}"</div>
                                    @endif
                                    <div class="text-[11px] text-slate-600 mt-0.5">{{ $intake->complainant_contact }}</div>
                                    <div class="text-[11px] text-slate-500 truncate" title="{{ $intake->complainant_address }}">{{ $intake->complainant_address }}</div>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-semibold rounded {{ $intake->complainant_residency_claim === 'resident' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst($intake->complainant_residency_claim) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 align-top">
                                    <div class="font-bold text-slate-900">{{ $intake->respondent_name }}</div>
                                    @if($intake->respondent_alias)
                                        <div class="text-[10px] text-slate-500">"{{ $intake->respondent_alias }}"</div>
                                    @endif
                                    <div class="text-[11px] text-slate-600 mt-0.5">{{ $intake->respondent_contact ?? 'No phone provided' }}</div>
                                    <div class="text-[11px] text-slate-500 truncate" title="{{ $intake->respondent_address }}">{{ $intake->respondent_address }}</div>
                                </td>
                                <td class="py-3 px-4 align-top">
                                    <p class="font-medium text-slate-800 line-clamp-2" title="{{ $intake->what_happened }}">{{ $intake->what_happened }}</p>
                                    <div class="mt-1 text-[11px] text-slate-500 flex items-center gap-1.5">
                                        <i class="fa-solid fa-location-dot text-slate-400"></i>
                                        <span class="truncate">{{ $intake->where_happened }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400">Occurred: {{ $intake->when_happened?->format('M j, Y g:i A') }}</div>
                                </td>
                                <td class="py-3 px-4 align-top text-right">
                                    <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                        @can('cases.create')
                                            <a href="{{ route('admin.cases.create', ['intake_id' => $intake->id]) }}"
                                               class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-md shadow-sm transition-colors"
                                               title="Prefill case creation form and docket">
                                                <i class="fa-solid fa-plus-circle"></i>
                                                <span>Docket this</span>
                                            </a>
                                        @endcan
                                        @can('cases.edit')
                                            <button type="button"
                                                    onclick="openRejectModal({{ $intake->id }}, @json($intake->complainant_name), @json($intake->respondent_name))"
                                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 border border-red-200 rounded-md transition-colors"
                                                    title="Reject or redirect intake request">
                                                <i class="fa-solid fa-ban"></i>
                                                <span>Reject</span>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($pendingIntakes->hasPages())
                <div class="p-4 border-t border-slate-200">
                    {{ $pendingIntakes->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
