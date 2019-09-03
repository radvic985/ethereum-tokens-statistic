<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Token;
use App\Models\Whale;

class TopBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'topBalance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get top holders balance';

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
        \Log::info('Top Bal START - ' . \Carbon\Carbon::now());
        ini_set('max_execution_time', 300000);
define('ETHPLORER_API_KEY', 'skffj61105BkR78');
//        $whales = Whale::all()->sortBy('id');
        $whales = Whale::all()->sortByDesc('balance_current')->slice(0, 100);
        foreach ($whales as $whale) {
            $url = "https://api.ethplorer.io/getAddressInfo/". $whale->holder . "?apiKey=" . ETHPLORER_API_KEY;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            $output = json_decode($output, true);
$totalTokens = count($output['tokens']);
//        echo "<pre>";
//        print_r($output);
//        echo "</pre>";
//        $a = json_decode($a, true);
            $sum = 0;
            foreach ($output['tokens'] as $token) {
//            echo $aa['tokenInfo']['address']. "<br/>";
                $tokenOne = Token::all()->where('token', $token['tokenInfo']['address'])->toArray();
//             echo "<pre>";
//        print_r($pr);
//        echo "</pre>";
                if (!empty($tokenOne)) {

                    foreach ($tokenOne as $item) {
//                    echo $item['price_usd'] . "<br/>";
//                    $token_info['price_usd'] * (double)$output['result'] * 1 / (10 ** $token_info['token_decimal']);
                        $sum += $token['balance'] * 1 / (10 ** $item['token_decimal']) * $item['price_usd'];
                    }
//                print_r($pr);
                }
//

//            $sum += $aa['balance'];

            }
            Whale::where('id', $whale->id)->where('balance_start', null)->update([
                    'balance_start' => $sum,
                ]);
                Whale::where('id', $whale->id)->update([
                    'balance_current' => $sum,
                    'total_tokens' => $totalTokens,
                ]);
//                \Log::info('TOP BAL ITEM - ' . $whale->id . " - " . \Carbon\Carbon::now());
//                \Log::info('TOP 100 GOOD ITEM - ' . $cc . " - " . \Carbon\Carbon::now());
//        die;
//            echo $sum . "<br>";
//            sleep(1);
        }
        \Log::info('TOP  BAL END - ' . $whale->id . " - " . \Carbon\Carbon::now());
    }
}
