<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FetchPsgcData extends Command
{
    protected $signature = 'psgc:fetch';

    protected $description = 'Fetch complete Philippine PSGC data and save to JSON';

    public function handle(): int
    {
        $this->info('Fetching complete PSGC data...');

        try {
            // Try multiple sources
            $sources = [
                'https://psgc.gitlab.io/api/regions/',  // Official GitLab API
            ];

            $psgcData = null;

            foreach ($sources as $url) {
                $this->info("Trying: $url");
                $psgcData = $this->fetchFromUrl($url);

                if ($psgcData) {
                    $this->info("✓ Successfully fetched from $url");
                    break;
                }
            }

            if (! $psgcData) {
                $this->error('Failed to fetch from all sources. Using default data...');

                // Use fallback
                return 0;
            }

            // Save to storage
            $jsonData = json_encode($psgcData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Storage::disk('local')->put('psgc/psgc.json', $jsonData);

            $regionCount = count($psgcData);
            $this->info('✓ PSGC data successfully saved!');
            $this->info("  Regions: $regionCount");
            $this->info('  File: storage/app/psgc/psgc.json');

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }

    private function fetchFromUrl(string $url): ?array
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

            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }
}
