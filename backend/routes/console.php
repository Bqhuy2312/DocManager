<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('guests:cleanup', function () {
    $deleted = 0;

    User::query()
        ->where('is_guest', true)
        ->whereNotNull('guest_expires_at')
        ->where('guest_expires_at', '<=', now())
        ->chunkById(100, function ($guests) use (&$deleted): void {
            foreach ($guests as $guest) {
                $guest->tokens()->delete();
                $guest->favorites()->detach();
                $guest->notifications()->delete();
                $guest->activityLogs()->delete();
                $guest->delete();

                $deleted++;
            }
        });

    $this->info("Deleted {$deleted} expired guest viewer account(s).");
})->purpose('Delete expired guest viewer accounts and related temporary data');

Schedule::command('guests:cleanup')->daily();
