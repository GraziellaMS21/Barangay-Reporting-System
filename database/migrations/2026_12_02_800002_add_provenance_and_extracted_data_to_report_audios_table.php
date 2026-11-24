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
        Schema::table('report_audios', function (Blueprint $table) {
            if (! Schema::hasColumn('report_audios', 'extracted_data')) {
                $table->json('extracted_data')->nullable()->after('transcript_status');
            }
            if (! Schema::hasColumn('report_audios', 'ai_description')) {
                $table->text('ai_description')->nullable()->after('extracted_data');
            }
            if (! Schema::hasColumn('report_audios', 'provenance')) {
                $table->json('provenance')->nullable()->after('ai_description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_audios', function (Blueprint $table) {
            $table->dropColumn(['extracted_data', 'ai_description', 'provenance']);
        });
    }
};
