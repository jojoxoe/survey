<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PsgcApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_regions_endpoint_returns_array(): void
    {
        $response = $this->getJson('/api/psgc/regions');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertGreaterThan(10, count($data), 'Should have multiple regions');

        // Verify structure
        foreach ($data as $region) {
            $this->assertArrayHasKey('code', $region);
            $this->assertArrayHasKey('name', $region);
        }
    }

    public function test_provinces_endpoint_returns_provinces_for_region(): void
    {
        // Get regions first
        $regionsResponse = $this->getJson('/api/psgc/regions');
        $regions = $regionsResponse->json();

        $this->assertNotEmpty($regions);

        // Test provinces endpoint with first region
        $regionCode = $regions[0]['code'];
        $response = $this->getJson("/api/psgc/regions/{$regionCode}/provinces");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data), 'Region should have provinces');

        // Verify structure
        foreach ($data as $province) {
            $this->assertArrayHasKey('code', $province);
            $this->assertArrayHasKey('name', $province);
        }
    }

    public function test_cities_endpoint_returns_cities_for_province(): void
    {
        // Get regions and provinces
        $regionsResponse = $this->getJson('/api/psgc/regions');
        $regions = $regionsResponse->json();
        $regionCode = $regions[0]['code'];

        $provincesResponse = $this->getJson("/api/psgc/regions/{$regionCode}/provinces");
        $provinces = $provincesResponse->json();

        $this->assertNotEmpty($provinces);

        // Test cities endpoint
        $provinceCode = $provinces[0]['code'];
        $response = $this->getJson("/api/psgc/provinces/{$provinceCode}/cities");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data), 'Province should have cities');

        // Verify structure
        foreach ($data as $city) {
            $this->assertArrayHasKey('code', $city);
            $this->assertArrayHasKey('name', $city);
        }
    }

    public function test_barangays_endpoint_returns_barangays_for_city(): void
    {
        // Navigate through the hierarchy
        $regionsResponse = $this->getJson('/api/psgc/regions');
        $regions = $regionsResponse->json();
        $regionCode = $regions[0]['code'];

        $provincesResponse = $this->getJson("/api/psgc/regions/{$regionCode}/provinces");
        $provinces = $provincesResponse->json();
        $provinceCode = $provinces[0]['code'];

        $citiesResponse = $this->getJson("/api/psgc/provinces/{$provinceCode}/cities");
        $cities = $citiesResponse->json();

        $this->assertNotEmpty($cities);

        // Test barangays endpoint
        $cityCode = $cities[0]['code'];
        $response = $this->getJson("/api/psgc/cities/{$cityCode}/barangays");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data), 'City should have barangays');

        // Verify structure
        foreach ($data as $barangay) {
            $this->assertArrayHasKey('code', $barangay);
            $this->assertArrayHasKey('name', $barangay);
        }
    }

    public function test_cascading_chain_works(): void
    {
        // Get regions
        $regionsResponse = $this->getJson('/api/psgc/regions');
        $regions = $regionsResponse->json();
        $this->assertgreaterThan(5, count($regions), 'Should have regions');

        // Get provinces for first region
        $regionCode = $regions[0]['code'];
        $provincesResponse = $this->getJson("/api/psgc/regions/{$regionCode}/provinces");
        $provinces = $provincesResponse->json();
        $this->assertGreaterThan(0, count($provinces), 'Region should have provinces');

        // Get cities for first province
        $provinceCode = $provinces[0]['code'];
        $citiesResponse = $this->getJson("/api/psgc/provinces/{$provinceCode}/cities");
        $cities = $citiesResponse->json();
        $this->assertGreaterThan(0, count($cities), 'Province should have cities');

        // Get barangays for first city
        $cityCode = $cities[0]['code'];
        $barangaysResponse = $this->getJson("/api/psgc/cities/{$cityCode}/barangays");
        $barangays = $barangaysResponse->json();
        $this->assertGreaterThan(0, count($barangays), 'City should have barangays');
    }
}
