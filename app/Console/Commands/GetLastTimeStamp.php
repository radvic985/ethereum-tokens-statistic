<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Whale;

class GetLastTimeStamp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'last_active';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get timestamp of the holders address transaction';

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
//        \Log::info('LAST ACTIVE START - ' . \Carbon\Carbon::now());
        define('ETHPLORER_API_KEY', 'skffj61105BkR78');
        function _isCurl()
        {
            return function_exists('curl_version');
        }

        function getTimestamp($id)
        {
            $output = [];
            if (_iscurl()) {
                $url = "https://api.ethplorer.io/getAddressHistory/" . $id . "?apiKey=" . ETHPLORER_API_KEY . "&type=transfer&limit=1";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $output = curl_exec($ch);
                curl_close($ch);
                $output = json_decode($output, true);
            }
            if (isset($output['operations'][0]['timestamp'])) {
                return $output['operations'][0]['timestamp'];
            } else {
                return false;
            }
        }

        $whales = Whale::all()->sortByDesc('balance_current')->slice(99);
//        $counter = 1;
//        $time_start = microtime(true);
        foreach ($whales as $item) {
            $result = getTimestamp($item->holder);
            if ($result !== false) {
                Whale::where('id', $item->id)->update([
                    'last_active' => $result
                ]);
            }
//            else {
//                Whale::where('id', $item->id)->update([
//                    'last_active' => 0
//                ]);
//            }

//            usleep(700000);

//            if ($counter % 2 == 0) {
//                if (microtime(true) - $time_start - 1 < 0) {
//                    usleep(500000);
//                    \Log::info('TIMESTAMP -  OVER 2 REQUESTS PER MINUTE - ' . $item->id . \Carbon\Carbon::now());
//                }
//                $time_start = microtime(true);
//                $counter = 0;
//            }
//            $counter++;

        }
//        \Log::info('LAST ACTIVE END - ' . \Carbon\Carbon::now());
    }
}
