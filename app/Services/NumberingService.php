<?php

namespace App\Services;

use App\Models\FiscalYear;
use App\Models\NumberSequence;
use Illuminate\Support\Facades\DB;

class NumberingService
{
    public function __construct(
        protected FiscalYearService $fiscalYearService,
        protected SettingsService $settings
    ) {}

    public function next(string $type, ?FiscalYear $fy = null): string
    {
        $fiscalYear = $fy ?? $this->fiscalYearService->forNewRecord();
        $format = $this->getFormat($type);

        return DB::transaction(function () use ($type, $fiscalYear, $format) {
            $sequence = NumberSequence::where('type', $type)
                ->where('fiscal_year_id', $fiscalYear->id)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = NumberSequence::create([
                    'type' => $type,
                    'fiscal_year_id' => $fiscalYear->id,
                    'last_number' => 0,
                ]);
            }

            $newNumber = $sequence->last_number + 1;
            $sequence->update(['last_number' => $newNumber]);

            return $this->formatNumber($format, $fiscalYear, $newNumber);
        });
    }

    public function preview(string $type, ?FiscalYear $fy = null): string
    {
        $fiscalYear = $fy ?? $this->fiscalYearService->forNewRecord();
        $format = $this->getFormat($type);

        $sequence = NumberSequence::where('type', $type)
            ->where('fiscal_year_id', $fiscalYear->id)
            ->first();

        $previewNumber = ($sequence?->last_number ?? 0) + 1;

        return $this->formatNumber($format, $fiscalYear, $previewNumber);
    }

    protected function getFormat(string $type): string
    {
        $default = match ($type) {
            'referral' => 'REF-{YEAR}-{SEQ:4}',
            'case' => 'CASE-{YEAR}-{SEQ:5}',
            'blotter' => '{MM}-{SEQ:3}-{YEAR}',
            'summons' => 'SUM-{YEAR}-{SEQ:4}',
            default => strtoupper($type).'-{YEAR}-{SEQ:4}',
        };

        return (string) $this->settings->get("numbering.{$type}.format", $default);
    }

    public function isValidFormat(string $format): bool
    {
        if (! preg_match('/\{SEQ:([1-8])\}/', $format)) {
            return false;
        }

        // Must not contain invalid characters
        $stripped = preg_replace('/\{(YEAR|YY|MM|SEQ:[1-8])\}/', '', $format);

        return preg_match('/^[a-zA-Z0-9_\-\.\/]*$/', $stripped) === 1;
    }

    protected function formatNumber(string $format, FiscalYear $fy, int $number, ?string $date = null): string
    {
        $year = (string) $fy->year;
        $yy = substr($year, -2);
        $month = $date ? date('m', strtotime($date)) : now()->format('m');

        $formatted = str_replace(
            ['{YEAR}', '{YY}', '{MM}'],
            [$year, $yy, $month],
            $format
        );

        if (preg_match('/\{SEQ:(\d+)\}/', $formatted, $matches)) {
            $digits = (int) $matches[1];
            $padded = str_pad((string) $number, $digits, '0', STR_PAD_LEFT);
            $formatted = str_replace($matches[0], $padded, $formatted);
        } else {
            $formatted .= '-'.$number;
        }

        return $formatted;
    }
}
