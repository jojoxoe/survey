<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\City;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class PsgcSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = storage_path('app/psgc/psgc.json');

        if (! File::exists($jsonPath)) {
            $this->command->error("PSGC JSON file not found at: {$jsonPath}");

            return;
        }

        $data = json_decode(File::get($jsonPath), true);

        if (! is_array($data)) {
            $this->command->error('Invalid PSGC JSON structure');

            return;
        }

        foreach ($data as $regionData) {
            $region = Region::create([
                'code' => $regionData['code'],
                'name' => $regionData['name'],
            ]);

            if (isset($regionData['provinces']) && is_array($regionData['provinces'])) {
                foreach ($regionData['provinces'] as $provinceData) {
                    $province = Province::create([
                        'code' => $provinceData['code'],
                        'region_id' => $region->id,
                        'name' => $provinceData['name'],
                    ]);

                    if (isset($provinceData['cities']) && is_array($provinceData['cities'])) {
                        foreach ($provinceData['cities'] as $cityData) {
                            $city = City::create([
                                'code' => $cityData['code'],
                                'province_id' => $province->id,
                                'name' => $cityData['name'],
                            ]);

                            if (isset($cityData['barangays']) && is_array($cityData['barangays'])) {
                                foreach ($cityData['barangays'] as $barangayData) {
                                    Barangay::create([
                                        'code' => $barangayData['code'],
                                        'city_id' => $city->id,
                                        'name' => $barangayData['name'],
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->command->info('PSGC data seeded successfully!');
    }
}
