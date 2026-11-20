<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (! is_string($user->slug) || $user->slug === '') {
                $user->slug = static::uniqueSlug($user->name);
            }
        });
    }

    /**
     * A public address for this profile, kept unique when names match.
     */
    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'profile';
        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public function ensureSlug(): void
    {
        if (is_string($this->slug) && $this->slug !== '') {
            return;
        }

        $this->slug = static::uniqueSlug($this->name ?: 'profile');
        $this->save();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The account whose stats stay on the public dashboard.
     */
    public static function owner(): ?self
    {
        $email = config('owner.email');

        if (is_string($email) && $email !== '') {
            return static::query()->where('email', $email)->first();
        }

        return static::query()->orderBy('id')->first();
    }

    public function isOwner(): bool
    {
        $owner = static::owner();

        return $owner !== null && $this->is($owner);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function weights(): HasMany
    {
        return $this->hasMany(WeightEntry::class);
    }

    public function foods(): HasMany
    {
        return $this->hasMany(FoodEntry::class);
    }

    public function bodyFats(): HasMany
    {
        return $this->hasMany(BodyFatEntry::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(StepEntry::class);
    }
}
