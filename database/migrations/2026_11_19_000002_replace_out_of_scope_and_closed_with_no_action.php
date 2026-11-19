<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds no_action_reason columns to reports and status_logs, and migrates
     * all historical 'out_of_scope' and 'closed' records to 'no_action'.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('no_action_reason')->nullable()->index()->after('status');
        });

        Schema::table('status_logs', function (Blueprint $table) {
            $table->string('no_action_reason')->nullable()->after('to_status');
        });

        DB::table('reports')
            ->where('status', 'out_of_scope')
            ->update([
                'status' => 'no_action',
                'no_action_reason' => 'not_barangay_matter',
            ]);

        DB::table('reports')
            ->where('status', 'closed')
            ->update([
                'status' => 'no_action',
                'no_action_reason' => 'cannot_resolve',
            ]);

        DB::table('status_logs')
            ->where('from_status', 'out_of_scope')
            ->update(['from_status' => 'no_action']);

        DB::table('status_logs')
            ->where('to_status', 'out_of_scope')
            ->update([
                'to_status' => 'no_action',
                'no_action_reason' => 'not_barangay_matter',
            ]);

        DB::table('status_logs')
            ->where('from_status', 'closed')
            ->update(['from_status' => 'no_action']);

        DB::table('status_logs')
            ->where('to_status', 'closed')
            ->update([
                'to_status' => 'no_action',
                'no_action_reason' => 'cannot_resolve',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('status_logs', function (Blueprint $table) {
            $table->dropColumn('no_action_reason');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('no_action_reason');
        });
    }
};
