<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\EmergencyContact;
use App\Models\Office;
use App\Models\ReportAiResult;
use App\Models\RoutingRule;
use App\Models\SensitivePlace;
use App\Models\Setting;
use App\Models\StatusLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    private const AUDIT_MODELS = [
        Category::class => 'Category',
        EmergencyContact::class => 'Emergency contact',
        Office::class => 'Office',
        ReportAiResult::class => 'Report AI result',
        RoutingRule::class => 'Routing rule',
        SensitivePlace::class => 'Sensitive place',
        Setting::class => 'Setting',
        User::class => 'User',
    ];

    public function index(Request $request): View
    {
        $validated = $this->validatedFilters($request);

        $auditLogs = AuditLog::query()
            ->with('user:id,name')
            ->when($validated['action'] ?? null, fn ($query, string $action) => $query->where('action', $action))
            ->when($validated['model'] ?? null, fn ($query, string $model) => $query->where('model', $model))
            ->when($validated['user_id'] ?? null, fn ($query, int $userId) => $query->where('user_id', $userId))
            ->when($validated['from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'audit_page')
            ->withQueryString();

        $statusLogs = StatusLog::query()
            ->with(['user:id,name', 'report:id,tracking_code'])
            ->when($validated['user_id'] ?? null, fn ($query, int $userId) => $query->where('user_id', $userId))
            ->when($validated['from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'status_page')
            ->withQueryString();

        return view('admin.audit-log.index', [
            'auditLogs' => $auditLogs,
            'filters' => $validated,
            'modelLabels' => self::AUDIT_MODELS,
            'statusLogs' => $statusLogs,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function filter(Request $request): RedirectResponse
    {
        $filters = array_filter(
            $this->validatedFilters($request),
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );

        return redirect()->route('admin.audit-log.index', $filters);
    }

    /**
     * @return array{action?: string|null, model?: string|null, user_id?: int|null, from?: string|null, to?: string|null}
     */
    private function validatedFilters(Request $request): array
    {
        $filters = $request->validate([
            'action' => ['nullable', Rule::in(['created', 'updated', 'deleted', 'ai_override'])],
            'model' => ['nullable', Rule::in(array_keys(self::AUDIT_MODELS))],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ], [
            'to.after_or_equal' => 'The end date must be on or after the start date.',
        ]);

        if (isset($filters['user_id'])) {
            $filters['user_id'] = (int) $filters['user_id'];
        }

        return $filters;
    }
}
