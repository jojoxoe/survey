<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExpandPsgcData extends Command
{
    protected $signature = 'psgc:expand';

    protected $description = 'Expand PSGC data with all provinces, cities, and barangays';

    public function handle(): int
    {
        $this->info('Expanding PSGC data with complete hierarchy...');

        try {
            // Load existing regions
            $jsonPath = storage_path('app/psgc/psgc.json');
            $jsonData = file_get_contents($jsonPath);
            $regions = json_decode($jsonData, true);

            if (! $regions) {
                $this->error('Could not load region data');

                return 1;
            }

            // Expand each region with provinces, cities, and barangays
            foreach ($regions as &$region) {
                $this->info("Loading provinces for: {$region['name']}");

                $provincesUrl = "https://psgc.gitlab.io/api/regions/{$region['code']}/provinces/";
                $provinces = $this->fetchData($provincesUrl);

                if ($provinces) {
                    $region['provinces'] = [];

                    foreach ($provinces as $province) {
                        $this->line("  - {$province['name']}");

                        $citiesUrl = "https://psgc.gitlab.io/api/provinces/{$province['code']}/cities-municipalities/";
                        $cities = $this->fetchData($citiesUrl);

                        $provinceData = [
                            'code' => $province['code'],
                            'name' => $province['name'],
                            'cities' => [],
                        ];

                        if ($cities) {
                            foreach ($cities as $city) {
                                $barangaysUrl = "https://psgc.gitlab.io/api/cities-municipalities/{$city['code']}/barangays/";
                                $barangays = $this->fetchData($barangaysUrl);

                                $cityData = [
                                    'code' => $city['code'],
                                    'name' => $city['name'],
                                    'barangays' => $barangays ?? [],
                                ];

                                $provinceData['cities'][] = $cityData;
                            }
                        }

                        $region['provinces'][] = $provinceData;
                    }
                }
            }

            // Save expanded data
            $expandedJson = json_encode($regions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Storage::disk('local')->put('psgc/psgc.json', $expandedJson);

            $this->info('✓ PSGC data successfully expanded and saved!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }

    private function fetchData(string $url): ?array
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return null;
            }

            $data = json_decode($response, true);

            return is_array($data) ? $data : null;
        } catch (\Exception $e) {
            $this->warn("Failed to fetch: $url");

            return null;
        }
    }
}
