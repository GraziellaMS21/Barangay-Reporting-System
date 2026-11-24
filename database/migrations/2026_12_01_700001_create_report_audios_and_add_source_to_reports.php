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
            $table->string('source', 20)->default('web')->after('status');
        });

        Schema::create('report_audios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->nullable()->constrained('reports')->cascadeOnDelete();
            $table->uuid('token')->unique();
            $table->string('origin', 20)->default('resident'); // 'resident' or 'staff'
            $table->string('disk_path');
            $table->string('mime', 100);
            $table->unsignedInteger('size_bytes');
            $table->unsignedSmallInteger('duration_seconds')->default(0);
            $table->text('transcript')->nullable();
            $table->boolean('unverified_draft')->default(false);
            $table->string('transcript_language', 20)->nullable();
            $table->string('transcript_status', 30)->default('pending'); // 'pending', 'done', 'needs_verification', 'failed', 'mock'
            $table->text('error')->nullable();
            $table->timestamp('transcribed_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_audios');

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
