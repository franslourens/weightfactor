<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SyncOwner extends Command
{
    protected $signature = 'owner:sync';

    protected $description = 'Create or update the single login that can change weight, food, and the target';

    public function handle(): int
    {
        $email = config('owner.email');
        $password = config('owner.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->warn('Set OWNER_EMAIL and OWNER_PASSWORD before signing in.');

            return self::SUCCESS;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('owner.name') ?: 'Frans',
                'password' => $password,
            ],
        );

        $this->info('Owner login is ready.');

        return self::SUCCESS;
    }
}
