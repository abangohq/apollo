<?php

namespace App\Console;

use App\Jobs\CloseDailyBooks;
use App\Models\Kyc;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new CloseDailyBooks())
            ->dailyAt('00:00')->timezone('Africa/Lagos');

        $schedule->call(function () {
            Kyc::where('status', 'pending')->where('created_at', '<=', now()->subDay())->delete();
        })->daily()->timezone('Africa/Lagos');

        $schedule->command('redbiller:check-balance')->everyFifteenMinutes();
        $schedule->command('app:fetch-crypto-prices')->everyTenMinutes();
        $schedule->command('bills:resolve')->everyTenMinutes();
        $schedule->command('swap:expire')->everyTenMinutes();
        // $schedule->command('withdrawal:resolve')->everyFifteenMinutes();
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
