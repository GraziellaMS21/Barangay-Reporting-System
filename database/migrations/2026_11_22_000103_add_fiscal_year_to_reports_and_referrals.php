<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('fiscal_year_id')->nullable()->after('id')->index()->constrained('fiscal_years')->nullOnDelete();
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->foreignId('fiscal_year_id')->nullable()->after('id')->index()->constrained('fiscal_years')->nullOnDelete();
        });

        // Backfill: find distinct years from existing reports and referrals
        $currentYear = (int) date('Y');
        $years = collect([$currentYear]);
        $driver = DB::getDriverName();
        $yearExpr = ($driver === 'sqlite') ? "strftime('%Y', created_at) as yr" : 'YEAR(created_at) as yr';

        if (Schema::hasTable('reports')) {
            $reportYears = DB::table('reports')
                ->whereNotNull('created_at')
                ->selectRaw($yearExpr)
                ->distinct()
                ->pluck('yr')
                ->filter()
                ->map(fn ($y) => (int) $y);
            $years = $years->merge($reportYears);
        }

        if (Schema::hasTable('referrals')) {
            $referralYears = DB::table('referrals')
                ->whereNotNull('created_at')
                ->selectRaw($yearExpr)
                ->distinct()
                ->pluck('yr')
                ->filter()
                ->map(fn ($y) => (int) $y);
            $years = $years->merge($referralYears);
        }

        $years = $years->unique()->sort()->values();

        foreach ($years as $year) {
            $existing = DB::table('fiscal_years')->where('year', $year)->first();
            $isActive = ($year === $currentYear);
            $isPast = ($year < $currentYear);

            if (! $existing) {
                $fyId = DB::table('fiscal_years')->insertGetId([
                    'year' => $year,
                    'label' => "FY {$year}",
                    'starts_on' => "{$year}-01-01",
                    'ends_on' => "{$year}-12-31",
                    'status' => $isActive ? 'active' : 'closed',
                    'is_locked' => $isPast,
                    'closed_at' => $isPast ? Carbon::create($year, 12, 31, 23, 59, 59) : null,
                    'close_note' => $isPast ? 'Automatically closed during system migration.' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $fyId = $existing->id;
            }

            // Stamp existing records portably using date ranges
            $startDate = "{$year}-01-01 00:00:00";
            $endDate = "{$year}-12-31 23:59:59";

            DB::table('reports')
                ->whereNull('fiscal_year_id')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->update(['fiscal_year_id' => $fyId]);

            DB::table('referrals')
                ->whereNull('fiscal_year_id')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->update(['fiscal_year_id' => $fyId]);
        }

        // If an admin user exists, mark system.setup_completed = true in settings
        $adminUser = DB::table('users')->where('role', 'admin')->first();
        if ($adminUser) {
            $existingSetting = DB::table('settings')->where('key', 'system.setup_completed')->first();
            if ($existingSetting) {
                DB::table('settings')->where('key', 'system.setup_completed')->update([
                    'value' => 'true',
                    'type' => 'boolean',
                    'group' => 'system',
                    'label' => 'System Setup Completed',
                    'is_confirmed' => true,
                    'confirmed_by' => $adminUser->id,
                    'confirmed_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('settings')->insert([
                    'key' => 'system.setup_completed',
                    'value' => 'true',
                    'type' => 'boolean',
                    'group' => 'system',
                    'label' => 'System Setup Completed',
                    'is_confirmed' => true,
                    'confirmed_by' => $adminUser->id,
                    'confirmed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropIndex(['fiscal_year_id']);
            $table->dropConstrainedForeignId('fiscal_year_id');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['fiscal_year_id']);
            $table->dropConstrainedForeignId('fiscal_year_id');
        });
    }
};
