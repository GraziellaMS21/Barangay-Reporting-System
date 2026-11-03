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
        Schema::table('reports', function (Blueprint $table) {
            Schema::table('reports', function (Blueprint $table): void {
                $table->string('reporter_name', 100)->nullable()->after('reporter_email');
                $table->string('reporter_contact', 20)->nullable()->after('reporter_name');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            Schema::table('reports', function (Blueprint $table): void {
                $table->dropColumn(['reporter_name', 'reporter_contact']);
            });
        });
    }
};
