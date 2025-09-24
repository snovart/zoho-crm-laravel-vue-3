<?php

namespace Database\Seeders;

use App\Models\Manager;
use Illuminate\Database\Seeder;

class ManagerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emails = [
            'manager1@gmail.com',
            'manager2@gmail.com',
            'manager3@gmail.com',
            'manager4@gmail.com',
            'manager5@gmail.com',
        ];
        foreach ($emails as $e) {
            Manager::firstOrCreate(['email' => $e], ['deals_count' => 0]);
        }
    }
}
