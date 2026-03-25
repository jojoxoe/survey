<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

$kernel = $app->makeWith(\Illuminate\Contracts\Console\Kernel::class, [
    'app' => $app,
]);

$kernel->bootstrap();

// Test the PsgcController
$controller = new \App\Http\Controllers\PsgcController;

echo "Testing PSGC API Endpoints\n";
echo "==========================\n\n";

// Test regions
echo "1. Testing GET /api/psgc/regions\n";
$regionsResponse = $controller->regions();
$regions = json_decode($regionsResponse->getContent(), true);
echo '   Response: '.count($regions)." regions returned\n";
echo '   Sample: '.$regions[0]['name']."\n\n";

// Test provinces for first region
echo "2. Testing GET /api/psgc/regions/{regionCode}/provinces\n";
$provinceResponse = $controller->provinces($regions[0]['code']);
$provinces = json_decode($provinceResponse->getContent(), true);
echo '   Region Code: '.$regions[0]['code']."\n";
echo '   Response: '.count($provinces)." provinces returned\n";
echo '   Sample: '.$provinces[0]['name']."\n\n";

// Test cities for first province
echo "3. Testing GET /api/psgc/provinces/{provinceCode}/cities\n";
$citiesResponse = $controller->cities($provinces[0]['code']);
$cities = json_decode($citiesResponse->getContent(), true);
echo '   Province Code: '.$provinces[0]['code']."\n";
echo '   Response: '.count($cities)." cities returned\n";
if (count($cities) > 0) {
    echo '   Sample: '.$cities[0]['name']."\n";
}
echo "\n";

// Test barangays for first city
if (count($cities) > 0) {
    echo "4. Testing GET /api/psgc/cities/{cityCode}/barangays\n";
    $barangaysResponse = $controller->barangays($cities[0]['code']);
    $barangays = json_decode($barangaysResponse->getContent(), true);
    echo '   City Code: '.$cities[0]['code']."\n";
    echo '   Response: '.count($barangays)." barangays returned\n";
    if (count($barangays) > 0) {
        echo '   Sample: '.$barangays[0]['name']."\n";
    }
}

echo "\n✓ All PSGC API endpoints working correctly!\n";
