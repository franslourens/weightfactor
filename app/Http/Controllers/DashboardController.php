<?php

namespace App\Http\Controllers;

use App\Models\BodyFatEntry;
use App\Models\FoodEntry;
use App\Models\Profile;
use App\Models\WeightEntry;
use App\Support\BodyPlan;
use App\Support\FoodWeek;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Show today's calories and the weight trend.
     */
    public function index(): View
    {
        $weights = WeightEntry::query()->orderBy('recorded_on')->get();
        $foods = FoodEntry::query()->orderByDesc('eaten_on')->orderByDesc('id')->limit(20)->get();

        $latest = $weights->last();
        $previous = $weights->count() > 1 ? $weights[$weights->count() - 2] : null;
        $change = $latest && $previous
            ? (float) $latest->weight_kg - (float) $previous->weight_kg
            : null;
        $fromStart = $latest && $weights->count() > 1
            ? (float) $latest->weight_kg - (float) $weights->first()->weight_kg
            : null;

        $start = now()->startOfDay()->subDays(13);
        $recentFoods = FoodEntry::query()
            ->whereDate('eaten_on', '>=', $start->toDateString())
            ->get();
        $todayFoods = $recentFoods->filter(
            fn (FoodEntry $food) => $food->eaten_on->toDateString() === now()->toDateString()
        );
        $sums = $recentFoods
            ->groupBy(fn (FoodEntry $food) => $food->eaten_on->toDateString())
            ->map(fn ($group) => (int) $group->sum(fn (FoodEntry $food) => $food->totalCalories()));

        $caloriePoints = collect(range(0, 13))->map(function (int $offset) use ($start, $sums) {
            $day = $start->copy()->addDays($offset);

            return [
                'label' => $day->format('M j'),
                'value' => (int) ($sums[$day->toDateString()] ?? 0),
            ];
        })->all();

        $week = FoodWeek::current();
        $weekFoods = FoodEntry::query()
            ->whereDate('eaten_on', '>=', $week->start->toDateString())
            ->whereDate('eaten_on', '<=', $week->end->toDateString())
            ->get();

        $profile = Profile::query()->first();
        $plan = $profile && $latest
            ? BodyPlan::fromProfile($profile, (float) $latest->weight_kg)
            : null;

        return view('dashboard', [
            'today' => now()->toDateString(),
            'weekStart' => $week->start->toDateString(),
            'weekEnd' => $week->end->toDateString(),
            'weekCalories' => (int) $weekFoods->sum(fn (FoodEntry $food) => $food->totalCalories()),
            'weekProtein' => round((float) $weekFoods->sum(fn (FoodEntry $food) => $food->totalProtein()), 1),
            'weekCarbs' => round((float) $weekFoods->sum(fn (FoodEntry $food) => $food->totalCarbs()), 1),
            'weekFat' => round((float) $weekFoods->sum(fn (FoodEntry $food) => $food->totalFat()), 1),
            'latest' => $latest,
            'profile' => $profile,
            'plan' => $plan,
            'change' => $change,
            'fromStart' => $fromStart,
            'todayCalories' => (int) ($sums[now()->toDateString()] ?? 0),
            'todayProtein' => round((float) $todayFoods->sum(fn (FoodEntry $food) => $food->totalProtein()), 1),
            'todayCarbs' => round((float) $todayFoods->sum(fn (FoodEntry $food) => $food->totalCarbs()), 1),
            'todayFat' => round((float) $todayFoods->sum(fn (FoodEntry $food) => $food->totalFat()), 1),
            'weights' => $weights->reverse()->take(10),
            'bodyFats' => BodyFatEntry::query()->orderByDesc('recorded_at')->orderByDesc('id')->limit(20)->get(),
            'foods' => $foods,
            'weightPoints' => $weights->map(fn (WeightEntry $entry) => [
                'label' => $entry->recorded_on->format('M j'),
                'value' => (float) $entry->weight_kg,
            ])->all(),
            'caloriePoints' => $caloriePoints,
        ]);
    }
}
