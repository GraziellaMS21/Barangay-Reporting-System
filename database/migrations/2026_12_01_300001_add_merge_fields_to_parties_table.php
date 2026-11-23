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
        Schema::table('parties', function (Blueprint $table) {
            $table->foreignId('merged_into_id')->nullable()->after('extra')->constrained('parties')->nullOnDelete();
            $table->timestamp('merged_at')->nullable()->after('merged_into_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropForeign(['merged_into_id']);
            $table->dropColumn(['merged_into_id', 'merged_at']);
        });
    }
};
