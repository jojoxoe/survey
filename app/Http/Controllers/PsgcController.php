<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\City;
use App\Models\Province;
use App\Models\Region;
use Illuminate\Http\JsonResponse;

class PsgcController extends Controller
{
    public function regions(): JsonResponse
    {
        $regions = Region::query()
            ->select('code', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($regions);
    }

    public function provinces(string $regionCode): JsonResponse
    {
        $provinces = Province::query()
            ->whereHas('region', fn ($query) => $query->where('code', $regionCode))
            ->select('code', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($provinces);
    }

    public function cities(string $provinceCode): JsonResponse
    {
        $cities = City::query()
            ->whereHas('province', fn ($query) => $query->where('code', $provinceCode))
            ->select('code', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($cities);
    }

    public function barangays(string $cityCode): JsonResponse
    {
        $barangays = Barangay::query()
            ->whereHas('city', fn ($query) => $query->where('code', $cityCode))
            ->select('code', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($barangays);
    }
}
