<?php

namespace App\Services\Cases;

use App\Models\Party;
use Illuminate\Support\Collection;

class PartyMatchingService
{
    /**
     * Search for possible existing parties that match by name, birthdate, or contact number.
     *
     * @return Collection<int, Party>
     */
    public function findPossibleDuplicates(
        string $fullName,
        ?string $birthDate = null,
        ?string $contactNumber = null,
        ?int $excludePartyId = null
    ): Collection {
        $normalizedInputName = $this->normalizeName($fullName);
        if (empty($normalizedInputName)) {
            return collect();
        }

        $cleanedPhone = $this->cleanPhoneNumber($contactNumber);
        $words = array_filter(explode(' ', trim($fullName)), fn ($w) => strlen(trim($w)) >= 3);

        // Fetch candidate active parties using token matching or birthdate or phone
        $candidates = Party::query()
            ->active()
            ->when($excludePartyId, fn ($q) => $q->where('id', '!=', $excludePartyId))
            ->where(function ($q) use ($fullName, $words, $birthDate, $cleanedPhone) {
                $q->where('full_name', 'like', '%'.trim($fullName).'%')
                    ->orWhere('alias', 'like', '%'.trim($fullName).'%');

                foreach ($words as $word) {
                    $q->orWhere('full_name', 'like', '%'.trim($word).'%');
                }

                if ($birthDate) {
                    $q->orWhere('birth_date', $birthDate);
                }

                if (! empty($cleanedPhone)) {
                    $q->orWhere('contact_number', 'like', '%'.$cleanedPhone.'%');
                }
            })
            ->limit(25)
            ->get();

        return $candidates->filter(function (Party $candidate) use ($normalizedInputName, $birthDate, $cleanedPhone) {
            $candidateName = $this->normalizeName($candidate->full_name);
            $candidateAlias = $candidate->alias ? $this->normalizeName($candidate->alias) : '';

            // 1. Direct name match or alias match
            if ($candidateName === $normalizedInputName || (! empty($candidateAlias) && $candidateAlias === $normalizedInputName)) {
                return true;
            }

            // 2. Levenshtein match for slight typos (distance <= 2 on longer names)
            if (strlen($normalizedInputName) > 5 && strlen($candidateName) > 5) {
                if (levenshtein($normalizedInputName, $candidateName) <= 2) {
                    return true;
                }
            }

            // 3. Same birth date and high name similarity
            if (! empty($birthDate) && $candidate->birth_date?->format('Y-m-d') === $birthDate) {
                similar_text($normalizedInputName, $candidateName, $percent);
                if ($percent >= 60) {
                    return true;
                }
            }

            // 4. Same contact number and high name similarity
            $candidatePhone = $this->cleanPhoneNumber($candidate->contact_number);
            if (! empty($cleanedPhone) && ! empty($candidatePhone) && $cleanedPhone === $candidatePhone) {
                similar_text($normalizedInputName, $candidateName, $percent);
                if ($percent >= 50) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    /**
     * Check if a party input is a likely duplicate.
     */
    public function isLikelyDuplicate(
        string $fullName,
        ?string $birthDate = null,
        ?string $contactNumber = null,
        ?int $excludePartyId = null
    ): bool {
        return $this->findPossibleDuplicates($fullName, $birthDate, $contactNumber, $excludePartyId)->isNotEmpty();
    }

    /**
     * Normalize a name string for comparison.
     */
    public function normalizeName(string $name): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
        $clean = preg_replace('/\s+/', ' ', $clean);

        return strtolower(trim($clean ?? ''));
    }

    /**
     * Normalize phone number to digits only.
     */
    public function cleanPhoneNumber(?string $number): string
    {
        if (empty($number)) {
            return '';
        }

        $digits = preg_replace('/[^0-9]/', '', $number);

        return $digits ?? '';
    }
}
