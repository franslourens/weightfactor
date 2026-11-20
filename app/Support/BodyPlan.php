<?php

namespace App\Support;

use App\Models\Profile;

final class BodyPlan
{
    /**
     * Usual estimate for the energy in one kilogram of body-weight change.
     */
    public const KCAL_PER_KG = 7700;

    public const DEFICIT_MAX = 1000;

    public function __construct(
        public readonly float $weightKg,
        public readonly float $bodyFatPercent,
        public readonly float $targetBodyFatPercent,
        public readonly string $sex,
        public readonly int $age,
        public readonly float $heightCm,
        public readonly string $activity,
        public readonly float $weeklyLossKg,
        public readonly ?int $deficitKcal = null,
    ) {}

    public static function fromProfile(Profile $profile, float $weightKg): self
    {
        return new self(
            weightKg: $weightKg,
            bodyFatPercent: (float) $profile->body_fat_percent,
            targetBodyFatPercent: (float) $profile->target_body_fat_percent,
            sex: $profile->sex,
            age: (int) $profile->age,
            heightCm: (float) $profile->height_cm,
            activity: $profile->activity,
            weeklyLossKg: (float) $profile->weekly_loss_kg,
        );
    }

    public function fatMassKg(): float
    {
        return $this->weightKg * ($this->bodyFatPercent / 100);
    }

    public function leanMassKg(): float
    {
        return $this->weightKg - $this->fatMassKg();
    }

    /**
     * Scale weight at the target body fat if lean mass stays the same.
     */
    public function targetWeightKg(): float
    {
        return $this->leanMassKg() / (1 - ($this->targetBodyFatPercent / 100));
    }

    public function weightToLoseKg(): float
    {
        return $this->weightKg - $this->targetWeightKg();
    }

    /**
     * Mifflin-St Jeor resting metabolism.
     */
    public function bmr(): float
    {
        $bmr = (10 * $this->weightKg) + (6.25 * $this->heightCm) - (5 * $this->age);

        return $bmr + ($this->sex === 'male' ? 5 : -161);
    }

    public function activityLabel(): string
    {
        return Profile::activities()[$this->activity]['label'];
    }

    public function tdee(): float
    {
        return $this->bmr() * Profile::activities()[$this->activity]['multiplier'];
    }

    public function dailyAdjustment(): float
    {
        if ($this->deficitKcal !== null) {
            return (float) $this->deficitKcal;
        }

        return $this->weeklyLossKg * self::KCAL_PER_KG / 7;
    }

    /**
     * Same plan at a daily calorie deficit, in 500 kcal steps.
     */
    public function withDeficit(int $kcal): self
    {
        return new self(
            weightKg: $this->weightKg,
            bodyFatPercent: $this->bodyFatPercent,
            targetBodyFatPercent: $this->targetBodyFatPercent,
            sex: $this->sex,
            age: $this->age,
            heightCm: $this->heightCm,
            activity: $this->activity,
            weeklyLossKg: $kcal * 7 / self::KCAL_PER_KG,
            deficitKcal: $kcal,
        );
    }

    public function nearestDeficit(): int
    {
        return max(0, min(self::DEFICIT_MAX, (int) round($this->dailyAdjustment())));
    }

    /**
     * Figures the deficit slider needs in order to update weeks and calories.
     *
     * @return array{weightKg: float, weightToLose: float, tdee: float, floor: int}
     */
    public function deficitInputs(): array
    {
        return [
            'weightKg' => $this->weightKg,
            'weightToLose' => $this->weightToLoseKg(),
            'tdee' => $this->tdee(),
            'floor' => $this->calorieFloor(),
        ];
    }

    /**
     * Calories under maintenance at this weight. Negative means a surplus.
     * The calorie floor can make this smaller than the chosen deficit.
     */
    public function actualDailyDeficit(): int
    {
        return (int) round($this->tdee() - $this->calorieTarget());
    }

    public function weightAfterDays(int $days): float
    {
        return $this->weightKg - ($this->actualDailyDeficit() * $days / self::KCAL_PER_KG);
    }

    public function monthOutlook(): string
    {
        $gap = $this->actualDailyDeficit();
        $kind = $gap < 0 ? 'surplus' : 'deficit';

        return 'At '.number_format($this->weightKg, 2).' kg the actual '.$kind.' is '.number_format(abs($gap)).' kcal. In 30 days the scale would read about '.number_format($this->weightAfterDays(30), 2).' kg.';
    }

    public function timeline(): string
    {
        $weekly = $this->effectiveWeeklyLossKg();

        if ($weekly <= 0) {
            return 'holding your weight';
        }

        return 'about '.number_format($this->weeks(), 1).' weeks at '.number_format($weekly, 2).' kg/week';
    }

