<?php

namespace App\Services\Voice;

use App\Models\Category;
use App\Models\Purok;
use App\Models\ReportAudio;
use App\Models\Setting;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VoiceAnalyzeService
{
    public const ALLOWED_MIMES = [
        'audio/webm',
        'audio/ogg',
        'audio/wav',
        'audio/x-wav',
        'audio/mp4',
        'audio/mpeg',
        'audio/mp3',
        'audio/x-m4a',
        'audio/aac',
        'video/webm',
    ];

    public function __construct(
        public GeminiService $gemini,
        public VoiceService $voiceService,
    ) {}

    /**
     * Analyze audio recording and return normalized form extraction and token.
     *
     * @return array<string, mixed>
     */
    public function analyze(
        UploadedFile|string $file,
        string $origin = ReportAudio::ORIGIN_RESIDENT,
        ?User $user = null,
        ?int $durationSeconds = null,
        bool $isAnonymous = false,
        ?string $mockScenario = null,
    ): array {
        // 1. Master enable switch check
        $masterEnabled = filter_var(Setting::value('voice.enabled') ?? config('voice.enabled', true), FILTER_VALIDATE_BOOLEAN);
        if (! $masterEnabled) {
            return [
                'ok' => false,
                'status' => 'disabled',
                'fallback' => true,
                'message' => 'Voice reporting is currently disabled.',
            ];
        }

        // 2. Resident specific recording enable check
        if ($origin === ReportAudio::ORIGIN_RESIDENT) {
            $residentEnabled = filter_var(Setting::value('voice.resident_recording_enabled') ?? config('voice.resident_recording_enabled', true), FILTER_VALIDATE_BOOLEAN);
            if (! $residentEnabled) {
                return [
                    'ok' => false,
                    'status' => 'disabled',
                    'fallback' => true,
                    'message' => 'Resident voice recording is currently disabled. Please fill in the report details manually.',
                ];
            }
        }

        // 3. Daily analyze limit check
        $dailyLimit = (int) (Setting::value('voice.daily_analyze_limit') ?? config('voice.daily_analyze_limit', 100));
        $todayAnalysesCount = ReportAudio::query()
            ->whereDate('created_at', today())
            ->whereIn('transcript_status', [
                ReportAudio::STATUS_DONE,
                'partial',
                ReportAudio::STATUS_NEEDS_VERIFICATION,
                ReportAudio::STATUS_FAILED,
            ])
            ->count();

        if ($dailyLimit > 0 && $todayAnalysesCount >= $dailyLimit) {
            return [
                'ok' => false,
                'status' => 'limit_reached',
                'fallback' => true,
                'message' => 'The daily voice analysis limit has been reached. Please fill in the report details manually.',
            ];
        }

        // 4. File duration & MIME validation
        $maxSeconds = (int) (Setting::value('voice.max_seconds') ?? config('voice.max_seconds', 120));
        if ($durationSeconds !== null && $durationSeconds > $maxSeconds) {
            throw ValidationException::withMessages([
                'duration_seconds' => ["The recording duration exceeds the maximum allowed {$maxSeconds} seconds."],
            ]);
        }

        $detectedMime = $this->detectAndValidateMime($file);

        // 5. Store audio record to private disk
        $audio = $this->voiceService->storeAudio(
            $file,
            $origin,
            $user?->id,
            $durationSeconds,
            $detectedMime
        );

        // 6. Contact extraction permission check per channel
        $extractContact = $origin === ReportAudio::ORIGIN_STAFF
            ? filter_var(Setting::value('voice.extract_contact_from_audio') ?? config('voice.extract_contact_from_audio', true), FILTER_VALIDATE_BOOLEAN)
            : filter_var(Setting::value('voice.resident_extract_contact') ?? config('voice.resident_extract_contact', true), FILTER_VALIDATE_BOOLEAN);

        // 7. Extract data (Mock mode or Gemini API call)
        $isMock = (bool) config('voice.mock_transcription', false) || $mockScenario !== null;
        if ($isMock) {
            $rawExtraction = $this->generateMockExtraction($mockScenario ?? 'full', $extractContact);
        } else {
            $puroks = Purok::query()->orderBy('name')->pluck('name')->all();
            $categories = Category::query()->where('is_active', true)->orderBy('slug')->pluck('slug')->all();
            $bytes = Storage::disk('private')->get($audio->disk_path);

            $rawExtraction = $this->gemini->analyzeVoice(
                $bytes,
                $audio->mime,
                $puroks,
                $categories,
                $extractContact
            );
        }

        // 8. Server-side normalization & validation (Never trust model output)
        $normalized = $this->normalizeExtraction($rawExtraction, $extractContact);

        // 9. Update ReportAudio record
        $audioStatus = ($rawExtraction['status'] ?? null) === 'failed'
            ? ReportAudio::STATUS_FAILED
            : match ($normalized['clarity']) {
                'unclear' => ReportAudio::STATUS_NEEDS_VERIFICATION,
                'partial' => 'partial',
                default => ReportAudio::STATUS_DONE,
            };

        $nonContactExtraction = [
            'description' => $normalized['description'] ?? '',
            'category' => $normalized['category'] ?? null,
            'category_id' => $normalized['category_id'] ?? null,
            'purok' => $normalized['purok'] ?? null,
            'purok_id' => $normalized['purok_id'] ?? null,
            'landmark' => $normalized['landmark'] ?? null,
            'emergency' => (bool) ($normalized['emergency'] ?? false),
            'wants_anonymous' => (bool) ($normalized['wants_anonymous'] ?? false),
            'not_stated' => $normalized['not_stated'] ?? [],
        ];

        $audio->update([
            'transcript' => $normalized['transcript'] ?? '',
            'transcript_language' => $normalized['language'] ?? null,
            'transcript_status' => $audioStatus,
            'unverified_draft' => in_array($audioStatus, [ReportAudio::STATUS_NEEDS_VERIFICATION, ReportAudio::STATUS_FAILED], true),
            'error' => $rawExtraction['confidence_note'] ?? null,
            'transcribed_at' => now(),
            'extracted_data' => $nonContactExtraction,
            'ai_description' => $normalized['description'] ?? '',
        ]);

        // Log operation without transcripts or extracted PII
        Log::info('Voice recording analyzed', [
            'audio_id' => $audio->id,
            'token' => $audio->token,
            'origin' => $origin,
            'status' => $audioStatus,
            'clarity' => $normalized['clarity'],
            'duration' => $durationSeconds,
            'user_id' => $user?->id,
        ]);

        // 10. Channel-specific response shaping
        return $this->formatResponseForChannel($audio, $normalized, $origin, $isAnonymous);
    }

    /**
     * Validate real MIME type.
     */
    protected function detectAndValidateMime(UploadedFile|string $file): string
    {
        if ($file instanceof UploadedFile) {
            $mime = $file->getMimeType() ?: $file->getClientMimeType() ?: 'audio/webm';
        } else {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($file) ?: 'audio/webm';
        }

        if (! in_array(strtolower($mime), self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'audio' => ['The uploaded audio format ('.$mime.') is unsupported. Please upload a valid webm, ogg, wav, mp4, or mp3 file.'],
            ]);
        }

        return $mime;
    }

    /**
     * Server-side normalization of model output.
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    protected function normalizeExtraction(array $raw, bool $extractContact): array
    {
        $clarity = strtolower((string) ($raw['clarity'] ?? 'clear'));
        if (! in_array($clarity, ['clear', 'partial', 'unclear'], true)) {
            $clarity = 'clear';
        }

        $transcript = is_string($raw['transcript'] ?? null) ? trim($raw['transcript']) : '';
        $language = is_string($raw['language'] ?? null) ? trim($raw['language']) : null;
        $emergency = (bool) ($raw['emergency'] ?? false);
        $wantsAnonymous = (bool) ($raw['wants_anonymous'] ?? false);

        $notStated = is_array($raw['not_stated'] ?? null)
            ? array_values(array_filter($raw['not_stated'], 'is_string'))
            : [];

        // If audio is unclear or transcript is empty, return empty fields with needs_verification
        if ($clarity === 'unclear' || $transcript === '' || ($raw['status'] ?? null) === 'failed') {
            $allFields = ['purok', 'landmark', 'category', 'urgency', 'description'];
            if ($extractContact) {
                $allFields = array_merge(['caller_name', 'caller_phone', 'caller_email'], $allFields);
            }

            return [
                'transcript' => $transcript,
                'description' => '',
                'language' => $language,
                'clarity' => 'unclear',
                'caller_name' => null,
                'caller_phone' => null,
                'caller_email' => null,
                'wants_anonymous' => false,
                'purok_id' => null,
                'purok' => null,
                'landmark' => null,
                'category_id' => null,
                'category' => null,
                'urgency' => null,
                'emergency' => false,
                'not_stated' => array_values(array_unique(array_merge($notStated, $allFields))),
            ];
        }

        // Contact normalization
        $callerName = null;
        $callerPhone = null;
        $callerEmail = null;

        if ($extractContact) {
            // Name: trimmed, max 100
            if (! empty($raw['caller_name']) && is_string($raw['caller_name'])) {
                $trimmedName = trim($raw['caller_name']);
                $callerName = $trimmedName !== '' ? mb_substr($trimmedName, 0, 100) : null;
            }
            if ($callerName === null) {
                $notStated[] = 'caller_name';
            }

            // Phone: Philippine mobile validation (09XXXXXXXXX or +639XXXXXXXXX)
            if (! empty($raw['caller_phone']) && is_string($raw['caller_phone'])) {
                $cleanPhone = preg_replace('/[^\d+]/', '', trim($raw['caller_phone']));
                if (preg_match('/^(?:09|\+639)\d{9}$/', $cleanPhone)) {
                    // Standardize to digits format (09XXXXXXXXX)
                    $callerPhone = str_starts_with($cleanPhone, '+639')
                        ? '09'.substr($cleanPhone, 4)
                        : $cleanPhone;
                }
            }
            if ($callerPhone === null) {
                $notStated[] = 'caller_phone';
            }

            // Email: valid format
            if (! empty($raw['caller_email']) && is_string($raw['caller_email'])) {
                $cleanEmail = trim($raw['caller_email']);
                if (filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                    $callerEmail = mb_substr($cleanEmail, 0, 100);
                }
            }
            if ($callerEmail === null) {
                $notStated[] = 'caller_email';
            }
        }

        // Location: Purok validation against existing database puroks
        $purokName = null;
        $purokId = null;
        if (! empty($raw['purok']) && is_string($raw['purok'])) {
            $purokRecord = Purok::query()
                ->where('name', trim($raw['purok']))
                ->orWhereRaw('LOWER(name) = ?', [strtolower(trim($raw['purok']))])
                ->first();

            if ($purokRecord) {
                $purokName = $purokRecord->name;
                $purokId = $purokRecord->id;
            }
        }
        if ($purokName === null) {
            $notStated[] = 'purok';
        }

        // Landmark: trimmed, max 255
        $landmark = null;
        if (! empty($raw['landmark']) && is_string($raw['landmark'])) {
            $trimmedLandmark = trim($raw['landmark']);
            $landmark = $trimmedLandmark !== '' ? mb_substr($trimmedLandmark, 0, 255) : null;
        }
        if ($landmark === null) {
            $notStated[] = 'landmark';
        }

        // Category: validation against active database categories
        $categorySlug = null;
        $categoryId = null;
        if (! empty($raw['category']) && is_string($raw['category'])) {
            $categoryRecord = Category::query()
                ->where('is_active', true)
                ->where(function ($q) use ($raw) {
                    $slug = strtolower(trim((string) $raw['category']));
                    $q->where('slug', $slug)
                        ->orWhereRaw('LOWER(name) = ?', [$slug]);
                })
                ->first();

            if ($categoryRecord) {
                $categorySlug = $categoryRecord->slug;
                $categoryId = $categoryRecord->id;
            }
        }
        if ($categorySlug === null) {
            $notStated[] = 'category';
        }

        // Urgency: Low, Medium, High, Critical
        $urgency = null;
        if (! empty($raw['urgency']) && is_string($raw['urgency'])) {
            $cleanUrgency = ucfirst(strtolower(trim($raw['urgency'])));
            if (in_array($cleanUrgency, ['Low', 'Medium', 'High', 'Critical'], true)) {
                $urgency = $cleanUrgency;
            }
        }
        if ($urgency === null) {
            $notStated[] = 'urgency';
        }

        // Description: problem statement without PII
        $description = is_string($raw['description'] ?? null) ? trim($raw['description']) : '';
        if ($description === '') {
            $notStated[] = 'description';
        }

        return [
            'transcript' => $transcript,
            'description' => $description,
            'language' => $language,
            'clarity' => $clarity,
            'caller_name' => $callerName,
            'caller_phone' => $callerPhone,
            'caller_email' => $callerEmail,
            'wants_anonymous' => $wantsAnonymous,
            'purok_id' => $purokId,
            'purok' => $purokName,
            'landmark' => $landmark,
            'category_id' => $categoryId,
            'category' => $categorySlug,
            'urgency' => $urgency,
            'emergency' => $emergency,
            'not_stated' => array_values(array_unique($notStated)),
        ];
    }

    /**
     * Shape the JSON response based on channel (Staff vs Resident).
     *
     * @param  array<string, mixed>  $normalized
     * @return array<string, mixed>
     */
    protected function formatResponseForChannel(
        ReportAudio $audio,
        array $normalized,
        string $origin,
        bool $isAnonymous
    ): array {
        $clarity = $normalized['clarity'];
        $isUnclear = $clarity === 'unclear';
        $isPartial = $clarity === 'partial';

        $notice = null;
        if ($isUnclear) {
            $notice = $origin === ReportAudio::ORIGIN_STAFF
                ? "We couldn't understand this recording clearly. Listen again and enter the details."
                : "We couldn't clearly transcribe parts of your voice note. Barangay staff will listen and review it before processing.";
        } elseif ($isPartial) {
            $notice = 'Parts of this recording were unclear. Please listen again and check every field.';
        }

        $base = [
            'ok' => true,
            'token' => $audio->token,
            'status' => $audio->transcript_status,
            'clarity' => $clarity,
            'is_partial' => $isPartial,
            'is_unclear' => $isUnclear,
            'notice' => $notice,
            'language' => $normalized['language'],
            'description' => $normalized['description'],
            'purok_id' => $normalized['purok_id'],
            'purok' => $normalized['purok'],
            'landmark' => $normalized['landmark'],
            'emergency' => $normalized['emergency'],
            'wants_anonymous' => $normalized['wants_anonymous'],
            'not_stated' => $normalized['not_stated'],
        ];

        if ($origin === ReportAudio::ORIGIN_STAFF) {
            // Staff channel: receives raw transcript, category & urgency suggestions, and extracted caller details
            return array_merge($base, [
                'transcript' => $audio->transcript,
                'caller_name' => $normalized['caller_name'],
                'caller_phone' => $normalized['caller_phone'],
                'caller_email' => $normalized['caller_email'],
                'category_id' => $normalized['category_id'],
                'category' => $normalized['category'],
                'urgency' => $normalized['urgency'],
            ]);
        }

        // Resident channel:
        // - Raw transcript is NEVER returned
        // - Category may be returned as suggestion (validated slug or null)
        // - Urgency is NEVER returned (must remain null)
        // - If resident toggled anonymous or model found wants_anonymous: DROP caller details on server!
        $dropContact = $isAnonymous || $normalized['wants_anonymous'];

        return array_merge($base, [
            'caller_name' => $dropContact ? null : $normalized['caller_name'],
            'caller_phone' => $dropContact ? null : $normalized['caller_phone'],
            'caller_email' => $dropContact ? null : $normalized['caller_email'],
            'category_id' => $normalized['category_id'],
            'category' => $normalized['category'],
            'urgency' => null,
        ]);
    }

    /**
     * Generate realistic canned extractions for mock mode.
     *
     * @return array<string, mixed>
     */
    public function generateMockExtraction(string $scenario, bool $extractContact = true): array
    {
        return match ($scenario) {
            'no_contact' => [
                'transcript' => 'Gusto ko lang i-report na pundido ang poste ng ilaw sa Purok 4 kanto ng Dahlia Street, delikado pag gabi.',
                'description' => 'Pundido ang ilaw sa poste sa kanto ng Dahlia Street, madilim sa gabi.',
                'language' => 'fil',
                'clarity' => 'clear',
                'caller_name' => null,
                'caller_phone' => null,
                'caller_email' => null,
                'wants_anonymous' => false,
                'purok' => 'Purok 4',
                'landmark' => 'Corner Dahlia Street',
                'category' => 'streetlight',
                'urgency' => 'Medium',
                'emergency' => false,
                'not_stated' => ['caller_name', 'caller_phone', 'caller_email'],
                'status' => 'done',
            ],
            'partial' => [
                'transcript' => 'Ako si Aling Nena... [static] ...tubig tumutulo sa may basketball court sa Purok 1...',
                'description' => 'May tumutulong tubig sa tubo malapit sa basketball court.',
                'language' => 'fil',
                'clarity' => 'partial',
                'caller_name' => $extractContact ? 'Aling Nena' : null,
                'caller_phone' => null,
                'caller_email' => null,
                'wants_anonymous' => false,
                'purok' => 'Purok 1',
                'landmark' => 'Near basketball court',
                'category' => 'drainage',
                'urgency' => 'Low',
                'emergency' => false,
                'not_stated' => ['caller_phone', 'caller_email'],
                'status' => 'partial',
            ],
            'unclear' => [
                'transcript' => '',
                'description' => '',
                'language' => null,
                'clarity' => 'unclear',
                'caller_name' => null,
                'caller_phone' => null,
                'caller_email' => null,
                'wants_anonymous' => false,
                'purok' => null,
                'landmark' => null,
                'category' => null,
                'urgency' => null,
                'emergency' => false,
                'not_stated' => ['caller_name', 'caller_phone', 'caller_email', 'purok', 'landmark', 'category', 'urgency', 'description'],
                'status' => 'needs_verification',
            ],
            'emergency' => [
                'transcript' => 'Tulong po! Nasusunog ang bahay dito sa Purok 3 tapat ng barangay hall! Kailangan ng bumbero agad!',
                'description' => 'Nasusunog ang residential house tapat ng barangay hall, kailangan ng bumbero agad!',
                'language' => 'fil',
                'clarity' => 'clear',
                'caller_name' => $extractContact ? 'Pedro Penduko' : null,
                'caller_phone' => $extractContact ? '09189876543' : null,
                'caller_email' => null,
                'wants_anonymous' => false,
                'purok' => 'Purok 3',
                'landmark' => 'Opposite Barangay Hall',
                'category' => 'other',
                'urgency' => 'Critical',
                'emergency' => true,
                'not_stated' => ['caller_email'],
                'status' => 'done',
            ],
            default => [ // 'full'
                'transcript' => 'Magandang umaga po, ako po si Juan Dela Cruz, 09171234567. May tambak po ng basura dito sa Purok 2 tapat ng Calarian Elementary School dalawang linggo na hindi nahahakot.',
                'description' => 'May tambak ng basura sa tapat ng school na hindi nahahakot ng dalawang linggo.',
                'language' => 'fil',
                'clarity' => 'clear',
                'caller_name' => $extractContact ? 'Juan Dela Cruz' : null,
                'caller_phone' => $extractContact ? '09171234567' : null,
                'caller_email' => $extractContact ? 'juan@example.com' : null,
                'wants_anonymous' => false,
                'purok' => 'Purok 2',
                'landmark' => 'Near Calarian Elementary School',
                'category' => 'garbage',
                'urgency' => 'High',
                'emergency' => false,
                'not_stated' => [],
                'status' => 'done',
            ],
        };
    }
}
