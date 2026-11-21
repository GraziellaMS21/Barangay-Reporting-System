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
        // 1. Case Types
        Schema::create('case_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();
        });

        // 2. Parties
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->index();
            $table->string('alias')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('sex')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('occupation')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('address_line')->nullable();
            $table->foreignId('purok_id')->nullable()->constrained('puroks')->nullOnDelete();
            $table->string('home_barangay')->nullable();
            $table->string('home_city')->nullable();
            $table->string('residency_status')->default('unverified'); // resident, non_resident, unverified
            $table->string('residency_basis')->nullable();
            $table->foreignId('residency_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('residency_verified_at')->nullable();
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();
        });

        // 3. Cases (Model: BarangayCase)
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years');
            $table->foreignId('case_type_id')->nullable()->constrained('case_types')->nullOnDelete();
            $table->string('source')->default('walk_in'); // walk_in, online_report, referral, other
            $table->foreignId('source_report_id')->nullable()->constrained('reports')->nullOnDelete();
            $table->string('title');
            $table->text('narrative');
            $table->date('filed_on');
            $table->date('incident_date')->nullable();
            $table->text('incident_location')->nullable();
            $table->foreignId('purok_id')->nullable()->constrained('puroks')->nullOnDelete();
            $table->string('status')->default('filed')->index();
            $table->foreignId('encoded_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('settled_on')->nullable();
            $table->text('settlement_terms')->nullable();
            $table->text('outcome_note')->nullable();
            $table->boolean('is_confidential')->default(false); // SPEC_ADDENDUM.md section 1.2 default false
            $table->timestamps();
        });

        // 4. Case Parties
        Schema::create('case_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('role'); // complainant, respondent, witness
            $table->string('residency_at_filing')->nullable();
            $table->string('representative_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['case_id', 'party_id', 'role']);
        });

        // 5. Case Status Logs
        Schema::create('case_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // 6. Case Notes
        Schema::create('case_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->text('body');
            $table->timestamps();
        });

        // 7. Case Field Configs
        Schema::create('case_field_configs', function (Blueprint $table) {
            $table->id();
            $table->string('entity'); // party, case, summons
            $table->string('field_key');
            $table->string('label');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->string('applies_to')->default('all'); // all, resident, non_resident
            $table->integer('sort_order')->default(0);
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();

            $table->unique(['entity', 'field_key']);
        });

        // 8. Summons
        Schema::create('summons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('respondent_case_party_id')->constrained('case_parties')->cascadeOnDelete();
            $table->integer('sequence_no');
            $table->string('summons_number')->unique()->nullable();
            $table->date('issued_on');
            $table->foreignId('issued_by')->constrained('users');
            $table->timestamp('hearing_at')->nullable();
            $table->string('venue')->nullable();
            $table->foreignId('served_by_personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->date('served_on')->nullable();
            $table->string('service_result')->default('pending'); // pending, received, refused, not_found, other
            $table->string('attendance')->default('pending'); // pending, appeared, no_show, rescheduled
            $table->text('remarks')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_note')->nullable();
            $table->text('override_note')->nullable();
            $table->timestamps();
        });

        // 9. Hearings
        Schema::create('hearings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('summons_id')->nullable()->constrained('summons')->nullOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('venue')->nullable();
            $table->foreignId('presided_by_personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->string('status')->default('scheduled'); // scheduled, held, cancelled, reset
            $table->text('outcome_note')->nullable();
            $table->timestamps();
        });

        // 10. Escalation Destinations
        Schema::create('escalation_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();
        });

        // 11. Case Escalations
        Schema::create('case_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('escalation_destination_id')->constrained('escalation_destinations');
            $table->foreignId('escalated_by')->constrained('users');
            $table->foreignId('decided_by_personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->text('reason');
            $table->text('override_note')->nullable();
            $table->integer('summons_count_at_time')->default(0);
            $table->foreignId('referral_id')->nullable()->constrained('referrals')->nullOnDelete();
            $table->timestamp('escalated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_escalations');
        Schema::dropIfExists('escalation_destinations');
        Schema::dropIfExists('hearings');
        Schema::dropIfExists('summons');
        Schema::dropIfExists('case_field_configs');
        Schema::dropIfExists('case_notes');
        Schema::dropIfExists('case_status_logs');
        Schema::dropIfExists('case_parties');
        Schema::dropIfExists('cases');
        Schema::dropIfExists('parties');
        Schema::dropIfExists('case_types');
    }
};