    public function eatNote(): string
    {
        if ($this->effectiveWeeklyLossKg() <= 0 || abs($this->weightToLoseKg()) <= 0.05) {
            return 'per day to hold your weight';
        }

        if ($this->weightToLoseKg() < 0) {
            return 'per day to gain weight at that rate';
        }

        return 'per day to lose weight at that rate';
    }

    public function paceSentence(int $deficit): string
    {
        if (abs($this->weightToLoseKg()) <= 0.05) {
            return 'You are already at the target weight, so the eating target is '.number_format($this->calorieTarget()).' kcal.';
        }

        if ($deficit === 0) {
            return 'A 0 kcal deficit holds your weight, so the eating target is '.number_format($this->calorieTarget()).' kcal.';
        }

        if ($this->calorieTargetIsFloored()) {
            return 'A '.number_format($deficit).' kcal deficit is capped so the target stays at '.number_format($this->calorieFloor()).' kcal, about '.number_format($this->effectiveWeeklyLossKg(), 2).' kg a week.';
        }

        $gaining = $this->weightToLoseKg() < 0;

        return ($gaining ? 'Gaining ' : 'Losing ')
            .number_format($this->weeklyLossKg, 2)
            .' kg a week is about a '.number_format($deficit)
            .' kcal daily '.($gaining ? 'surplus' : 'deficit')
            .', so the eating target is '.number_format($this->calorieTarget()).' kcal.';
    }

    public function calorieTarget(): int
    {
        if ($this->weightToLoseKg() > 0.05) {
            return (int) round(max($this->tdee() - $this->dailyAdjustment(), $this->calorieFloor()));
        }

        if ($this->weightToLoseKg() < -0.05) {
            return (int) round($this->tdee() + $this->dailyAdjustment());
        }

        return (int) round($this->tdee());
    }

    public function calorieTargetIsFloored(): bool
    {
        return $this->weightToLoseKg() > 0.05
            && ($this->tdee() - $this->dailyAdjustment()) < $this->calorieFloor();
    }

    public function weeks(): float
    {
        $weekly = $this->effectiveWeeklyLossKg();

        if ($weekly <= 0) {
            return 0;
        }

        return abs($this->weightToLoseKg()) / $weekly;
    }

    /**
     * Weekly change the eating target can actually produce.
     * A large deficit is limited by the calorie floor.
     */
    private function effectiveWeeklyLossKg(): float
    {
        if ($this->calorieTargetIsFloored()) {
            return max(0, $this->tdee() - $this->calorieFloor()) * 7 / self::KCAL_PER_KG;
        }

        return max(0, $this->weeklyLossKg);
    }

    /**
     * Height-only ideal-weight formulas, in kilograms.
     *
     * @return array<string, int>
     */
    public function idealWeights(): array
    {
        $inchesOverFiveFeet = ($this->heightCm / 2.54) - 60;
        $male = $this->sex === 'male';

        return [
            'G.J. Hamwi (1964)' => (int) round(($male ? 48.0 : 45.5) + ($male ? 2.7 : 2.2) * $inchesOverFiveFeet),
            'B.J. Devine (1974)' => (int) round(($male ? 50.0 : 45.5) + 2.3 * $inchesOverFiveFeet),
            'J.D. Robinson (1983)' => (int) round(($male ? 52.0 : 49.0) + ($male ? 1.9 : 1.7) * $inchesOverFiveFeet),
            'D.R. Miller (1983)' => (int) round(($male ? 56.2 : 53.1) + ($male ? 1.41 : 1.36) * $inchesOverFiveFeet),
        ];
    }

    public function idealWeightLowKg(): int
    {
        return min($this->idealWeights());
    }

    public function idealWeightHighKg(): int
    {
        return max($this->idealWeights());
    }

    public function bmi(): float
    {
        $meters = $this->heightCm / 100;

        return round($this->weightKg / ($meters * $meters), 1);
    }

    public function bmiCategory(): string
    {
        return match (true) {
            $this->bmi() < 18.5 => 'Underweight',
            $this->bmi() < 25 => 'Normal weight',
            $this->bmi() < 30 => 'Overweight',
            default => 'Obese',
        };
    }

    /**
     * Martin Berkhan's height rule: contest weight is about height in cm minus 100.
     * The 10% and 15% figures add 5% and 10% of that contest weight.
     *
     * @return array<int, int>
     */
    public function muscularPotentialKg(): array
    {
        $contest = $this->heightCm - 100;

        return [
            5 => (int) round($contest),
            10 => (int) round($contest * 1.05),
            15 => (int) round($contest * 1.10),
        ];
    }

    private function calorieFloor(): int
    {
        return $this->sex === 'male' ? 1500 : 1200;
    }
}
