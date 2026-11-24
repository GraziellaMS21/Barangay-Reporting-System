<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_intakes', function (Blueprint $table) {
            $table->id();
            $table->string('complainant_name');
            $table->string('complainant_alias', 100)->nullable();
            $table->unsignedSmallInteger('complainant_age')->nullable();
            $table->string('complainant_contact', 50);
            $table->text('complainant_address');
            $table->string('complainant_residency_claim', 50)->default('resident');

            // PII & Respondent Isolation: Free-text only; NEVER linked to parties table on submission
            $table->string('respondent_name');
            $table->string('respondent_alias', 100)->nullable();
            $table->text('respondent_address');
            $table->string('respondent_contact', 50)->nullable();

            // 5Ws Narrative
            $table->text('what_happened');
            $table->text('who_involved');
            $table->dateTime('when_happened');
            $table->text('where_happened');
            $table->text('why_how_happened');

            // Legal Grounds & Relief
            $table->text('rights_violated');
            $table->text('relief_requested');
            $table->string('case_type_suggestion', 100)->nullable();

            $table->boolean('confidentiality_screen_passed')->default(true);
            $table->timestamp('consent_at');

            // Intake Workflow
            $table->string('status', 30)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('outcome_reason')->nullable();
            $table->foreignId('case_id')->nullable()->constrained('cases')->nullOnDelete();

            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_intakes');
    }
};
