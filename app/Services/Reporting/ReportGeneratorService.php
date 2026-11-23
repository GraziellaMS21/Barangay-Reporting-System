<?php

namespace App\Services\Reporting;

use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\Category;
use App\Models\FiscalYear;
use App\Models\GeneratedReport;
use App\Models\Office;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Purok;
use App\Models\Referral;
use App\Models\Report;
use App\Models\ReportTemplate;
use App\Models\Summons;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FiscalYearContext;
use App\Services\SignatoryResolver;
use App\Support\CaseStatus;
use Illuminate\Support\Collection;

class ReportGeneratorService
{
    public function __construct(
        protected FiscalYearContext $fiscalYearContext,
        protected SignatoryResolver $signatoryResolver,
        protected AuditLogger $audit,
    ) {}

    /**
     * Canonical report definitions from PLACEHOLDERS.md B6 & SPEC_ADDENDUM.md 4.2.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function canonicalTemplates(): array
    {
        return [
            'kp_accomplishment' => [
                'key' => 'kp_accomplishment',
                'title' => 'KP Accomplishment Report (DILG)',
                'description' => 'Yearly and monthly Katarungang Pambarangay accomplishment summary by dispute type and status submitted to DILG.',
                'source' => 'cases',
                'orientation' => 'landscape',
                'signatory_document_type' => 'monthly_report',
                'columns' => [
                    ['key' => 'case_type', 'label' => 'Case Type', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'filed', 'label' => 'Cases Filed', 'type' => 'int', 'align' => 'right'],
                    ['key' => 'settled', 'label' => 'Settled', 'type' => 'int', 'align' => 'right'],
                    ['key' => 'unsettled', 'label' => 'Unsettled', 'type' => 'int', 'align' => 'right'],
                    ['key' => 'escalated', 'label' => 'Escalated (CFA)', 'type' => 'int', 'align' => 'right'],
                    ['key' => 'dismissed', 'label' => 'Dismissed', 'type' => 'int', 'align' => 'right'],
                    ['key' => 'withdrawn', 'label' => 'Withdrawn', 'type' => 'int', 'align' => 'right'],
                    ['key' => 'pending', 'label' => 'Pending / Ongoing', 'type' => 'int', 'align' => 'right'],
                ],
                'filters' => [
                    ['key' => 'fiscal_year_id', 'type' => 'fiscal_year', 'default' => 'viewing'],
                    ['key' => 'month', 'type' => 'select', 'source' => 'months'],
                ],
                'is_active' => true,
                'is_confirmed' => false,
            ],
            'case_summary' => [
                'key' => 'case_summary',
                'title' => 'Monthly Case Summary by Type (DILG)',
                'description' => 'Monthly register of filed Katarungang Pambarangay cases with party residency, summons count, and status breakdown.',
                'source' => 'cases',
                'orientation' => 'landscape',
                'signatory_document_type' => 'monthly_report',
                'columns' => [
                    ['key' => 'case_number', 'label' => 'Case No.', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'filed_on', 'label' => 'Filed On', 'type' => 'date', 'align' => 'left'],
                    ['key' => 'case_type', 'label' => 'Case Type', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'complainant_residency', 'label' => 'Complainant Residency', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'respondent_residency', 'label' => 'Respondent Residency', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'align' => 'center'],
                    ['key' => 'summons_count', 'label' => 'Summons Count', 'type' => 'int', 'align' => 'right'],
                    ['key' => 'outcome', 'label' => 'Outcome / Notes', 'type' => 'string', 'align' => 'left'],
                ],
                'filters' => [
                    ['key' => 'fiscal_year_id', 'type' => 'fiscal_year', 'default' => 'viewing'],
                    ['key' => 'date_from', 'type' => 'date'],
                    ['key' => 'date_to', 'type' => 'date'],
                    ['key' => 'case_type_id', 'type' => 'select', 'source' => 'case_types'],
                    ['key' => 'status', 'type' => 'select', 'source' => 'case_statuses'],
                    ['key' => 'purok_id', 'type' => 'select', 'source' => 'puroks'],
                ],
                'is_active' => true,
                'is_confirmed' => false,
            ],
            'purok_category_matrix' => [
                'key' => 'purok_category_matrix',
                'title' => 'Problems per Purok & Category Matrix',
                'description' => 'Cross-tabulated matrix showing which community problems and dispute cases happen most in each purok.',
                'source' => 'mixed',
                'orientation' => 'landscape',
                'signatory_document_type' => 'monthly_report',
                'columns' => [
                    ['key' => 'purok', 'label' => 'Purok', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'problem_reports', 'label' => 'Problem Reports', 'type' => 'int', 'align' => 'right'],
                    ['key' => 'kp_cases', 'label' => 'KP Cases', 'type' => 'int', 'align' => 'right'],
                    ['key' => 'top_category', 'label' => 'Top Category / Issue', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'total', 'label' => 'Total Concerns', 'type' => 'int', 'align' => 'right'],
                ],
                'filters' => [
                    ['key' => 'fiscal_year_id', 'type' => 'fiscal_year', 'default' => 'viewing'],
                    ['key' => 'date_from', 'type' => 'date'],
                    ['key' => 'date_to', 'type' => 'date'],
                ],
                'is_active' => true,
                'is_confirmed' => false,
            ],
            'problem_reports_summary' => [
                'key' => 'problem_reports_summary',
                'title' => 'Problem Reports Summary',
                'description' => 'Community problem reports grouped by category, purok, status, and average days to resolve.',
                'source' => 'problem_reports',
                'orientation' => 'portrait',
                'signatory_document_type' => 'monthly_report',
                'columns' => [
                    ['key' => 'category', 'label' => 'Category', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'purok', 'label' => 'Purok', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'align' => 'center'],
                    ['key' => 'count', 'label' => 'Count', 'type' => 'int', 'align' => 'right'],
                    ['key' => 'avg_days_to_resolve', 'label' => 'Avg Days to Resolve', 'type' => 'string', 'align' => 'right'],
                ],
                'filters' => [
                    ['key' => 'fiscal_year_id', 'type' => 'fiscal_year', 'default' => 'viewing'],
                    ['key' => 'date_from', 'type' => 'date'],
                    ['key' => 'date_to', 'type' => 'date'],
                    ['key' => 'purok_id', 'type' => 'select', 'source' => 'puroks'],
                    ['key' => 'category_id', 'type' => 'select', 'source' => 'categories'],
                    ['key' => 'status', 'type' => 'select', 'source' => 'report_statuses'],
                ],
                'is_active' => true,
                'is_confirmed' => false,
            ],
            'referral_register' => [
                'key' => 'referral_register',
                'title' => 'External Referrals Register',
                'description' => 'Register of reports referred beyond barangay authority with agency status, outcome, and follow-up dates.',
                'source' => 'referrals',
                'orientation' => 'landscape',
                'signatory_document_type' => 'monthly_report',
                'columns' => [
                    ['key' => 'reference_no', 'label' => 'Referral No.', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'date_prepared', 'label' => 'Date Prepared', 'type' => 'date', 'align' => 'left'],
                    ['key' => 'date_sent', 'label' => 'Date Sent', 'type' => 'date', 'align' => 'left'],
                    ['key' => 'office', 'label' => 'Office / Agency', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'category', 'label' => 'Category', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'align' => 'center'],
                    ['key' => 'outcome', 'label' => 'Outcome', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'follow_up_date', 'label' => 'Follow-up Date', 'type' => 'date', 'align' => 'left'],
                ],
                'filters' => [
                    ['key' => 'fiscal_year_id', 'type' => 'fiscal_year', 'default' => 'viewing'],
                    ['key' => 'date_from', 'type' => 'date'],
                    ['key' => 'date_to', 'type' => 'date'],
                    ['key' => 'office_id', 'type' => 'select', 'source' => 'offices'],
                    ['key' => 'status', 'type' => 'select', 'source' => 'referral_statuses'],
                ],
                'is_active' => true,
                'is_confirmed' => false,
            ],
            'summons_log' => [
                'key' => 'summons_log',
                'title' => 'Summons & Service Log',
                'description' => 'Log of all issued summons notices, delivery service results, and hearing attendance.',
                'source' => 'summons',
                'orientation' => 'landscape',
                'signatory_document_type' => 'summons',
                'columns' => [
                    ['key' => 'summons_number', 'label' => 'Summons No.', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'case_number', 'label' => 'Case No.', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'respondent', 'label' => 'Respondent', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'issued_on', 'label' => 'Issued On', 'type' => 'date', 'align' => 'left'],
                    ['key' => 'hearing_date', 'label' => 'Hearing Date', 'type' => 'datetime', 'align' => 'left'],
                    ['key' => 'service_result', 'label' => 'Service Result', 'type' => 'badge', 'align' => 'center'],
                    ['key' => 'attendance', 'label' => 'Attendance', 'type' => 'badge', 'align' => 'center'],
                    ['key' => 'voided', 'label' => 'Voided', 'type' => 'string', 'align' => 'center'],
                ],
                'filters' => [
                    ['key' => 'fiscal_year_id', 'type' => 'fiscal_year', 'default' => 'viewing'],
                    ['key' => 'date_from', 'type' => 'date'],
                    ['key' => 'date_to', 'type' => 'date'],
                    ['key' => 'case_type_id', 'type' => 'select', 'source' => 'case_types'],
                    ['key' => 'service_result', 'type' => 'select', 'source' => 'service_results'],
                ],
                'is_active' => true,
                'is_confirmed' => false,
            ],
            'personnel_list' => [
                'key' => 'personnel_list',
                'title' => 'Barangay Officials & Personnel Roster',
                'description' => 'Directory of active and past barangay officials, Lupon members, terms of office, and signatory designations.',
                'source' => 'personnel',
                'orientation' => 'portrait',
                'signatory_document_type' => 'annual_report',
                'columns' => [
                    ['key' => 'full_name', 'label' => 'Name', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'position', 'label' => 'Position', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'term_start', 'label' => 'Term Start', 'type' => 'date', 'align' => 'left'],
                    ['key' => 'term_end', 'label' => 'Term End', 'type' => 'date', 'align' => 'left'],
                    ['key' => 'signatory', 'label' => 'Signatory', 'type' => 'string', 'align' => 'center'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'align' => 'center'],
                ],
                'filters' => [
                    ['key' => 'position_id', 'type' => 'select', 'source' => 'positions'],
                    ['key' => 'status', 'type' => 'select', 'source' => 'personnel_statuses'],
                ],
                'is_active' => true,
                'is_confirmed' => false,
            ],
            'year_over_year' => [
                'key' => 'year_over_year',
                'title' => 'Year-over-Year Operational Comparison',
                'description' => 'Comparative summary across fiscal years for reports filed, reports resolved, referrals sent, cases filed, and cases settled.',
                'source' => 'mixed',
                'orientation' => 'portrait',
                'signatory_document_type' => 'annual_report',
                'columns' => [
                    ['key' => 'metric', 'label' => 'Operational Metric', 'type' => 'string', 'align' => 'left'],
                    ['key' => 'fy_a', 'label' => 'Previous FY', 'type' => 'int', 'align' => 'right'],
                    ['key' => 'fy_b', 'label' => 'Selected FY', 'type' => 'int', 'align' => 'right'],
                    ['key' => 'change', 'label' => 'Change', 'type' => 'string', 'align' => 'right'],
                    ['key' => 'change_percent', 'label' => 'Change %', 'type' => 'percent', 'align' => 'right'],
                ],
                'filters' => [
                    ['key' => 'fiscal_year_id', 'type' => 'fiscal_year', 'default' => 'viewing'],
                    ['key' => 'compare_fiscal_year_id', 'type' => 'fiscal_year'],
                ],
                'is_active' => true,
                'is_confirmed' => false,
            ],
        ];
    }

    /**
     * Ensure canonical templates exist in DB and return active templates.
     *
     * @return Collection<int, ReportTemplate>
     */
    public function catalog(): Collection
    {
        foreach (self::canonicalTemplates() as $key => $def) {
            ReportTemplate::firstOrCreate(['key' => $key], $def);
        }

        return ReportTemplate::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    public function findTemplate(string $key): ReportTemplate
    {
        $canonical = self::canonicalTemplates();
        if (isset($canonical[$key])) {
            return ReportTemplate::firstOrCreate(['key' => $key], $canonical[$key]);
        }

        return ReportTemplate::query()->where('key', $key)->firstOrFail();
    }

    /**
     * Build report dataset, columns, totals, filters metadata, and signatories.
     *
     * @param  array<string, mixed>  $filters
     * @return array{
     *   template: ReportTemplate,
     *   columns: array<int, array{key: string, label: string, type: string, align: string}>,
     *   rows: array<int, array<string, mixed>>,
     *   totals: array<string, mixed>,
     *   summary: array<string, mixed>,
     *   applied_filters: array<string, string>,
     *   signatories: array<int, array<string, mixed>>,
     *   fiscal_year: ?FiscalYear
     * }
     */
    public function build(string $key, array $filters, User $user): array
    {
        $template = $this->findTemplate($key);
        $canonical = self::canonicalTemplates()[$key] ?? null;
        $columns = $this->normalizeColumns($template->columns ?: ($canonical['columns'] ?? []));

        $viewingFy = $this->fiscalYearContext->viewing();
        $fiscalYear = null;
        if (array_key_exists('fiscal_year_id', $filters)) {
            $fyInput = $filters['fiscal_year_id'];
            $fiscalYear = ($fyInput === '' || $fyInput === 'all' || $fyInput === null)
                ? null
                : FiscalYear::find((int) $fyInput);
        } else {
            $fiscalYear = $viewingFy;
        }

        $result = match ($key) {
            'kp_accomplishment' => $this->buildKpAccomplishment($fiscalYear, $filters),
            'case_summary', 'cases_summary', 'kp_monthly' => $this->buildCaseSummary($fiscalYear, $filters, $user, $columns),
            'purok_category_matrix' => $this->buildPurokCategoryMatrix($fiscalYear, $filters, $columns),
            'problem_reports_summary' => $this->buildProblemReportsSummary($fiscalYear, $filters),
            'referral_register', 'referrals_status' => $this->buildReferralRegister($fiscalYear, $filters),
            'summons_log' => $this->buildSummonsLog($fiscalYear, $filters, $user),
            'personnel_list' => $this->buildPersonnelList($filters),
            'year_over_year' => $this->buildYearOverYear($fiscalYear, $filters, $columns),
            default => ['rows' => [], 'totals' => [], 'summary' => [], 'columns' => $columns],
        };

        $finalColumns = $result['columns'] ?? $columns;
        $docType = $template->signatory_document_type ?: ($canonical['signatory_document_type'] ?? 'monthly_report');
        $signatories = $this->signatoryResolver->forDocument($docType);

        return [
            'template' => $template,
            'columns' => $finalColumns,
            'rows' => $result['rows'],
            'totals' => $result['totals'] ?? [],
            'summary' => $result['summary'] ?? [],
            'applied_filters' => $this->describeFilters($fiscalYear, $filters),
            'signatories' => $signatories,
            'fiscal_year' => $fiscalYear,
        ];
    }

    public function recordGeneration(
        ReportTemplate $template,
        array $filters,
        string $format,
        ?FiscalYear $fiscalYear,
        User $user
    ): GeneratedReport {
        $record = GeneratedReport::create([
            'report_template_id' => $template->id,
            'report_key' => $template->key,
            'params' => $filters,
            'format' => $format,
            'fiscal_year_id' => $fiscalYear?->id,
            'generated_by' => $user->id,
        ]);

        $this->audit->log($user, 'report.generated', $record, [], [
            'report_key' => $template->key,
            'format' => $format,
            'fiscal_year_id' => $fiscalYear?->id,
        ]);

        return $record;
    }

    /**
     * @param  array<int, mixed>  $rawColumns
     * @return array<int, array{key: string, label: string, type: string, align: string}>
     */
    protected function normalizeColumns(array $rawColumns): array
    {
        $normalized = [];
        foreach ($rawColumns as $col) {
            if (is_string($col)) {
                $normalized[] = [
                    'key' => $col,
                    'label' => ucwords(str_replace('_', ' ', $col)),
                    'type' => 'string',
                    'align' => 'left',
                ];
            } elseif (is_array($col) && isset($col['key'])) {
                $normalized[] = [
                    'key' => (string) $col['key'],
                    'label' => (string) ($col['label'] ?? ucwords(str_replace('_', ' ', $col['key']))),
                    'type' => (string) ($col['type'] ?? 'string'),
                    'align' => (string) ($col['align'] ?? 'left'),
                ];
            }
        }

        return $normalized;
    }

    protected function buildKpAccomplishment(?FiscalYear $fiscalYear, array $filters): array
    {
        $caseTypes = CaseType::query()->orderBy('sort_order')->orderBy('name')->get();
        $query = BarangayCase::query()->forFiscalYear($fiscalYear);

        if (! empty($filters['month'])) {
            $month = str_pad((string) (int) $filters['month'], 2, '0', STR_PAD_LEFT);
            $query->whereRaw(" strftime('%m', filed_on) = ? OR MONTH(filed_on) = ? ", [$month, (int) $filters['month']]);
        }

        $cases = $query->get(['id', 'case_type_id', 'status', 'filed_on']);
        if (! empty($filters['month'])) {
            $targetMonth = (int) $filters['month'];
            $cases = $cases->filter(fn (BarangayCase $c) => (int) $c->filed_on?->format('n') === $targetMonth);
        }

        $rows = [];
        $totals = [
            'case_type' => 'TOTAL',
            'filed' => 0,
            'settled' => 0,
            'unsettled' => 0,
            'escalated' => 0,
            'dismissed' => 0,
            'withdrawn' => 0,
            'pending' => 0,
        ];

        foreach ($caseTypes as $type) {
            $group = $cases->where('case_type_id', $type->id);
            $filed = $group->count();
            $settled = $group->where('status', CaseStatus::SETTLED)->count();
            $unsettled = $group->where('status', CaseStatus::UNSETTLED)->count();
            $escalated = $group->where('status', CaseStatus::ESCALATED)->count();
            $dismissed = $group->where('status', CaseStatus::DISMISSED)->count();
            $withdrawn = $group->where('status', CaseStatus::WITHDRAWN)->count();
            $pending = $group->whereIn('status', [
                CaseStatus::FILED,
                CaseStatus::IN_MEDIATION,
                CaseStatus::IN_CONCILIATION,
            ])->count();

            $rows[] = [
                'case_type' => $type->name,
                'filed' => $filed,
                'settled' => $settled,
                'unsettled' => $unsettled,
                'escalated' => $escalated,
                'dismissed' => $dismissed,
                'withdrawn' => $withdrawn,
                'pending' => $pending,
            ];

            $totals['filed'] += $filed;
            $totals['settled'] += $settled;
            $totals['unsettled'] += $unsettled;
            $totals['escalated'] += $escalated;
            $totals['dismissed'] += $dismissed;
            $totals['withdrawn'] += $withdrawn;
            $totals['pending'] += $pending;
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
            'summary' => [
                'Total Cases Filed' => $totals['filed'],
                'Total Settled' => $totals['settled'],
                'Total Escalated (CFA)' => $totals['escalated'],
                'Total Pending' => $totals['pending'],
            ],
        ];
    }

    protected function buildCaseSummary(?FiscalYear $fiscalYear, array $filters, User $user, array $columns): array
    {
        // Aggregate counts include all cases in scope; row-level data respects visibleTo($user) (SPEC_ADDENDUM 4.2 & 7)
        $baseQuery = BarangayCase::query()->forFiscalYear($fiscalYear);

        if (! empty($filters['date_from'])) {
            $baseQuery->whereDate('filed_on', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $baseQuery->whereDate('filed_on', '<=', $filters['date_to']);
        }
        if (! empty($filters['case_type_id'])) {
            $baseQuery->where('case_type_id', (int) $filters['case_type_id']);
        }
        if (! empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
            $baseQuery->whereIn('status', array_filter($statuses));
        }
        if (! empty($filters['purok_id'])) {
            $baseQuery->where('purok_id', (int) $filters['purok_id']);
        }

        $totalInScope = (clone $baseQuery)->count();

        $cases = (clone $baseQuery)
            ->visibleTo($user)
            ->with(['caseType', 'complainants', 'respondents'])
            ->withCount('activeSummons')
            ->orderByDesc('filed_on')
            ->orderByDesc('id')
            ->get();

        $rows = [];
        foreach ($cases as $case) {
            $compResidency = $case->complainants->pluck('pivot.residency_at_filing')->filter()->unique()->implode(', ')
                ?: ($case->complainants->pluck('residency_status')->filter()->unique()->implode(', ') ?: 'N/A');
            $respResidency = $case->respondents->pluck('pivot.residency_at_filing')->filter()->unique()->implode(', ')
                ?: ($case->respondents->pluck('residency_status')->filter()->unique()->implode(', ') ?: 'N/A');

            $rows[] = [
                'case_number' => $case->case_number,
                'filed_on' => $case->filed_on?->format('Y-m-d') ?? '',
                'title' => $case->title,
                'case_type' => $case->caseType?->name ?? 'Unassigned',
                'complainant_residency' => ucfirst(str_replace('_', ' ', $compResidency)),
                'respondent_residency' => ucfirst(str_replace('_', ' ', $respResidency)),
                'status' => CaseStatus::labels()[$case->status] ?? ucfirst(str_replace('_', ' ', $case->status)),
                'summons_count' => (int) $case->active_summons_count,
                'settled_on' => $case->settled_on?->format('Y-m-d') ?? '—',
                'hearings_count' => $case->hearings()->count(),
                'outcome' => $case->settlement_terms ?: ($case->outcome_note ?: '—'),
            ];
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'totals' => [],
            'summary' => [
                'Total Cases (Aggregate)' => $totalInScope,
                'Visible Rows' => count($rows),
            ],
        ];
    }

    protected function buildPurokCategoryMatrix(?FiscalYear $fiscalYear, array $filters, array $defaultColumns): array
    {
        $puroks = Purok::query()->orderBy('name')->get();
        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();

        $reportQuery = Report::query()->forFiscalYear($fiscalYear);
        $caseQuery = BarangayCase::query()->forFiscalYear($fiscalYear);

        if (! empty($filters['date_from'])) {
            $reportQuery->whereDate('created_at', '>=', $filters['date_from']);
            $caseQuery->whereDate('filed_on', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $reportQuery->whereDate('created_at', '<=', $filters['date_to']);
            $caseQuery->whereDate('filed_on', '<=', $filters['date_to']);
        }

        $reports = $reportQuery->get(['id', 'purok_id', 'category_id']);
        $cases = $caseQuery->get(['id', 'purok_id', 'case_type_id']);

        $columns = [
            ['key' => 'purok', 'label' => 'Purok', 'type' => 'string', 'align' => 'left'],
        ];
        foreach ($categories as $cat) {
            $columns[] = [
                'key' => 'cat_'.$cat->id,
                'label' => $cat->name,
                'type' => 'int',
                'align' => 'right',
            ];
        }
        $columns[] = ['key' => 'kp_cases', 'label' => 'KP Cases', 'type' => 'int', 'align' => 'right'];
        $columns[] = ['key' => 'total', 'label' => 'Total', 'type' => 'int', 'align' => 'right'];

        $rows = [];
        $totals = ['purok' => 'TOTAL', 'kp_cases' => 0, 'total' => 0];
        foreach ($categories as $cat) {
            $totals['cat_'.$cat->id] = 0;
        }

        foreach ($puroks as $purok) {
            $purokReports = $reports->where('purok_id', $purok->id);
            $purokCases = $cases->where('purok_id', $purok->id)->count();
            $row = ['purok' => $purok->name];
            $rowTotal = $purokCases;

            foreach ($categories as $cat) {
                $catCount = $purokReports->where('category_id', $cat->id)->count();
                $row['cat_'.$cat->id] = $catCount;
                $totals['cat_'.$cat->id] += $catCount;
                $rowTotal += $catCount;
            }

            $row['kp_cases'] = $purokCases;
            $row['total'] = $rowTotal;
            $totals['kp_cases'] += $purokCases;
            $totals['total'] += $rowTotal;
            $rows[] = $row;
        }

        return [
            'columns' => count($categories) > 0 ? $columns : $defaultColumns,
            'rows' => $rows,
            'totals' => $totals,
            'summary' => [
                'Total Community Reports' => $reports->count(),
                'Total KP Cases' => $cases->count(),
                'Combined Concerns' => $totals['total'],
            ],
        ];
    }

    protected function buildProblemReportsSummary(?FiscalYear $fiscalYear, array $filters): array
    {
        $query = Report::query()
            ->forFiscalYear($fiscalYear)
            ->with(['category:id,name', 'purok:id,name']);

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['purok_id'])) {
            $query->where('purok_id', (int) $filters['purok_id']);
        }
        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $reports = $query->get();

        $grouped = $reports->groupBy(fn (Report $r) => ($r->category?->name ?? 'Unassigned').'|'.($r->purok?->name ?? 'Unspecified').'|'.$r->status);

        $rows = [];
        $totalCount = 0;
        foreach ($grouped as $key => $items) {
            [$catName, $purokName, $status] = explode('|', $key);
            $count = $items->count();
            $totalCount += $count;

            $resolvedItems = $items->filter(fn (Report $r) => $r->resolved_at !== null && $r->created_at !== null);
            $avgDays = $resolvedItems->isNotEmpty()
                ? number_format($resolvedItems->avg(fn (Report $r) => max(0, $r->created_at->diffInHours($r->resolved_at) / 24)), 1).' d'
                : '—';

            $rows[] = [
                'category' => $catName,
                'purok' => $purokName,
                'status' => Report::STATUSES[$status] ?? ucfirst(str_replace('_', ' ', $status)),
                'count' => $count,
                'avg_days_to_resolve' => $avgDays,
            ];
        }

        return [
            'rows' => $rows,
            'totals' => [
                'category' => 'GRAND TOTAL',
                'purok' => '',
                'status' => '',
                'count' => $totalCount,
                'avg_days_to_resolve' => '',
            ],
            'summary' => [
                'Total Reports' => $totalCount,
                'Resolved' => $reports->where('status', 'resolved')->count(),
            ],
        ];
    }

    protected function buildReferralRegister(?FiscalYear $fiscalYear, array $filters): array
    {
        $query = Referral::query()
            ->forFiscalYear($fiscalYear)
            ->with(['office:id,name', 'report.category:id,name'])
            ->orderByDesc('id');

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['office_id'])) {
            $query->where('office_id', (int) $filters['office_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $referrals = $query->get();
        $rows = [];
        foreach ($referrals as $referral) {
            $rows[] = [
                'reference_no' => $referral->reference_no ?? 'Not assigned',
                'date_prepared' => $referral->created_at?->format('Y-m-d') ?? '',
                'date_sent' => $referral->sent_at?->format('Y-m-d') ?? 'Not sent',
                'office' => $referral->office?->name ?? 'Unassigned',
                'category' => $referral->report?->category?->name ?? 'Unassigned',
                'status' => Referral::STATUSES[$referral->status] ?? ucfirst($referral->status),
                'outcome' => $referral->outcome ? (Referral::OUTCOMES[$referral->outcome] ?? ucfirst($referral->outcome)) : 'Pending',
                'follow_up_date' => $referral->follow_up_at?->format('Y-m-d') ?? '—',
                'sent_at' => $referral->sent_at?->format('Y-m-d') ?? 'Not sent',
                'follow_up_at' => $referral->follow_up_at?->format('Y-m-d') ?? '—',
            ];
        }

        return [
            'rows' => $rows,
            'totals' => [],
            'summary' => [
                'Total Referrals' => $referrals->count(),
                'Closed Referrals' => $referrals->where('status', 'closed')->count(),
            ],
        ];
    }

    protected function buildSummonsLog(?FiscalYear $fiscalYear, array $filters, User $user): array
    {
        $visibleCaseIds = BarangayCase::query()
            ->forFiscalYear($fiscalYear)
            ->visibleTo($user)
            ->when(! empty($filters['case_type_id']), fn ($q) => $q->where('case_type_id', (int) $filters['case_type_id']))
            ->pluck('id');

        $query = Summons::query()
            ->with(['barangayCase:id,case_number', 'respondentCaseParty.party:id,full_name'])
            ->whereIn('case_id', $visibleCaseIds)
            ->orderByDesc('issued_on')
            ->orderByDesc('id');

        if (! empty($filters['date_from'])) {
            $query->whereDate('issued_on', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('issued_on', '<=', $filters['date_to']);
        }
        if (! empty($filters['service_result'])) {
            $query->where('service_result', $filters['service_result']);
        }

        $summonsList = $query->get();
        $rows = [];
        foreach ($summonsList as $summons) {
            $rows[] = [
                'summons_number' => $summons->summons_number ?? ('#'.$summons->sequence_no),
                'case_number' => $summons->barangayCase?->case_number ?? '—',
                'respondent' => $summons->respondentCaseParty?->party?->full_name ?? '—',
                'issued_on' => $summons->issued_on?->format('Y-m-d') ?? '',
                'hearing_date' => $summons->hearing_at?->format('Y-m-d H:i') ?? '—',
                'service_result' => ucfirst(str_replace('_', ' ', $summons->service_result)),
                'attendance' => ucfirst(str_replace('_', ' ', $summons->attendance)),
                'voided' => $summons->voided_at ? 'Yes' : 'No',
            ];
        }

        return [
            'rows' => $rows,
            'totals' => [],
            'summary' => [
                'Total Summons' => $summonsList->count(),
                'Received / Served' => $summonsList->whereIn('service_result', ['received', 'served_personal', 'served_substituted'])->count(),
                'Voided' => $summonsList->whereNotNull('voided_at')->count(),
            ],
        ];
    }

    protected function buildPersonnelList(array $filters): array
    {
        $query = Personnel::query()
            ->with('position')
            ->orderByDesc('is_active')
            ->orderBy('full_name');

        if (! empty($filters['position_id'])) {
            $query->where('position_id', (int) $filters['position_id']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        $personnel = $query->get();
        $rows = [];
        foreach ($personnel as $person) {
            $rows[] = [
                'full_name' => $person->full_name,
                'position' => $person->position?->name ?? 'Unassigned',
                'term_start' => $person->term_start?->format('Y-m-d') ?? '—',
                'term_end' => $person->term_end?->format('Y-m-d') ?? 'Present',
                'signatory' => $person->is_signatory ? 'Yes' : 'No',
                'status' => $person->is_active ? 'Active' : 'Inactive',
            ];
        }

        return [
            'rows' => $rows,
            'totals' => [],
            'summary' => [
                'Total Personnel' => $personnel->count(),
                'Active' => $personnel->where('is_active', true)->count(),
                'Authorized Signatories' => $personnel->where('is_signatory', true)->where('is_active', true)->count(),
            ],
        ];
    }

    protected function buildYearOverYear(?FiscalYear $fiscalYear, array $filters, array $defaultColumns): array
    {
        $fyB = $fiscalYear ?? FiscalYear::query()->orderByDesc('year')->first();
        $fyA = ! empty($filters['compare_fiscal_year_id'])
            ? FiscalYear::find((int) $filters['compare_fiscal_year_id'])
            : ($fyB ? FiscalYear::query()->where('year', '<', $fyB->year)->orderByDesc('year')->first() : null);

        $labelA = $fyA ? $fyA->label : 'Previous FY';
        $labelB = $fyB ? $fyB->label : 'Current FY';

        $columns = [
            ['key' => 'metric', 'label' => 'Operational Metric', 'type' => 'string', 'align' => 'left'],
            ['key' => 'fy_a', 'label' => $labelA, 'type' => 'int', 'align' => 'right'],
            ['key' => 'fy_b', 'label' => $labelB, 'type' => 'int', 'align' => 'right'],
            ['key' => 'change', 'label' => 'Change', 'type' => 'string', 'align' => 'right'],
            ['key' => 'change_percent', 'label' => 'Change %', 'type' => 'percent', 'align' => 'right'],
        ];

        $metrics = [
            'Problem Reports Filed' => [
                $fyA ? Report::query()->where('fiscal_year_id', $fyA->id)->count() : 0,
                $fyB ? Report::query()->where('fiscal_year_id', $fyB->id)->count() : 0,
            ],
            'Problem Reports Resolved' => [
                $fyA ? Report::query()->where('fiscal_year_id', $fyA->id)->where('status', 'resolved')->count() : 0,
                $fyB ? Report::query()->where('fiscal_year_id', $fyB->id)->where('status', 'resolved')->count() : 0,
            ],
            'External Referrals Sent' => [
                $fyA ? Referral::query()->where('fiscal_year_id', $fyA->id)->count() : 0,
                $fyB ? Referral::query()->where('fiscal_year_id', $fyB->id)->count() : 0,
            ],
            'KP Cases Filed' => [
                $fyA ? BarangayCase::query()->where('fiscal_year_id', $fyA->id)->count() : 0,
                $fyB ? BarangayCase::query()->where('fiscal_year_id', $fyB->id)->count() : 0,
            ],
            'KP Cases Settled' => [
                $fyA ? BarangayCase::query()->where('fiscal_year_id', $fyA->id)->where('status', CaseStatus::SETTLED)->count() : 0,
                $fyB ? BarangayCase::query()->where('fiscal_year_id', $fyB->id)->where('status', CaseStatus::SETTLED)->count() : 0,
            ],
        ];

        $rows = [];
        foreach ($metrics as $label => [$valA, $valB]) {
            $diff = $valB - $valA;
            $pct = $valA > 0
                ? sprintf('%+.1f%%', ($diff / $valA) * 100)
                : ($valB > 0 ? '+100.0%' : '0.0%');

            $rows[] = [
                'metric' => $label,
                'fy_a' => $valA,
                'fy_b' => $valB,
                'change' => sprintf('%+d', $diff),
                'change_percent' => $pct,
            ];
        }

        return [
            'columns' => $columns ?: $defaultColumns,
            'rows' => $rows,
            'totals' => [],
            'summary' => [
                'Baseline Year' => $labelA,
                'Comparison Year' => $labelB,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    protected function describeFilters(?FiscalYear $fiscalYear, array $filters): array
    {
        $described = [
            'Fiscal Year' => $fiscalYear ? $fiscalYear->label : 'All Fiscal Years',
        ];

        if (! empty($filters['month'])) {
            $monthNum = (int) $filters['month'];
            $described['Month'] = date('F', mktime(0, 0, 0, max(1, min(12, $monthNum)), 1));
        }
        if (! empty($filters['date_from'])) {
            $described['From'] = (string) $filters['date_from'];
        }
        if (! empty($filters['date_to'])) {
            $described['To'] = (string) $filters['date_to'];
        }
        if (! empty($filters['purok_id'])) {
            $described['Purok'] = Purok::find((int) $filters['purok_id'])?->name ?? (string) $filters['purok_id'];
        }
        if (! empty($filters['category_id'])) {
            $described['Category'] = Category::find((int) $filters['category_id'])?->name ?? (string) $filters['category_id'];
        }
        if (! empty($filters['case_type_id'])) {
            $described['Case Type'] = CaseType::find((int) $filters['case_type_id'])?->name ?? (string) $filters['case_type_id'];
        }
        if (! empty($filters['office_id'])) {
            $described['Office'] = Office::find((int) $filters['office_id'])?->name ?? (string) $filters['office_id'];
        }
        if (! empty($filters['position_id'])) {
            $described['Position'] = Position::find((int) $filters['position_id'])?->name ?? (string) $filters['position_id'];
        }
        if (! empty($filters['status'])) {
            $statusVal = is_array($filters['status']) ? implode(', ', $filters['status']) : (string) $filters['status'];
            $described['Status'] = ucfirst(str_replace('_', ' ', $statusVal));
        }

        return $described;
    }
}
