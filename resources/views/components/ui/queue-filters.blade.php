@props([
    'currentStage' => 'all',
    'stageCounts' => collect(),
    'statusFilter' => null,
    'statusLabels' => [],
    'statusCounts' => collect(),
    'statusGroups' => [],
    'emergencyOnly' => false,
    'emergencyCount' => 0,
    'isMyCases' => false,
    'myCasesCount' => 0,
    'overdueOnly' => false,
    'overdueCount' => 0,
    'puroks' => collect(),
    'categories' => collect(),
    'staffUsers' => collect(),
    'fiscalYears' => collect(),
    'aiStatusFilter' => null,
    'queueRoute' => 'admin.reports.index',
])

@php
    $isMyCases = (bool) $isMyCases || (string) request('my_cases') === '1';
    $overdueOnly = (bool) $overdueOnly || (string) request('overdue') === '1';
    $emergencyOnly = (bool) $emergencyOnly || (string) request('emergency') === '1';

    $preservedQuery = request()->except(['page']);

    $tabs = [
        'all' => 'All',
        'needs_review' => 'Needs review',
        'in_progress' => 'In progress',
        'waiting' => 'Waiting on others',
        'done' => 'Done',
    ];

    // Helper to build URL with modified query params
    $buildFilterUrl = function (array $overrides, array $removals = []) use ($queueRoute, $preservedQuery) {
        $params = $preservedQuery;
        foreach ($removals as $key) {
            unset($params[$key]);
        }
        foreach ($overrides as $key => $val) {
            if ($val === null || $val === '') {
                unset($params[$key]);
            } else {
                $params[$key] = $val;
            }
        }
        return route($queueRoute, $params);
    };

    // Chip toggle URLs
    $myCasesToggleUrl = $isMyCases
        ? $buildFilterUrl([], ['my_cases', 'assigned_to'])
        : $buildFilterUrl(['my_cases' => 1]);

    $overdueToggleUrl = $overdueOnly
        ? $buildFilterUrl([], ['overdue'])
        : $buildFilterUrl(['overdue' => 1]);

    $emergencyToggleUrl = $emergencyOnly
        ? $buildFilterUrl([], ['emergency'])
        : $buildFilterUrl(['emergency' => 1]);

    // Active secondary filters count for badge
    $activeSecondaryKeys = ['purok_id', 'category_id', 'assigned_to', 'status', 'year', 'ai_status'];
    $secondaryFilterCount = 0;
    foreach ($activeSecondaryKeys as $key) {
        if ($key === 'assigned_to' && $isMyCases) {
            continue; // covered by my_cases chip
        }
        if (request()->filled($key) && request($key) !== 'all') {
            $secondaryFilterCount++;
        }
    }

    // Secondary filters clear URL
    $clearSecondaryUrl = $buildFilterUrl([], ['purok_id', 'category_id', 'assigned_to', 'status', 'year', 'ai_status']);

    // Build removable secondary filter chips
    $removableChips = [];
    if (request()->filled('q')) {
        $removableChips[] = [
            'label' => 'Search: "' . str(request('q'))->limit(16) . '"',
            'url' => $buildFilterUrl([], ['q']),
        ];
    }
    if (request()->filled('purok_id') && request('purok_id') !== 'all') {
        $purok = $puroks->firstWhere('id', request('purok_id'));
        $removableChips[] = [
            'label' => 'Purok: ' . ($purok?->name ?? request('purok_id')),
            'url' => $buildFilterUrl([], ['purok_id']),
        ];
    }
    if (request()->filled('category_id') && request('category_id') !== 'all') {
        $cat = $categories->firstWhere('id', request('category_id'));
        $removableChips[] = [
            'label' => 'Category: ' . ($cat?->name ?? request('category_id')),
            'url' => $buildFilterUrl([], ['category_id']),
        ];
    }
    if ($statusFilter !== null && $statusFilter !== 'all') {
        $label = $statusLabels[$statusFilter] ?? $statusFilter;
        $removableChips[] = [
            'label' => 'Status: ' . $label,
            'url' => $buildFilterUrl([], ['status']),
        ];
    }
    if (request()->filled('assigned_to') && request('assigned_to') !== 'all' && !$isMyCases) {
        $assigneeName = request('assigned_to') === 'unassigned'
            ? 'Unassigned'
            : ($staffUsers->firstWhere('id', request('assigned_to'))?->name ?? 'Staff');
        $removableChips[] = [
            'label' => 'Assignee: ' . $assigneeName,
            'url' => $buildFilterUrl([], ['assigned_to']),
        ];
    }
    if (request()->filled('year') && request('year') !== 'all') {
        $removableChips[] = [
            'label' => 'FY ' . request('year'),
            'url' => $buildFilterUrl([], ['year']),
        ];
    }
    if (request()->filled('ai_status') && request('ai_status') !== 'all') {
        $aiLabels = [
            'failed' => 'Failed (Manual screening)',
            'done' => 'Done',
            'pending' => 'Pending',
            'processing' => 'Processing',
        ];
        $aiLabel = $aiLabels[request('ai_status')] ?? ucfirst((string) request('ai_status'));
        $removableChips[] = [
            'label' => 'AI: ' . $aiLabel,
            'url' => $buildFilterUrl([], ['ai_status']),
        ];
    }

    $hasActiveFilters = $isMyCases || $overdueOnly || $emergencyOnly || count($removableChips) > 0;
    $clearAllUrl = route($queueRoute, ['stage' => $currentStage]);
