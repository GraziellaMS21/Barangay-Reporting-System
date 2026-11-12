<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InsightsController extends Controller
{
    /**
     * Display the insights dashboard.
     */
    public function index(): \Illuminate\View\View
    {
        $totalReports      = Report::count();
        $resolvedReports   = Report::where('status', 'resolved')->count();
        $pendingReports    = Report::whereNotIn('status', ['resolved', 'rejected'])->count();
        $emergencyReports  = Report::where('is_emergency', true)->orWhere('status', 'emergency_flagged')->count();

        $resolutionRate = $totalReports > 0
            ? round(($resolvedReports / $totalReports) * 100, 1)
            : 0;

        // Reports per month (last 6 months)
        $monthlyTrend = Report::selectRaw("strftime('%Y-%m', created_at) as month, count(*) as total")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupByRaw("strftime('%Y-%m', created_at)")
            ->orderBy('month')
            ->get();

        // Reports by category
        $byCategory = Report::selectRaw('category_id, count(*) as total')
            ->with('category:id,name')
            ->groupBy('category_id')
            ->get()
            ->map(fn($r) => [
                'label' => $r->category?->name ?? 'Uncategorized',
                'total' => $r->total,
            ]);

        // Reports by status
        $byStatus = Report::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->get();

        $stats = compact(
            'totalReports',
            'resolvedReports',
            'pendingReports',
            'emergencyReports',
            'resolutionRate',
            'monthlyTrend',
            'byCategory',
            'byStatus'
        );

        return view('admin.insights', compact('stats'));
    }

    /**
     * Export insights data as CSV.
     */
    public function export(): StreamedResponse
    {
        $reports = Report::with('category')
            ->select('tracking_code', 'status', 'urgency', 'category_id', 'created_at', 'resolved_at')
            ->latest()
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="insights-export-' . now()->format('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($reports) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tracking Code', 'Status', 'Urgency', 'Category', 'Submitted', 'Resolved At']);
            foreach ($reports as $report) {
                fputcsv($handle, [
                    $report->tracking_code,
                    $report->status,
                    $report->urgency ?? '—',
                    $report->category?->name ?? 'Uncategorized',
                    $report->created_at?->format('Y-m-d H:i'),
                    $report->resolved_at?->format('Y-m-d H:i') ?? '—',
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
