<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tasks model
        Task::insert([
            [
                'description' => 'description',
                'id' => 1,
                'image' => NULL,
                'name' => 'Create account',
                'points' => 100,
                'position' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ],
            [
                'description' => 'description',
                'id' => 2,
                'image' => NULL,
                'name' => 'Create Pin',
                'points' => 100,
                'position' => 2,
                'updated_at' => now(),
                'created_at' => now(),
            ],
            [
                'description' => 'description',
                'id' => 3,
                'image' => NULL,
                'name' => 'Add Bank Account',
                'points' => 100,
                'position' => 3,
                'updated_at' => now(),
                'created_at' => now(),
            ],
            [
                'description' => 'description',
                'id' => 4,
                'image' => NULL,
                'name' => 'First crypto sale',
                'points' => 100,
                'position' => 4,
                'updated_at' => now(),
                'created_at' => now(),
            ],
            [
                'description' => 'description',
                'id' => 5,
                'image' => NULL,
                'name' => 'Buy 2000 Airtime',
                'points' => 100,
                'position' => 5,
                'updated_at' => now(),
                'created_at' => now(),
            ],
            [
                'description' => 'description',
                'id' => 6,
                'image' => NULL,
                'name' => 'Leave a review',
                'points' => 100,
                'position' => 6,
                'updated_at' => now(),
                'created_at' => now(),
            ],
            [
                'description' => 'description',
                'id' => 7,
                'image' => NULL,
                'name' => 'Follow socials',
                'points' => 100,
                'position' => 7,
                'updated_at' => now(),
                'created_at' => now(),
            ],
            [
                'description' => 'description',
                'id' => 8,
                'image' => NULL,
                'name' => 'Buy 2000 data',
                'points' => 100,
                'position' => 8,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        ]);
    }
}
