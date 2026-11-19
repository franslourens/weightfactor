<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'sex',
    'age',
    'height_cm',
    'body_fat_percent',
    'target_body_fat_percent',
    'activity',
    'weekly_loss_kg',
])]
class Profile extends Model
{
    /**
     * Activity multipliers applied to resting metabolism.
     *
     * @return array<string, array{label: string, multiplier: float}>
     */
    public static function activities(): array
    {
        return [
            'sedentary' => ['label' => 'Sedentary, desk job and little exercise', 'multiplier' => 1.2],
            'light' => ['label' => 'Light exercise, 1–3 workouts a week', 'multiplier' => 1.375],
            'moderate' => ['label' => 'Moderately active, 3–5 workouts a week', 'multiplier' => 1.55],
            'active' => ['label' => 'Very active, 6–7 workouts a week', 'multiplier' => 1.725],
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'height_cm' => 'decimal:1',
            'body_fat_percent' => 'decimal:1',
            'target_body_fat_percent' => 'decimal:1',
            'weekly_loss_kg' => 'decimal:2',
        ];
    }
}
