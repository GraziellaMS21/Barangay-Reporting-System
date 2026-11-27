<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add stable code, nullable official GeoJSON boundary_geometry, and active flag to puroks.
     * Official boundary_geometry remains null until verified barangay data is imported.
     */
    public function up(): void
    {
        Schema::table('puroks', function (Blueprint $table) {
            $table->string('code', 64)->nullable()->unique()->after('name');
            $table->json('boundary_geometry')->nullable()->after('code');
            $table->boolean('is_active')->default(true)->after('boundary_geometry');
        });

        // Backfill deterministic, unique codes for any existing Purok rows.
        $usedCodes = [];
        $rows = DB::table('puroks')->orderBy('id')->get(['id', 'name']);

        foreach ($rows as $row) {
            $base = Str::slug((string) $row->name) ?: 'purok-'.$row->id;
            $code = $base;

            if (isset($usedCodes[$code])) {
                $code = $base.'-'.$row->id;
            }

            $usedCodes[$code] = true;

            DB::table('puroks')
                ->where('id', $row->id)
                ->update(['code' => $code]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('puroks', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'boundary_geometry', 'is_active']);
        });
    }
};
