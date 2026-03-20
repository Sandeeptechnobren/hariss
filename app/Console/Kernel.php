<?php

// namespace App\Console;

// use Illuminate\Console\Scheduling\Schedule;
// use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
// use App\Jobs\ExpireBonusPointsJob;

// class Kernel extends ConsoleKernel
// {
//     /**
//      * The Artisan commands provided by your application.
//      *
//      * @var array
//      */
//     protected $commands = [
//         // Register Commands Here
//         \App\Console\Commands\UpdateCustomerTiers::class,
//     ];

//     /**
//      * Define the application's command schedule.
//      */
//     protected function schedule(Schedule $schedule)
//     {
//         // Run tier update daily
//         $schedule->command('tiers:update-daily')->daily();
//         $schedule->command('passport:cleanup-tokens')->daily(); 

//         // You can add more cron commands here...
//         // $schedule->command('emails:send')->everyMinute();
//     }

//     /**
//      * Register the commands for the application.
//      */
//     protected function commands()
//     {
//         $this->load(__DIR__.'/Commands');

//         require base_path('routes/console.php');
//     }

//         protected function schedule(Schedule $schedule)
//     {
//         $schedule->job(new \App\Jobs\ExpireBonusPointsJob())
//             ->dailyAt('00:30'); // everyday at 12:30 AM
//     }
// }
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('tiers:update-daily')->daily();

        $schedule->command('passport:cleanup-tokens')->daily();

        $schedule->job(new \App\Jobs\ExpireBonusPointsJob())
                 ->dailyAt('00:30');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
