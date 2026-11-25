<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            $table->integer('sequence_no')->default(1)->after('summons_id');
            $table->timestamp('superseded_at')->nullable()->after('outcome_note');
            $table->text('superseded_reason')->nullable()->after('superseded_at');
            $table->boolean('no_summons_flag')->default(false)->after('superseded_reason');
        });

        Schema::table('summons', function (Blueprint $table) {
            $table->foreignId('hearing_id')->nullable()->after('case_id')->constrained('hearings')->nullOnDelete();
            $table->string('service_method')->nullable()->after('served_on');
            $table->string('served_by')->nullable()->after('service_method');
            $table->string('received_by_name')->nullable()->after('served_by');
            $table->string('received_by_relationship')->nullable()->after('received_by_name');
            $table->text('service_notes')->nullable()->after('received_by_relationship');
            $table->text('service_skipped_reason')->nullable()->after('service_notes');
        });

        $this->backfillHearingSummonsChain();
    }

    protected function backfillHearingSummonsChain(): void
    {
        $linkedExisting = 0;
        $createdHearings = 0;
        $createdSummons = 0;
        $flaggedNoSummons = 0;

        // 1. Link existing hearings that already have summons_id -> summons.hearing_id
        $hearingsWithSummons = DB::table('hearings')->whereNotNull('summons_id')->get();
        foreach ($hearingsWithSummons as $h) {
            DB::table('summons')->where('id', $h->summons_id)->whereNull('hearing_id')->update([
                'hearing_id' => $h->id,
            ]);
            $linkedExisting++;
        }

        // 2. Backfill summons that still have no hearing_id
        $orphanSummons = DB::table('summons')->whereNull('hearing_id')->orderBy('id')->get();
        foreach ($orphanSummons as $s) {
            $hearingId = DB::table('hearings')
                ->where('case_id', $s->case_id)
                ->where('summons_id', $s->id)
                ->value('id');

            if (! $hearingId) {
                $seq = (int) ($s->sequence_no ?: 1);
                $hearingId = DB::table('hearings')->insertGetId([
                    'case_id' => $s->case_id,
                    'summons_id' => $s->id,
                    'sequence_no' => $seq,
                    'scheduled_at' => $s->hearing_at ?? $s->issued_on ?? $s->created_at ?? now(),
                    'venue' => $s->venue ?? 'Barangay Calarian Hall (Lupon Mediation Room)',
                    'status' => $s->superseded_at ? 'reset' : 'scheduled',
                    'superseded_at' => $s->superseded_at,
                    'superseded_reason' => $s->superseded_at ? ($s->void_note ?? 'Backfilled superseded record') : null,
                    'created_at' => $s->created_at ?? now(),
                    'updated_at' => now(),
                ]);
                $createdHearings++;
            }

            // Copy case-level service details onto summons if missing
            $case = DB::table('cases')->where('id', $s->case_id)->first();
            $serviceMethod = match ($s->service_result) {
                'received' => 'personal',
                'substituted' => 'substituted',
                'refused' => 'refused',
                'not_found' => 'not_served',
                default => null,
            };

            DB::table('summons')->where('id', $s->id)->update([
                'hearing_id' => $hearingId,
                'service_method' => $serviceMethod ?? $case?->summons_service_status,
                'served_by' => $case?->summons_served_by,
                'received_by_name' => $case?->summons_recipient_name,
                'received_by_relationship' => $case?->summons_recipient_relation,
                'service_notes' => $s->remarks,
            ]);
        }

        // 3. Backfill hearings that still have no summons_id
        $orphanHearings = DB::table('hearings')->whereNull('summons_id')->orderBy('id')->get();
        $activeStatuses = ['filed', 'in_mediation', 'in_conciliation'];

        foreach ($orphanHearings as $h) {
            $case = DB::table('cases')->where('id', $h->case_id)->first();
            if ($case && in_array($case->status, $activeStatuses, true)) {
                $respondentId = DB::table('case_parties')
                    ->where('case_id', $case->id)
                    ->where('role', 'respondent')
                    ->value('id');

                if (! $respondentId) {
                    $partyId = DB::table('parties')->insertGetId([
                        'full_name' => 'Respondent (Unassigned)',
                        'residency_status' => 'unverified',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $respondentId = DB::table('case_parties')->insertGetId([
                        'case_id' => $case->id,
                        'party_id' => $partyId,
                        'role' => 'respondent',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $maxSeq = (int) DB::table('summons')->where('case_id', $case->id)->max('sequence_no');
                $seq = $maxSeq + 1;
                $summonsNumber = "SUMMONS-{$case->case_number}-#{$seq}";

                // Ensure uniqueness if a summons_number collision occurs
                if (DB::table('summons')->where('summons_number', $summonsNumber)->exists()) {
                    $summonsNumber .= '-H'.$h->id;
                }

                $summonsId = DB::table('summons')->insertGetId([
                    'case_id' => $case->id,
                    'hearing_id' => $h->id,
                    'respondent_case_party_id' => $respondentId,
                    'sequence_no' => $seq,
                    'summons_number' => $summonsNumber,
                    'issued_on' => $h->created_at ? date('Y-m-d', strtotime((string) $h->created_at)) : now()->toDateString(),
                    'issued_by' => $case->encoded_by ?? 1,
                    'hearing_at' => $h->scheduled_at,
                    'venue' => $h->venue ?? 'Barangay Calarian Hall (Lupon Mediation Room)',
                    'service_result' => 'pending',
                    'attendance' => 'pending',
                    'created_at' => $h->created_at ?? now(),
                    'updated_at' => now(),
                ]);

                DB::table('hearings')->where('id', $h->id)->update([
                    'summons_id' => $summonsId,
                    'sequence_no' => $seq,
                ]);
                $createdSummons++;
            } else {
                DB::table('hearings')->where('id', $h->id)->update([
                    'no_summons_flag' => true,
                ]);
                $flaggedNoSummons++;
            }
        }

        // 4. Normalize sequence numbers per case for hearings
        $caseIds = DB::table('hearings')->distinct()->pluck('case_id');
        foreach ($caseIds as $cid) {
            $caseHearings = DB::table('hearings')->where('case_id', $cid)->orderBy('id')->get();
            $seq = 1;
            foreach ($caseHearings as $ch) {
                DB::table('hearings')->where('id', $ch->id)->update(['sequence_no' => $seq]);
                if ($ch->summons_id) {
                    DB::table('summons')->where('id', $ch->summons_id)->update(['sequence_no' => $seq]);
                }
                $seq++;
            }
        }

        Log::info('Patch 2 Hearing-Summons backfill completed', [
            'linked_existing' => $linkedExisting,
            'created_hearings' => $createdHearings,
            'created_summons' => $createdSummons,
            'flagged_no_summons' => $flaggedNoSummons,
        ]);
    }

    public function down(): void
    {
        Schema::table('summons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hearing_id');
            $table->dropColumn([
                'service_method',
                'served_by',
                'received_by_name',
                'received_by_relationship',
                'service_notes',
                'service_skipped_reason',
            ]);
        });

        Schema::table('hearings', function (Blueprint $table) {
            $table->dropColumn([
                'sequence_no',
                'superseded_at',
                'superseded_reason',
                'no_summons_flag',
            ]);
        });
    }
};
