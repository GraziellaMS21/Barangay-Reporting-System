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
        Schema::table('referrals', function (Blueprint $table) {
            $table->foreignId('report_id')->nullable()->change();
            if (! Schema::hasColumn('referrals', 'case_id')) {
                $table->foreignId('case_id')->nullable()->after('report_id')->constrained('cases')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            if (Schema::hasColumn('referrals', 'case_id')) {
                $table->dropConstrainedForeignId('case_id');
            }
        });
    }
};
