<?php

namespace App\Console\Commands;

use App\Models\Holder;
use App\Models\Token;
use App\Models\Whale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class GetBalancesByAllHoldersFromTokenBalanceCom extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balances_from_tbcom';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get balances by all holders using Etherscan API';

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
        function _isCurl()
        {
            return function_exists('curl_version');
        }

        ini_set('max_execution_time', 300000);
//        \Log::info('FROM TOKEN BalanceStart - ' . \Carbon\Carbon::now());
        Artisan::call('tokens_info');

        $holderData = Holder::all()->sortBy('id');
        $timeStart = time();
        foreach ($holderData as $item) {
            if (time() - $timeStart > 60) {
                Artisan::call('tokens_info');
                $timeStart = time();
            }
            $token_info = Token::where('id', $item->token_id)->first(['token', 'token_decimal', 'price_usd', 'total_supply'])->toArray();
            $holder_address = Whale::where('id', $item->holder_id)->first(['holder'])->toArray();

            $output = [];
            if (_iscurl()) {
                $url = "https://api.tokenbalance.com/token/" . $token_info['token'] . "/" . $holder_address['holder'];
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $output = curl_exec($ch);
                curl_close($ch);
                $output = json_decode($output, true);
            }
//            \Log::info('AFTER CURL - ' . \Carbon\Carbon::now());
            if (isset($output['balance'])) {
//                \Log::info('ISSET - ' . \Carbon\Carbon::now());
                $outputBalance = (double)$output['balance'];
//                \Log::info('BEFORE IF - ' . \Carbon\Carbon::now());
                if ($outputBalance > $token_info['total_supply']) {
//                    \Log::info('BIGGER TOTAL - ' . \Carbon\Carbon::now());
                    $quantity = $outputBalance * 1 / (10 ** $token_info['token_decimal']);
                    $balance = $token_info['price_usd'] * $quantity;
                    Holder::where('id', $item->id)->update([
                        'balance_current' => round($balance, 2),
                        'quantity' => $quantity,
                    ]);
                } else {
//                    \Log::info('NORMAL - ' . \Carbon\Carbon::now());
                    $quantity = (double)$output['balance'];
                    $balance = $token_info['price_usd'] * $quantity;
                    Holder::where('id', $item->id)->update([
                        'balance_current' => round($balance, 2),
                        'quantity' => $quantity,
                    ]);
                }
//                \Log::info('AFTER IF - ' . \Carbon\Carbon::now());
            }
//            \Log::info('AFTER ISSET - ' . \Carbon\Carbon::now());
            usleep(100000);
        }
//        \Log::info('AFTER FOREACH - ' . \Carbon\Carbon::now());
        $tokens = Token::all()->sortBy('id');
        foreach ($tokens as $token) {
            $quantity = round(Holder::where('token_id', $token->id)->sum('quantity'), 2);
            if($quantity >= $token->quantity) {
                Token::where('id', $token->id)->update([
                    'balance' => round(Holder::where('token_id', $token->id)->sum('balance_current'), 2),
                    'quantity' => $quantity,
                    'holders_count' => Holder::where('token_id', $token->id)->count(),
                    'in_out' => 1
                ]);
            }
            else{
                Token::where('id', $token->id)->update([
                    'balance' => round(Holder::where('token_id', $token->id)->sum('balance_current'), 2),
                    'quantity' => $quantity,
                    'holders_count' => Holder::where('token_id', $token->id)->count(),
                    'in_out' => 0
                ]);
            }
        }
//        \Log::info('FROM TOKENBalances End - ' . \Carbon\Carbon::now());
    }
}
