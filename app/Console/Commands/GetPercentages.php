<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Whale;
use App\Models\Balance;

class GetPercentages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'percents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get percents by all periods';

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
//        \Log::info('PERCENTS start - ' . \Carbon\Carbon::now());
        $whales = Whale::all()
//            ->where('balance_current', '<>', null)
            ->where('balance_start', '<>', null)
            ->where('balance_start', '<>', 0)
            ->sortBy('id');
        $period = [
            time() - 86400,
            time() - 604800,
            time() - 2628000,
            time() - 31540000,
            mktime(23, 59, 59, 01, 01, date('Y')),
            1
        ];
        $countPeriod = count($period);
        $today = 0;
        $week = 0;
        $month = 0;
        $year = 0;
        $yeartd = 0;
        $all = 0;
        foreach ($whales as $whale) {
            if($whale->balance_current === null){
                continue;
            }
            for($i = 0; $i < $countPeriod; $i++){
                $temp = -900;
                $balance_start = Balance::where('holder_id', $whale->id)
                    ->where('time', '>=', $period[$i])
                    ->orderBy('time')
                    ->first(['balance']);
                if (empty($balance_start)) {
                    $balance_start = Balance::where('holder_id', $whale->id)->orderBy('time')->first(['balance']);
                }
                if (!empty($balance_start)) {
                    $balance_start = $balance_start->balance;
                    if ($balance_start == 0) {
                        $temp = -100;
                    }
                    else{
                        $temp = round(($whale->balance_current * 100) / $balance_start - 100, 1);
                    }
                }else{
                    $temp = round(($whale->balance_current * 100) / $whale->balance_start - 100, 1);
                }
                switch($i){
                    case 0:
                        $today = $temp;
                        break;
                    case 1:
                        $week = $temp;
                        break;
                    case 2:
                        $month = $temp;
                        break;
                    case 3:
                        $year = $temp;
                        break;
                    case 4:
                        $yeartd = $temp;
                        break;
                    case 5:
                        $all = $temp;
                        break;
                }
            }
            $whale->update([
//            Whale::where('id', $whale->id)->update([
                'percent_1' => $today,
                'percent_7' => $week,
                'percent_30' => $month,
                'percent_y' => $year,
                'percent_ytd' => $yeartd,
                'percent_all' => $all,
            ]);
            usleep(100000);
        }
//        \Log::info('PERCENTS END - ' . \Carbon\Carbon::now());
    }
}
