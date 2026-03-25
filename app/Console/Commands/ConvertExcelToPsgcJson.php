<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;
use ZipArchive;

class ConvertExcelToPsgcJson extends Command
{
    protected $signature = 'psgc:convert-excel';

    protected $description = 'Convert official PSA PSGC Excel file to JSON format';

    public function handle(): int
    {
        $this->info('Converting Excel PSGC data to JSON...');

        try {
            $excelPath = storage_path('app/psgc/PSGC-3Q-2025-Publication-Datafile.xlsx');

            if (! file_exists($excelPath)) {
                $this->error('Excel file not found at: '.$excelPath);

                return 1;
            }

            $this->info("Reading Excel file: $excelPath");

            // Extract data from Excel
            $data = $this->readExcelFile($excelPath);

            if (empty($data)) {
                $this->error('No data extracted from Excel file');

                return 1;
            }

            // Convert to hierarchical JSON format
            $psgcJson = $this->convertToHierarchy($data);

            // Save to JSON
            $jsonPath = 'psgc/psgc.json';
            Storage::disk('local')->put($jsonPath, json_encode($psgcJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $regionCount = count($psgcJson);
            $this->info('✓ Successfully converted Excel to JSON!');
            $this->info("  Regions: $regionCount");
            $this->info("  File: storage/app/$jsonPath");

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());

            return 1;
        }
    }

    private function readExcelFile(string $excelPath): array
    {
        $data = [];
        $zip = new ZipArchive;

        if (! $zip->open($excelPath)) {
            throw new \Exception('Failed to open Excel file');
        }

        // Read the worksheet data
        $xmlData = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! $xmlData) {
            throw new \Exception('Could not find worksheet data');
        }

        // Parse XML
        $xml = new SimpleXMLElement($xmlData);
        $xml->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = $xml->xpath('//main:row');
        $firstRow = true;
        $headers = [];

        foreach ($rows as $row) {
            $cells = $row->xpath('.//main:c');
            $rowData = [];

            foreach ($cells as $cell) {
                $value = '';
                if (isset($cell->v)) {
                    $value = (string) $cell->v;
                }
                $rowData[] = $value;
            }

            if (empty(array_filter($rowData))) {
                continue;
            }

            if ($firstRow) {
                $headers = $rowData;
                $firstRow = false;

                continue;
            }

            if (count($rowData) < 4) {
                continue;
            }

            // Map columns: Region | Province | City | Barangay
            $data[] = [
                'region' => trim($rowData[0] ?? ''),
                'region_code' => trim($rowData[1] ?? ''),
                'province' => trim($rowData[2] ?? ''),
                'province_code' => trim($rowData[3] ?? ''),
                'city' => trim($rowData[4] ?? ''),
                'city_code' => trim($rowData[5] ?? ''),
                'barangay' => trim($rowData[6] ?? ''),
                'barangay_code' => trim($rowData[7] ?? ''),
            ];
        }

        $this->info('Read '.count($data).' records from Excel');

        return $data;
    }

    private function convertToHierarchy(array $data): array
    {
        $hierarchy = [];

        foreach ($data as $row) {
            // Find or create region
            $regionCode = $row['region_code'] ?? $row['region'];
            $regionKey = $this->findOrCreateKey($hierarchy, 'code', $regionCode);

            if ($regionKey === false) {
                $hierarchy[] = [
                    'code' => $regionCode,
                    'name' => $row['region'],
                    'provinces' => [],
                ];
                $regionKey = count($hierarchy) - 1;
            }

            // Find or create province
            $provinceCode = $row['province_code'] ?? $row['province'];
            $provinceKey = $this->findOrCreateKey($hierarchy[$regionKey]['provinces'], 'code', $provinceCode);

            if ($provinceKey === false) {
                $hierarchy[$regionKey]['provinces'][] = [
                    'code' => $provinceCode,
                    'name' => $row['province'],
                    'cities' => [],
                ];
                $provinceKey = count($hierarchy[$regionKey]['provinces']) - 1;
            }

            // Find or create city
            $cityCode = $row['city_code'] ?? $row['city'];
            $cityKey = $this->findOrCreateKey($hierarchy[$regionKey]['provinces'][$provinceKey]['cities'], 'code', $cityCode);

            if ($cityKey === false) {
                $hierarchy[$regionKey]['provinces'][$provinceKey]['cities'][] = [
                    'code' => $cityCode,
                    'name' => $row['city'],
                    'barangays' => [],
                ];
                $cityKey = count($hierarchy[$regionKey]['provinces'][$provinceKey]['cities']) - 1;
            }

            // Add barangay if not empty
            if (! empty($row['barangay'])) {
                $barangayKey = $this->findOrCreateKey(
                    $hierarchy[$regionKey]['provinces'][$provinceKey]['cities'][$cityKey]['barangays'],
                    'code',
                    $row['barangay_code'] ?? $row['barangay']
                );

                if ($barangayKey === false) {
                    $hierarchy[$regionKey]['provinces'][$provinceKey]['cities'][$cityKey]['barangays'][] = [
                        'code' => $row['barangay_code'] ?? $row['barangay'],
                        'name' => $row['barangay'],
                    ];
                }
            }
        }

        return array_values($hierarchy);
    }

    private function findOrCreateKey(array &$array, string $key, string $value): int|false
    {
        foreach ($array as $index => $item) {
            if (isset($item[$key]) && $item[$key] === $value) {
                return $index;
            }
        }

        return false;
    }
}
