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
        Schema::table('complaint_intakes', function (Blueprint $table) {
            // Verbatim natural language resident inputs
            $table->text('incident_narrative')->nullable()->after('respondent_contact');
            $table->text('rights_and_relief')->nullable()->after('incident_narrative');

            // AI suggestions & provenance
            $table->json('ai_suggestions')->nullable()->after('case_type_suggestion');
            $table->string('ai_status', 30)->default('pending')->after('ai_suggestions');
            $table->string('ai_confidence', 20)->nullable()->after('ai_status');
            $table->string('detected_language', 50)->nullable()->after('ai_confidence');
            $table->json('field_provenance')->nullable()->after('detected_language');

            // Make previous structured fields nullable to accommodate natural language intake before extraction
            $table->text('what_happened')->nullable()->change();
            $table->text('who_involved')->nullable()->change();
            $table->dateTime('when_happened')->nullable()->change();
            $table->text('where_happened')->nullable()->change();
            $table->text('why_how_happened')->nullable()->change();
            $table->text('rights_violated')->nullable()->change();
            $table->text('relief_requested')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaint_intakes', function (Blueprint $table) {
            $table->dropColumn([
                'incident_narrative',
                'rights_and_relief',
                'ai_suggestions',
                'ai_status',
                'ai_confidence',
                'detected_language',
                'field_provenance',
            ]);
        });
    }
};
