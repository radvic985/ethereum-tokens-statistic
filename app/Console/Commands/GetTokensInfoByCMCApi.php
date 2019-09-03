<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Token;

class GetTokensInfoByCMCApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens_info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get tokens info by CoinMarketCap API';

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
//        \Log::info('Tokens Info Start - ' . \Carbon\Carbon::now());
        $output = [];
//        if (_iscurl()) {
            $url = "https://api.coinmarketcap.com/v1/ticker/?limit=0";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            $output = json_decode($output, true);
//        }

        $tokens = Token::all('coinmarket_id');
        $countOutput = count($output);
        $coinMarketInfo = [];
        $indexCoin = 0;
        for ($j = 0; $j < $countOutput; $j++) {
            $flag = false;
            foreach ($tokens as $tokenItem) {
                if ($output[$j]['id'] == strtolower($tokenItem->coinmarket_id)) {
                    $flag = true;
                    break;
                }
            }
            if ($flag) {
                $coinMarketInfo[$indexCoin] = $output[$j];
                $indexCoin++;
            }
        }

        $countCoinMarketInfo = count($coinMarketInfo);
        for ($j = 0; $j < $countCoinMarketInfo; $j++) {
            Token::where('coinmarket_id', $coinMarketInfo[$j]['id'])->update([
                'symbol' => $coinMarketInfo[$j]['symbol'],
                'price_usd' => $coinMarketInfo[$j]['price_usd'],
                'volume_usd' => $coinMarketInfo[$j]['24h_volume_usd'],
                'market_cap_usd' => $coinMarketInfo[$j]['market_cap_usd'],
                'available_supply' => $coinMarketInfo[$j]['available_supply'],
                'total_supply' => $coinMarketInfo[$j]['total_supply'],
                'last_updated' => $coinMarketInfo[$j]['last_updated'],
            ]);
        }
//        \Log::info('Tokens Info End - ' . \Carbon\Carbon::now());
    }
}
