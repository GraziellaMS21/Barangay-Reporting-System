<div class="space-y-4">
    <x-admin.case-actions.summons-counter :case="$case" />

    <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-base font-semibold text-slate-900">Summons Management</h3>
            <p class="mt-1 text-sm text-slate-600">Issue summonses and view their informational count.</p>
        </div>

        @if(auth()->user()?->hasPermission('cases.summons'))
            <x-ui.button href="{{ route('admin.cases.summons.create', $case) }}" variant="primary">
                Issue Summons
            </x-ui.button>
        @endif
    </div>

    @include('admin.case-actions.partials.feature-placeholder', [
        'id' => 'summons-follow-up-placeholder',
        'heading' => 'Service, attendance, voiding, and printed summons',
        'message' => 'Recording service or attendance, voiding a summons, and printing an official summons are not available yet.',
        'note' => 'Issuing a summons and viewing the informational count remain available. These follow-up actions do not save or change records.',
    ])
</div>
