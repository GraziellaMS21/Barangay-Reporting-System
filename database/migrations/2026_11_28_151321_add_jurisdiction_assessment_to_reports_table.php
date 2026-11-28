<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('jurisdiction_status', 32)->nullable()->after('landmark');
            $table->text('jurisdiction_note')->nullable()->after('jurisdiction_status');
            $table->foreignId('jurisdiction_assessed_by')->nullable()->after('jurisdiction_note')->constrained('users')->nullOnDelete();
            $table->timestamp('jurisdiction_assessed_at')->nullable()->after('jurisdiction_assessed_by');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jurisdiction_assessed_by');
            $table->dropColumn(['jurisdiction_status', 'jurisdiction_note', 'jurisdiction_assessed_at']);
        });
    }
};
