<?php

namespace App\Console\Commands;

use App\Models\Temp1;
use App\Models\Temp2;
use App\Models\Temp3;
use Illuminate\Console\Command;
use App\Models\Token;
use App\Models\Whale;
use App\Models\Alert;

class AlertTypeTwo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alert2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'See changes by alert 2';

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
//        \Log::info('Alert 2 START - ' . \Carbon\Carbon::now());
        define('ETHPLORER_API_KEY', 'skffj61105BkR78');
        $alerts = Alert::all()->where('type_id', 2)->where('active', 0);
        $whaleCount = Whale::all()->count();
        foreach ($alerts as $alert) {
            $alertValue = explode(',', $alert->val_per_token);
            $type = $alertValue[0];
            $value = $alertValue[1];
            $token = Token::where('id', $alert->whale_token)->first();
            $url = "https://api.ethplorer.io/getTokenHistory/" . $token->token . "?apiKey=" . ETHPLORER_API_KEY . "&type=transfer&limit=1000&timestamp=" . time();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            $output = json_decode($output, true);
            if (!empty($output['operations'])) {
                if ($type == 'l') {
                    foreach ($output['operations'] as $item) {
                        if ($item['timestamp'] <= $alert->time_updated) {
                            break;
                        }
                        if (Whale::where('holder', $item['from'])->count() > 0) {
                            Temp1::firstOrCreate([
                                'alert_id' => $alert->id,
                                'from' => $item['from']
                            ]);
                        }
                        if (Whale::where('holder', $item['to'])->count() > 0) {
                            Temp1::firstOrCreate([
                                'alert_id' => $alert->id,
                                'to' => $item['to']
                            ]);
                        }
                    }
                    $percentFrom = (Temp1::where('alert_id', $alert->id)->where('from', '<>', null)->count() * 100) / $whaleCount;
                    $percentTo = (Temp1::where('alert_id', $alert->id)->where('to', '<>', null)->count() * 100) / $whaleCount;
                    if ($percentFrom > $value) {
                        Alert::where('id', $alert->id)->update([
                            'active' => 1,
                            'change' => -1,
                            'active_time' => time(),
                            'message' => "<a href='/token/" . $token->id . "'>" . $token->symbol . "</a> was removed by at least " . number_format($percentFrom)
                                . "% of whales in your specified period from " . date('m/d/y', $alert->time_created)
                                . " to " . date('m/d/y', $output['operations'][0]['timestamp']) . "."
                        ]);
                        Temp1::where('alert_id', $alert->id)->delete();
                    }
                    if ($percentTo > $value) {
                        Alert::where('id', $alert->id)->update([
                            'active' => 1,
                            'change' => 1,
                            'active_time' => time(),
                            'message' => "<a href='/token/" . $token->id . "'>" . $token->symbol . "</a> was added by at least " . number_format($percentTo)
                                . "% of whales in your specified period from " . date('m/d/y', $alert->time_created)
                                . " to " . date('m/d/y', $output['operations'][0]['timestamp']) . "."
                        ]);
                        Temp1::where('alert_id', $alert->id)->delete();
                    }
                    Alert::where('id', $alert->id)->update([
                        'time_updated' => $output['operations'][0]['timestamp']
                    ]);
                }
                if ($type == 't') {
                    $topWhales = Whale::all()->sortByDesc('balance_current');
                    foreach ($output['operations'] as $item) {
                        if ($item['timestamp'] <= $alert->time_updated) {
                            break;
                        }
                        if ($topWhales->where('holder', $item['from'])->count() > 0) {
                            Temp2::firstOrCreate([
                                'alert_id' => $alert->id,
                                'from' => $item['from']
                            ]);
                            $countFrom = Temp2::where('alert_id', $alert->id)->where('from', '<>', null)->count();
                            if ($countFrom > $value) {
                                Alert::where('id', $alert->id)->update([
                                    'time_updated' => $output['operations'][0]['timestamp'],
                                    'active' => 1,
                                    'change' => -1,
                                    'active_time' => time(),
                                    'message' => "<a href='/token/" . $token->id . "'>" . $token->symbol . "</a> was removed by top " . $value
                                        . " whales in your specified period from " . date('m/d/y', $alert->time_created)
                                . " to " . date('m/d/y', $output['operations'][0]['timestamp']) . "."
                                ]);
                                Temp2::where('alert_id', $alert->id)->delete();
                                break;
                            }
                        }
                        if ($topWhales->where('holder', $item['to'])->count() > 0) {
                            Temp2::firstOrCreate([
                                'alert_id' => $alert->id,
                                'to' => $item['to']
                            ]);
                            $countTo = Temp2::where('alert_id', $alert->id)->where('to', '<>', null)->count();
                            if ($countTo > $value) {
                                Alert::where('id', $alert->id)->update([
                                    'time_updated' => $output['operations'][0]['timestamp'],
                                    'active' => 1,
                                    'change' => 1,
                                    'active_time' => time(),
                                    'message' => "<a href='/token/" . $token->id . "'>" . $token->symbol . "</a> was added by top " . $value
                                        . " whales in your specified period from " . date('m/d/y', $alert->time_created)
                                . " to " . date('m/d/y', $output['operations'][0]['timestamp']) . "."
                                ]);
                                Temp2::where('alert_id', $alert->id)->delete();
                                break;
                            }
                        }
                    }
                    Alert::where('id', $alert->id)->update([
                        'time_updated' => $output['operations'][0]['timestamp']
                    ]);
                }
//                if ($type == 'a') {
//                    $topWhales = Whale::all()->sortByDesc('balance_current');
//                    foreach ($output['operations'] as $item) {
//                        if ($item['timestamp'] <= $alert->time_updated) {
//                            break;
//                        }
////                        echo "<pre>";
////print_r($item);
////echo "</pre>";
//
////echo $alert->whale_token."<br>";
//                        if ($topWhales->where('holder', $item['from'])->count() > 0) {
//                            Temp2::firstOrCreate([
//                                'alert_id' => $alert->id,
//                                'from' => $item['from']
//                            ]);
//                            $countFrom = Temp2::where('alert_id', $alert->id)->where('from', '<>', null)->count();
//                            if ($countFrom > $value) {
//                                Alert::where('id', $alert->id)->update([
//                                    'time_updated' => $output['operations'][0]['timestamp'],
//                                    'active' => 1,
//                                    'change' => -1,
//                                    'active_time' => time(),
//                                    'message' => $token->symbol . " is removed by top " . $value
//                                        . " whales for the period from " . date('y/m/d g:i:sa', $alert->time_created)
//                                        . " to " . date('y/m/d g:i:sa', $output['operations'][0]['timestamp']) . "!"
//                                ]);
//                                Temp2::where('alert_id', $alert->id)->delete();
//                                break;
//                            }
//                        }
//                        if ($topWhales->where('holder', $item['to'])->count() > 0) {
//                            Temp2::firstOrCreate([
//                                'alert_id' => $alert->id,
//                                'to' => $item['to']
//                            ]);
//                            $countTo = Temp2::where('alert_id', $alert->id)->where('to', '<>', null)->count();
//                            if ($countTo > $value) {
//                                Alert::where('id', $alert->id)->update([
//                                    'time_updated' => $output['operations'][0]['timestamp'],
//                                    'active' => 1,
//                                    'change' => 1,
//                                    'active_time' => time(),
//                                    'message' => $token->symbol . " is added by top " . $value
//                                        . " whales for the period from " . date('y/m/d g:i:sa', $alert->time_created)
//                                        . " to " . date('y/m/d g:i:sa', $output['operations'][0]['timestamp']) . "!"
//                                ]);
//                                Temp2::where('alert_id', $alert->id)->delete();
//                                break;
//                            }
//                        }
//                    }
////                    echo "<br>done<br>";
////                    die;
////                    $percentFrom = 80;
////                    $percentFrom = (Temp2::where('alert_id', $alert->id)->where('from', '<>', null)->count() * 100) / $whaleCount;
////                    $percentTo = (Temp2::where('alert_id', $alert->id)->where('to', '<>', null)->count() * 100) / $whaleCount;
////                    $percentTo = 60;
//
//
//                    Alert::where('id', $alert->id)->update([
//                        'time_updated' => $output['operations'][0]['timestamp']
//                    ]);
//                }
            }
        }
//        \Log::info('Alert2 END  ' . " - " . \Carbon\Carbon::now());
    }
}
