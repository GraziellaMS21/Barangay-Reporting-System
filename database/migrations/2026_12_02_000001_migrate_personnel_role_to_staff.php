<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $personnelUsers = DB::table('users')->where('role', 'personnel')->get();

        if ($personnelUsers->isNotEmpty()) {
            DB::table('users')->where('role', 'personnel')->update(['role' => 'staff']);

            $staffFullSlugs = config('permission_presets.presets.staff_full.permissions', []);
            $permissions = DB::table('permissions')->whereIn('slug', $staffFullSlugs)->get()->keyBy('slug');

            foreach ($personnelUsers as $user) {
                $existingPermIds = DB::table('permission_user')
                    ->where('user_id', $user->id)
                    ->pluck('permission_id')
                    ->all();

                if (empty($existingPermIds)) {
                    $inserts = [];
                    foreach ($staffFullSlugs as $slug) {
                        if (isset($permissions[$slug])) {
                            $inserts[] = [
                                'user_id' => $user->id,
                                'permission_id' => $permissions[$slug]->id,
                            ];
                        }
                    }
                    if (! empty($inserts)) {
                        DB::table('permission_user')->insert($inserts);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversal is a no-op as 'personnel' is a deprecated role.
    }
};
