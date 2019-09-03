<?php

namespace App\Console\Commands;

use App\Models\Holder;
use Illuminate\Console\Command;
use App\Models\Token;
use App\Models\Whale;
use App\Models\Alert;

class AlertTypeThree extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alert3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'See changes by alert 3';

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
//        \Log::info('Alert 3 START - ' . \Carbon\Carbon::now());
        define('ETHPLORER_API_KEY', 'skffj61105BkR78');
        $alerts = Alert::all()->where('type_id', 3)->where('active', 0);
        foreach ($alerts as $alert) {
            $whale = Whale::where('id', $alert->whale_token)->first();
            $token = Token::where('id', $alert->val_per_token)->first();
            $url = "https://api.ethplorer.io/getAddressHistory/" . $whale->holder . "?apiKey=" . ETHPLORER_API_KEY
                . "&token=" . $token->token . "&type=transfer&limit=1&timestamp=" . time();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            $output = json_decode($output, true);
            if (!empty($output['operations'])) {
                foreach ($output['operations'] as $item) {
                    if ($item['timestamp'] < $alert->time_updated) {
                        break;
                    }
                    $value = $item['value'] * 1 / (10 ** $token->token_decimal) * $token->price_usd;
                    $amount = $item['value'] * 1 / (10 ** $token->token_decimal);
                    if ($whale->holder == $item['from']) {
                        Alert::where('id', $alert->id)->update([
                            'active' => 1,
                            'change' => -1,
                            'active_time' => time(),
                            'message' => "<a href='/whale/" . $whale->id . "'>" . $whale->name . "</a> removed " . number_format($amount, 2) . " <a href='/token/" . $token->id . "'>" . $token->symbol . "</a>, currently worth $" . number_format($value, 2) . " from their holdings at " . date('g:i:sa', $item['timestamp']) . " on " . date('m/d/y', $item['timestamp']) . "."

                        ]);
                        break;
                    }
                    if ($whale->holder == $item['to']) {
                        Alert::where('id', $alert->id)->update([
                            'active' => 1,
                            'change' => 1,
                            'active_time' => time(),
                            'message' => "<a href='/whale/" . $whale->id . "'>" . $whale->name . "</a> added " . number_format($amount, 2) . " <a href='/token/" . $token->id . "'>" . $token->symbol . "</a>, currently worth $" . number_format($value, 2) . " to their holdings at " . date('g:i:sa', $item['timestamp']) . " on " . date('m/d/y', $item['timestamp']) . "."
                        ]);
                        break;
                    }
                }
                Alert::where('id', $alert->id)->update([
                    'time_updated' => time(),
                ]);
            }
        }
//        \Log::info('Alert3 END ' . " - " . \Carbon\Carbon::now());
    }
}
