<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->foreignId('resident_category_id')
                ->nullable()
                ->after('category_id')
                ->constrained('categories')
                ->nullOnDelete();
            $table->foreignId('resident_purok_id')
                ->nullable()
                ->after('purok_id')
                ->constrained('puroks')
                ->nullOnDelete();
            $table->string('resident_landmark')
                ->nullable()
                ->after('landmark');
        });

        DB::table('reports')
            ->whereNull('reviewed_at')
            ->update([
                'resident_category_id' => DB::raw('category_id'),
                'resident_purok_id' => DB::raw('purok_id'),
                'resident_landmark' => DB::raw('landmark'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resident_category_id');
            $table->dropConstrainedForeignId('resident_purok_id');
            $table->dropColumn('resident_landmark');
        });
    }
};
