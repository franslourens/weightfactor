<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Weight change over the month before the latest weigh-in, and the daily
 * calorie gap that change works out to.
 */
final class PastMonth
{
    public const DAYS = 30;

    private function __construct(
        public readonly int $days,
        public readonly float $startWeightKg,
        public readonly float $endWeightKg,
    ) {}

    /**
     * @param  Collection<int, mixed>  $weights  Oldest first, each with recorded_on and weight_kg.
     */
    public static function fromWeights(Collection $weights): ?self
    {
        if ($weights->count() < 2) {
            return null;
        }

        $latest = $weights->last();
        $end = Carbon::parse($latest->recorded_on)->startOfDay();
        $cutoff = $end->copy()->subDays(self::DAYS);

        $earlier = $weights->filter(
            fn ($entry) => Carbon::parse($entry->recorded_on)->startOfDay()->lt($end)
        );

        if ($earlier->isEmpty()) {
            return null;
        }

        $onOrBefore = $earlier->filter(
            fn ($entry) => Carbon::parse($entry->recorded_on)->startOfDay()->lte($cutoff)
        );
        $startEntry = $onOrBefore->isNotEmpty() ? $onOrBefore->last() : $earlier->first();
        $start = Carbon::parse($startEntry->recorded_on)->startOfDay();
        $days = (int) round($start->diffInDays($end));

        if ($days < 1) {
            return null;
        }

        return new self($days, (float) $startEntry->weight_kg, (float) $latest->weight_kg);
    }

    /**
     * Kilograms lost. Negative means the scale went up.
     */
    public function weightLostKg(): float
    {
        return $this->startWeightKg - $this->endWeightKg;
    }

    /**
     * Daily calorie gap implied by the scale. Positive is a deficit.
     */
    public function dailyDeficit(): int
    {
        return (int) round($this->weightLostKg() * BodyPlan::KCAL_PER_KG / $this->days);
    }

    public function sentence(): string
    {
        $daysWord = $this->days === 1 ? 'day' : 'days';
        $deficit = $this->dailyDeficit();
        $kind = $deficit < 0 ? 'surplus' : 'deficit';
        $kcal = number_format(abs($deficit));
        $lost = $this->weightLostKg();

        if (abs($lost) < 0.005) {
            return 'Over the last '.$this->days.' '.$daysWord.' the scale stayed at '.number_format($this->endWeightKg, 2).' kg, a '.$kcal.' kcal '.$kind.'.';
        }

        $verb = $lost > 0 ? 'lost' : 'gained';

        return 'Over the last '.$this->days.' '.$daysWord.' you '.$verb.' '.number_format(abs($lost), 2).' kg, a '.$kcal.' kcal '.$kind.'.';
    }
}
