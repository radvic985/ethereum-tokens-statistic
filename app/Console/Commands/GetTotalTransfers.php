<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Whale;
use App\Models\Token;
use App\Models\Addedtransfer;
use App\Models\Removedtransfer;
use App\Models\Transfer;

class GetTotalTransfers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'total_transfers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get total transfers';

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
        \Log::info('TOTAL TRANSFERS START - ' . \Carbon\Carbon::now());
        ini_set('max_execution_time', 30000000);
        define('ETHPLORER_API_KEY2', 'gsuh40102enmvnM55');
        $whales = Whale::all()->sortByDesc('balance_current')->where('id', '<>', 9221)->slice(99);
        $ii = 99;
        foreach ($whales as $whale) {
//            \Log::info('TOTAL TRANSFERS index - ' . $ii . " - " . \Carbon\Carbon::now());
            $url = "https://api.ethplorer.io/getAddressHistory/" . $whale->holder . "?apiKey=" . ETHPLORER_API_KEY2 . "&type=transfer&limit=100&timestamp=" . time();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            $output = json_decode($output, true);
//            \Log::info('TOTAL TRANSFERS AFTER CURL- ' . $ii . " - " . \Carbon\Carbon::now());



//            $time_updated_removed = Transfer::all()->where('whale_id', $whale->id)->sortByDesc('time_updated')->first();

            if (!empty($output['operations'])) {
//                \Log::info('TOTAL TRANSFERS NOT EMPTY OUTPUT - ' . $ii . " - " . \Carbon\Carbon::now());
                $added = [];
                $removed = [];
                $index1 = 0;
                $index2 = 0;
                $time_updated = Transfer::where('whale_id', $whale->id)->orderBy('time_updated', 'desc')->first();
//                $time_updated = Transfer::all()->where('whale_id', $whale->id)->sortByDesc('time_updated')->first();
//                \Log::info('TOTAL TRANSFERS AFTER TIME UPDATED- ' . $ii . " - " . \Carbon\Carbon::now());
                foreach ($output['operations'] as $item) {
//                    if (!empty($time_updated_added) && !empty($time_updated_removed)) {
//                        if ($item['timestamp'] <= $time_updated_added->time_updated || $item['timestamp'] <= $time_updated_removed->time_updated) {
//                            break;
//                        }
//                    }
                    if (!empty($time_updated)) {
                        if ($item['timestamp'] <= $time_updated->time_updated) {
                            break;
                        }
                    }
//                    $transfer = new Transfer();
                    $tokenOne = Token::where('token', $item['tokenInfo']['address'])->first();
                    if (!empty($tokenOne)) {
                        if (!empty($tokenOne->symbol)) {
                            if ($whale->holder == $item['from']) {

//                                $transfer->whale_id = $whale->id;
//                                $transfer->token_id = $tokenOne->id;
//                                $transfer->is_added = 0;
//                                $transfer->token_symbol = $tokenOne->symbol;
//                                $transfer->quantity = $item['value'] * 1 / (10 ** $tokenOne->token_decimal);
//                                $transfer->value = $item['value'] * 1 / (10 ** $tokenOne->token_decimal) * $tokenOne->price_usd;
//                                $transfer->time_updated = $item['timestamp'];
//                                $transfer->save();

                                $removed[$index2]['whale_id'] = $whale->id;
                                $removed[$index2]['token_id'] = $tokenOne->id;
                                $removed[$index2]['is_added'] = 0;
                                $removed[$index2]['token_symbol'] = $tokenOne->symbol;
                                $removed[$index2]['quantity'] = round($item['value'] * 1 / (10 ** $tokenOne->token_decimal), 2);
                                $removed[$index2]['value'] = round($item['value'] * 1 / (10 ** $tokenOne->token_decimal) * $tokenOne->price_usd, 2);
                                $removed[$index2]['time_updated'] = $item['timestamp'];
                                $index2++;
                            }
                            if ($whale->holder == $item['to']) {
//                                $transfer->whale_id = $whale->id;
//                                $transfer->token_id = $tokenOne->id;
//                                $transfer->is_added = 1;
//                                $transfer->token_symbol = $tokenOne->symbol;
//                                $transfer->quantity = $item['value'] * 1 / (10 ** $tokenOne->token_decimal);
//                                $transfer->value = $item['value'] * 1 / (10 ** $tokenOne->token_decimal) * $tokenOne->price_usd;
//                                $transfer->time_updated = $item['timestamp'];
//                                $transfer->save();


                                $added[$index1]['whale_id'] = $whale->id;
                                $added[$index1]['token_id'] = $tokenOne->id;
                                $added[$index1]['is_added'] = 1;
                                $added[$index1]['token_symbol'] = $tokenOne->symbol;
                                $added[$index1]['quantity'] = round($item['value'] * 1 / (10 ** $tokenOne->token_decimal), 2);
                                $added[$index1]['value'] = round($item['value'] * 1 / (10 ** $tokenOne->token_decimal) * $tokenOne->price_usd, 2);
                                $added[$index1]['time_updated'] = $item['timestamp'];
                                $index1++;
                            }
                        }
                    } else {
                        $reg = "/[\\\\\/\.]+/";
                        $str = json_encode($item['tokenInfo']['symbol']);
//                        $ss = json_encode($s);
//        echo $s."<br>";
//        echo $ss."<br>";
//        $reg = "/[\\/]+/";
                        if ($whale->holder == $item['from']) {
//                            $transfer->whale_id = $whale->id;
//                            $transfer->token_id = 0;
//                            $transfer->is_added = 0;
//                                if(!is_string($item['tokenInfo']['symbol'])){
//                                    \Log::info('NOT STRING  FROM- ' . \Carbon\Carbon::now());
//                                }


                            if(empty(preg_match($reg, $str))){
                                $removed[$index2]['token_symbol'] = $item['tokenInfo']['symbol'];
                            }
                            else {
                                $removed[$index2]['token_symbol'] = "N/A";
                            }
//                            if (!empty(preg_match($reg, $item['tokenInfo']['symbol']))) {
//                                if (strpos($item['tokenInfo']['symbol'], '.com') === false) {
//                                    $removed[$index2]['token_symbol'] = (string)$item['tokenInfo']['symbol'];
//                                } else {
//                                    $removed[$index2]['token_symbol'] = "N/A";
//                                }
//                            } else {
//                                $removed[$index2]['token_symbol'] = "N/A";
//                            }
//                            if (strpos($item['tokenInfo']['symbol'], 'www') !== false
//                                || strpos($item['tokenInfo']['symbol'], 'efereum.com') !== false
//                                || empty($item['tokenInfo']['symbol'])
//                            ) {
////                                $transfer->token_symbol = "N/A";
//                                $removed[$index2]['token_symbol'] = "N/A";
//                            } else {
////                                $transfer->token_symbol = (string)$item['tokenInfo']['symbol'];
//                                $removed[$index2]['token_symbol'] = (string)$item['tokenInfo']['symbol'];
//                            }
//                            $transfer->quantity = $item['value'] * 1 / (10 ** (int)$item['tokenInfo']['decimals']);
//                            $transfer->value = -1;
//                            $transfer->time_updated = $item['timestamp'];
//                            $transfer->save();

                            $removed[$index2]['whale_id'] = $whale->id;
                            $removed[$index2]['token_id'] = 0;
                            $removed[$index2]['is_added'] = 0;

//                            $removed[$index2]['token_symbol'] = (string)$item['tokenInfo']['symbol'];
                            $removed[$index2]['quantity'] = round($item['value'] * 1 / (10 ** (int)$item['tokenInfo']['decimals']), 2);
                            $removed[$index2]['value'] = -1;
                            $removed[$index2]['time_updated'] = $item['timestamp'];
                            $index2++;
                        }
                        if ($whale->holder == $item['to']) {
//                            $transfer->whale_id = $whale->id;
//                            $transfer->token_id = 0;
//                            $transfer->is_added = 1;
//                                if(!is_string($item['tokenInfo']['symbol'])){
//                                    \Log::info('NOT STRING  TO - ' . \Carbon\Carbon::now());
//                                }
                            if(empty(preg_match($reg, $str))){
                                $added[$index1]['token_symbol'] = $item['tokenInfo']['symbol'];
                            }
                            else {
                                $added[$index1]['token_symbol'] = "N/A";
                            }
//                            if (!empty(preg_match($reg, $item['tokenInfo']['symbol']))) {
//                                if (strpos($item['tokenInfo']['symbol'], '.com') === false) {
//                                    $added[$index1]['token_symbol'] = (string)$item['tokenInfo']['symbol'];
//                                } else {
//                                    $added[$index1]['token_symbol'] = "N/A";
//                                }
//                            } else {
//                                $added[$index1]['token_symbol'] = "N/A";
//                            }
//                            if (strpos($item['tokenInfo']['symbol'], 'www') !== false
//                                || strpos($item['tokenInfo']['symbol'], 'efereum.com') !== false
//                                || empty($item['tokenInfo']['symbol'])
//                            ) {
////                                $transfer->token_symbol = "N/A";
//                                $added[$index1]['token_symbol'] = "N/A";
//                            } else {
////                                $transfer->token_symbol = (string)$item['tokenInfo']['symbol'];
//                                $added[$index1]['token_symbol'] = (string)$item['tokenInfo']['symbol'];
//                            }
//                            $transfer->quantity = $item['value'] * 1 / (10 ** (int)$item['tokenInfo']['decimals']);
//                            $transfer->value = -1;
//                            $transfer->time_updated = $item['timestamp'];
//                            $transfer->save();

                            $added[$index1]['whale_id'] = $whale->id;
                            $added[$index1]['token_id'] = 0;
                            $added[$index1]['is_added'] = 1;
//                            $added[$index1]['token_symbol'] = (string)$item['tokenInfo']['symbol'];
                            $added[$index1]['quantity'] = round($item['value'] * 1 / (10 ** (int)$item['tokenInfo']['decimals']), 2);
                            $added[$index1]['value'] = -1;
                            $added[$index1]['time_updated'] = $item['timestamp'];
                            $index1++;
                        }
                    }
                }
                Transfer::insert($added);
                Transfer::insert($removed);
            }else{
//                \Log::info('TOTAL TRANSFERS !!!!! EMPTY OUTPUT - ' . $ii . " - " . \Carbon\Carbon::now());
            }
            $ii++;
        }
        \Log::info('TOTAL TRANSFERS END  - ' . \Carbon\Carbon::now());
    }
}
