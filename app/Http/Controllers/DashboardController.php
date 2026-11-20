<?php

namespace App\Http\Controllers;

use App\Models\FoodEntry;
use App\Models\Profile;
use App\Models\StepEntry;
use App\Models\User;
use App\Support\BodyPlan;
use App\Support\FoodWeek;
use App\Support\PastMonth;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    /**
     * Show the owner's calories and weight. This page stays theirs.
     */
    public function index(): View|RedirectResponse
    {
        if ($redirect = $this->redirectForSteps(User::owner(), onDashboard: true)) {
            return $redirect;
        }

        return $this->page(User::owner(), personal: false, onDashboard: true);
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
    public function show(User $user): View|RedirectResponse
    {
        if ($redirect = $this->redirectForSteps($user, onDashboard: false)) {
            return $redirect;
        }

        $own = auth()->id() === $user->id;

        return $this->page($user, personal: $own && ! $user->isOwner(), onDashboard: false, publicName: $own ? null : $user->name);
    }

    /**
     * @param  bool  $personal  True on a member's own page, false on the public dashboard.
     */
    private function page(?User $user, bool $personal, bool $onDashboard = false, ?string $publicName = null): View
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
            'pastMonth' => PastMonth::fromWeights($weights)?->sentence(),
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
            ...$this->stepMonth($user, $ownPage, $onDashboard),
        ]);
    }

    /**
     * @return array{stepMonth: string|null, stepTotal: int, stepCells: array<int, array{date: string, label: string, steps: int|null, today: bool}|null>, canEditSteps: bool, stepPreviousUrl: string|null, stepPreviousLabel: string|null, stepNextUrl: string|null, stepNextLabel: string|null}
     */
    private function stepMonth(?User $user, bool $ownPage, bool $onDashboard): array
    {
        $empty = [
            'stepMonth' => null,
            'stepTotal' => 0,
            'stepCells' => [],
            'canEditSteps' => false,
            'stepPreviousUrl' => null,
            'stepPreviousLabel' => null,
            'stepNextUrl' => null,
            'stepNextLabel' => null,
        ];

        if ($user === null) {
            return $empty;
        }

        $current = now()->startOfMonth();
        $earliest = $user->created_at->copy()->startOfMonth();

        if ($earliest->greaterThan($current)) {
            $earliest = $current->copy();
        }

        $month = $this->requestedStepMonth($current);

        if ($month->lessThan($earliest)) {
            $month = $earliest->copy();
        }

        if ($month->greaterThan($current)) {
            $month = $current->copy();
        }

        $entries = $user->steps()
            ->whereDate('recorded_on', '>=', $month->toDateString())
            ->whereDate('recorded_on', '<=', $month->copy()->endOfMonth()->toDateString())
            ->get()
            ->keyBy(fn (StepEntry $entry) => $entry->recorded_on->toDateString());

        $cells = [];

        for ($pad = 1; $pad < $month->dayOfWeekIso; $pad++) {
            $cells[] = null;
        }

        for ($day = 1; $day <= $month->daysInMonth; $day++) {
            $date = $month->copy()->day($day);
            $key = $date->toDateString();

            $cells[] = [
                'date' => $key,
                'label' => $date->format('D j'),
                'steps' => $entries->has($key) ? (int) $entries[$key]->steps : null,
                'today' => $key === now()->toDateString(),
            ];
        }

        $previous = $month->greaterThan($earliest) ? $month->copy()->subMonth() : null;
        $next = $month->lessThan($current) ? $month->copy()->addMonth() : null;

        return [
            'stepMonth' => $month->format('F Y'),
            'stepTotal' => (int) $entries->sum('steps'),
            'stepCells' => $cells,
            'canEditSteps' => $ownPage && $month->isSameMonth($current),
            'stepPreviousUrl' => $previous ? $this->stepUrl($user, $onDashboard, $previous) : null,
            'stepPreviousLabel' => $previous?->format('F Y'),
            'stepNextUrl' => $next ? $this->stepUrl($user, $onDashboard, $next) : null,
            'stepNextLabel' => $next?->format('F Y'),
        ];
    }

    private function redirectForSteps(?User $user, bool $onDashboard): ?RedirectResponse
    {
        $query = request()->query('steps');

        if (is_string($query) && preg_match('/^(\d{4})-(\d{2})$/', $query, $matches)) {
            $month = $this->monthFromParts((int) $matches[1], (int) $matches[2]) ?? now()->startOfMonth();

            return redirect($this->stepUrl($user, $onDashboard, $this->clampMonth($user, $month)));
        }

        $year = request()->route('year');
        $monthNumber = request()->route('month');

        if ($year === null && $monthNumber === null) {
            return null;
        }

        $requested = $this->monthFromParts((int) $year, (int) $monthNumber);

        if ($requested === null) {
            abort(404);
        }

        $canonical = $this->clampMonth($user, $requested);
        $target = $this->stepUrl($user, $onDashboard, $canonical);

        if (request()->getPathInfo() !== (parse_url($target, PHP_URL_PATH) ?: $target)) {
            return redirect($target);
        }

        return null;
    }

    private function requestedStepMonth(Carbon $current): Carbon
    {
        $year = request()->route('year');
        $monthNumber = request()->route('month');

        if ($year === null && $monthNumber === null) {
            return $current->copy();
        }

        return $this->monthFromParts((int) $year, (int) $monthNumber) ?? $current->copy();
    }

    private function clampMonth(?User $user, Carbon $month): Carbon
    {
        $current = now()->startOfMonth();
        $earliest = $user?->created_at->copy()->startOfMonth() ?? $current->copy();

        if ($earliest->greaterThan($current)) {
            $earliest = $current->copy();
        }

        if ($month->lessThan($earliest)) {
            return $earliest->copy();
        }

        if ($month->greaterThan($current)) {
            return $current->copy();
        }

        return $month->copy()->startOfMonth();
    }

    private function stepUrl(?User $user, bool $onDashboard, Carbon $month): string
    {
        if ($month->isSameMonth(now())) {
            return $onDashboard || $user === null
                ? route('dashboard')
                : route('profile.show', $user);
        }

        $parameters = [
            'year' => $month->year,
            'month' => $month->month,
        ];

        if ($onDashboard || $user === null) {
            return route('dashboard.steps', $parameters);
        }

        return route('profile.steps', ['user' => $user, ...$parameters]);
    }

    private function monthFromParts(int $year, int $month): ?Carbon
    {
        if ($month < 1 || $month > 12 || $year < 1) {
            return null;
        }

        try {
            $parsed = Carbon::createFromDate($year, $month, 1)?->startOfMonth();
        } catch (\Throwable) {
            return null;
        }

        if ($parsed === null || $parsed->year !== $year || $parsed->month !== $month) {
            return null;
        }

        return $parsed;
    }
}
