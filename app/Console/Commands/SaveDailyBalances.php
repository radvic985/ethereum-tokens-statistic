<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Balance;
use App\Models\Whale;

class SaveDailyBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save_balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        \Log::info('SAVE DAILY Total Balance START - ' . \Carbon\Carbon::now());
        $whales = Whale::all('id', 'balance_current')
            ->where('balance_current', '<>', null)
            ->sortBy('id');
        $balances = [];
        $index = 0;
        foreach ($whales as $whale) {
            $balances[$index]['holder_id'] = $whale->id;
            $balances[$index]['balance'] = $whale->balance_current;
            $balances[$index]['date'] = date("Y-m-d");
            $balances[$index]['time'] = time();
            $index++;
        }
        Balance::insert($balances);
        \Log::info('SAVE DAILY Total Balance END - ' . \Carbon\Carbon::now());
    }
}
