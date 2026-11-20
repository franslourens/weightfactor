<?php

namespace App\Console\Commands;

use App\Models\BodyFatEntry;
use App\Models\FoodEntry;
use App\Models\Profile;
use App\Models\User;
use App\Models\WeightEntry;
use Illuminate\Console\Command;

class SyncOwner extends Command
{
    protected $signature = 'owner:sync';

    protected $description = 'Create or update the owner login and keep existing records on that account';

    public function handle(): int
    {
        $email = config('owner.email');
        $password = config('owner.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->warn('Set OWNER_EMAIL and OWNER_PASSWORD before signing in.');

            return self::SUCCESS;
        }

        $owner = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('owner.name') ?: 'Frans',
                'password' => $password,
            ],
        );

        foreach ([Profile::class, WeightEntry::class, FoodEntry::class, BodyFatEntry::class] as $model) {
            $model::query()->whereNull('user_id')->update(['user_id' => $owner->id]);
        }

        if (! $owner->profile()->exists()) {
            $owner->profile()->create(Profile::defaults());
        }

        $this->info('Owner login is ready.');

        return self::SUCCESS;
    }
}
