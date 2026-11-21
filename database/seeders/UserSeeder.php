<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Demo password for development/testing only
        $defaultPassword = Hash::make('password');

        User::updateOrCreate(
            ['email' => 'admin@barangay.test'],
            [
                'name' => 'Barangay Admin',
                'password' => $defaultPassword,
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@barangay.test'],
            [
                'name' => 'Barangay Staff',
                'password' => $defaultPassword,
                'role' => 'staff',
            ]
        );
    }
}
