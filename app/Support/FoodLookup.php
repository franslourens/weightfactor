<?php

namespace App\Support;

use App\Models\FoodEntry;
use App\Models\User;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class FoodLookup
{
    /**
     * Find foods by name, including calories, protein, carbs, and fat.
     *
     * @return array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>
     */
    public function search(string $query): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return [];
        }

        if (preg_match('/^\d{8,14}$/', $query) === 1) {
            return $this->fromBarcode($query);
        }

        $remote = $this->remoteFoods($query);

        return collect()
            ->merge($this->fromLog($query, auth()->user()))
            ->merge(array_slice($this->fromCatalog($query), 0, 6))
            ->merge(array_slice($remote['usda'], 0, 4))
            ->merge(array_slice($this->matchingName($remote['off'], $query), 0, 4))
            ->unique(fn (array $food) => mb_strtolower($food['name'].'|'.$food['serving']))
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>
     */
    private function fromLog(string $query, ?User $user): array
    {
        $words = array_values(array_filter(
            preg_split('/\s+/u', $query) ?: [],
            fn (string $word) => mb_strlen($word) >= 2,
        ));

        if ($words === []) {
            return [];
        }

        $foods = FoodEntry::query()->with('user')->orderByDesc('id');

        foreach ($words as $word) {
            $like = '%'.str_replace(['%', '_', '\\'], '', $word).'%';
            $foods->where('name', 'like', $like);
        }

        return $foods
            ->limit(40)
            ->get()
            ->unique(fn (FoodEntry $food) => mb_strtolower($food->name).'|'.$food->user_id)
            ->sortBy(fn (FoodEntry $food) => $user !== null && (int) $food->user_id === (int) $user->id ? 0 : 1)
            ->take(5)
            ->map(function (FoodEntry $food) use ($user) {
                $own = $user !== null && (int) $food->user_id === (int) $user->id;

                return [
                    'name' => $food->name,
                    'calories' => (int) $food->calories,
                    'protein' => (float) $food->protein_g,
                    'carbs' => (float) $food->carbs_g,
                    'fat' => (float) $food->fat_g,
                    'serving' => 'previously logged',
                    'source' => $own ? 'Your log' : 'Logged by '.($food->user->name ?? 'someone'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>
     */
    private function fromCatalog(string $query): array
    {
        $matches = [];

        foreach ([...FoodCatalog::foods(), ...RestaurantMenus::foods()] as $food) {
            $position = mb_stripos($food['name'], $query);

            if ($position === false) {
                continue;
            }

            $matches[] = [
                'food' => [
                    'name' => $food['name'],
                    'calories' => $food['calories'],
                    'protein' => $food['protein'],
                    'carbs' => $food['carbs'],
                    'fat' => $food['fat'],
                    'serving' => $food['serving'],
                    'source' => $food['source'] ?? 'Common foods',
                ],
                'rank' => $position === 0 ? 0 : 1,
            ];
        }

        usort($matches, fn (array $left, array $right) => [$left['rank'], $left['food']['name']] <=> [$right['rank'], $right['food']['name']]);

        return array_map(fn (array $match) => $match['food'], $matches);
    }

    /**
     * Open Food Facts matches any word, including a brand such as Burger, so keep hits that contain the whole search.
     *
     * @param  array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>  $foods
     * @return array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>
     */
    private function matchingName(array $foods, string $query): array
    {
        $words = array_values(array_filter(
            preg_split('/\s+/u', mb_strtolower($query)) ?: [],
            fn (string $word) => mb_strlen($word) >= 3,
        ));

        return array_values(array_filter($foods, function (array $food) use ($words): bool {
            $name = mb_strtolower($food['name']);

            foreach ($words as $word) {
                if (! str_contains($name, $word)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @return array{usda: array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>, off: array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>}
     */
    private function remoteFoods(string $query): array
    {
        $usdaKey = 'usda-food:'.md5(mb_strtolower($query));
        $offKey = 'off-food:'.md5(mb_strtolower($query));
        $usda = Cache::get($usdaKey);
        $off = Cache::get($offKey);
        $apiKey = config('services.usda.key');
        $needsUsda = ! is_array($usda) && is_string($apiKey) && $apiKey !== '';
        $needsOff = ! is_array($off);
        $responses = [];

        if ($needsUsda || $needsOff) {
            $responses = Http::pool(function (Pool $pool) use ($query, $needsUsda, $needsOff, $apiKey): void {
                if ($needsUsda) {
                    $pool->as('usda')->timeout(4)->acceptJson()->get('https://api.nal.usda.gov/fdc/v1/foods/search', [
                        'query' => $query,
                        'pageSize' => 5,
                        'dataType' => 'Foundation,SR Legacy',
                        'api_key' => $apiKey,
                    ]);
                }

                if ($needsOff) {
                    $pool->as('off')
                        ->timeout(4)
                        ->withHeaders(['User-Agent' => 'WeightFactor/1.0 (personal food log)'])
                        ->acceptJson()
                        ->get('https://search.openfoodfacts.org/search', [
                            'q' => $query,
                            'page_size' => 5,
                            'fields' => 'code,product_name,brands,serving_size,nutriments',
                        ]);
                }
            });
        }

        if (! is_array($usda)) {
            $usda = $this->cachedFoods($responses['usda'] ?? null, $usdaKey, fn (Response $response) => $this->usdaFoods($response));
        }

        if (! is_array($off)) {
            $off = $this->cachedFoods($responses['off'] ?? null, $offKey, fn (Response $response) => $this->offFoods($response->json('hits') ?? []));
        }

        return ['usda' => $usda, 'off' => $off];
    }

    /**
     * @param  callable(Response): array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>  $parse
     * @return array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>
     */
    private function cachedFoods(mixed $response, string $cacheKey, callable $parse): array
    {
        if (! $response instanceof Response || ! $response->ok()) {
            return [];
        }

        $foods = $parse($response);
        Cache::put($cacheKey, $foods, now()->addDay());

        return $foods;
    }

    /**
     * @return array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>
     */
    private function fromBarcode(string $code): array
    {
        $cacheKey = 'off-barcode:'.$code;
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = Http::timeout(4)
                ->withHeaders(['User-Agent' => 'WeightFactor/1.0 (personal food log)'])
                ->acceptJson()
                ->get('https://world.openfoodfacts.org/api/v2/product/'.$code.'.json', [
                    'fields' => 'product_name,brands,serving_size,nutriments',
                ]);
        } catch (Throwable) {
            return [];
        }

        if (! $response->ok() || (int) $response->json('status') !== 1) {
            return [];
        }

        $food = $this->offProduct($response->json('product') ?? []);
        $foods = $food === null ? [] : [$food];
        Cache::put($cacheKey, $foods, now()->addDay());

        return $foods;
    }

    /**
     * @return array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>
     */
    private function usdaFoods(Response $response): array
    {
        $foods = [];

        foreach ($response->json('foods') ?? [] as $food) {
            if (! is_array($food)) {
                continue;
            }

            $parsed = $this->usdaFood($food);

            if ($parsed !== null) {
                $foods[] = $parsed;
            }
        }

        return $foods;
    }

    /**
     * @param  array<int, mixed>  $hits
     * @return array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>
     */
    private function offFoods(array $hits): array
    {
        $foods = [];
        $pending = [];

        foreach ($hits as $hit) {
            if (! is_array($hit)) {
                continue;
            }

            $nutriments = is_array($hit['nutriments'] ?? null) ? $hit['nutriments'] : [];
            $code = trim((string) ($hit['code'] ?? ''));

            if ($code !== '' && ! is_numeric($nutriments['energy-kcal_serving'] ?? null)) {
                $pending[$code] = $hit;

                continue;
            }

            $parsed = $this->offProduct($hit);

            if ($parsed !== null) {
                $foods[] = $parsed;
            }
        }

        if ($pending !== []) {
            $responses = Http::pool(function (Pool $pool) use ($pending): void {
                foreach (array_keys($pending) as $code) {
                    $pool->as('code-'.$code)
                        ->timeout(4)
                        ->withHeaders(['User-Agent' => 'WeightFactor/1.0 (personal food log)'])
                        ->acceptJson()
                        ->get('https://world.openfoodfacts.org/api/v2/product/'.$code.'.json', [
                            'fields' => 'product_name,brands,serving_size,nutriments',
                        ]);
                }
            });

            foreach ($pending as $code => $hit) {
                $response = $responses['code-'.$code] ?? null;
                $product = $hit;

                if ($response instanceof Response && $response->ok() && (int) $response->json('status') === 1) {
                    $loaded = $response->json('product');
                    $product = is_array($loaded) ? $loaded : $hit;
                }

                $parsed = $this->offProduct($product);

                if ($parsed !== null) {
                    $foods[] = $parsed;
                }
            }
        }

        return $this->preferServings($foods);
    }

    /**
     * Keep one result per food name, and keep the whole item when a per-100 g copy is also present.
     *
     * @param  array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>  $foods
     * @return array<int, array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}>
     */
    private function preferServings(array $foods): array
    {
        $best = [];

        foreach ($foods as $food) {
            $key = mb_strtolower(trim(explode(' · ', $food['name'])[0]));
            $current = $best[$key] ?? null;

            if ($current === null || ($current['serving'] === 'per 100 g' && $food['serving'] !== 'per 100 g')) {
                $best[$key] = $food;
            }
        }

        return array_values($best);
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}|null
     */
    private function offProduct(array $product): ?array
    {
        $nutriments = is_array($product['nutriments'] ?? null) ? $product['nutriments'] : [];
        $perServing = is_numeric($nutriments['energy-kcal_serving'] ?? null);
        $calories = $perServing ? $nutriments['energy-kcal_serving'] : ($nutriments['energy-kcal_100g'] ?? null);

        if (! is_numeric($calories) || (int) round((float) $calories) < 1) {
            return null;
        }

        $name = trim((string) ($product['product_name'] ?? ''));
        $brand = trim(strtok($this->brandName($product['brands'] ?? ''), ',') ?: '');

        if ($name === '') {
            $name = $brand;
        } elseif ($brand !== '' && ! str_contains(mb_strtolower($name), mb_strtolower($brand))) {
            $name .= ' · '.$brand;
        }

        if ($name === '') {
            return null;
        }

        $suffix = $perServing ? 'serving' : '100g';

        return [
            'name' => mb_substr($name, 0, 120),
            'calories' => (int) round((float) $calories),
            'protein' => round((float) ($nutriments['proteins_'.$suffix] ?? 0), 1),
            'carbs' => round((float) ($nutriments['carbohydrates_'.$suffix] ?? 0), 1),
            'fat' => round((float) ($nutriments['fat_'.$suffix] ?? 0), 1),
            'serving' => $perServing ? (trim((string) ($product['serving_size'] ?? '')) ?: '1 serving') : 'per 100 g',
            'source' => 'Open Food Facts',
        ];
    }

    private function brandName(mixed $brands): string
    {
        if (is_array($brands)) {
            $brands = $brands[0] ?? '';
        }

        return trim((string) $brands);
    }

    /**
     * @param  array<string, mixed>  $food
     * @return array{name: string, calories: int, protein: float, carbs: float, fat: float, serving: string, source: string}|null
     */
    private function usdaFood(array $food): ?array
    {
        $name = trim((string) ($food['description'] ?? ''));
        $nutrients = [];

        foreach ($food['foodNutrients'] ?? [] as $nutrient) {
            if (isset($nutrient['nutrientId'])) {
                $nutrients[(int) $nutrient['nutrientId']] = $nutrient['value'] ?? null;
            }
        }

        if ($name === '' || ! is_numeric($nutrients[1008] ?? null)) {
            return null;
        }

        return [
            'name' => mb_substr($name, 0, 120),
            'calories' => (int) round((float) $nutrients[1008]),
            'protein' => round((float) ($nutrients[1003] ?? 0), 1),
            'carbs' => round((float) ($nutrients[1005] ?? 0), 1),
            'fat' => round((float) ($nutrients[1004] ?? 0), 1),
            'serving' => 'per 100 g',
            'source' => 'USDA',
        ];
    }
}
