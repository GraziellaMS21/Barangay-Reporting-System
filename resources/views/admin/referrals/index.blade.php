@extends('layouts.admin')

@section('title', 'Referrals and routing')
@section('page-heading', 'Referrals and routing')

@section('content')
{{-- ── Page Header ───────────────────────────────────────────── --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Referrals &amp; Routing Directory</h1>
    <p class="text-sm text-slate-500 mt-0.5">Track reports routed to internal offices and external agencies.</p>
  </div>
  @if(auth()->user()->isAdmin())
    <button type="button" class="btn btn-gold btn-small self-start sm:self-auto" onclick="openAddOfficeModal()">
      <i class="fa-solid fa-plus" aria-hidden="true"></i> Add office or agency
    </button>
  @endif
</div>

{{-- Flash messages --}}
@if(session('success'))
  <div class="mb-5 rounded-xl p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3" role="alert">
    <i class="fa-solid fa-circle-check text-emerald-600" aria-hidden="true"></i>
    {{ session('success') }}
  </div>
@endif
@if($errors->has('office'))
  <div class="mb-5 rounded-xl p-3.5 bg-red-50 border border-red-200 text-red-800 text-sm flex items-center gap-3" role="alert">
    <i class="fa-solid fa-triangle-exclamation text-red-600" aria-hidden="true"></i>
    {{ $errors->first('office') }}
  </div>
@endif

{{-- ── KPI Row ───────────────────────────────────────────────── --}}
<section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-6" aria-label="Routing metrics">
  {{-- Destinations --}}
  <div class="card rounded-2xl bg-white p-6 shadow-sm border border-slate-100 flex items-center justify-between">
    <div class="min-w-0 pr-3">
      <span class="text-xs font-medium text-slate-500">Destinations</span>
      <div class="text-3xl font-bold tracking-tight text-slate-900 mt-1">{{ $metrics['totalOffices'] }}</div>
      <span class="text-xs text-slate-400 mt-1 block">{{ $metrics['internalOffices'] }} internal · {{ $metrics['externalOffices'] }} external</span>
    </div>
    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 bg-blue-50 text-blue-600" aria-hidden="true">
      <i class="fa-solid fa-building text-lg"></i>
    </div>
  </div>

  {{-- Internal referrals --}}
  <div class="card rounded-2xl bg-white p-6 shadow-sm border border-slate-100 flex items-center justify-between">
    <div class="min-w-0 pr-3">
      <span class="text-xs font-medium text-slate-500">Internal referrals</span>
      <div class="text-3xl font-bold tracking-tight text-blue-700 mt-1">{{ $metrics['activeInternalReports'] }}</div>
      <span class="text-xs text-slate-400 mt-1 block">Active cases within barangay</span>
    </div>
    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 bg-blue-50 text-blue-600" aria-hidden="true">
      <i class="fa-solid fa-house-laptop text-lg"></i>
    </div>
  </div>

  {{-- External referrals --}}
  <div class="card rounded-2xl bg-white p-6 shadow-sm border border-slate-100 flex items-center justify-between">
    <div class="min-w-0 pr-3">
      <span class="text-xs font-medium text-slate-500">External referrals</span>
      <div class="text-3xl font-bold tracking-tight text-amber-700 mt-1">{{ $metrics['activeExternalReferrals'] }}</div>
      <span class="text-xs text-slate-400 mt-1 block">Active cases sent to agencies</span>
    </div>
    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 bg-amber-50 text-amber-600" aria-hidden="true">
      <i class="fa-solid fa-building-flag text-lg"></i>
    </div>
  </div>

  {{-- Overdue --}}
  <div class="card rounded-2xl bg-white p-6 shadow-sm border border-slate-100 flex items-center justify-between">
    <div class="min-w-0 pr-3">
      <span class="text-xs font-medium text-slate-500">Overdue</span>
      <div class="text-3xl font-bold tracking-tight {{ $metrics['overdueCases'] > 0 ? 'text-red-600' : 'text-emerald-600' }} mt-1">
        {{ $metrics['overdueCases'] ?: 0 }}
      </div>
      <span class="text-xs text-slate-400 mt-1 block">Past follow-up or SLA due date</span>
    </div>
    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 {{ $metrics['overdueCases'] > 0 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }}" aria-hidden="true">
      <i class="fa-solid {{ $metrics['overdueCases'] > 0 ? 'fa-triangle-exclamation' : 'fa-check-circle' }} text-lg"></i>
    </div>
  </div>
</section>

{{-- ── Tabs ───────────────────────────────────────────────────── --}}
<div class="flex items-center gap-1 mb-5 border-b border-slate-200" role="tablist">
  <button type="button" role="tab" class="ref-tab px-4 py-2.5 text-sm font-semibold transition-colors {{ ($tab ?? 'referrals') === 'referrals' ? 'text-blue-700 border-b-2 border-blue-600' : 'text-slate-500 hover:text-slate-800' }}"
          data-tab-target="tab-referrals" aria-selected="{{ ($tab ?? 'referrals') === 'referrals' ? 'true' : 'false' }}">
    Referrals
  </button>
  <button type="button" role="tab" class="ref-tab px-4 py-2.5 text-sm font-semibold transition-colors {{ ($tab ?? 'referrals') === 'offices' ? 'text-blue-700 border-b-2 border-blue-600' : 'text-slate-500 hover:text-slate-800' }}"
          data-tab-target="tab-offices" aria-selected="{{ ($tab ?? 'referrals') === 'offices' ? 'true' : 'false' }}">
    Offices &amp; agencies
  </button>
</div>

{{-- ══════════════════════════════════════════════════════════════
     TAB 1 — REFERRALS (single card: filter bar + compact table)
     ══════════════════════════════════════════════════════════════ --}}
<div id="tab-referrals" class="ref-tab-panel {{ ($tab ?? 'referrals') !== 'referrals' ? 'hidden' : '' }}">
  <section class="card rounded-2xl overflow-hidden">

    {{-- Filter bar (inside the card) --}}
    <form method="GET" action="{{ route('admin.referrals.index') }}" class="p-4 sm:p-5 border-b border-slate-100">
      @if(($tab ?? 'referrals') === 'offices')
        <input type="hidden" name="tab" value="offices">
      @endif

      {{-- Office chips (horizontal, scrollable on mobile) --}}
      <div class="flex items-center gap-2 overflow-x-auto pb-3 mb-3 -mx-1 px-1 scrollbar-thin" style="scrollbar-width:thin;">
        <a href="{{ route('admin.referrals.index', array_filter(['status' => $statusFilter !== 'all' ? $statusFilter : null, 'search' => $search])) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap border transition-colors {{ !$officeFilter ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300' }}">
          All
          <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold {{ !$officeFilter ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $referrals->total() }}</span>
        </a>
        @foreach($offices->where('is_active', true) as $office)
          @php $oc = $office->reports_count + $office->referrals_count; @endphp
          <a href="{{ route('admin.referrals.index', array_filter(['office_id' => $office->id, 'status' => $statusFilter !== 'all' ? $statusFilter : null, 'search' => $search])) }}"
             class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap border transition-colors {{ (int)$officeFilter === $office->id ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300' }}">
            {{ \Illuminate\Support\Str::limit($office->name, 24) }}
            <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 rounded-full text-[10px] font-bold {{ (int)$officeFilter === $office->id ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $oc }}</span>
          </a>
        @endforeach
      </div>

      {{-- Status + search + apply --}}
      <div class="flex items-end gap-3 flex-wrap">
        <div class="w-44">
          <label for="status" class="block text-xs font-bold text-slate-600 mb-1">Status</label>
          <select id="status" name="status" class="w-full text-xs rounded-lg border-slate-300 py-1.5">
            <option value="all" @selected($statusFilter === 'all')>All statuses</option>
            @foreach($statusOptions as $groupLabel => $groupItems)
              <optgroup label="{{ $groupLabel }}">
                @foreach($groupItems as $k => $v)
                  <option value="{{ $k }}" @selected($statusFilter === $k)>{{ $v }}</option>
                @endforeach
              </optgroup>
            @endforeach
          </select>
        </div>
        <div class="flex-1 min-w-[180px]">
          <label for="search" class="block text-xs font-bold text-slate-600 mb-1">Search</label>
          <input type="text" id="search" name="search" value="{{ $search }}" placeholder="Tracking code or keyword…" class="w-full text-xs rounded-lg border-slate-300 py-1.5">
        </div>
        @if($officeFilter)
          <input type="hidden" name="office_id" value="{{ $officeFilter }}">
        @endif
        <div class="flex items-center gap-2">
          <button type="submit" class="btn btn-navy btn-small text-xs py-1.5 px-3"><i class="fa-solid fa-filter" aria-hidden="true"></i> Apply</button>
          <a href="{{ route('admin.referrals.index') }}" class="btn btn-ghost btn-small text-xs py-1.5 px-2.5">Clear</a>
        </div>
      </div>
    </form>

    {{-- Referrals table (5 columns, table-fixed, never scrolls horizontally) --}}
    <div class="overflow-hidden">
      <table class="data-table data-table-cards ref-table w-full" style="table-layout:fixed;">
        <colgroup>
          <col style="width:22%;">{{-- Ref --}}
          <col style="width:22%;">{{-- Report --}}
          <col style="width:22%;">{{-- Destination --}}
          <col style="width:28%;">{{-- Status --}}
          <col style="width:6%;">{{-- View --}}
        </colgroup>
        <thead>
          <tr>
            <th class="text-left">Ref no.</th>
            <th class="text-left">Report</th>
            <th class="text-left">Destination</th>
            <th class="text-left">Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($referrals as $row)
            @php
              $isOverdue = $row['is_overdue'] && $row['follow_up_at'];
              $isDueSoon = !$isOverdue && $row['follow_up_at'] && $row['follow_up_at']->isFuture() && $row['follow_up_at']->diffInDays(now()) <= 7;

              // Status pill color
              $pillClass = match ($row['status']) {
                'prepared', 'needs_info', 'awaiting_confirmation' => 'bg-amber-50 text-amber-800 border-amber-200',
                'sent', 'acknowledged', 'routed', 'in_progress' => 'bg-blue-50 text-blue-800 border-blue-200',
                'action_taken', 'resolved' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                default => 'bg-slate-50 text-slate-700 border-slate-200',
              };
              if ($isOverdue) $pillClass = 'bg-red-50 text-red-800 border-red-200';
            @endphp

            <tr class="ref-row {{ $isOverdue ? 'ref-row-overdue' : '' }}" data-href="{{ $row['url'] }}"
                data-drawer="{{ json_encode([
                  'ref' => $row['reference_no'],
                  'tracking' => $row['tracking_code'],
                  'kind' => $row['kind'],
                  'type_label' => $row['type_label'],
                  'office' => $row['office_name'],
                  'category' => $row['category_name'],
                  'urgency' => $row['urgency'],
                  'status' => $row['status_label'],
                  'owner' => $row['owner_name'],
                  'routed_at' => $row['routed_or_sent_at']?->format('M d, Y') ?? $row['routed_or_sent_empty'],
                  'follow_up' => $row['follow_up_at']?->format('M d, Y') ?? 'Not scheduled',
                  'is_overdue' => $isOverdue,
                  'url' => $row['url'],
                ]) }}">

              {{-- 1. Ref no. --}}
              <td data-label="Ref no.">
                <span class="font-mono font-bold text-blue-700 text-sm truncate block">{{ $row['reference_no'] }}</span>
                @if($row['kind'] === 'external' && $row['tracking_code'])
                  <span class="text-[11px] text-slate-400 font-mono truncate block mt-0.5">{{ $row['tracking_code'] }}</span>
                @endif
              </td>

              {{-- 2. Report (category + urgency) --}}
              <td data-label="Report">
                <span class="text-sm font-medium text-slate-800 truncate block">{{ $row['category_name'] }}</span>
                @if(!empty($row['urgency']) && $row['urgency'] !== 'Normal')
                  <span class="mt-0.5 inline-block text-[10px] font-bold px-1.5 py-0.5 rounded {{ match($row['urgency']) {
                    'Critical' => 'bg-red-100 text-red-700',
                    'High' => 'bg-amber-100 text-amber-800',
                    default => 'bg-slate-100 text-slate-700',
                  } }}">{{ $row['urgency'] }}</span>
                @endif
              </td>

              {{-- 3. Destination --}}
              <td data-label="Destination">
                <span class="text-sm font-medium text-slate-800 truncate block">{{ $row['office_name'] }}</span>
                <span class="text-[11px] font-semibold {{ $row['kind'] === 'external' ? 'text-amber-700' : 'text-blue-700' }}">{{ $row['type_label'] }}</span>
              </td>

              {{-- 4. Status + follow-up combined pill --}}
              <td data-label="Status">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $pillClass }}">
                  @if($isOverdue)
                    <i class="fa-solid fa-triangle-exclamation text-[10px]" aria-hidden="true"></i>
                    Overdue · due {{ $row['follow_up_at']->format('M d') }}
                  @elseif($isDueSoon)
                    {{ $row['status_label'] }} · due {{ $row['follow_up_at']->format('M d') }}
                  @else
                    {{ $row['status_label'] }}
                  @endif
                </span>
              </td>

              {{-- 5. Chevron --}}
              <td data-label="" class="text-right">
                <button type="button" class="ref-row-detail-btn text-slate-400 hover:text-blue-600 transition-colors p-1" aria-label="View details" onclick="event.stopPropagation(); openDrawer(this.closest('tr'))">
                  <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="p-10 text-center">
                <i class="fa-solid fa-inbox text-4xl text-slate-200 block mb-3" aria-hidden="true"></i>
                <p class="text-sm text-slate-500 font-medium">No referrals match the current filters.</p>
                <a href="{{ route('admin.referrals.index') }}" class="text-xs text-blue-600 hover:underline mt-1 inline-block">Clear filters</a>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($referrals->hasPages())
      <div class="p-4 border-t border-slate-100">{{ $referrals->links() }}</div>
    @endif
  </section>
