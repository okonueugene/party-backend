<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\County;
use App\Models\Constituency;
use App\Models\Ward;
use Illuminate\Support\Facades\DB;

class IEBCDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder populates the database with Kenya's IEBC geographic data.
     * You should populate this with actual IEBC data from the official source.
     */
    public function run(): void
    {
        // Clear existing data (optional - comment out if you want to preserve existing data)
        // DB::table('wards')->delete();
        // DB::table('constituencies')->delete();
        // DB::table('counties')->delete();

        // Example: Siaya County (043)
        $siaya = County::firstOrCreate(
            ['code' => '043'],
            ['name' => 'Siaya']
        );

        // Example: Alego Usonga Constituency
        $alegoUsonga = Constituency::firstOrCreate(
            [
                'county_id' => $siaya->id,
                'name' => 'Alego Usonga',
            ]
        );

        // Example wards for Alego Usonga
        $wards = [
            'Usonga',
            'West Alego',
            'Central Alego',
            'Siaya Township',
            'North Alego',
            'South East Alego',
        ];

        foreach ($wards as $wardName) {
            Ward::firstOrCreate(
                [
                    'constituency_id' => $alegoUsonga->id,
                    'name' => $wardName,
                ]
            );
        }

        // Example: Nakuru County (032)
        $nakuru = County::firstOrCreate(
            ['code' => '032'],
            ['name' => 'Nakuru']
        );

        // Example: Nakuru Town West Constituency
        $nakuruTownWest = Constituency::firstOrCreate(
            [
                'county_id' => $nakuru->id,
                'name' => 'Nakuru Town West',
            ]
        );

        // Example wards for Nakuru Town West
        $nakuruWards = [
            'Barut',
            'London',
            'Kaptembwo',
            'Kapkures',
            'Rhoda',
            'Shaabab',
        ];

        foreach ($nakuruWards as $wardName) {
            Ward::firstOrCreate(
                [
                    'constituency_id' => $nakuruTownWest->id,
                    'name' => $wardName,
                ]
            );
        }

        // TODO: Add all 47 counties, 290 constituencies, and all wards
        // You can import this data from a CSV file or JSON file containing IEBC data
        // Example structure for CSV import:
        // County Code, County Name, Constituency Name, Ward Name

        $this->command->info('IEBC geographic data seeded successfully!');
        $this->command->info('Note: This is sample data. Please populate with complete IEBC data.');
    }
}

