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
        // 1. Alter settings table
        Schema::table('settings', function (Blueprint $table) {
            $table->string('type')->default('string')->after('key');
            $table->string('group')->default('general')->after('type');
            $table->string('label')->nullable()->after('group');
            $table->text('description')->nullable()->after('label');
            $table->boolean('is_confirmed')->default(false)->after('value');
            $table->foreignId('confirmed_by')->nullable()->after('is_confirmed')->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
            $table->text('source_question')->nullable()->after('confirmed_at');
        });

        // 2. Fiscal Years
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->string('label');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('inactive'); // active, inactive, closed
            $table->boolean('is_locked')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('close_note')->nullable();
            $table->timestamps();
        });

        // 3. Number Sequences
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->foreignId('fiscal_year_id')->nullable()->constrained('fiscal_years')->nullOnDelete();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['type', 'fiscal_year_id']);
        });

        // 4. Positions
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_signatory_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();
        });

        // 5. Personnel
        Schema::create('personnel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('position_id')->constrained('positions');
            $table->string('full_name');
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->date('term_start');
            $table->date('term_end')->nullable();
            $table->boolean('is_signatory')->default(false);
            $table->string('signature_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Signatory Assignments
        Schema::create('signatory_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');
            $table->string('role_label');
            $table->foreignId('position_id')->constrained('positions');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();

            $table->unique(['document_type', 'role_label']);
        });

        // 7. Permissions & Pivot
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('label');
            $table->string('group');
            $table->timestamps();
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['user_id', 'permission_id']);
        });

        // 8. Alter response_templates table
        Schema::table('response_templates', function (Blueprint $table) {
            $table->string('title')->nullable()->after('id');
            $table->string('kind')->default('status_update')->after('category_id');
            $table->foreignId('created_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
        });

        // 9. Report Templates
        Schema::create('report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('source');
            $table->json('columns');
            $table->json('filters')->nullable();
            $table->string('orientation')->default('portrait');
            $table->string('signatory_document_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();
        });

        // 10. Generated Reports
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_template_id')->nullable()->constrained('report_templates')->nullOnDelete();
            $table->string('report_key');
            $table->json('params')->nullable();
            $table->string('format')->default('pdf');
            $table->foreignId('fiscal_year_id')->nullable()->constrained('fiscal_years')->nullOnDelete();
            $table->foreignId('generated_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
        Schema::dropIfExists('report_templates');

        Schema::table('response_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['title', 'kind']);
        });

        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('signatory_assignments');
        Schema::dropIfExists('personnel');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('number_sequences');
        Schema::dropIfExists('fiscal_years');

        Schema::table('settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn([
                'type',
                'group',
                'label',
                'description',
                'is_confirmed',
                'confirmed_at',
                'source_question',
            ]);
        });
    }
};
