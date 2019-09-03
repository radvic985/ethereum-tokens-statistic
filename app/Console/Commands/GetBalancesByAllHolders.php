<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Models\Holder;
use App\Models\Token;
use App\Models\Whale;

class GetBalancesByAllHolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balances';

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
//        \Log::info('Balances Start - ' . \Carbon\Carbon::now());
        Artisan::call('tokens_info');
        define('ETHERSCAN_API_KEY', '7GTJCQMRQ3XSIZXHN15EIZW7ZVC77XESVU');

        $holderData = Holder::all('id', 'token_id', 'holder_id');
        $timeStart = time();
        $counter = 1;
        $time_start = microtime(true);
        foreach ($holderData as $item) {
            if (time() - $timeStart > 60) {
                Artisan::call('tokens_info');
                $timeStart = time();
            }
            $token_info = Token::where('id', $item->token_id)->first(['token', 'token_decimal', 'price_usd'])->toArray();
            $holder_address = Whale::where('id', $item->holder_id)->first(['holder'])->toArray();

            $output = [];
            if (_iscurl()) {
                $url = "https://api.etherscan.io/api?module=account&action=tokenbalance&contractaddress=" . $token_info['token']
                    . "&address=" . $holder_address['holder'] . "&tag=latest&apikey=" . ETHERSCAN_API_KEY;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $output = curl_exec($ch);
                curl_close($ch);
                $output = json_decode($output, true);
            }

            if ($counter % 5 == 0) {
                if (microtime(true) - $time_start - 1 < 0) {
                    usleep(500000);
                    \Log::info('Balances OVER 5 REQUESTS PER MINUTE - ' . \Carbon\Carbon::now());
                }
                $time_start = microtime(true);
                $counter = 0;
            }
            $counter++;
//            if ($output['message'] == 'OK' && isset($output['result']) && !empty($output['result'])) {
            if ($output['message'] == 'OK' && isset($output['result'])) {
                $quantity = (double)$output['result'] * 1 / (10 ** $token_info['token_decimal']);
                $balance = $token_info['price_usd'] * $quantity;
                Holder::where('id', $item->id)->update([
                    'balance_current' => round($balance, 2),
                    'quantity' => $quantity,
                ]);
            }
        }
        $tokens = Token::all()->sortBy('id');
        foreach ($tokens as $token){
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
//        \Log::info('Balances End - ' . \Carbon\Carbon::now());
    }
}
