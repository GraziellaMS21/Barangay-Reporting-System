<?php

namespace App\Services\CaseActions;

use App\Models\BarangayCase;
use App\Models\Summons;
use App\Models\User;
use App\Services\NumberingService;
use App\Support\CaseStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssueSummonsService
{
    public function __construct(
        private NumberingService $numberingService
    ) {}

    public function canIssue(BarangayCase $case): bool
    {
        return $case->record_type === 'complaint'
            && ! in_array($case->status, [
                CaseStatus::SETTLED,
                CaseStatus::ESCALATED,
                CaseStatus::DISMISSED,
                CaseStatus::WITHDRAWN,
            ], true);
    }

    /**
     * @param  array{respondent_case_party_id: int|string, hearing_at: string, venue: string}  $data
     */
    public function issue(BarangayCase $case, User $issuer, array $data): Summons
    {
        return DB::transaction(function () use ($case, $issuer, $data): Summons {
            $lockedCase = BarangayCase::query()
                ->lockForUpdate()
                ->findOrFail($case->getKey());

            if (! $this->canIssue($lockedCase)) {
                throw ValidationException::withMessages([
                    'case' => 'Summons may only be issued for active formal complaints.',
                ]);
            }

            $sequenceNo = ((int) $lockedCase->summons()->max('sequence_no')) + 1;

            return $lockedCase->summons()->create([
                'respondent_case_party_id' => $data['respondent_case_party_id'],
                'sequence_no' => $sequenceNo,
                'summons_number' => $this->numberingService->next('summons', $lockedCase->fiscalYear),
                'issued_on' => today(),
                'issued_by' => $issuer->id,
                'hearing_at' => $data['hearing_at'],
                'venue' => $data['venue'],
            ]);
        });
    }
}