</div>

{{-- ══════════════════════════════════════════════════════════════
     TAB 2 — OFFICES & AGENCIES (management table)
     ══════════════════════════════════════════════════════════════ --}}
<div id="tab-offices" class="ref-tab-panel {{ ($tab ?? 'referrals') !== 'offices' ? 'hidden' : '' }}">
  <section class="card rounded-2xl overflow-hidden">

    {{-- Offices filter bar --}}
    <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center gap-3">
      {{-- Segmented filter --}}
      <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-lg text-xs" role="tablist">
        <button type="button" class="off-seg px-3 py-1.5 rounded-md font-bold bg-white text-slate-800 shadow-sm" data-seg="all">All ({{ $offices->count() }})</button>
        <button type="button" class="off-seg px-3 py-1.5 rounded-md font-semibold text-slate-600 hover:text-slate-900" data-seg="internal">Internal ({{ $metrics['internalOffices'] }})</button>
        <button type="button" class="off-seg px-3 py-1.5 rounded-md font-semibold text-slate-600 hover:text-slate-900" data-seg="external">External ({{ $metrics['externalOffices'] }})</button>
      </div>

      <div class="flex-1"></div>

      {{-- Search --}}
      <div class="relative w-full sm:w-56">
        <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400 text-xs" aria-hidden="true"></i>
        <input type="text" id="off-search" class="w-full text-xs rounded-lg border-slate-300 py-1.5 pl-7" placeholder="Search offices…" autocomplete="off">
      </div>
    </div>

    {{-- Offices table --}}
    <div class="overflow-hidden">
      <table class="data-table data-table-cards w-full" style="table-layout:fixed;">
        <colgroup>
          <col style="width:28%;">
          <col style="width:14%;">
          <col style="width:24%;">
          <col style="width:10%;">
          <col style="width:12%;">
          <col style="width:12%;">
        </colgroup>
        <thead>
          <tr>
            <th class="text-left">Office / Agency</th>
            <th class="text-left">Type</th>
            <th class="text-left">Contact</th>
            <th class="text-center">Cases</th>
            <th class="text-center">Active</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($offices as $office)
            @php
              $isInternal = $office->directoryType() === 'internal';
              $totalCases = $office->reports_count + $office->referrals_count;
              $canDelete = ($totalCases + $office->categories_count + $office->routing_rules_count) === 0;
              $searchText = strtolower($office->name . ' ' . $office->contact_person . ' ' . ($office->defaultAssignee?->name ?? ''));
            @endphp
            <tr class="off-row" data-off-type="{{ $office->directoryType() }}" data-off-search="{{ $searchText }}">
              {{-- Name --}}
              <td data-label="Office / Agency">
                <span class="text-sm font-bold text-slate-900 truncate block">{{ $office->name }}</span>
                @if($office->description)
                  <span class="text-[11px] text-slate-400 truncate block mt-0.5">{{ \Illuminate\Support\Str::limit($office->description, 60) }}</span>
                @endif
              </td>

              {{-- Type --}}
              <td data-label="Type">
                @if($isInternal)
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200">Internal</span>
                @else
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">External</span>
                @endif
              </td>

              {{-- Contact --}}
              <td data-label="Contact">
                @if($office->contact_person || ($isInternal && $office->defaultAssignee))
                  <span class="text-xs font-medium text-slate-800 truncate block">
                    {{ $office->contact_person ?: $office->defaultAssignee?->name }}
                  </span>
                @endif
                @if($office->contact_phone)
                  <span class="text-[11px] text-slate-400 truncate block">{{ $office->contact_phone }}</span>
                @endif
              </td>

              {{-- Cases --}}
              <td data-label="Cases" class="text-center">
                @if($totalCases > 0)
                  <a href="{{ route('admin.referrals.index', ['office_id' => $office->id]) }}" class="text-sm font-bold text-blue-700 hover:underline">{{ $totalCases }}<span class="sr-only">{{ $totalCases === 1 ? '1 case' : 'cases' }}</span></a>
                @else
                  <span class="text-sm text-slate-400">0</span>
                @endif
              </td>

              {{-- Active toggle --}}
              <td data-label="Active" class="text-center">
                @if(auth()->user()->isAdmin())
                  <form method="POST" action="{{ route('admin.settings.offices.toggle-active', $office) }}" class="inline">
                    @csrf @method('PATCH')
                    <input type="hidden" name="_redirect_to" value="{{ route('admin.referrals.index', ['tab' => 'offices']) }}">
                    <button type="submit"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $office->is_active ? 'bg-blue-600' : 'bg-slate-300' }}"
                            role="switch" aria-checked="{{ $office->is_active ? 'true' : 'false' }}"
                            title="{{ $office->is_active ? 'Deactivate' : 'Activate' }} {{ $office->name }}">
                      <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 {{ $office->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                    </button>
                  </form>
                @else
                  <span class="text-xs font-semibold {{ $office->is_active ? 'text-emerald-700' : 'text-slate-400' }}">{{ $office->is_active ? 'Yes' : 'No' }}</span>
                @endif
              </td>

              {{-- Actions --}}
              <td data-label="Actions" class="text-center">
                @if(auth()->user()->isAdmin())
                  <div class="flex items-center justify-center gap-1">
                    <button type="button" class="text-slate-400 hover:text-blue-600 p-1 transition-colors" onclick="openEditOfficeModal({{ json_encode($office) }})" title="Edit {{ $office->name }}">
                      <i class="fa-solid fa-pen-to-square text-xs"></i>
                    </button>
                    @if($canDelete)
                      <form method="POST" action="{{ route('admin.settings.offices.destroy', $office) }}" class="inline" onsubmit="return confirm('Delete {{ $office->name }}?')">
                        @csrf @method('DELETE')
                        <input type="hidden" name="_redirect_to" value="{{ route('admin.referrals.index', ['tab' => 'offices']) }}">
                        <button type="submit" class="text-slate-400 hover:text-red-600 p-1 transition-colors" title="Delete">
                          <i class="fa-solid fa-trash-can text-xs"></i>
                        </button>
                      </form>
                    @else
                      <span class="text-slate-200 p-1 cursor-not-allowed" title="Linked to cases or rules — deactivate instead">
                        <i class="fa-solid fa-lock text-xs"></i>
                      </span>
                    @endif
                  </div>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="p-8 text-center text-sm text-slate-500">No offices or agencies registered.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</div>

{{-- ── Detail Drawer (right-side, shared for all rows) ───────── --}}
<div id="ref-drawer-backdrop" class="fixed inset-0 bg-slate-900/40 z-[1040] hidden" onclick="closeDrawer()"></div>
<aside id="ref-drawer" class="fixed top-0 right-0 h-full w-full sm:w-96 bg-white shadow-xl z-[1050] transform translate-x-full transition-transform duration-200 ease-in-out overflow-y-auto" aria-label="Case details">
  <div class="p-5">
    {{-- Drawer header --}}
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-base font-bold text-slate-900 tracking-tight" id="drawer-title">Case details</h3>
      <button type="button" onclick="closeDrawer()" class="text-slate-400 hover:text-slate-600 p-1" aria-label="Close"><i class="fa-solid fa-xmark text-lg"></i></button>
    </div>

    {{-- Drawer body — filled by JS --}}
    <dl class="space-y-3 text-sm" id="drawer-body"></dl>

    {{-- Go to report --}}
    <a id="drawer-link" href="#" class="btn btn-navy btn-small w-full mt-6 text-center text-xs py-2">
      <i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Open full report
    </a>
  </div>
</aside>

{{-- ── Modals ────────────────────────────────────────────────── --}}
@if(auth()->user()->isAdmin())
  {{-- ADD OFFICE MODAL --}}
  <div id="add-office-modal" class="office-modal-overlay" onclick="handleModalBackdropClick(event, 'add-office-modal')">
    <div class="office-modal-container" role="dialog" aria-modal="true" aria-labelledby="add-office-title">
      <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
        <h2 id="add-office-title" class="text-lg font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-plus-circle text-blue-600"></i> Add Office or Agency</h2>
        <button type="button" class="text-slate-400 hover:text-slate-600 p-1" onclick="closeAddOfficeModal()" aria-label="Close"><i class="fa-solid fa-xmark text-lg"></i></button>
      </div>
      <form method="POST" action="{{ route('admin.settings.offices.store') }}">
        @csrf
        <input type="hidden" name="_redirect_to" value="{{ route('admin.referrals.index', ['tab' => 'offices']) }}">
        <div class="space-y-4 text-xs">
          <div>
            <label for="add_name" class="block font-bold text-slate-700 mb-1">Name <span class="text-red-500">*</span></label>
            <input type="text" id="add_name" name="name" required class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5" placeholder="e.g. Barangay Health Committee">
          </div>
          <div>
            <label for="add_type" class="block font-bold text-slate-700 mb-1">Type <span class="text-red-500">*</span></label>
            <select id="add_type" name="type" required class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5" onchange="toggleAssigneeField('add')">
              <option value="internal">Internal (within barangay)</option>
              <option value="external">External (beyond barangay)</option>
            </select>
          </div>
          <div>
            <label for="add_description" class="block font-bold text-slate-700 mb-1">Description</label>
            <textarea id="add_description" name="description" rows="2" class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5" placeholder="Scope and responsibilities…"></textarea>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="add_contact_person" class="block font-bold text-slate-700 mb-1">Contact person</label>
              <input type="text" id="add_contact_person" name="contact_person" class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
            </div>
            <div>
              <label for="add_contact_phone" class="block font-bold text-slate-700 mb-1">Phone</label>
              <input type="text" id="add_contact_phone" name="contact_phone" class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="add_contact_email" class="block font-bold text-slate-700 mb-1">Email</label>
              <input type="email" id="add_contact_email" name="contact_email" class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
            </div>
            <div>
              <label for="add_address" class="block font-bold text-slate-700 mb-1">Address</label>
              <input type="text" id="add_address" name="address" class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
            </div>
          </div>
          <div id="add_assignee_container">
            <label for="add_default_assignee_id" class="block font-bold text-slate-700 mb-1">Default assignee</label>
            <select id="add_default_assignee_id" name="default_assignee_id" class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
              <option value="">None (manual assignment)</option>
              @foreach($staffUsers as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ ucfirst($s->role) }})</option>@endforeach
            </select>
          </div>
          <div class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="add_is_active" name="is_active" value="1" checked class="rounded border-slate-300 text-blue-600">
            <label for="add_is_active" class="font-bold text-slate-700">Active</label>
          </div>
        </div>
        <div class="flex items-center justify-end gap-2 mt-6 pt-3 border-t border-slate-100">
          <button type="button" class="btn btn-ghost btn-small text-xs py-1.5 px-3" onclick="closeAddOfficeModal()">Cancel</button>
          <button type="submit" class="btn btn-navy btn-small text-xs py-1.5 px-4 font-bold"><i class="fa-solid fa-check" aria-hidden="true"></i> Save</button>
        </div>
      </form>
    </div>
  </div>

  {{-- EDIT OFFICE MODAL --}}
  <div id="edit-office-modal" class="office-modal-overlay" onclick="handleModalBackdropClick(event, 'edit-office-modal')">
    <div class="office-modal-container" role="dialog" aria-modal="true" aria-labelledby="edit-office-title">
      <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
        <h2 id="edit-office-title" class="text-lg font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-pen-to-square text-blue-600"></i> Edit Office</h2>
        <button type="button" class="text-slate-400 hover:text-slate-600 p-1" onclick="closeEditOfficeModal()" aria-label="Close"><i class="fa-solid fa-xmark text-lg"></i></button>
      </div>
      <form id="edit-office-form" method="POST" action="">
        @csrf @method('PUT')
        <input type="hidden" name="_redirect_to" value="{{ route('admin.referrals.index', ['tab' => 'offices']) }}">
        <div class="space-y-4 text-xs">
          <div>
            <label for="edit_name" class="block font-bold text-slate-700 mb-1">Name <span class="text-red-500">*</span></label>
            <input type="text" id="edit_name" name="name" required class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
          </div>
          <div>
            <label for="edit_type" class="block font-bold text-slate-700 mb-1">Type <span class="text-red-500">*</span></label>
            <select id="edit_type" name="type" required class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5" onchange="toggleAssigneeField('edit')">
              <option value="internal">Internal (within barangay)</option>
              <option value="external">External (beyond barangay)</option>
            </select>
          </div>
          <div>
            <label for="edit_description" class="block font-bold text-slate-700 mb-1">Description</label>
            <textarea id="edit_description" name="description" rows="2" class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5"></textarea>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="edit_contact_person" class="block font-bold text-slate-700 mb-1">Contact person</label>
              <input type="text" id="edit_contact_person" name="contact_person" class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
            </div>
            <div>
              <label for="edit_contact_phone" class="block font-bold text-slate-700 mb-1">Phone</label>
              <input type="text" id="edit_contact_phone" name="contact_phone" class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="edit_contact_email" class="block font-bold text-slate-700 mb-1">Email</label>
              <input type="email" id="edit_contact_email" name="contact_email" class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
            </div>
            <div>
              <label for="edit_address" class="block font-bold text-slate-700 mb-1">Address</label>
              <input type="text" id="edit_address" name="address" class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
            </div>
          </div>
          <div id="edit_assignee_container">
            <label for="edit_default_assignee_id" class="block font-bold text-slate-700 mb-1">Default assignee</label>
            <select id="edit_default_assignee_id" name="default_assignee_id" class="w-full text-xs rounded-lg border-slate-300 py-1.5 px-2.5">
              <option value="">None (manual assignment)</option>
              @foreach($staffUsers as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ ucfirst($s->role) }})</option>@endforeach
            </select>
          </div>
          <div class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="edit_is_active" name="is_active" value="1" class="rounded border-slate-300 text-blue-600">
            <label for="edit_is_active" class="font-bold text-slate-700">Active</label>
          </div>
        </div>
        <div class="flex items-center justify-end gap-2 mt-6 pt-3 border-t border-slate-100">
          <button type="button" class="btn btn-ghost btn-small text-xs py-1.5 px-3" onclick="closeEditOfficeModal()">Cancel</button>
          <button type="submit" class="btn btn-navy btn-small text-xs py-1.5 px-4 font-bold"><i class="fa-solid fa-check" aria-hidden="true"></i> Update</button>
        </div>
      </form>
    </div>
  </div>
