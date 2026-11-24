<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->date('first_meeting_on')->nullable()->after('filed_on');
            $table->json('eligibility_checklist')->nullable()->after('is_confidential');
        });

        Schema::table('hearings', function (Blueprint $table) {
            $table->string('complainant_attendance', 20)->default('pending')->after('status');
            $table->string('respondent_attendance', 20)->default('pending')->after('complainant_attendance');
            $table->boolean('respondent_barred_counterclaim')->default(false)->after('respondent_attendance');
        });
    }

    public function down(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            $table->dropColumn([
                'complainant_attendance',
                'respondent_attendance',
                'respondent_barred_counterclaim',
            ]);
        });

        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn([
                'first_meeting_on',
                'eligibility_checklist',
            ]);
        });
    }
};
