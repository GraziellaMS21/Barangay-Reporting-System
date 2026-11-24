<?php

namespace App\Http\Controllers\Admin;

use App\Events\ReportSubmitted;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ReportAudio;
use App\Models\StatusLog;
use App\Services\FiscalYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PhoneReportController extends Controller
{
    public function create(): View
    {
        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();
        $puroks = Purok::query()->orderBy('name')->get();

        return view('admin.reports.phone', compact('categories', 'puroks'));
    }

    public function store(Request $request, FiscalYearService $fiscalYearService): RedirectResponse
    {
        $validated = $request->validate([
            'verbal_consent' => ['required', 'accepted'],
            'caller_name' => ['nullable', 'string', 'max:100'],
            'caller_phone' => ['nullable', 'string', 'max:20'],
            'purok_id' => ['required', 'exists:puroks,id'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'urgency' => ['nullable', 'in:Low,Medium,High,Critical'],
            'description' => ['required', 'string', 'min:5'],
            'is_emergency' => ['nullable', 'boolean'],
            'audio_token' => ['nullable', 'string', 'exists:report_audios,token'],
        ], [
            'verbal_consent.accepted' => 'You must confirm that the caller gave verbal consent to record and process their report.',
            'purok_id.required' => 'Please select the purok where the incident is located.',
            'description.required' => 'Please enter the incident description.',
        ]);

        $isEmergency = (bool) ($validated['is_emergency'] ?? false);
        $fiscalYear = $fiscalYearService->forNewRecord();

        $report = DB::transaction(function () use ($validated, $isEmergency, $fiscalYear): Report {
            $report = Report::create([
                'fiscal_year_id' => $fiscalYear->id,
                'tracking_code' => Report::generateTrackingCode(),
                'source' => Report::SOURCE_PHONE_LOG,
                'status' => $isEmergency ? 'emergency_flagged' : 'submitted',
                'is_emergency' => $isEmergency,
                'category_id' => $validated['category_id'] ?? null,
                'purok_id' => $validated['purok_id'],
                'landmark' => $validated['landmark'] ?? null,
                'reporter_name' => $validated['caller_name'] ?? null,
                'reporter_contact' => $validated['caller_phone'] ?? null,
                'description' => $validated['description'],
                'staff_description' => $validated['description'],
                'urgency' => $validated['urgency'] ?? 'Medium',
                'is_anonymous' => empty($validated['caller_name']),
                'preferred_contact' => ! empty($validated['caller_phone']) ? 'phone' : 'none',
                'consent_at' => now(),
                'consent_recorded_by' => auth()->id(),
            ]);

            if (! empty($validated['audio_token'])) {
                ReportAudio::query()
                    ->where('token', $validated['audio_token'])
                    ->update([
                        'report_id' => $report->id,
                        'uploaded_by' => auth()->id(),
                        'origin' => ReportAudio::ORIGIN_STAFF,
                    ]);
            }

            StatusLog::create([
                'report_id' => $report->id,
                'from_status' => null,
                'to_status' => $report->status,
                'note' => 'Phone intake logged by '.auth()->user()->name,
            ]);

            return $report;
        });

        ReportSubmitted::dispatch($report);

        return redirect()
            ->route('admin.reports.show', $report)
            ->with('toast', [
                'type' => 'success',
                'message' => "Phone report logged successfully ({$report->tracking_code}).",
            ]);
    }
}
