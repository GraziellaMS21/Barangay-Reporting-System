<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrate all historical 'on_hold' reports and status logs to 'in_progress'.
     */
    public function up(): void
    {
        DB::table('reports')
            ->where('status', 'on_hold')
            ->update(['status' => 'in_progress']);

        DB::table('status_logs')
            ->where('from_status', 'on_hold')
            ->update(['from_status' => 'in_progress']);

        DB::table('status_logs')
            ->where('to_status', 'on_hold')
            ->update(['to_status' => 'in_progress']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 'on_hold' has been eliminated from the domain state machine.
        // Records migrated to 'in_progress' remain as 'in_progress'.
    }
};
