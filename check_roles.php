<?php

// Load Laravel framework
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Check if roles exist
$roles = DB::table('roles')->get();
echo "Current roles in the database:\n";
foreach ($roles as $role) {
    echo "ID: {$role->id}, Name: {$role->name}, Display Name: {$role->display_name}\n";
}

// If no roles exist, create them
if (count($roles) === 0) {
    echo "\nNo roles found. Creating roles...\n";
    
    DB::table('roles')->insert([
        [
            'id' => 1,
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Full access to all system features',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 2,
            'name' => 'service_provider',
            'display_name' => 'Service Provider',
            'description' => 'Can manage their own services and bookings',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 3,
            'name' => 'customer',
            'display_name' => 'Customer',
            'description' => 'Can book services and manage their own bookings',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);
    
    echo "Roles created successfully!\n";
} else {
    echo "\nRoles already exist. No need to create them.\n";
}

echo "\nDone.\n"; 