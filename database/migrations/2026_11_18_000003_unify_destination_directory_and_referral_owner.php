<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->string('description', 500)->nullable()->after('type');
            $table->foreignId('default_assignee_id')
                ->nullable()
                ->after('address')
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('default_assignee_id');
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->foreignId('follow_up_owner_id')
                ->nullable()
                ->after('prepared_by')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('referrals')
            ->whereNull('follow_up_owner_id')
            ->whereNotNull('prepared_by')
            ->update(['follow_up_owner_id' => DB::raw('prepared_by')]);
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('follow_up_owner_id');
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_assignee_id');
            $table->dropColumn(['description', 'is_active']);
        });
    }
};
