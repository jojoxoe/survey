<?php

$jsonFile = 'c:\laragon\www\survey\storage\app\psgc\psgc.json';
$data = json_decode(file_get_contents($jsonFile), true);

echo "✓ PSGC JSON Data Summary:\n";
echo "=====================\n";
echo 'Total Regions: '.count($data)."\n";
echo 'Total Provinces: '.array_sum(array_map(fn ($r) => count($r['provinces']), $data))."\n";

$totalCities = 0;
$totalBarangays = 0;

foreach ($data as $region) {
    foreach ($region['provinces'] as $province) {
        $totalCities += count($province['cities']);
        foreach ($province['cities'] as $city) {
            $totalBarangays += count($city['barangays']);
        }
    }
}

echo 'Total Cities/Municipalities: '.$totalCities."\n";
echo 'Total Barangays: '.$totalBarangays."\n\n";

echo "Sample Data:\n";
echo 'Region: '.$data[0]['name']."\n";
echo '  Province: '.$data[0]['provinces'][0]['name']."\n";
echo '  City: '.$data[0]['provinces'][0]['cities'][0]['name']."\n";
echo '  Barangays: '.count($data[0]['provinces'][0]['cities'][0]['barangays'])."\n";
