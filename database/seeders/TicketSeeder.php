<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;  
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pull existing IDs from each table to satisfy FKs
        $enforcerIds         = DB::table('enforcers')->pluck('id')->toArray();
        $violatorIds         = DB::table('violators')->pluck('id')->toArray();
        $vehicleIds          = DB::table('vehicles')->pluck('vehicle_id')->toArray();
        $confiscationTypeIds = DB::table('confiscation_types')->pluck('id')->toArray();
        $adminIds            = DB::table('admins')->pluck('id')->toArray();

        // Ensure we have at least something to pick from
        if (empty($enforcerIds) || empty($violatorIds) || empty($vehicleIds)) {
            $this->command->error('Please seed enforcers, violators, and vehicles first.');
            return;
        }

        $areas = [
            'Zone 1 – Main St', 'Downtown Plaza', 'Market District',
            'East Avenue', 'West Gate',
        ];
        $violationCodesList = [
            '["V101"]', '["V102","V103"]', '["V104"]', '["V105","V106","V107"]',
        ];

        for ($i = 1; $i <= 5; $i++) {
            DB::table('tickets')->insert([
                'ticket_number'        => str_pad($i, 3, '0', STR_PAD_LEFT),
                'enforcer_id'          => $enforcerIds[array_rand($enforcerIds)],
                'violator_id'          => $violatorIds[array_rand($violatorIds)],
                'vehicle_id'           => $vehicleIds[array_rand($vehicleIds)],
                'violation_codes'      => $violationCodesList[array_rand($violationCodesList)],
                'location'             => $areas[array_rand($areas)],
                'issued_at'            => Carbon::now()->subDays(rand(0, 30)),
                'latitude'             => 15.90  + mt_rand(0, 100) / 1000,
                'longitude'            => 120.30 + mt_rand(0, 100) / 1000,
                'status_id'            => rand(1, 2),   // adjust if you have more statuses
                'confiscation_type_id' => $confiscationTypeIds[array_rand($confiscationTypeIds)] ?? null,
                'offline'              => rand(0, 1),
                'admin_id'             => $adminIds[array_rand($adminIds)] ?? 1,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }

        $this->command->info('✅ TicketSeeder: 5 tickets inserted.');
    }
}
