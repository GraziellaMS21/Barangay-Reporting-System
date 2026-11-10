<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('report_type')->default('non_emergency')->after('is_emergency');
            $table->json('answers')->nullable()->after('report_type');
            $table->boolean('is_ongoing')->default(false)->after('answers');
            $table->boolean('is_recurring')->default(false)->after('is_ongoing');
            $table->boolean('people_at_risk')->default(false)->after('is_recurring');
            $table->boolean('is_anonymous')->default(false)->after('people_at_risk');
            $table->string('preferred_contact')->nullable()->after('is_anonymous');
            $table->timestamp('consent_at')->nullable()->after('preferred_contact');
            $table->foreignId('assigned_to')->nullable()->after('consent_at')->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable()->after('assigned_to');
            $table->timestamp('escalated_at')->nullable()->after('due_at');
            $table->timestamp('resolution_confirmed_at')->nullable()->after('escalated_at');
            $table->unsignedInteger('reopened_count')->default(0)->after('resolution_confirmed_at');
            $table->index('assigned_to');
            $table->index('due_at');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->json('question_schema')->nullable();
            $table->unsignedInteger('sla_hours')->nullable();
        });

        Schema::create('internal_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('info_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->text('question');
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('response_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('resolution_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('photo_path')->nullable();
            $table->text('note');
            $table->timestamps();
        });

        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete()->unique();
            $table->tinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('resolution_proofs');
        Schema::dropIfExists('response_templates');
        Schema::dropIfExists('info_requests');
        Schema::dropIfExists('internal_notes');

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['question_schema', 'sla_hours']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['assigned_to']);
            $table->dropIndex(['due_at']);
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn(['report_type','answers','is_ongoing','is_recurring','people_at_risk','is_anonymous','preferred_contact','consent_at','due_at','escalated_at','resolution_confirmed_at','reopened_count']);
        });
    }
};
