<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            if (! Schema::hasColumn('cases', 'complaint_printed_at')) {
                $table->timestamp('complaint_printed_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('cases', 'complaint_signed_received_at')) {
                $table->timestamp('complaint_signed_received_at')->nullable()->after('complaint_printed_at');
            }
            if (! Schema::hasColumn('cases', 'complaint_signed_received_by')) {
                $table->string('complaint_signed_received_by')->nullable()->after('complaint_signed_received_at');
            }
            if (! Schema::hasColumn('cases', 'complaint_signed_document_path')) {
                $table->string('complaint_signed_document_path')->nullable()->after('complaint_signed_received_by');
            }

            if (! Schema::hasColumn('cases', 'notices_issued_at')) {
                $table->timestamp('notices_issued_at')->nullable()->after('complaint_signed_document_path');
            }
            if (! Schema::hasColumn('cases', 'summons_service_status')) {
                $table->string('summons_service_status')->nullable()->after('notices_issued_at');
            }
            if (! Schema::hasColumn('cases', 'summons_served_at')) {
                $table->timestamp('summons_served_at')->nullable()->after('summons_service_status');
            }
            if (! Schema::hasColumn('cases', 'summons_served_by')) {
                $table->string('summons_served_by')->nullable()->after('summons_served_at');
            }
            if (! Schema::hasColumn('cases', 'summons_recipient_name')) {
                $table->string('summons_recipient_name')->nullable()->after('summons_served_by');
            }
            if (! Schema::hasColumn('cases', 'summons_recipient_relation')) {
                $table->string('summons_recipient_relation')->nullable()->after('summons_recipient_name');
            }

            if (! Schema::hasColumn('cases', 'mediation_outcome')) {
                $table->string('mediation_outcome')->nullable()->after('summons_recipient_relation');
            }
            if (! Schema::hasColumn('cases', 'repudiation_ends_at')) {
                $table->timestamp('repudiation_ends_at')->nullable()->after('settled_on');
            }
            if (! Schema::hasColumn('cases', 'repudiated_at')) {
                $table->timestamp('repudiated_at')->nullable()->after('repudiation_ends_at');
            }
            if (! Schema::hasColumn('cases', 'repudiation_ground')) {
                $table->string('repudiation_ground')->nullable()->after('repudiated_at');
            }
            if (! Schema::hasColumn('cases', 'repudiated_by')) {
                $table->string('repudiated_by')->nullable()->after('repudiation_ground');
            }
            if (! Schema::hasColumn('cases', 'repudiation_document_path')) {
                $table->string('repudiation_document_path')->nullable()->after('repudiated_by');
            }

            if (! Schema::hasColumn('cases', 'court_transmitted_at')) {
                $table->timestamp('court_transmitted_at')->nullable()->after('repudiation_document_path');
            }
            if (! Schema::hasColumn('cases', 'cfa_issued_at')) {
                $table->timestamp('cfa_issued_at')->nullable()->after('court_transmitted_at');
            }
        });

        // Clean up any literal 'Pending AI review' in existing database
        if (Schema::hasTable('complaint_intakes')) {
            $intakeCols = ['what_happened', 'who_involved', 'when_happened', 'where_happened', 'why_how_happened', 'rights_violated', 'relief_requested'];
            foreach ($intakeCols as $col) {
                if (Schema::hasColumn('complaint_intakes', $col)) {
                    DB::table('complaint_intakes')
                        ->where($col, 'like', '%Pending AI review%')
                        ->update([$col => null]);
                }
            }
        }

        if (Schema::hasTable('cases')) {
            $nullableCaseCols = ['incident_location', 'settlement_terms', 'outcome_note'];
            foreach ($nullableCaseCols as $col) {
                if (Schema::hasColumn('cases', $col)) {
                    DB::table('cases')
                        ->where($col, 'like', '%Pending AI review%')
                        ->update([$col => null]);
                }
            }
            if (Schema::hasColumn('cases', 'narrative')) {
                DB::table('cases')
                    ->where('narrative', 'like', '%Pending AI review%')
                    ->update(['narrative' => 'Intake narrative recorded.']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn([
                'complaint_printed_at',
                'complaint_signed_received_at',
                'complaint_signed_received_by',
                'complaint_signed_document_path',
                'notices_issued_at',
                'summons_service_status',
                'summons_served_at',
                'summons_served_by',
                'summons_recipient_name',
                'summons_recipient_relation',
                'mediation_outcome',
                'repudiation_ends_at',
                'repudiated_at',
                'repudiation_ground',
                'repudiated_by',
                'repudiation_document_path',
                'court_transmitted_at',
                'cfa_issued_at',
            ]);
        });
    }
};
