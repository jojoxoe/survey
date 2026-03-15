<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PsgcController extends Controller
{
    protected function loadPsgc(): array
    {
        return Cache::remember('psgc_data', 86400, function () {
            $path = storage_path('app/psgc/psgc.json');
            return json_decode(file_get_contents($path), true);
        });
    }

    public function regions(): JsonResponse
    {
        $data = $this->loadPsgc();

        return response()->json(collect($data)->map(fn ($r) => [
            'code' => $r['code'],
            'name' => $r['name'],
        ])->sortBy('name')->values());
    }

    public function provinces(string $regionCode): JsonResponse
    {
        $data = $this->loadPsgc();
        $region = collect($data)->firstWhere('code', $regionCode);

        if (!$region) {
            return response()->json([]);
        }

        return response()->json(collect($region['provinces'])->map(fn ($p) => [
            'code' => $p['code'],
            'name' => $p['name'],
        ])->sortBy('name')->values());
    }

    public function cities(string $provinceCode): JsonResponse
    {
        $data = $this->loadPsgc();

        foreach ($data as $region) {
            foreach ($region['provinces'] as $province) {
                if ($province['code'] === $provinceCode) {
                    return response()->json(collect($province['cities'])->map(fn ($c) => [
                        'code' => $c['code'],
                        'name' => $c['name'],
                    ])->sortBy('name')->values());
                }
            }
        }

        return response()->json([]);
    }

    public function barangays(string $cityCode): JsonResponse
    {
        $data = $this->loadPsgc();

        foreach ($data as $region) {
            foreach ($region['provinces'] as $province) {
                foreach ($province['cities'] as $city) {
                    if ($city['code'] === $cityCode) {
                        return response()->json(collect($city['barangays'])->map(fn ($b) => [
                            'code' => $b['code'],
                            'name' => $b['name'],
                        ])->sortBy('name')->values());
                    }
                }
            }
        }

        return response()->json([]);
    }
}
