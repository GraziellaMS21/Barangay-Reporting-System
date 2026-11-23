<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add Phase 0.5 fields to cases table
        Schema::table('cases', function (Blueprint $table) {
            $table->string('record_type', 20)->default('complaint')->after('case_type_id'); // blotter, complaint
            $table->date('complaint_started_on')->nullable()->after('filed_on');
            $table->date('endorsed_to_pangkat_on')->nullable()->after('complaint_started_on');
            $table->unsignedTinyInteger('conciliation_extension_days')->default(0)->after('endorsed_to_pangkat_on');
            $table->foreignId('pangkat_chairman_personnel_id')->nullable()->after('assigned_to')->constrained('personnel')->nullOnDelete();
            $table->decimal('settlement_amount', 12, 2)->nullable()->after('settlement_terms');
        });

        // 2. Add Phase 0.5 flags to case_types table
        Schema::table('case_types', function (Blueprint $table) {
            $table->boolean('is_confidential_default')->default(false)->after('sort_order');
            $table->boolean('uses_kp_timeline')->default(true)->after('is_confidential_default');
        });

        // 3. Add Phase 0.5 CFA & complainant request fields to case_escalations table
        Schema::table('case_escalations', function (Blueprint $table) {
            $table->foreignId('requested_by_case_party_id')->nullable()->after('case_id')->constrained('case_parties')->nullOnDelete();
            $table->date('requested_on')->nullable()->after('requested_by_case_party_id');
            $table->string('cfa_number', 60)->nullable()->after('decided_by_personnel_id');
            $table->foreignId('signed_by_personnel_id')->nullable()->after('cfa_number')->constrained('personnel')->nullOnDelete();
        });

        // 4. Create referral_attachments table on private disk
        Schema::create('referral_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_id')->constrained('referrals')->cascadeOnDelete();
            $table->string('label');
            $table->string('path'); // Stored on 'private' disk
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_attachments');

        Schema::table('case_escalations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signed_by_personnel_id');
            $table->dropColumn('cfa_number');
            $table->dropColumn('requested_on');
            $table->dropConstrainedForeignId('requested_by_case_party_id');
        });

        Schema::table('case_types', function (Blueprint $table) {
            $table->dropColumn(['is_confidential_default', 'uses_kp_timeline']);
        });

        Schema::table('cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pangkat_chairman_personnel_id');
            $table->dropColumn([
                'record_type',
                'complaint_started_on',
                'endorsed_to_pangkat_on',
                'conciliation_extension_days',
                'settlement_amount',
            ]);
        });
    }
};
