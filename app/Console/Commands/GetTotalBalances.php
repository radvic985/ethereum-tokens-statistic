<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Whale;
use App\Models\Name;


class GetTotalBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'total_balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get total balance of each holder';

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
        \Log::info('Total Balance START - ' . \Carbon\Carbon::now());
        ini_set('max_execution_time', 300000);
        require_once 'simple_html_dom.php';
        define('MINIMUM_LIMIT', 500000);
        define('HOLDER_GENERAL_ADDRESS_SITE', 'https://etherscan.io/address/');
        $whales = Whale::all()->sortByDesc('balance_current')->slice(99);
//        $whales = Whale::all()->sortBy('id');
//        $cc = 99;
        foreach ($whales as $whale) {
            $url = HOLDER_GENERAL_ADDRESS_SITE . $whale->holder;

//            $html = file_get_html($url);
//            if ($html === false) {
//                \Log::info('HTML FALSE - ITEM - ' . $cc . " - " . \Carbon\Carbon::now());
//            } else {

            $html = '';
            while (($html = file_get_html($url)) == '') {
//                \Log::info('IN WHILE - ' . $whale->id . " - " . \Carbon\Carbon::now());
//                \Log::info('IN WHILE - ' . $cc . " - " . \Carbon\Carbon::now());
                sleep(3);
            }

//            if ($html === false) {
//                \Log::info('HTML FALSE - ITEM - ' . $cc . " - " . \Carbon\Carbon::now());
//            } else {
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
                }

                $result2 = $html->find('#balancelistbtn > span.badge');
                $totalTokens = $whale->total_tokens;
                if (!empty($result2)) {
                    foreach ($result2 as $a) {
                        $totalTokens = $a->plaintext;
                    }
                }
                if (!empty($result1) && !empty($result2)) {
                    $wh = Whale::where('id', $whale->id)->where('balance_start', null);
                    if ($wh->count() > 0) {
                        if($balance >= MINIMUM_LIMIT) {
                            $name = Name::where('whale_id', null)->where('active', 0)->first();
                            $wh->update([
                                'balance_start' => $balance,
                                'name' => $name->name
                            ]);
                            $name->update([
                                'whale_id' => $whale->id,
                                'active' => 1
                            ]);
                        }else{
                            $wh->delete();
                        }
                    }

                    Whale::where('id', $whale->id)->update([
                        'balance_current' => $balance,
                        'total_tokens' => $totalTokens,
                    ]);
                }
//                \Log::info('TOP 100 GOOD ITEM - ' . $cc . " - " . \Carbon\Carbon::now());
                $html->clear();
                unset($html);
            }
            sleep(2);

        }
        \Log::info('Total Balance END - ' . \Carbon\Carbon::now());
    }
}
