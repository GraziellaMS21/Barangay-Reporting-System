<?php

namespace App\Services;

use App\Models\DuplicateSuggestion;
use App\Models\Report;
use Illuminate\Support\Collection;

class DuplicateService
{
    public function findMatches(Report $report): Collection
    {
        if (! $report->category_id || ! $report->lat || ! $report->lng) {
            return collect();
        }

        $threshold = now()->subDays(14);

        return Report::query()
            ->where('id', '!=', $report->id)
            ->where('category_id', $report->category_id)
            ->where('status', '!=', 'merged')
            ->where('created_at', '>=', $threshold)
            ->get()
            ->filter(function (Report $candidate) use ($report) {
                $distance = $this->haversineDistance($report->lat, $report->lng, $candidate->lat, $candidate->lng);
                $textSimilarity = 0.0;

                if ($report->description && $candidate->description) {
                    similar_text(strtolower($report->description), strtolower($candidate->description), $textSimilarity);
                }

                return $distance <= 150 && $textSimilarity >= 45;
            })
            ->map(function (Report $candidate) use ($report) {
                $distance = $this->haversineDistance($report->lat, $report->lng, $candidate->lat, $candidate->lng);
                $similarity = 0.0;
                similar_text(strtolower($report->description ?? ''), strtolower($candidate->description ?? ''), $similarity);

                return DuplicateSuggestion::firstOrCreate(
                    [
                        'report_id' => $report->id,
                        'candidate_id' => $candidate->id,
                    ],
                    [
                        'score' => min(1, max(0, $similarity / 100)),
                        'decision' => 'pending',
                    ]
                );
            });
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
