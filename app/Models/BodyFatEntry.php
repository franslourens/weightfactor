<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'recorded_at', 'body_fat_percent'])]
class BodyFatEntry extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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

    public static function record(User $user, float $percent): self
    {
        return $user->bodyFats()->create([
            'recorded_at' => now(),
            'body_fat_percent' => round($percent, 1),
        ]);
    }
}
