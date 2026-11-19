<?php

namespace App\Support;

final class BodyFat
{
    /**
     * U.S. Navy body-fat percentage from metric circumferences.
     * https://www.calculator.net/body-fat-calculator.html
     */
    public static function navy(string $sex, float $heightCm, float $neckCm, float $waistCm, ?float $hipCm = null): float
    {
        if ($sex === 'female') {
            $denominator = 1.29579
                - 0.35004 * log10($waistCm + (float) $hipCm - $neckCm)
                + 0.22100 * log10($heightCm);
        } else {
            $denominator = 1.0324
                - 0.19077 * log10($waistCm - $neckCm)
                + 0.15456 * log10($heightCm);
        }

        return 495 / $denominator - 450;
    }

    /**
     * BMI method from calculator.net.
     */
    public static function bmiMethod(string $sex, float $bmi, int $age): float
    {
        return (1.20 * $bmi) + (0.23 * $age) - ($sex === 'male' ? 16.2 : 5.4);
    }

    public static function category(string $sex, float $percent): string
    {
        $bands = $sex === 'male'
            ? [5 => 'Essential fat', 13 => 'Athletes', 17 => 'Fitness', 24 => 'Average']
            : [13 => 'Essential fat', 20 => 'Athletes', 24 => 'Fitness', 31 => 'Average'];

        foreach ($bands as $maximum => $label) {
            if ($percent <= $maximum) {
                return $label;
            }
        }

        return 'Obese';
    }
}
