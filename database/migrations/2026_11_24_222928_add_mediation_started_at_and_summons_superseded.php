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
        Schema::table('cases', function (Blueprint $table) {
            if (! Schema::hasColumn('cases', 'mediation_started_at')) {
                $table->timestamp('mediation_started_at')->nullable();
            }
        });

        Schema::table('summons', function (Blueprint $table) {
            if (! Schema::hasColumn('summons', 'superseded_at')) {
                $table->timestamp('superseded_at')->nullable()->after('void_note');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropColumn('mediation_started_at');
        });

        Schema::table('summons', function (Blueprint $table) {
            $table->dropColumn('superseded_at');
        });
    }
};
