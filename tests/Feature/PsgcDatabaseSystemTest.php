<?php

namespace Tests\Feature;

use App\Models\Region;
use Database\Seeders\PsgcSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsgcDatabaseSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PsgcSeeder::class);
    }

    public function test_psgc_database_has_all_data(): void
    {
        // Verify all 18 regions loaded
        $this->assertGreaterThanOrEqual(18, Region::count());

        // Verify regions have provinces
        $region = Region::with('provinces')->first();
        $this->assertNotNull($region);
        $this->assertGreaterThan(0, $region->provinces->count());

        // Verify provinces have cities
        $province = $region->provinces->first();
        $this->assertGreaterThan(0, $province->cities->count());

        // Verify cities have barangays
        $city = $province->cities->first();
        $this->assertGreaterThan(0, $city->barangays->count());
    }

    public function test_regions_api_endpoint(): void
    {
        $response = $this->getJson('/api/psgc/regions');

        $response->assertStatus(200);
        $regions = $response->json();

        $this->assertNotEmpty($regions);
        $this->assertGreaterThanOrEqual(18, count($regions));

        foreach ($regions as $region) {
            $this->assertArrayHasKey('code', $region);
            $this->assertArrayHasKey('name', $region);
            $this->assertNotEmpty($region['code']);
            $this->assertNotEmpty($region['name']);
        }
    }

    public function test_provinces_cascading_lookup(): void
    {
        $region = Region::first();
        $this->assertNotNull($region);

        $response = $this->getJson("/api/psgc/regions/{$region->code}/provinces");

        $response->assertStatus(200);
        $provinces = $response->json();

        $this->assertNotEmpty($provinces);

        foreach ($provinces as $province) {
            $this->assertArrayHasKey('code', $province);
            $this->assertArrayHasKey('name', $province);
            $this->assertNotEmpty($province['code']);
            $this->assertNotEmpty($province['name']);
        }
    }

    public function test_cities_cascading_lookup(): void
    {
        $province = Region::with('provinces')
            ->first()
            ->provinces
            ->first();

        $this->assertNotNull($province);

        $response = $this->getJson("/api/psgc/provinces/{$province->code}/cities");

        $response->assertStatus(200);
        $cities = $response->json();

        $this->assertNotEmpty($cities);

        foreach ($cities as $city) {
            $this->assertArrayHasKey('code', $city);
            $this->assertArrayHasKey('name', $city);
            $this->assertNotEmpty($city['code']);
            $this->assertNotEmpty($city['name']);
        }
    }

    public function test_barangays_cascading_lookup(): void
    {
        $city = Region::with(['provinces' => ['cities']])
            ->first()
            ->provinces
            ->first()
            ->cities
            ->first();

        $this->assertNotNull($city);

        $response = $this->getJson("/api/psgc/cities/{$city->code}/barangays");

        $response->assertStatus(200);
        $barangays = $response->json();

        $this->assertNotEmpty($barangays);

        foreach ($barangays as $barangay) {
            $this->assertArrayHasKey('code', $barangay);
            $this->assertArrayHasKey('name', $barangay);
            $this->assertNotEmpty($barangay['code']);
            $this->assertNotEmpty($barangay['name']);
        }
    }

    public function test_complete_cascading_chain(): void
    {
        // Get first region
        $regionsResponse = $this->getJson('/api/psgc/regions');
        $regions = $regionsResponse->json();
        $this->assertNotEmpty($regions);
        $regionCode = $regions[0]['code'];

        // Get provinces for that region
        $provincesResponse = $this->getJson("/api/psgc/regions/{$regionCode}/provinces");
        $provinces = $provincesResponse->json();
        $this->assertNotEmpty($provinces);
        $provinceCode = $provinces[0]['code'];

        // Get cities for that province
        $citiesResponse = $this->getJson("/api/psgc/provinces/{$provinceCode}/cities");
        $cities = $citiesResponse->json();
        $this->assertNotEmpty($cities);
        $cityCode = $cities[0]['code'];

        // Get barangays for that city
        $barangaysResponse = $this->getJson("/api/psgc/cities/{$cityCode}/barangays");
        $barangays = $barangaysResponse->json();
        $this->assertNotEmpty($barangays);

        // Verify all have proper structure
        foreach ([$regionCode, $provinceCode, $cityCode] as $code) {
            $this->assertNotEmpty($code);
            $this->assertIsString($code);
        }

        foreach ($barangays as $barangay) {
            $this->assertArrayHasKey('code', $barangay);
            $this->assertArrayHasKey('name', $barangay);
        }
    }
}
