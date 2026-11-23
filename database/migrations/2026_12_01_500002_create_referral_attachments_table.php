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
        if (! Schema::hasTable('referral_attachments')) {
            Schema::create('referral_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('referral_id')->constrained('referrals')->cascadeOnDelete();
                $table->string('label');
                $table->string('path');
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_attachments');
    }
};
