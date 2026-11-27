<?php

namespace App\Http\Controllers;

use App\Events\ReportSubmitted;
use App\Models\Category;
use App\Models\EmergencyContact;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ReportAudio;
use App\Models\StatusLog;
use App\Services\BoundaryService;
use App\Services\FiscalYearService;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('resident.index', [
            'contacts' => EmergencyContact::query()->active()->ordered()->get(),
        ]);
    }

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

    /**
     * Stateless pre-submission AI classification of a resident's concern description.
     */
    public function analyzeConcern(Request $request, GeminiService $gemini): JsonResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
        ], [
            'description.required' => 'Please enter a concern description before analyzing.',
            'description.min' => 'Please enter at least 10 characters describing your concern.',
            'description.max' => 'Your concern description cannot exceed 2000 characters.',
            'category_id.exists' => 'The selected category is not active.',
        ]);

        $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
        $result = $gemini->classifyConcernText($data['description'], $categoryId);

        if (! $result['ok']) {
            return response()->json([
                'success' => false,
                'available' => false,
                'message' => $result['error'] ?? 'AI classification is temporarily unavailable. You can still submit your concern manually.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'classification' => $result['classification'],
                'suggested_category' => $result['suggested_category'],
                'summary' => $result['summary'],
                'clarification_questions' => $result['clarification_questions'],
                'is_emergency' => $result['is_emergency'],
                'confidence' => $result['confidence'],
            ],
        ]);
    }

    /**
     * Stateless read-only Purok & Barangay boundary detection for a coordinate pair.
     */
    public function detectLocation(Request $request, BoundaryService $boundaryService): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'lat.required' => 'Latitude is required.',
            'lat.between' => 'Latitude must be between -90 and 90.',
            'lng.required' => 'Longitude is required.',
            'lng.between' => 'Longitude must be between -180 and 180.',
        ]);

        $barangayBoundary = null;
        $path = config('reports.gis.barangay_boundary_path');
        if (is_string($path) && trim($path) !== '' && is_file($path) && is_readable($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                $barangayBoundary = ($decoded['type'] ?? null) === 'FeatureCollection'
                    ? ($decoded['features'][0]['geometry'] ?? null)
                    : (($decoded['type'] ?? null) === 'Feature' ? ($decoded['geometry'] ?? null) : $decoded);
            }
        }

        return response()->json(
            $boundaryService->detectLocation((float) $data['lat'], (float) $data['lng'], $barangayBoundary)
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $isAnonymous = $request->boolean('is_anonymous');

        $data = $request->validate([
            'report_type' => ['nullable', 'string', 'in:emergency,non_emergency'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'voice_fields_filled' => ['nullable', 'array'],
            'voice_fields_filled.*' => ['string'],
            'voice_field_edited_reporter_name' => ['nullable', 'boolean'],
            'voice_field_edited_reporter_contact' => ['nullable', 'boolean'],
            'voice_field_edited_reporter_email' => ['nullable', 'boolean'],
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
        $recentDuplicateQuery = Report::query()
            ->where('description', $data['description'])
            ->where('created_at', '>=', now()->subSeconds(20));

        if (! empty($data['category_id'])) {
            $recentDuplicateQuery->where('category_id', $data['category_id']);
        } else {
            $recentDuplicateQuery->whereNull('category_id');
        }

        $recentDuplicate = $recentDuplicateQuery->first();

        if ($recentDuplicate) {
            return redirect()->route('reports.success', $recentDuplicate->tracking_code);
        }

        $report = DB::transaction(function () use ($data, $request, $photoPaths, $reportType, $isEmergency, $isAnonymous, $consentAt) {
            $fiscalYear = app(FiscalYearService::class)->forNewRecord();
            $fiscalYear = app(FiscalYearService::class)->forNewRecord();
            $report = Report::create([
                'fiscal_year_id' => $fiscalYear->id,
                'tracking_code' => Report::generateTrackingCode(),
                'source' => ! empty($data['audio_token']) ? Report::SOURCE_VOICE : Report::SOURCE_WEB,
                'report_type' => $reportType,
                'category_id' => $data['category_id'] ?? null,
                'resident_category_id' => $data['category_id'] ?? null,
                'resident_purok_id' => $data['purok_id'] ?? null,
                'resident_landmark' => $data['landmark'] ?? null,
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
                $reportAudio = ReportAudio::query()
                    ->where('token', $data['audio_token'])
                    ->first();

                if ($reportAudio) {
                    $extracted = is_array($reportAudio->extracted_data) ? $reportAudio->extracted_data : [];
                    $clientFilled = (array) ($request->input('voice_fields_filled') ?? []);

                    $provenance = [];

                    // Description provenance
                    if (in_array('description', $clientFilled, true) || filled($extracted['description'] ?? null)) {
                        $origDesc = trim((string) ($extracted['description'] ?? ''));
                        $currentDesc = trim((string) ($data['description'] ?? ''));
                        $isEdited = $origDesc !== '' && $origDesc !== $currentDesc;
                        $provenance['description'] = [
                            'filled' => true,
                            'edited' => $isEdited,
                        ];
                    }

                    // Category provenance
                    if (in_array('category', $clientFilled, true) || filled($extracted['category_id'] ?? null) || filled($extracted['category'] ?? null)) {
                        $origCatId = $extracted['category_id'] ?? null;
                        $currentCatId = $data['category_id'] ?? null;
                        $isEdited = (string) $origCatId !== (string) $currentCatId;
                        $provenance['category'] = [
                            'filled' => true,
                            'edited' => $isEdited,
                        ];
                    }

                    // Purok provenance
                    if (in_array('purok', $clientFilled, true) || filled($extracted['purok_id'] ?? null)) {
                        $origPurokId = $extracted['purok_id'] ?? null;
                        $currentPurokId = $data['purok_id'] ?? null;
                        $isEdited = (string) $origPurokId !== (string) $currentPurokId;
                        $provenance['purok'] = [
                            'filled' => true,
                            'edited' => $isEdited,
                        ];
                    }

                    // Landmark provenance
                    if (in_array('landmark', $clientFilled, true) || filled($extracted['landmark'] ?? null)) {
                        $origLandmark = trim((string) ($extracted['landmark'] ?? ''));
                        $currentLandmark = trim((string) ($data['landmark'] ?? ''));
                        $isEdited = $origLandmark !== $currentLandmark;
                        $provenance['landmark'] = [
                            'filled' => true,
                            'edited' => $isEdited,
                        ];
                    }

                    // Emergency provenance
                    if (in_array('emergency', $clientFilled, true) || ! empty($extracted['emergency'])) {
                        $origEmg = (bool) ($extracted['emergency'] ?? false);
                        $provenance['emergency'] = [
                            'filled' => true,
                            'edited' => $origEmg !== $isEmergency,
                        ];
                    }

                    // Contact fields: flags only, never values
                    if (in_array('reporter_name', $clientFilled, true)) {
                        $provenance['reporter_name'] = [
                            'filled' => true,
                            'edited' => $request->boolean('voice_field_edited_reporter_name'),
                        ];
                    }
                    if (in_array('reporter_contact', $clientFilled, true)) {
                        $provenance['reporter_contact'] = [
                            'filled' => true,
                            'edited' => $request->boolean('voice_field_edited_reporter_contact'),
                        ];
                    }
                    if (in_array('reporter_email', $clientFilled, true)) {
                        $provenance['reporter_email'] = [
                            'filled' => true,
                            'edited' => $request->boolean('voice_field_edited_reporter_email'),
                        ];
                    }

                    $reportAudio->update([
                        'report_id' => $report->id,
                        'origin' => ReportAudio::ORIGIN_RESIDENT,
                        'provenance' => $provenance,
                        'ai_description' => $reportAudio->ai_description ?: ($extracted['description'] ?? null),
                    ]);
                }
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
