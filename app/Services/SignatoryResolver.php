<?php

namespace App\Services;

use App\Models\BarangayCase;
use App\Models\Personnel;
use App\Models\SignatoryAssignment;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;

class SignatoryResolver
{
    public const ROLE_LABELS = [
        'prepared_by' => 'Prepared by',
        'noted_by' => 'Noted by',
        'approved_by' => 'Approved by',
        'presided_by' => 'Presided by',
        'attested_by' => 'Attested by',
    ];

    /**
     * Resolve document signatories for printables and official reports.
     * Return shape: [['label' => 'Prepared by', 'name' => '...', 'position' => '...', 'signature_path' => null], ...]
     * Every consumer must render a blank signature line when the array is empty.
     *
     * @return array<int, array{label: string, name: string, position: string, signature_path: ?string, signature_data_uri?: ?string, is_confirmed?: bool}>
     */
    public function forDocument(string $documentType, ?CarbonInterface $asOf = null, ?BarangayCase $case = null): array
    {
        $normalizedType = match ($documentType) {
            'cfa' => 'certificate_to_file_action',
            'settlement' => 'settlement_agreement',
            'hearing_notice' => 'conciliation_notice',
            default => $documentType,
        };

        $assignments = SignatoryAssignment::query()
            ->with('position')
            ->where('document_type', $normalizedType)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($assignments->isEmpty() && $normalizedType !== $documentType) {
            $assignments = SignatoryAssignment::query()
                ->with('position')
                ->where('document_type', $documentType)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        if ($assignments->isEmpty()) {
            return [];
        }

        $date = ($asOf ?? now())->toDateString();
        $resolved = [];

        foreach ($assignments as $assignment) {
            $position = $assignment->position;
            if (! $position) {
                continue;
            }

            $label = self::ROLE_LABELS[strtolower($assignment->role_label)] ?? $assignment->role_label;
            $person = $this->resolvePersonnelForAssignment($normalizedType, $assignment, $date, $case);

            $signatureDataUri = null;
            if ($person && ! empty($person->signature_path)) {
                $signatureDataUri = $this->encodeSignatureBase64($person->signature_path);
            }

            $resolved[] = [
                'label' => $label,
                'name' => $person?->full_name ?? '____________________',
                'position' => $position->name,
                'signature_path' => $person?->signature_path,
                'signature_data_uri' => $signatureDataUri,
                'is_confirmed' => (bool) $assignment->is_confirmed,
            ];
        }

        return $resolved;
    }

    protected function resolvePersonnelForAssignment(
        string $documentType,
        SignatoryAssignment $assignment,
        string $date,
        ?BarangayCase $case
    ): ?Personnel {
        // Case-specific Pangkat Chairman override for Certificate to File Action (REVISION_2 5.4, PLACEHOLDERS B2)
        if (
            $documentType === 'certificate_to_file_action'
            && $case !== null
            && ! empty($case->pangkat_chairman_personnel_id)
            && (
                $assignment->position?->code === 'pangkat_chairman'
                || in_array(strtolower($assignment->role_label), ['approved_by', 'approved by'], true)
            )
        ) {
            $caseChairman = Personnel::query()
                ->with('position')
                ->find($case->pangkat_chairman_personnel_id);

            if ($caseChairman) {
                return $caseChairman;
            }
        }

        // Active incumbent whose term covers $date
        $incumbent = Personnel::query()
            ->where('position_id', $assignment->position_id)
            ->where('is_active', true)
            ->where(function ($query) use ($date): void {
                $query->whereNull('term_start')->orWhereDate('term_start', '<=', $date);
            })
            ->where(function ($query) use ($date): void {
                $query->whereNull('term_end')->orWhereDate('term_end', '>=', $date);
            })
            ->orderByDesc('is_signatory')
            ->orderByDesc('term_start')
            ->orderByDesc('id')
            ->first();

        if ($incumbent) {
            return $incumbent;
        }

        // Fallback to any active personnel holding the position
        return Personnel::query()
            ->where('position_id', $assignment->position_id)
            ->where('is_active', true)
            ->orderByDesc('is_signatory')
            ->orderByDesc('id')
            ->first();
    }

    public function encodeSignatureBase64(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (Storage::disk('private')->exists($path)) {
            $contents = Storage::disk('private')->get($path);

            return 'data:image/png;base64,'.base64_encode($contents);
        }

        return null;
    }
}
