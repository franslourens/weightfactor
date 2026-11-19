<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class FoodWeek
{
    public function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end,
    ) {}

    /**
     * The current logging week, Sunday through the following Saturday.
     */
    public static function current(?CarbonInterface $today = null): self
    {
        $today = Carbon::parse($today ?? now())->startOfDay();
        $start = $today->copy()->startOfWeek(Carbon::SUNDAY);

        return new self($start, $start->copy()->addDays(6));
    }

    public function contains(string $date): bool
    {
        $day = Carbon::parse($date)->startOfDay();

        return $day->betweenIncluded($this->start, $this->end);
    }
}
