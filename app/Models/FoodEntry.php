<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['eaten_on', 'name', 'quantity', 'calories', 'protein_g', 'carbs_g', 'fat_g'])]
class FoodEntry extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'eaten_on' => 'date',
            'quantity' => 'decimal:2',
            'calories' => 'integer',
            'protein_g' => 'decimal:1',
            'carbs_g' => 'decimal:1',
            'fat_g' => 'decimal:1',
        ];
    }

    public function quantityLabel(): string
    {
        $formatted = number_format((float) $this->quantity, 2, '.', '');

        return str_ends_with($formatted, '.00')
            ? (string) (int) $formatted
            : rtrim(rtrim($formatted, '0'), '.');
    }

    public function totalCalories(): int
    {
        return (int) round($this->calories * (float) $this->quantity);
    }

    public function totalProtein(): float
    {
        return round((float) $this->protein_g * (float) $this->quantity, 1);
    }

    public function totalCarbs(): float
    {
        return round((float) $this->carbs_g * (float) $this->quantity, 1);
    }

    public function totalFat(): float
    {
        return round((float) $this->fat_g * (float) $this->quantity, 1);
    }
}
