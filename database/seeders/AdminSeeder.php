<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Admin::updateOrCreate(
            ['username' => 'ADMINPOSO'], // Condition to check
            [
                'name' => 'Admin',
                'password' => Hash::make('poso2025'), // Hash password for security
            ]
        );
    }
}
