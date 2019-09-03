<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\TokensList::class,
        \App\Console\Commands\GetTokensInfoByCMCApi::class,
        \App\Console\Commands\GetAllHolders::class,
        \App\Console\Commands\GetBalancesByAllHolders::class,
        \App\Console\Commands\GetLastTimeStamp::class,
        \App\Console\Commands\GetTotalBalances::class,
        \App\Console\Commands\SaveDailyBalances::class,
        \App\Console\Commands\GetTopLastActive::class,
        \App\Console\Commands\GetTopBalances::class,
        \App\Console\Commands\GetTopHoldings::class,
        \App\Console\Commands\TopBalance::class,
        \App\Console\Commands\GetBalancesByAllHoldersFromTokenBalanceCom::class,
        \App\Console\Commands\GetPercentages::class,
        \App\Console\Commands\AlertTypeOne::class,
        \App\Console\Commands\AlertTypeTwo::class,
        \App\Console\Commands\AlertTypeThree::class,
        \App\Console\Commands\AlertTypeFour::class,
        \App\Console\Commands\SendEmail::class,
        \App\Console\Commands\Populartokens::class,
        \App\Console\Commands\Infotokens::class,
        \App\Console\Commands\GetTopAddedRemoved::class,
        \App\Console\Commands\GetTotalAddedRemoved::class,
        \App\Console\Commands\GetTopTransfers::class,
        \App\Console\Commands\GetTotalTransfers::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {



        $schedule->command('top_holdings')->dailyAt('23:35')->withoutOverlapping();
        $schedule->command('save_balances')->dailyAt('23:55')->withoutOverlapping();
        $schedule->command('tokens')->daily()->withoutOverlapping();

        $schedule->command('top_last')->everyMinute()->withoutOverlapping();
        $schedule->command('top_balances')->everyMinute()->withoutOverlapping();
        $schedule->command('total_balances')->everyMinute()->withoutOverlapping();
        $schedule->command('last_active')->everyMinute()->withoutOverlapping();

        $schedule->command('balances')->everyMinute()->withoutOverlapping();
        $schedule->command('balances_from_tbcom')->everyMinute()->withoutOverlapping();

//        $schedule->command('top_added_removed')->everyMinute()->withoutOverlapping();
//        $schedule->command('total_added_removed')->everyMinute()->withoutOverlapping();


        $schedule->command('top_transfers')->everyMinute()->withoutOverlapping();
        $schedule->command('total_transfers')->everyMinute()->withoutOverlapping();

        $schedule->command('alert1')->everyMinute()->withoutOverlapping();
        $schedule->command('alert2')->everyMinute()->withoutOverlapping();
        $schedule->command('alert3')->everyMinute()->withoutOverlapping();
        $schedule->command('alert4')->everyMinute()->withoutOverlapping();

        $schedule->command('tokens_info')->everyMinute()->withoutOverlapping();
        $schedule->command('percents')->everyMinute()->withoutOverlapping();

        $schedule->command('send_emails')->everyMinute()->withoutOverlapping();

        $schedule->command('popular_tokens')->everyThirtyMinutes()->withoutOverlapping();
        $schedule->command('info_tokens')->everyThirtyMinutes()->withoutOverlapping();



//        $schedule->command('tokens_info')->cron('* * * * *')->withoutOverlapping();
//        $schedule->command('topBalance')->everyMinute()->withoutOverlapping();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
