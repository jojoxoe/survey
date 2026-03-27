<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PsgcLocationService
{
    public function __construct(
        protected string $baseUrl = '',
        protected int $timeout = 10,
        protected int $retryTimes = 2,
        protected int $retrySleepMs = 250,
        protected int $cacheTtlMinutes = 1440,
    ) {
        $this->baseUrl = (string) config('services.psgc.base_url', 'https://psgc.cloud/api/v2');
        $this->timeout = (int) config('services.psgc.timeout', 10);
        $this->retryTimes = (int) config('services.psgc.retry_times', 2);
        $this->retrySleepMs = (int) config('services.psgc.retry_sleep_ms', 250);
        $this->cacheTtlMinutes = (int) config('services.psgc.cache_ttl_minutes', 1440);
    }

    public function regions(): array
    {
        return $this->cachedList('regions:all', '/regions');
    }

    public function provinces(string $regionCode): array
    {
        return $this->cachedList(
            sprintf('regions:%s:provinces', $regionCode),
            sprintf('/regions/%s/provinces', rawurlencode($regionCode))
        );
    }

    public function citiesMunicipalities(string $provinceCode): array
    {
        return $this->cachedList(
            sprintf('provinces:%s:cities-municipalities', $provinceCode),
            sprintf('/provinces/%s/cities-municipalities', rawurlencode($provinceCode))
        );
    }

    public function barangays(string $cityMunicipalityCode): array
    {
        return $this->cachedList(
            sprintf('cities-municipalities:%s:barangays', $cityMunicipalityCode),
            sprintf('/cities-municipalities/%s/barangays', rawurlencode($cityMunicipalityCode))
        );
    }

    protected function cachedList(string $cacheKey, string $path): array
    {
        return Cache::remember(
            sprintf('psgc:v2:%s', $cacheKey),
            now()->addMinutes($this->cacheTtlMinutes),
            fn () => $this->fetchNormalizedList($path)
        );
    }

    protected function fetchNormalizedList(string $path): array
    {
        $response = Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retrySleepMs, throw: false)
            ->get($path);

        if ($response->failed()) {
            throw new RuntimeException(sprintf('PSGC API request failed for path [%s].', $path));
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException(sprintf('PSGC API returned invalid payload for path [%s].', $path));
        }

        $items = $payload;
        if (isset($payload['data']) && is_array($payload['data'])) {
            $items = $payload['data'];
        }

        return collect($items)
            ->map(function ($item) {
                if (! is_array($item)) {
                    return null;
                }

                $code = (string) ($item['code'] ?? '');
                $name = (string) ($item['name'] ?? '');
                if ($code === '' || $name === '') {
                    return null;
                }

                return [
                    'code' => $code,
                    'name' => $name,
                ];
            })
            ->filter()
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
