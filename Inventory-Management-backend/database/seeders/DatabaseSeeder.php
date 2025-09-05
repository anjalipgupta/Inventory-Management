<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Inventory;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
        ]);

        $viewer = User::create([
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => Hash::make('password'),
            'role' => 'viewer',
        ]);

        // Inventory Items
        Inventory::create([
            'name' => 'Laptop',
            'description' => 'Dell XPS 15',
            'quantity' => 5,
            'price' => 1500.00,
            'created_by' => $admin->id,
        ]);

        Inventory::create([
            'name' => 'Monitor',
            'description' => 'Samsung 24-inch',
            'quantity' => 10,
            'price' => 200.00,
            'created_by' => $manager->id,
        ]);
    }
}
