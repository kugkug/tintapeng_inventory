<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@tintapeng.local');
        $password = env('ADMIN_PASSWORD', 'password');

        $tenant = Tenant::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_TENANT_NAME', 'Demo Company'),
                'phone' => '+1234567890',
                'subscription_plan' => 'pro',
                'is_active' => true,
            ]
        );

        $admin = User::firstOrNew(['email' => $email]);
        $admin->tenant_id = $tenant->id;
        $admin->name = env('ADMIN_NAME', 'Admin User');
        $admin->role = 'admin';
        $admin->is_active = true;

        if (!$admin->exists) {
            $admin->password = Hash::make($password);
            $admin->email_verified_at = now();
        }

        $admin->save();

        $this->command?->info("Admin user ready: {$email}");
    }
}