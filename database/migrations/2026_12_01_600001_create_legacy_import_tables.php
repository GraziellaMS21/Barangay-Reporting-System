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
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('filename');
            $table->string('stored_path')->nullable();
            $table->unsignedSmallInteger('target_fiscal_year')->nullable();
            $table->string('source_name', 100)->nullable();
            $table->string('status', 30)->default('uploaded')->index();
            $table->json('headers')->nullable();
            $table->json('mapping')->nullable();
            $table->json('stats')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->foreignId('rolled_back_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rollback_note')->nullable();
            $table->timestamps();
        });

        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw');
            $table->json('mapped')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->json('errors')->nullable();
            $table->foreignId('case_id')->nullable()->constrained('cases')->nullOnDelete();
            $table->json('created_party_ids')->nullable();
            $table->timestamps();

            $table->index(['import_batch_id', 'row_number']);
            $table->index(['import_batch_id', 'status']);
        });

        Schema::table('cases', function (Blueprint $table) {
            $table->foreignId('import_batch_id')
                ->nullable()
                ->after('id')
                ->constrained('import_batches')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('import_batch_id');
        });

        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('import_batches');
    }
};
