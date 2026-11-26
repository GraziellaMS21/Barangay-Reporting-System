<?php

namespace App\Services;

use App\Models\DuplicateSuggestion;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DuplicateService
{
    public const LOOKBACK_DAYS = 14;

    public const MAX_DISTANCE_METERS = 150.0;

    public const MIN_SIMILARITY_PERCENT = 45.0;

    private const BOUNDING_BOX_LAT_DEGREES = 0.002;

    /**
     * Find potential duplicate candidates for a report without writing to the database.
     *
     * @return Collection<int, array{candidate: Report, distance_meters: ?float, similarity_score: float, match_type: string}>
     */
    public function findMatches(Report $report): Collection
    {
        $effectiveCategoryId = $report->category_id ?? $report->resident_category_id;
        if (! $effectiveCategoryId) {
            return collect();
        }

        $reportDescription = trim((string) ($report->description ?? ''));
        if ($reportDescription === '') {
            return collect();
        }

        $hasCoordinates = $report->lat !== null && $report->lng !== null;
        if (! $hasCoordinates && ! $report->purok_id) {
            return collect();
        }

        $referenceTime = ($report->created_at ?? now())->copy();
        $windowStart = $referenceTime->copy()->subDays(self::LOOKBACK_DAYS);

        $query = Report::query()
            ->where('id', '!=', $report->id)
            ->where('status', '!=', 'merged')
            ->where('created_at', '>=', $windowStart)
            ->where('created_at', '<=', $referenceTime)
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->where(function (Builder $categoryQuery) use ($effectiveCategoryId): void {
                $categoryQuery->where('category_id', $effectiveCategoryId)
                    ->orWhere(function (Builder $fallbackQuery) use ($effectiveCategoryId): void {
                        $fallbackQuery->whereNull('category_id')
                            ->where('resident_category_id', $effectiveCategoryId);
                    });
            });

        if ($hasCoordinates) {
            $latMargin = self::BOUNDING_BOX_LAT_DEGREES;
            $cosLat = max(0.01, abs(cos(deg2rad((float) $report->lat))));
            $lngMargin = $latMargin / $cosLat;

            $query->whereNotNull('lat')
                ->whereNotNull('lng')
                ->whereBetween('lat', [(float) $report->lat - $latMargin, (float) $report->lat + $latMargin])
                ->whereBetween('lng', [(float) $report->lng - $lngMargin, (float) $report->lng + $lngMargin]);
        } else {
            $query->where('purok_id', $report->purok_id);
        }

        $reportDescriptionLower = strtolower($reportDescription);

        return $query->get()
            ->map(function (Report $candidate) use ($report, $hasCoordinates, $reportDescriptionLower): ?array {
                $candidateDescription = trim((string) ($candidate->description ?? ''));
                if ($candidateDescription === '') {
                    return null;
                }

                $distanceMeters = null;
                if ($hasCoordinates) {
                    $distanceMeters = $this->haversineDistance(
                        (float) $report->lat,
                        (float) $report->lng,
                        $candidate->lat,
                        $candidate->lng
                    );

                    if ($distanceMeters > self::MAX_DISTANCE_METERS) {
                        return null;
                    }
                }

                $similarityPercent = 0.0;
                similar_text($reportDescriptionLower, strtolower($candidateDescription), $similarityPercent);

                if ($similarityPercent < self::MIN_SIMILARITY_PERCENT) {
                    return null;
                }

                return [
                    'candidate' => $candidate,
                    'distance_meters' => $distanceMeters,
                    'similarity_score' => round($similarityPercent, 2),
                    'match_type' => $hasCoordinates ? 'geo' : 'purok',
                ];
            })
            ->filter()
            ->sort(function (array $a, array $b): int {
                $scoreComparison = $b['similarity_score'] <=> $a['similarity_score'];
                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                $distanceA = $a['distance_meters'];
                $distanceB = $b['distance_meters'];

                if ($distanceA === null && $distanceB === null) {
                    return 0;
                }
                if ($distanceA === null) {
                    return 1;
                }
                if ($distanceB === null) {
                    return -1;
                }

                return $distanceA <=> $distanceB;
            })
            ->values();
    }

    /**
     * Persist candidate duplicate suggestions without resetting existing staff decisions.
     *
     * @return Collection<int, DuplicateSuggestion>
     */
    public function recordSuggestions(Report $report): Collection
    {
        $matches = $this->findMatches($report);
        if ($matches->isEmpty()) {
            return collect();
        }

        $candidateIds = $matches
            ->map(fn (array $match): int => $match['candidate']->id)
            ->all();

        $existingByCandidate = DuplicateSuggestion::query()
            ->where('report_id', $report->id)
            ->whereIn('candidate_id', $candidateIds)
            ->get()
            ->keyBy('candidate_id');

        $now = now();

        return $matches->map(function (array $match) use ($report, $existingByCandidate, $now): DuplicateSuggestion {
            /** @var Report $candidate */
            $candidate = $match['candidate'];
            $normalizedScore = round(min(1.0, max(0.0, $match['similarity_score'] / 100)), 3);

            /** @var DuplicateSuggestion|null $existing */
            $existing = $existingByCandidate->get($candidate->id);

            if ($existing !== null) {
                if ($existing->decision === 'pending' && (float) $existing->score !== $normalizedScore) {
                    $existing->update(['score' => $normalizedScore]);
                }

                return $existing;
            }

            DuplicateSuggestion::query()->insertOrIgnore([
                'report_id' => $report->id,
                'candidate_id' => $candidate->id,
                'score' => $normalizedScore,
                'decision' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $suggestion = DuplicateSuggestion::query()
                ->where('report_id', $report->id)
                ->where('candidate_id', $candidate->id)
                ->firstOrFail();

            if ($suggestion->decision === 'pending' && (float) $suggestion->score !== $normalizedScore) {
                $suggestion->update(['score' => $normalizedScore]);
            }

            return $suggestion;
        })->values();
    }

    protected function haversineDistance(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return PHP_FLOAT_MAX;
        }

        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lngDelta / 2) * sin($lngDelta / 2);

        return 2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a)) * 1000;
    }
}
