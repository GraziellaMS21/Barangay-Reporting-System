<?php

namespace App\Http\Controllers;

use App\Events\ReportSubmitted;
use App\Models\Category;
use App\Models\EmergencyContact;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ReportAudio;
use App\Models\StatusLog;
use App\Services\FiscalYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function create(): View
    {
        return view('resident.create', [
            'puroks' => Purok::orderBy('name')->get(),
            'contacts' => EmergencyContact::query()->active()->ordered()->get(),
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderByRaw("CASE WHEN LOWER(slug) IN ('other', 'others') OR LOWER(name) IN ('other', 'others') THEN 1 ELSE 0 END, name ASC")
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $isAnonymous = $request->boolean('is_anonymous');

        $data = $request->validate([
            'report_type' => ['nullable', 'string', 'in:emergency,non_emergency'],
            'category_id' => ['required', 'exists:categories,id'],
            'answers' => ['nullable', 'array'],
            'is_ongoing' => ['nullable', 'boolean'],
            'people_at_risk' => ['nullable', 'boolean'],
            'is_recurring' => ['nullable', 'boolean'],
            'is_anonymous' => ['nullable', 'boolean'],
            'preferred_contact' => ['nullable', 'string', 'in:phone,email,none'],
            'consent' => ['nullable'],
            'audio_token' => ['nullable', 'string', 'exists:report_audios,token'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
            'purok_id' => ['nullable', 'exists:puroks,id'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'reporter_email' => ['nullable', 'email', 'max:255'],
            'reporter_name' => ['nullable', 'string', 'max:100'],
            'reporter_contact' => [
                Rule::requiredIf((bool) config('reports.require_contact')),
                'nullable',
                'string',
                'max:20',
                'regex:/^(?:0[ -]*9|\\+[ -]*6[ -]*3[ -]*9)(?:[ -]*\\d){9}$/',
            ],
        ], [
            'category_id.required' => 'Please choose a category for this report.',
            'reporter_name.string' => 'Please enter a valid name.',
            'reporter_name.max' => 'Name cannot be longer than 100 characters.',
            'reporter_contact.required' => 'Please enter a contact number so barangay staff can reach you.',
            'reporter_contact.string' => 'Please enter a valid contact number.',
            'reporter_contact.max' => 'Contact number cannot be longer than 20 characters.',
            'reporter_contact.regex' => 'Enter a Philippine mobile number, such as 09171234567 or +639171234567. Spaces and dashes are okay.',
            'reporter_email.required' => 'Please provide an email address for status updates.',
            'photos.array' => 'Please select valid photo files.',
            'photos.*.image' => 'Each selected file must be a valid image.',
            'photos.*.mimes' => 'Each photo must be a JPG or PNG image.',
            'photos.*.max' => 'The combined size of all photos must not exceed 5 MB.',
        ]);

        // Normalize category answers against the selected category's question schema:
        // drop answers that do not belong to the schema (e.g. left over from a changed
        // category). Answers are only stored when a category is selected. Required
        // schema fields are enforced client-side via the "required" attribute.
        $selectedCategory = isset($data['category_id'])
            ? Category::query()->find($data['category_id'])
            : null;

        $schemaKeys = collect($selectedCategory?->question_schema ?? [])->pluck('key')->all();

        $answers = collect($data['answers'] ?? [])
            ->only($schemaKeys)
            ->filter(static fn ($value): bool => $value !== null && trim((string) $value) !== '')
            ->all();

        $data['answers'] = $answers === [] ? null : $answers;

        if (! $request->filled('lat') && ! $request->filled('purok_id') && ! $request->filled('landmark')) {
            return back()->withInput()
                ->withErrors(['location' => 'Please set a pin on the map, choose a purok, or enter a landmark.']);
        }

        // Validate privacy consent if submitted through the web form that provides the consent field
        if ($request->has('has_consent_field') && ! $request->boolean('consent')) {
            return back()->withInput()
                ->withErrors(['consent' => 'You must agree to the Data Privacy Notice before submitting.']);
        }

        $data['reporter_contact'] = isset($data['reporter_contact'])
            ? str_replace([' ', '-'], '', $data['reporter_contact'])
            : null;

        /** @var list<UploadedFile> $photos */
        $photos = $data['photos'] ?? [];
        $totalPhotoBytes = array_sum(array_map(
            static fn (UploadedFile $photo): int => (int) $photo->getSize(),
            $photos
        ));

        if ($totalPhotoBytes > 5 * 1024 * 1024) {
            return back()
                ->withInput()
                ->withErrors(['photos' => 'The combined size of all photos must not exceed 5 MB.']);
        }

        $photoPaths = array_map(
            static fn (UploadedFile $photo): string => $photo->store('reports', 'local'),
            $photos
        );

        $reportType = $data['report_type'] ?? 'non_emergency';
        $isEmergency = $reportType === 'emergency';
        $consentAt = ($request->boolean('consent') || ! $request->has('has_consent_field')) ? now() : null;

        // Server-side duplicate protection against rapid re-submits or double clicks
        $recentDuplicate = Report::query()
            ->where('description', $data['description'])
            ->where('category_id', $data['category_id'])
            ->where('created_at', '>=', now()->subSeconds(20))
            ->first();

        if ($recentDuplicate) {
            return redirect()->route('reports.success', $recentDuplicate->tracking_code);
        }

        $report = DB::transaction(function () use ($data, $photoPaths, $reportType, $isEmergency, $isAnonymous, $consentAt) {
            $fiscalYear = app(FiscalYearService::class)->forNewRecord();
            $fiscalYear = app(FiscalYearService::class)->forNewRecord();
            $report = Report::create([
                'fiscal_year_id' => $fiscalYear->id,
                'tracking_code' => Report::generateTrackingCode(),
                'source' => ! empty($data['audio_token']) ? Report::SOURCE_VOICE : Report::SOURCE_WEB,
                'report_type' => $reportType,
                'category_id' => $data['category_id'] ?? null,
                'answers' => $data['answers'] ?? null,
                'is_ongoing' => (bool) ($data['is_ongoing'] ?? false),
                'is_recurring' => (bool) ($data['is_recurring'] ?? false),
                'people_at_risk' => (bool) ($data['people_at_risk'] ?? false),
                'is_anonymous' => $isAnonymous,
                'preferred_contact' => $isAnonymous ? 'none' : (
                    ! empty($data['reporter_email']) ? 'email' : (! empty($data['reporter_contact']) ? 'phone' : 'none')
                ),
                'consent_at' => $consentAt,
                'description' => $data['description'],
                'photo_path' => $photoPaths[0] ?? null,
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'purok_id' => $data['purok_id'] ?? null,
                'landmark' => $data['landmark'] ?? null,
                'reporter_email' => $isAnonymous ? null : ($data['reporter_email'] ?? null),
                'reporter_name' => $isAnonymous ? null : ($data['reporter_name'] ?? null),
                'reporter_contact' => $isAnonymous ? null : $data['reporter_contact'],
                'is_emergency' => $isEmergency,
                'status' => $isEmergency ? 'emergency_flagged' : 'submitted',
            ]);

            foreach ($photoPaths as $photoPath) {
                $report->photos()->create(['photo_path' => $photoPath]);
            }

            if (! empty($data['audio_token'])) {
                ReportAudio::query()
                    ->where('token', $data['audio_token'])
                    ->update([
                        'report_id' => $report->id,
                        'origin' => ReportAudio::ORIGIN_RESIDENT,
                    ]);
            }

            StatusLog::create([
                'report_id' => $report->id,
                'from_status' => null,
                'to_status' => $report->status,
                'note' => $isEmergency ? 'Emergency report received.' : 'Report received.',
            ]);

            return $report;
        });

        // Contract: Dispatches ReportSubmitted; does not call Gemini, mail, or routing directly
        ReportSubmitted::dispatch($report);

        return redirect()->route('reports.success', $report->tracking_code);
    }

    public function success(string $trackingCode): View
    {
        $report = Report::query()
            ->select(['id', 'tracking_code', 'status', 'created_at'])
            ->where('tracking_code', $trackingCode)
            ->firstOrFail();
        $contacts = EmergencyContact::query()->active()->ordered()->get();

        return view('resident.success', compact('report', 'contacts'));
    }
}
