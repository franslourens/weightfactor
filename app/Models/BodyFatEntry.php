<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['recorded_at', 'body_fat_percent'])]
class BodyFatEntry extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'body_fat_percent' => 'decimal:1',
        ];
    }

    public static function record(float $percent): self
    {
        return static::query()->create([
            'recorded_at' => now(),
            'body_fat_percent' => round($percent, 1),
        ]);
    }
}
