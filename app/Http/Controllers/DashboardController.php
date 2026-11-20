<?php

namespace App\Http\Controllers;

use App\Models\FoodEntry;
use App\Models\Profile;
use App\Models\User;
use App\Support\BodyPlan;
use App\Support\FoodWeek;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    /**
     * Show the owner's calories and weight. This page stays theirs.
     */
    public function index(): View
    {
        return $this->page(User::owner(), personal: false);
    }

    /**
     * Show the signed-in user's own profile.
     */
    public function mine(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isOwner()) {
            return redirect()->route('dashboard');
        }

        if ($user->profile === null) {
            $user->profile()->create(Profile::defaults());
            $user->unsetRelation('profile');
        }

        $user->ensureSlug();

        return redirect()->route('profile.show', $user);
    }

    /**
     * Show one person's profile on its own link. Others can look, only they can change it.
     */
    public function show(User $user): View
    {
        $own = auth()->id() === $user->id;

        return $this->page($user, personal: $own && ! $user->isOwner(), publicName: $own ? null : $user->name);
    }

    /**
     * @param  bool  $personal  True on a member's own page, false on the public dashboard.
     */
    private function page(?User $user, bool $personal, ?string $publicName = null): View
    {
        $weights = $user
            ? $user->weights()->orderBy('recorded_on')->get()
            : new Collection;
        $foods = $user
            ? $user->foods()->orderByDesc('eaten_on')->orderByDesc('id')->limit(20)->get()
            : new Collection;

        $latest = $weights->last();
        $previous = $weights->count() > 1 ? $weights[$weights->count() - 2] : null;
        $change = $latest && $previous
            ? (float) $latest->weight_kg - (float) $previous->weight_kg
            : null;
        $fromStart = $latest && $weights->count() > 1
            ? (float) $latest->weight_kg - (float) $weights->first()->weight_kg
            : null;

        $start = now()->startOfDay()->subDays(13);
        $recentFoods = $user
            ? $user->foods()->whereDate('eaten_on', '>=', $start->toDateString())->get()
            : new Collection;
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
        $weekFoods = $user
            ? $user->foods()
                ->whereDate('eaten_on', '>=', $week->start->toDateString())
                ->whereDate('eaten_on', '<=', $week->end->toDateString())
                ->get()
            : new Collection;

        $profile = $user?->profile;
        $plan = $profile && $latest
            ? BodyPlan::fromProfile($profile, (float) $latest->weight_kg)
            : null;

        $ownPage = $user !== null && auth()->id() === $user->id;

        return view('dashboard', [
            'personal' => $personal,
            'publicName' => $publicName,
            'shareUrl' => $ownPage && $user->slug && ! $user->isOwner()
                ? route('profile.show', $user)
                : null,
            'canEdit' => $ownPage,
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
            'bodyFats' => $user
                ? $user->bodyFats()->orderByDesc('recorded_at')->orderByDesc('id')->limit(20)->get()
                : new Collection,
            'foods' => $foods,
            'weightPoints' => $weights->map(fn ($entry) => [
                'label' => $entry->recorded_on->format('M j'),
                'value' => (float) $entry->weight_kg,
            ])->all(),
            'caloriePoints' => $caloriePoints,
        ]);
    }
}