@endphp

<div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5 space-y-4 mb-4">
    <!-- Row 1: Mutually exclusive statuses tab strip (desktop) / Select (mobile) -->
    <div class="w-full">
        <!-- Desktop Tabs -->
        <nav class="hidden lg:flex items-center gap-6 border-b border-slate-200" role="tablist" aria-label="Report lifecycle stage">
            @foreach($tabs as $stageKey => $tabLabel)
                @php
                    $count = $stageCounts->get($stageKey, 0);
                    $isActive = $currentStage === $stageKey;
                    $tabUrl = $buildFilterUrl(['stage' => $stageKey], ['status']);
                @endphp
                <a href="{{ $tabUrl }}"
                   role="tab"
                   aria-selected="{{ $isActive ? 'true' : 'false' }}"
                   aria-current="{{ $isActive ? 'page' : 'false' }}"
                   @class([
                       'pb-3 pt-1 px-1 text-xs sm:text-sm font-semibold inline-flex items-center gap-2 border-b-2 transition-colors whitespace-nowrap',
                       'border-slate-900 text-slate-900 font-bold' => $isActive,
                       'border-transparent text-slate-500 hover:text-slate-800 font-medium' => !$isActive,
                   ])>
                    <span>{{ $tabLabel }}</span>
                    <span @class([
                        'rounded-full px-2 py-0.5 text-xs font-bold transition-colors',
                        'bg-slate-900 text-white' => $isActive,
                        'bg-slate-100 text-slate-600' => !$isActive,
                    ])>{{ $count }}</span>
                </a>
            @endforeach
        </nav>

        <!-- Mobile Select (< 1024px) -->
        <div class="lg:hidden">
            <label for="mobile-stage" class="sr-only">Report lifecycle stage</label>
            <select id="mobile-stage"
                    name="stage"
                    class="w-full text-xs font-semibold rounded-lg border border-slate-300 py-2.5 px-3 bg-white text-slate-800 focus:ring-1 focus:ring-slate-900 focus:border-slate-900 shadow-xs"
                    onchange="if(this.value) window.location.href=this.value;">
                @foreach($tabs as $stageKey => $tabLabel)
                    @php
                        $count = $stageCounts->get($stageKey, 0);
                        $tabUrl = $buildFilterUrl(['stage' => $stageKey], ['status']);
                    @endphp
                    <option value="{{ $tabUrl }}" @selected($currentStage === $stageKey)>
                        Status: {{ $tabLabel }} ({{ $count }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Row 2: Search input + Filters popover + Dark Apply button -->
    <form method="GET" action="{{ route($queueRoute) }}" class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap w-full">
        <input type="hidden" name="stage" value="{{ $currentStage }}">
        @if($isMyCases)
            <input type="hidden" name="my_cases" value="1">
        @endif
        @if($overdueOnly)
            <input type="hidden" name="overdue" value="1">
        @endif
        @if($emergencyOnly)
            <input type="hidden" name="emergency" value="1">
        @endif
        @if(request('sort'))
            <input type="hidden" name="sort" value="{{ request('sort') }}">
        @endif
        @if(request('direction'))
            <input type="hidden" name="direction" value="{{ request('direction') }}">
        @endif
        @if(request('pin'))
            <input type="hidden" name="pin" value="{{ request('pin') }}">
        @endif

        <!-- Search Bar -->
        <div class="relative flex-1 min-w-[200px]">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs" aria-hidden="true"></i>
            </div>
            <input type="search"
                   name="q"
                   id="queue-search"
                   value="{{ request('q') }}"
                   placeholder="Tracking code or description..."
                   class="block w-full pl-8 pr-3 py-2 text-xs border border-slate-300 rounded-lg bg-slate-50/50 focus:bg-white focus:ring-1 focus:ring-slate-900 focus:border-slate-900 text-slate-900 placeholder:text-slate-400 transition-colors"
                   aria-label="Search reports">
        </div>

        <!-- Filters Popover Trigger -->
        <details class="relative queue-filters-drawer">
            <summary class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 cursor-pointer select-none transition-colors shadow-xs">
                <i class="fa-solid fa-sliders text-blue-600" aria-hidden="true"></i>
                <span>Filters</span>
                @if($secondaryFilterCount > 0)
                    <span class="bg-blue-600 text-white rounded-full px-1.5 py-0.2 text-[10px] font-bold">{{ $secondaryFilterCount }}</span>
                @endif
                <i class="fa-solid fa-chevron-down text-[10px] text-slate-400" aria-hidden="true"></i>
            </summary>

            <div class="absolute right-0 sm:right-auto sm:left-0 top-full mt-2 z-40 w-80 p-4 bg-white rounded-xl shadow-xl border border-slate-200 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <strong class="text-xs font-bold text-slate-900">Secondary Filters</strong>
                    @if($secondaryFilterCount > 0)
                        <a href="{{ $clearSecondaryUrl }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold">Reset</a>
                    @endif
                </div>

                <div class="space-y-2.5 text-xs">
                    <div>
                        <label for="queue-filter-purok" class="block font-semibold text-slate-700 mb-1">Purok</label>
                        <select id="queue-filter-purok" name="purok_id" class="w-full rounded border border-slate-300 p-1.5 text-xs bg-white text-slate-800">
                            <option value="">All puroks</option>
                            @foreach($puroks as $purok)
                                <option value="{{ $purok->id }}" @selected((string) request('purok_id') === (string) $purok->id)>
                                    {{ $purok->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="queue-filter-category" class="block font-semibold text-slate-700 mb-1">Category</label>
                        <select id="queue-filter-category" name="category_id" class="w-full rounded border border-slate-300 p-1.5 text-xs bg-white text-slate-800">
                            <option value="">All categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="queue-filter-status" class="block font-semibold text-slate-700 mb-1">Status</label>
                        <select id="queue-filter-status" name="status" class="w-full rounded border border-slate-300 p-1.5 text-xs bg-white text-slate-800">
                            <option value="">All statuses</option>
                            @foreach($statusLabels as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}" @selected($statusFilter === $statusKey)>{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if(!$isMyCases)
                        <div>
                            <label for="queue-filter-assigned" class="block font-semibold text-slate-700 mb-1">Assignee</label>
                            <select id="queue-filter-assigned" name="assigned_to" class="w-full rounded border border-slate-300 p-1.5 text-xs bg-white text-slate-800">
                                <option value="">All assignees</option>
                                <option value="unassigned" @selected(request('assigned_to') === 'unassigned')>Unassigned</option>
                                @foreach($staffUsers as $user)
                                    <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if(!empty($fiscalYears) && $fiscalYears->isNotEmpty())
                        <div>
                            <label for="queue-filter-year" class="block font-semibold text-slate-700 mb-1">Fiscal Year</label>
                            <select id="queue-filter-year" name="year" class="w-full rounded border border-slate-300 p-1.5 text-xs bg-white text-slate-800">
                                <option value="">All years</option>
                                @foreach($fiscalYears as $fy)
                                    <option value="{{ $fy->year }}" @selected((string) request('year') === (string) $fy->year)>
                                        {{ $fy->label ?? 'FY ' . $fy->year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label for="queue-filter-ai-status" class="block font-semibold text-slate-700 mb-1">AI Screening</label>
                        <select id="queue-filter-ai-status" name="ai_status" class="w-full rounded border border-slate-300 p-1.5 text-xs bg-white text-slate-800">
                            <option value="">All AI statuses</option>
                            <option value="failed" @selected(request('ai_status') === 'failed' || $aiStatusFilter === 'failed')>Failed (Manual screening)</option>
                            <option value="done" @selected(request('ai_status') === 'done' || $aiStatusFilter === 'done')>Done</option>
                            <option value="pending" @selected(request('ai_status') === 'pending' || $aiStatusFilter === 'pending')>Pending</option>
                            <option value="processing" @selected(request('ai_status') === 'processing' || $aiStatusFilter === 'processing')>Processing</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                    <a href="{{ $clearSecondaryUrl }}" class="text-xs text-slate-500 hover:text-slate-700 font-medium">Clear</a>
                    <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded font-semibold text-xs transition-colors shadow-xs">
                        Apply
                    </button>
                </div>
            </div>
        </details>

        <!-- Dark Primary Action Button -->
        <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 rounded-lg inline-flex items-center gap-1.5 shadow-sm transition-colors shrink-0">
            <i class="fa-solid fa-filter text-[11px]" aria-hidden="true"></i>
            <span>Apply</span>
        </button>
    </form>

    <!-- Row 3: Quick-filter chips + Active secondary filter chips + Clear all -->
    <div class="flex items-center gap-2 flex-wrap text-xs pt-1">
        <!-- My Cases Chip -->
        <a href="{{ $myCasesToggleUrl }}"
           role="button"
           aria-pressed="{{ $isMyCases ? 'true' : 'false' }}"
           @class([
               'rounded-full px-3 py-1 text-xs inline-flex items-center gap-1.5 transition-colors border select-none',
               'bg-blue-50 text-blue-700 border-blue-300 font-semibold shadow-xs' => $isMyCases,
               'bg-slate-100 text-slate-600 border-transparent hover:border-slate-300 font-medium' => !$isMyCases,
           ])>
            <i class="fa-solid fa-user-check text-[11px]" aria-hidden="true"></i>
            <span>My cases</span>
            <span @class([
                'rounded-full px-1.5 py-0.2 text-[10px] font-bold',
                'bg-blue-600 text-white' => $isMyCases,
                'bg-slate-200 text-slate-700' => !$isMyCases,
            ])>{{ $myCasesCount }}</span>
        </a>

        <!-- Overdue Chip -->
        <a href="{{ $overdueToggleUrl }}"
           role="button"
           aria-pressed="{{ $overdueOnly ? 'true' : 'false' }}"
           @class([
               'rounded-full px-3 py-1 text-xs inline-flex items-center gap-1.5 transition-colors border select-none',
               'bg-amber-50 text-amber-800 border-amber-300 font-semibold shadow-xs' => $overdueOnly,
               'bg-slate-100 text-slate-600 border-transparent hover:border-slate-300 font-medium' => !$overdueOnly,
           ])>
            <i class="fa-solid fa-clock-rotate-left text-[11px]" aria-hidden="true"></i>
            <span>Overdue</span>
            <span @class([
                'rounded-full px-1.5 py-0.2 text-[10px] font-bold',
                'bg-amber-600 text-white' => $overdueOnly,
                'bg-slate-200 text-slate-700' => !$overdueOnly,
            ])>{{ $overdueCount }}</span>
        </a>

        <!-- Emergency Chip (Neutral if 0 and inactive, Red if active or count > 0) -->
        @php
            $emergencyIsRed = $emergencyOnly || $emergencyCount > 0;
        @endphp
        <a href="{{ $emergencyToggleUrl }}"
           role="button"
           aria-pressed="{{ $emergencyOnly ? 'true' : 'false' }}"
           @class([
               'rounded-full px-3 py-1 text-xs inline-flex items-center gap-1.5 transition-colors border select-none',
               'bg-red-50 text-red-700 border-red-300 font-semibold shadow-xs' => $emergencyOnly,
               'bg-red-50/70 text-red-700 border-red-200 hover:border-red-300 font-medium' => !$emergencyOnly && $emergencyCount > 0,
               'bg-slate-100 text-slate-600 border-transparent hover:border-slate-300 font-medium' => !$emergencyOnly && $emergencyCount === 0,
           ])>
            <i class="fa-solid fa-triangle-exclamation text-[11px]" aria-hidden="true"></i>
            <span>Emergency</span>
            <span @class([
                'rounded-full px-1.5 py-0.2 text-[10px] font-bold',
                'bg-red-600 text-white' => $emergencyIsRed,
                'bg-slate-200 text-slate-700' => !$emergencyIsRed,
            ])>{{ $emergencyCount }}</span>
        </a>

        <!-- Removable Secondary Filter Chips -->
        @foreach($removableChips as $chip)
            <a href="{{ $chip['url'] }}"
               class="rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200 px-2.5 py-1 text-xs inline-flex items-center gap-1.5 transition-colors font-medium select-none"
               title="Remove {{ $chip['label'] }}">
                <span>{{ $chip['label'] }}</span>
                <i class="fa-solid fa-xmark text-[10px] text-slate-400 hover:text-slate-800" aria-hidden="true"></i>
            </a>
        @endforeach

        <!-- Clear all link -->
        @if($hasActiveFilters)
            <a href="{{ $clearAllUrl }}"
               class="text-xs text-blue-600 hover:text-blue-800 font-semibold underline ml-auto py-1">
                Clear all
            </a>
        @endif
    </div>
</div>