@endif

{{-- ── Scripts ───────────────────────────────────────────────── --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  /* ── Tabs ─────────────────────────────────────────────── */
  document.querySelectorAll('.ref-tab').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.ref-tab').forEach(t => {
        t.classList.remove('text-blue-700', 'border-b-2', 'border-blue-600');
        t.classList.add('text-slate-500');
        t.setAttribute('aria-selected', 'false');
      });
      this.classList.remove('text-slate-500');
      this.classList.add('text-blue-700', 'border-b-2', 'border-blue-600');
      this.setAttribute('aria-selected', 'true');
      document.querySelectorAll('.ref-tab-panel').forEach(p => p.classList.add('hidden'));
      document.getElementById(this.dataset.tabTarget).classList.remove('hidden');
    });
  });

  /* ── Offices tab: segmented filter + search ──────────── */
  let offSeg = 'all';
  document.querySelectorAll('.off-seg').forEach(btn => {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.off-seg').forEach(b => { b.classList.remove('bg-white', 'text-slate-800', 'shadow-sm', 'font-bold'); b.classList.add('text-slate-600', 'font-semibold'); });
      this.classList.remove('text-slate-600', 'font-semibold'); this.classList.add('bg-white', 'text-slate-800', 'shadow-sm', 'font-bold');
      offSeg = this.dataset.seg; filterOffices();
    });
  });
  const offSearch = document.getElementById('off-search');
  if (offSearch) offSearch.addEventListener('input', filterOffices);
  function filterOffices() {
    const q = (offSearch ? offSearch.value : '').toLowerCase();
    document.querySelectorAll('.off-row').forEach(r => {
      const matchType = offSeg === 'all' || r.dataset.offType === offSeg;
      const matchSearch = !q || (r.dataset.offSearch || '').includes(q);
      r.style.display = matchType && matchSearch ? '' : 'none';
    });
  }

  /* ── Referral row click → open drawer ────────────────── */
  document.querySelectorAll('.ref-row').forEach(row => {
    row.style.cursor = 'pointer';
    row.addEventListener('click', function (e) {
      if (e.target.closest('a') || e.target.closest('button')) return;
      openDrawer(this);
    });
  });
});

