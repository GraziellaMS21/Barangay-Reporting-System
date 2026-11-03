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
        Schema::create('report_ai_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('suggested_category')->nullable();
            $table->string('suggested_urgency')->nullable();
            $table->text('summary')->nullable();
            $table->text('photo_description')->nullable();
            $table->boolean('photo_mismatch')->nullable();
            $table->boolean('emergency_flag')->nullable();
            $table->string('location_details')->nullable();
            $table->json('key_facts')->nullable();
            $table->decimal('confidence', 4, 3)->nullable();
            $table->json('raw_response')->nullable();
            $table->string('model')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('confirmation_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_ai_results');
    }
};
