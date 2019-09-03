<?php

namespace App\Console\Commands;

use App\Models\Holder;
use Illuminate\Console\Command;
use App\Models\Token;
use App\Models\Whale;
use App\Models\Alert;

class AlertTypeFour extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alert4';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'See changes by alert 4';

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
//        \Log::info('Alert 4 START - ' . \Carbon\Carbon::now());
        define('HOLDER_GENERAL_ADDRESS_SITE', 'https://etherscan.io/address/');
        ini_set('max_execution_time', 300000);
        require_once 'simple_html_dom.php';
        $alerts = Alert::all()->where('type_id', 4)->where('active', 0);
        foreach ($alerts as $alert) {
            $whale = Whale::where('id', $alert->whale_token)->first();
            $alertValue = explode(',', $alert->val_per_token);
            $percent = $alertValue[0];
            $balanceStart = $alertValue[1];
            $url = HOLDER_GENERAL_ADDRESS_SITE . $whale->holder;
            $html = '';
            while (($html = file_get_html($url)) == '') {
                sleep(3);
            }
            if (!empty($html)) {
                $result1 = $html->find('#balancelistbtn > span.pull-left');
                $balance = $whale->balance_current;
                if (!empty($result1)) {
                    foreach ($result1 as $a) {
                        $str = $a->plaintext;
                        $balanceBeginIndex = strpos($str, '$');
                        $balanceEndIndex = strpos($str, ')');
                        $balance = substr($str, $balanceBeginIndex + 1, $balanceEndIndex - $balanceBeginIndex - 1);
                        $balance = (double)(str_replace(',', '', $balance));
                    }

                    Whale::where('id', $whale->id)->update([
                        'balance_current' => $balance,
                    ]);
                    $percentNow = Whale::getPerformanceRound3($balanceStart, $balance);
                    if ($percent > 0) {
                        if ($percent < $percentNow) {
                            Alert::where('id', $alert->id)->update([
                                'active' => 1,
                                'change' => 1,
                                'active_time' => time(),
                                'message' =>  "<a href='/whale/" . $whale->id . "'>" . $whale->name . "</a>'s portfolio increased by " . ((int)($percentNow * 100) / 100) . "% at " . date('g:i:sa', time()) . " on " . date('y/m/d', time()) . ". Their current holdings are worth $" . number_format($balance, 2) . " USD."
                            ]);
                        }
                    } else {
                        if ($percent > $percentNow) {
                            Alert::where('id', $alert->id)->update([
                                'active' => 1,
                                'change' => -1,
                                'active_time' => time(),
                                'message' => "<a href='/whale/" . $whale->id . "'>" . $whale->name . "</a>'s portfolio decreased by " . ((int)($percentNow * 100) / 100) . "% at " . date('g:i:sa', time()) . " on " . date('m/d/y', time()) . ". Their current holdings are worth $" . number_format($balance, 2) . " USD."
                            ]);
                        }
                    }
                }
                Alert::where('id', $alert->id)->update([
                    'time_updated' => time(),
                ]);
                $html->clear();
                unset($html);
            }
            sleep(3);
        }
//        \Log::info('Alert4 END ' . " - " . \Carbon\Carbon::now());
    }
}