/* ── Drawer ──────────────────────────────────────────────── */
function openDrawer(row) {
  const data = JSON.parse(row.dataset.drawer || '{}');
  const body = document.getElementById('drawer-body');
  const fields = [
    ['Reference', data.ref],
    ['Tracking code', data.tracking || '—'],
    ['Type', data.type_label],
    ['Office / Agency', data.office],
    ['Category', data.category],
    ['Urgency', data.urgency],
    ['Status', data.status],
    ['Assignee / Owner', data.owner],
    ['Routed / Sent', data.routed_at],
    ['Follow-up due', data.follow_up + (data.is_overdue ? ' (overdue)' : '')],
  ];
  body.innerHTML = fields.map(([label, value]) =>
    '<div class="flex justify-between gap-4 py-2 border-b border-slate-100">' +
    '<dt class="text-slate-500 font-medium text-xs whitespace-nowrap">' + label + '</dt>' +
    '<dd class="text-slate-900 font-semibold text-xs text-right">' + (value || '—') + '</dd></div>'
  ).join('');
  document.getElementById('drawer-link').href = data.url || '#';
  document.getElementById('drawer-title').textContent = data.ref || 'Case details';
  document.getElementById('ref-drawer').classList.remove('translate-x-full');
  document.getElementById('ref-drawer-backdrop').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}
