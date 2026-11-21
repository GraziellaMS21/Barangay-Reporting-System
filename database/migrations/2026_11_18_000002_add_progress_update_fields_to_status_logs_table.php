<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('status_logs', function (Blueprint $table) {
            $table->string('update_type')->nullable()->after('to_status');
            $table->date('expected_completion_date')->nullable()->after('update_type');
            $table->boolean('visible_to_resident')->default(true)->after('expected_completion_date');
            $table->foreignId('office_id')->nullable()->after('visible_to_resident')->constrained('offices')->nullOnDelete();
            $table->json('attachments')->nullable()->after('office_id');
        });
    }

    public function down(): void
    {
        Schema::table('status_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('office_id');
            $table->dropColumn([
                'update_type',
                'expected_completion_date',
                'visible_to_resident',
                'attachments',
            ]);
        });
    }
};
