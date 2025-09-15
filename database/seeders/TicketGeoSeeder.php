<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TicketGeoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $centerLat = 15.9285;  // San Carlos City approx
        $centerLng = 120.3487;

        $areas = ['Downtown', 'Public Market', 'School Zone', 'Barangay A', 'Barangay B', 'Terminal'];
        $codes = ['DWL','NO_ORCR','NO_PLATE','NO_HELMET','RECKLESS','ILLEGAL_PARK'];

        for ($i = 0; $i < 150; $i++) {
            // small cluster around center
            $lat = $centerLat + (mt_rand(-900, 900) / 10000.0) * 0.10;
            $lng = $centerLng + (mt_rand(-900, 900) / 10000.0) * 0.10;

            // 1–3 random violation codes
            shuffle($codes);
            $vCsv = implode(',', array_slice($codes, 0, rand(1,3)));

            DB::table('tickets')->insert([
                'ticket_number'        => 'T-'.date('Ymd').'-'.Str::upper(Str::random(5)),
                'enforcer_id'          => null,
                'violator_id'          => null,
                'vehicle_id'           => null,
                'violation_codes'      => $vCsv,                 // CSV like "DWL,NO_ORCR"
                'location'             => $areas[array_rand($areas)],
                'issued_at'            => Carbon::now()->subDays(rand(0, 90))->subMinutes(rand(0, 1440)),
                'latitude'             => $lat,
                'longitude'            => $lng,
                'status_id'            => rand(0,1) ? 2 : 1,     // 2=paid, 1=unpaid (your convention)
                'confiscation_type_id' => null,
                'offline'              => 0,
                'admin_id'             => 1,                     // adjust if needed
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }
    }
}