function closeDrawer() {
  document.getElementById('ref-drawer').classList.add('translate-x-full');
  document.getElementById('ref-drawer-backdrop').classList.add('hidden');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeDrawer(); closeAddOfficeModal(); closeEditOfficeModal(); } });

/* ── Modals ──────────────────────────────────────────────── */
function openAddOfficeModal() {
  const m = document.getElementById('add-office-modal'); if (m) { m.classList.add('is-open'); toggleAssigneeField('add'); document.getElementById('add_name')?.focus(); }
}
function closeAddOfficeModal() { document.getElementById('add-office-modal')?.classList.remove('is-open'); }
function openEditOfficeModal(o) {
  const m = document.getElementById('edit-office-modal'); if (!m) return;
  document.getElementById('edit-office-form').action = '/admin/settings/offices/' + o.id;
  document.getElementById('edit_name').value = o.name || '';
  document.getElementById('edit_type').value = (o.is_external || o.type === 'external') ? 'external' : 'internal';
  document.getElementById('edit_description').value = o.description || '';
  document.getElementById('edit_contact_person').value = o.contact_person || '';
  document.getElementById('edit_contact_phone').value = o.contact_phone || '';
  document.getElementById('edit_contact_email').value = o.contact_email || '';
  document.getElementById('edit_address').value = o.address || '';
  document.getElementById('edit_default_assignee_id').value = o.default_assignee_id || '';
  document.getElementById('edit_is_active').checked = Boolean(o.is_active);
  toggleAssigneeField('edit'); m.classList.add('is-open'); document.getElementById('edit_name')?.focus();
}
function closeEditOfficeModal() { document.getElementById('edit-office-modal')?.classList.remove('is-open'); }
function handleModalBackdropClick(e, id) { if (e.target.id === id) { if (id === 'add-office-modal') closeAddOfficeModal(); else closeEditOfficeModal(); } }
function toggleAssigneeField(pfx) {
  const sel = document.getElementById(pfx + '_type'), box = document.getElementById(pfx + '_assignee_container');
  if (sel && box) box.style.display = sel.value === 'internal' ? '' : 'none';
}
</script>
@endsection
