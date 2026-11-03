<?php

namespace App\Services;

use App\Models\Report;
use App\Models\SensitivePlace;
use App\Models\Setting;

class PriorityService
{
    public function calculate(Report $report): int
    {
        if ($report->is_emergency) {
            return 0;
        }

        $weights = $this->weights();
        $score = 0;

        $score += $this->urgencyPoints($report->urgency ?? 'Medium') * ($weights['urgency'] ?? 1);

        if ($report->category) {
            $score += (int) $report->category->risk_weight * ($weights['category'] ?? 1);
        }

        if ($this->nearSensitivePlace($report)) {
            $score += (int) ($weights['sensitive_place'] ?? 25);
        }

        $score += $this->supportingReports($report) * (int) ($weights['supporting_reports'] ?? 8);
        $score += $this->ageInDays($report) * (int) ($weights['age_days'] ?? 2);

        return max(0, $score);
    }

    public function weights(): array
    {
        return [
            'urgency' => (int) Setting::value('priority.urgency', 20),
            'category' => (int) Setting::value('priority.category', 10),
            'sensitive_place' => (int) Setting::value('priority.sensitive_place', 25),
            'supporting_reports' => (int) Setting::value('priority.supporting_reports', 8),
            'age_days' => (int) Setting::value('priority.age_days', 2),
        ];
    }

    protected function urgencyPoints(?string $urgency): int
    {
        return match (strtoupper((string) $urgency)) {
            'CRITICAL' => 200,
            'HIGH' => 120,
            'MEDIUM' => 80,
            'LOW' => 30,
            default => 40,
        };
    }

    protected function nearSensitivePlace(Report $report): bool
    {
        if ($report->lat === null || $report->lng === null) {
            return false;
        }

        return SensitivePlace::query()->get()->some(function ($place) use ($report) {
            $distance = $this->distanceInMeters($report->lat, $report->lng, $place->lat, $place->lng);

            return $distance <= 150;
        });
    }

    protected function supportingReports(Report $report): int
    {
        return (int) $report->duplicateSuggestions()->where('decision', 'merged')->count();
    }

    protected function ageInDays(Report $report): int
    {
        return max(0, $report->created_at?->diffInDays(now()) ?? 0);
    }

    protected function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lngDelta / 2) * sin($lngDelta / 2);

        return 2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a));
    }
}
