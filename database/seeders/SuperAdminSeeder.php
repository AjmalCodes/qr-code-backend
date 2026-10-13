<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super admin user
        User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('12345678'),
                'role' => UserRole::SuperAdmin->value,
            ]
        );

        $this->command->info('Super admin user created successfully!');
        $this->command->info('Email: superadmin@gmail.com');
        $this->command->info('Password: 12345678');
    }
}
