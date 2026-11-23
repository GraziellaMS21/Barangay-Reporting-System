<section class="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
    <div>
        <h3 class="text-base font-semibold text-slate-900">Before escalation</h3>
        <p class="mt-1 text-sm text-slate-600">
            Review the case record before preparing a Certificate to File Action.
        </p>
    </div>

    <a
        href="#summons-counter"
        class="inline-flex min-h-11 items-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
    >
        View summons counter
    </a>

    <p class="text-xs leading-5 text-slate-600">
        Summons counts are for record-keeping only and do not determine escalation timing. Escalation still requires a complainant request and an authorized official's decision.
        The system will not contact police automatically.
    </p>

    @include('admin.case-actions.partials.feature-placeholder', [
        'id' => 'escalation-workflow-placeholder',
        'heading' => 'Escalation, CFA, endorsement, and referral handoff',
        'message' => 'The escalation workflow and official document generation are not available yet.',
        'note' => 'No case status will be changed, document generated, or referral sent by this placeholder.',
    ])
</section>
