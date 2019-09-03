<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Whale;

class GetTopBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'top_balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get top 200 holder balances';

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
//        \Log::info('Top HOLDERS Balance START - ' . \Carbon\Carbon::now());
        ini_set('max_execution_time', 300000);
        require_once 'simple_html_dom.php';
        define('HOLDER_GENERAL_ADDRESS_SITE', 'https://etherscan.io/address/');
        define('MINIMUM_LIMIT', 500000);
//        $whales = Whale::all()->sortBy('id')->slice(6600);

        $whales = Whale::all()->sortByDesc('balance_current')->slice(0, 100);
//        $cc = 1;
        foreach ($whales as $whale) {
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

//            $cc++;
//            usleep(500000);
            sleep(1);
        }

//        \Log::info('Top HOLDERS Balance END 111 - ' . \Carbon\Carbon::now());
    }
}
