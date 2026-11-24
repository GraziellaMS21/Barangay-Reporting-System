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
                $reportAudio = ReportAudio::query()
                    ->where('token', $validated['audio_token'])
                    ->first();

                if ($reportAudio) {
                    $extracted = is_array($reportAudio->extracted_data) ? $reportAudio->extracted_data : [];

                    $provenance = [];

                    // Description provenance
                    if (filled($extracted['description'] ?? null)) {
                        $origDesc = trim((string) ($extracted['description'] ?? ''));
                        $currentDesc = trim((string) ($validated['description'] ?? ''));
                        $isEdited = $origDesc !== '' && $origDesc !== $currentDesc;
                        $provenance['description'] = [
                            'filled' => true,
                            'edited' => $isEdited,
                        ];
                    }

                    // Category provenance
                    if (filled($extracted['category_id'] ?? null) || filled($extracted['category'] ?? null)) {
                        $origCatId = $extracted['category_id'] ?? null;
                        $currentCatId = $validated['category_id'] ?? null;
                        $isEdited = (string) $origCatId !== (string) $currentCatId;
                        $provenance['category'] = [
                            'filled' => true,
                            'edited' => $isEdited,
                        ];
                    }

                    // Purok provenance
                    if (filled($extracted['purok_id'] ?? null)) {
                        $origPurokId = $extracted['purok_id'] ?? null;
                        $currentPurokId = $validated['purok_id'] ?? null;
                        $isEdited = (string) $origPurokId !== (string) $currentPurokId;
                        $provenance['purok'] = [
                            'filled' => true,
                            'edited' => $isEdited,
                        ];
                    }

                    // Landmark provenance
                    if (filled($extracted['landmark'] ?? null)) {
                        $origLandmark = trim((string) ($extracted['landmark'] ?? ''));
                        $currentLandmark = trim((string) ($validated['landmark'] ?? ''));
                        $isEdited = $origLandmark !== $currentLandmark;
                        $provenance['landmark'] = [
                            'filled' => true,
                            'edited' => $isEdited,
                        ];
                    }

                    // Emergency provenance
                    if (! empty($extracted['emergency'])) {
                        $origEmg = (bool) ($extracted['emergency'] ?? false);
                        $currentEmg = ! empty($validated['is_emergency']);
                        $provenance['emergency'] = [
                            'filled' => true,
                            'edited' => $origEmg !== $currentEmg,
                        ];
                    }

                    // Contact fields: flags only, never values
                    if (filled($extracted['caller_name'] ?? null)) {
                        $provenance['reporter_name'] = [
                            'filled' => true,
                            'edited' => trim((string) ($extracted['caller_name'] ?? '')) !== trim((string) ($validated['caller_name'] ?? '')),
                        ];
                    }
                    if (filled($extracted['caller_phone'] ?? null)) {
                        $provenance['reporter_contact'] = [
                            'filled' => true,
                            'edited' => trim((string) ($extracted['caller_phone'] ?? '')) !== trim((string) ($validated['caller_phone'] ?? '')),
                        ];
                    }

                    $reportAudio->update([
                        'report_id' => $report->id,
                        'uploaded_by' => auth()->id(),
                        'origin' => ReportAudio::ORIGIN_STAFF,
                        'provenance' => $provenance,
                        'ai_description' => $reportAudio->ai_description ?: ($extracted['description'] ?? null),
                    ]);
                }
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
