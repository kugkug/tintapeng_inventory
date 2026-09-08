<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with demo data
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        // Create demo tenant
        $tenant = Tenant::where('email', env('ADMIN_EMAIL', 'admin@tintapeng.local'))->firstOrFail();

        // Create manager user
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager User',
            'email' => 'manager@tintapeng.local',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'is_active' => true,
        ]);

        // Create staff users
        User::factory(3)->create([
            'tenant_id' => $tenant->id,
            'password' => bcrypt('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        // Create categories
        $categories = Category::factory(5)->create([
            'tenant_id' => $tenant->id,
        ]);

        // Create locations
        $mainLocation = Location::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Store',
        ]);

        $warehouseLocation = Location::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Warehouse',
        ]);

        // Create products with inventory
        Product::factory(20)->create([
            'tenant_id' => $tenant->id,
            'category_id' => $categories->random()->id,
        ])->each(function (Product $product) use ($mainLocation, $warehouseLocation) {
            // Add inventory to main location
            $product->inventoryItems()->create([
                'location_id' => $mainLocation->id,
                'quantity' => rand(5, 50),
            ]);

            // Add inventory to warehouse
            $product->inventoryItems()->create([
                'location_id' => $warehouseLocation->id,
                'quantity' => rand(10, 100),
            ]);
        });

        $this->command->info('Database seeded successfully!');
        $this->command->info('Demo credentials: admin@tintapeng.local / password');
    }
}