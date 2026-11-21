<?php

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            $permissionService = app(PermissionService::class);
            $staffUsers = User::query()->where('role', 'staff')->get();

            foreach ($staffUsers as $staff) {
                $permissionService->applyPreset($staff, 'staff_full');
            }
        } catch (Throwable) {
            // Ignore during raw fresh migrations prior to seeding
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op for permission data migration
    }
};
