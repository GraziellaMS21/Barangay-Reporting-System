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
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->boolean('is_internal')->default(true);
            $table->string('contact')->nullable();
            $table->timestamps();
        });

        Schema::create('routing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->boolean('within_authority')->default(true);
            $table->text('jurisdiction_note')->nullable();
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->text('referral_text');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        Schema::create('duplicate_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('reports')->cascadeOnDelete();
            $table->decimal('score', 5, 3)->unsigned()->default(0);
            $table->string('decision')->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('model')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('recipient');
            $table->string('status')->default('sent');
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('sensitive_places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->timestamps();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('risk_weight')->default(1)->after('slug');
            $table->foreignId('default_office_id')->nullable()->after('risk_weight')->constrained('offices')->nullOnDelete();
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('office_id')->nullable()->after('category_id')->constrained('offices')->nullOnDelete();
            $table->integer('priority_score')->default(0)->after('office_id');
            $table->foreignId('merged_into_id')->nullable()->after('priority_score')->constrained('reports')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('merged_into_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('office_id');
            $table->dropConstrainedForeignId('merged_into_id');
            $table->dropColumn(['priority_score', 'resolved_at']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_office_id');
            $table->dropColumn('risk_weight');
        });

        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('sensitive_places');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('duplicate_suggestions');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('routing_rules');
        Schema::dropIfExists('offices');
    }
};
