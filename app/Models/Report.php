<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Report extends Model
{
    use HasFactory;

    public const STATUSES = [
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'routed' => 'Routed',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'referred' => 'Referred',
        'merged' => 'Merged',
        'no_action' => 'No Action',
        'emergency_flagged' => 'Emergency Flagged',
        'needs_info' => 'Needs Info',
        'awaiting_confirmation' => 'Awaiting Confirmation',
        'reopened' => 'Reopened',
    ];

    public const NO_ACTION_REASONS = [
        'not_barangay_matter' => 'Not a barangay matter',
        'emergency_redirected' => 'Emergency redirected to 911 / hotlines',
        'cannot_resolve' => 'Cannot be resolved',
        'invalid_report' => 'Invalid / Spam / Test report',
        'resident_unreachable' => 'Resident unreachable',
    ];

    public const UPDATE_TYPES = [
        'inspection_scheduled' => [
            'label' => 'Inspection scheduled',
            'status' => 'in_progress',
            'template' => 'An inspection has been scheduled for this report. Barangay staff will assess the site.',
            'message_required' => false,
        ],
        'work_started' => [
            'label' => 'Work started / Crew dispatched',
            'status' => 'in_progress',
            'template' => 'Our crew has been assigned to your report and will begin work soon.',
            'message_required' => false,
        ],
        'waiting_materials' => [
            'label' => 'Waiting on materials/equipment',
            'status' => 'in_progress',
            'template' => 'Work is temporarily on hold while waiting for required materials or equipment to arrive.',
            'message_required' => false,
        ],
        'needs_resident_input' => [
            'label' => 'Needs resident input',
            'status' => 'needs_info',
            'template' => 'We need additional information from you to proceed with this report. Please provide more details.',
            'message_required' => true,
        ],
        'referred_agency' => [
            'label' => 'Referred to another office/agency',
            'status' => 'referred',
            'template' => 'This matter has been referred to the appropriate government office/agency for further action.',
            'message_required' => true,
        ],
        'cannot_resolve' => [
            'label' => 'Cannot be resolved',
            'status' => 'no_action',
            'template' => 'This report cannot be resolved by the barangay at this time. Please see the explanation above.',
            'message_required' => true,
        ],
    ];

    public const STATUS_GROUPS = [
        'all' => [
            'label' => 'All',
            'statuses' => null,
        ],
        'new' => [
            'label' => 'New',
            'statuses' => ['submitted', 'under_review'],
        ],
        'active' => [
            'label' => 'Active',
            'statuses' => ['routed', 'in_progress', 'needs_info', 'awaiting_confirmation', 'reopened'],
        ],
        'resolved' => [
            'label' => 'Resolved',
            'statuses' => ['resolved'],
        ],
        'closed-other' => [
            'label' => 'Others',
            'statuses' => ['referred', 'merged', 'no_action'],
        ],
    ];

    public const MANUAL_STATUSES = [
        'under_review',
        'routed',
        'in_progress',
        'needs_info',
        'awaiting_confirmation',
        'resolved',
        'no_action',
    ];

    public const CLOSED_STATUSES = [
        'resolved',
        'no_action',
        'merged',
        'referred',
    ];

    protected $fillable = [
        'tracking_code',
        'status',
        'no_action_reason',
        'description',
        'staff_description',
        'photo_mismatch',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'photo_path',
        'lat',
        'lng',
        'purok_id',
        'landmark',
        'reporter_email',
        'reporter_name',
        'reporter_contact',
        'status',
        'ai_status',
        'category_id',
        'office_id',
        'urgency',
        'resident_category_id',
        'resident_purok_id',
        'resident_landmark',
        'summary',
        'is_emergency',
        'priority_score',
        'merged_into_id',
        'resolved_at',
        'report_type',
        'answers',
        'is_ongoing',
        'is_recurring',
        'people_at_risk',
        'is_anonymous',
        'preferred_contact',
        'consent_at',
        'assigned_to',
        'due_at',
        'escalated_at',
        'resolution_confirmed_at',
        'reopened_count',
    ];

    protected function casts(): array
    {
        return [
            'is_emergency' => 'boolean',
            'photo_mismatch' => 'boolean',
            'lat' => 'float',
            'lng' => 'float',
            'resolved_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'answers' => 'array',
            'is_ongoing' => 'boolean',
            'is_recurring' => 'boolean',
            'people_at_risk' => 'boolean',
            'is_anonymous' => 'boolean',
            'consent_at' => 'datetime',
            'due_at' => 'datetime',
            'escalated_at' => 'datetime',
            'resolution_confirmed_at' => 'datetime',
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::CLOSED_STATUSES);
    }

    public function purok(): BelongsTo
    {
        return $this->belongsTo(Purok::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function residentCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'resident_category_id');
    }

    public function residentPurok(): BelongsTo
    {
        return $this->belongsTo(Purok::class, 'resident_purok_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }

    public function aiResult(): HasOne
    {
        return $this->hasOne(ReportAiResult::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(StatusLog::class)->oldest();
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function proofPhotos(): HasMany
    {
        return $this->hasMany(ReportProofPhoto::class)->oldest('id');
    }

    public function activeReferral(): HasOne
    {
        return $this->hasOne(Referral::class)
            ->whereNotIn('status', ['closed'])
            ->latestOfMany('id');
    }

    public function duplicateSuggestions(): HasMany
    {
        return $this->hasMany(DuplicateSuggestion::class, 'report_id');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(InternalNote::class);
    }

    public function infoRequests(): HasMany
    {
        return $this->hasMany(InfoRequest::class);
    }

    public function resolutionProofs(): HasMany
    {
        return $this->hasMany(ResolutionProof::class);
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ReportPhoto::class);
    }

    public function getRecommendedOffice(): ?Office
    {
        if ($this->office_id) {
            return $this->office;
        }

        $category = $this->category;
        if ($category) {
            $rule = RoutingRule::query()
                ->where('category_id', $category->id)
                ->first();

            return $rule?->office;
        }

        return null;
    }

    public static function generateTrackingCode(): string
    {
        do {
            $code = 'BRG-'.strtoupper(Str::random(6));
        } while (static::where('tracking_code', $code)->exists());

        return $code;
    }
}
