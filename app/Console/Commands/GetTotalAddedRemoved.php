<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Whale;
use App\Models\Token;
use App\Models\Addedtransfer;
use App\Models\Removedtransfer;

class GetTotalAddedRemoved extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'total_added_removed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get total added and removed transfers';

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
        \Log::info('TOTAL ADDED_REMOVED START - ' . \Carbon\Carbon::now());
        ini_set('max_execution_time', 30000000);
        define('ETHPLORER_API_KEY2', 'gsuh40102enmvnM55');
        $whales = Whale::all()->sortByDesc('balance_current')->where('id', '<>', 9221)->slice(99);
        foreach ($whales as $whale) {
            $url = "https://api.ethplorer.io/getAddressHistory/" . $whale->holder . "?apiKey=" . ETHPLORER_API_KEY2 . "&type=transfer&limit=100&timestamp=" . time();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            $output = json_decode($output, true);

            $time_updated_added = Addedtransfer::all()->where('whale_id', $whale->id)->sortByDesc('time_updated')->first();
            $time_updated_removed = Removedtransfer::all()->where('whale_id', $whale->id)->sortByDesc('time_updated')->first();

            if (!empty($output['operations'])) {
                $added = [];
                $removed = [];
                $index1 = 0;
                $index2 = 0;
                foreach ($output['operations'] as $item) {
                    if (!empty($time_updated_added) && !empty($time_updated_removed)) {
                        if ($item['timestamp'] <= $time_updated_added->time_updated || $item['timestamp'] <= $time_updated_removed->time_updated) {
                            break;
                        }
                    }
                    $tokenOne = Token::where('token', $item['tokenInfo']['address'])->first();
                    if (!empty($tokenOne)) {
                        if (!empty($tokenOne->symbol)) {
                            if ($whale->holder == $item['from']) {
                                $removed[$index2]['whale_id'] = $whale->id;
                                $removed[$index2]['token_id'] = $tokenOne->id;
                                $removed[$index2]['token_symbol'] = $tokenOne->symbol;
                                $removed[$index2]['quantity'] = $item['value'] * 1 / (10 ** $tokenOne->token_decimal);
                                $removed[$index2]['value'] = $item['value'] * 1 / (10 ** $tokenOne->token_decimal) * $tokenOne->price_usd;
                                $removed[$index2]['time_updated'] = $item['timestamp'];
                                $index2++;
                            }
                            if ($whale->holder == $item['to']) {
                                $added[$index1]['whale_id'] = $whale->id;
                                $added[$index1]['token_id'] = $tokenOne->id;
                                $added[$index1]['token_symbol'] = $tokenOne->symbol;
                                $added[$index1]['quantity'] = $item['value'] * 1 / (10 ** $tokenOne->token_decimal);
                                $added[$index1]['value'] = $item['value'] * 1 / (10 ** $tokenOne->token_decimal) * $tokenOne->price_usd;
                                $added[$index1]['time_updated'] = $item['timestamp'];
                                $index1++;
                            }
                        }
                    }
                }
                Addedtransfer::insert($added);
                Removedtransfer::insert($removed);
        \Log::info('TOTAL ADDED_REMOVED END - ' . \Carbon\Carbon::now());
            }
        }
    }
}
