<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\User\Database\Seeders\RolesTableSeeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run the roles seeder first
        $this->call(RolesTableSeeder::class);

        // Then create users
        User::factory()->create([
            'fname' => 'Test',
            'lname' => 'User',
            'email' => 'test@example.com',
            'role_id' => 3,
            'password' => Hash::make('Password123'), // <-- This is required!
        ]);

        $this->call(\Modules\Community\Database\Seeders\ListingCategorySeeder::class);
    }
}
