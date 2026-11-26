<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('duplicate_suggestions')
            ->select('report_id', 'candidate_id', DB::raw('COUNT(*) as pair_count'))
            ->groupBy('report_id', 'candidate_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $pairs = $duplicates
                ->map(fn (object $row): string => "({$row->report_id}, {$row->candidate_id}) x{$row->pair_count}")
                ->implode(', ');

            throw new RuntimeException(
                "Cannot add unique index duplicate_suggestions_report_candidate_unique: duplicate (report_id, candidate_id) pairs exist: {$pairs}."
            );
        }

        Schema::table('duplicate_suggestions', function (Blueprint $table): void {
            $table->unique(['report_id', 'candidate_id'], 'duplicate_suggestions_report_candidate_unique');
        });
    }

    public function down(): void
    {
        Schema::table('duplicate_suggestions', function (Blueprint $table): void {
            $table->dropUnique('duplicate_suggestions_report_candidate_unique');
        });
    }
};
