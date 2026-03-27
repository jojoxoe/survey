<?php

namespace App\Http\Controllers;

use App\Services\PsgcLocationService;
use Illuminate\Http\JsonResponse;
use Throwable;

class PsgcLocationController extends Controller
{
    public function __construct(protected PsgcLocationService $psgcLocationService) {}

    public function getRegions(): JsonResponse
    {
        return $this->resolveLocationResponse(
            fn (): array => $this->psgcLocationService->regions(),
            'regions'
        );
    }

    public function getProvinces(string $region): JsonResponse
    {
        return $this->resolveLocationResponse(
            fn (): array => $this->psgcLocationService->provinces($region),
            'provinces'
        );
    }

    public function getCitiesMunicipalities(string $province): JsonResponse
    {
        return $this->resolveLocationResponse(
            fn (): array => $this->psgcLocationService->citiesMunicipalities($province),
            'cities_municipalities'
        );
    }

    public function getBarangays(string $cityMunicipality): JsonResponse
    {
        return $this->resolveLocationResponse(
            fn (): array => $this->psgcLocationService->barangays($cityMunicipality),
            'barangays'
        );
    }

    protected function resolveLocationResponse(callable $resolver, string $resource): JsonResponse
    {
        try {
            $locations = $resolver();

            return response()->json([
                'success' => true,
                'data' => $locations,
                'meta' => [
                    'resource' => $resource,
                    'count' => count($locations),
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load locations right now. Please try again in a moment.',
                'data' => [],
                'meta' => [
                    'resource' => $resource,
                    'count' => 0,
                ],
            ], 503);
        }
    }
}
