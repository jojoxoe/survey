<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PsgcLocationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_regions_endpoint_supports_v2_wrapped_data_payload(): void
    {
        Http::fake([
            'https://psgc.cloud/api/v2/regions' => Http::response([
                'data' => [
                    ['code' => '0200000000', 'name' => 'Region II (Cagayan Valley)'],
                    ['code' => '0100000000', 'name' => 'Region I (Ilocos Region)'],
                ],
            ], 200),
        ]);

        $response = $this->getJson(route('locations.regions'));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.count', 2);

        $codes = collect($response->json('data'))->pluck('code')->all();
        $this->assertSame(['0100000000', '0200000000'], $codes);
    }

    public function test_regions_endpoint_returns_normalized_payload(): void
    {
        Http::fake([
            'https://psgc.cloud/api/v2/regions' => Http::response([
                ['code' => '02', 'name' => 'Cagayan Valley'],
                ['code' => '01', 'name' => 'Ilocos Region'],
                ['code' => '', 'name' => 'Invalid'],
            ], 200),
        ]);

        $response = $this->getJson(route('locations.regions'));

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'meta' => [
                    'resource' => 'regions',
                    'count' => 2,
                ],
            ])
            ->assertJsonPath('data.0.code', '02')
            ->assertJsonPath('data.1.code', '01');
    }

    public function test_regions_endpoint_uses_cache_to_reduce_duplicate_api_calls(): void
    {
        Cache::flush();

        Http::fake([
            'https://psgc.cloud/api/v2/regions' => Http::response([
                ['code' => '01', 'name' => 'Ilocos Region'],
            ], 200),
        ]);

        $this->getJson(route('locations.regions'))->assertOk();
        $this->getJson(route('locations.regions'))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_location_endpoint_returns_fallback_error_payload_on_api_failure(): void
    {
        Http::fake([
            'https://psgc.cloud/api/v2/regions' => Http::response([], 500),
        ]);

        $response = $this->getJson(route('locations.regions'));

        $response
            ->assertStatus(503)
            ->assertJson([
                'success' => false,
                'data' => [],
                'meta' => [
                    'resource' => 'regions',
                    'count' => 0,
                ],
            ]);
    }
}
