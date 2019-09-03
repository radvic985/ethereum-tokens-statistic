<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Holder;
use App\Models\Token;
use App\Models\Whale;

class GetTopHoldings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'top_holdings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get top holdings';

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
        \Log::info('SAVE TOP HOLDINGS Start - ' . \Carbon\Carbon::now());
        $whales = Whale::all()->sortByDesc('balance_current');
        foreach ($whales as $item) {
            $threeTokens = Holder::all('id', 'token_id', 'holder_id', 'balance_current')->where('holder_id', $item->id)->sortByDesc('balance_current')->slice(0, 3);
            $tokenSymbol = '';
            foreach ($threeTokens as $threeToken) {
                $symbol = Token::where('id', $threeToken->token_id)->first()->toArray();
                $str = '<a href="/token/' . $symbol['id'] . '">' . $symbol['symbol'] . '</a>,';
                $tokenSymbol .= $str;
            }

            $tokenSymbol = substr($tokenSymbol, 0, strlen($tokenSymbol) - 1);

            Whale::where('id', $item->id)->update([
                'top_holdings' => $tokenSymbol
            ]);
        }
        \Log::info('SAVE TOP HOLDINGS END - ' . \Carbon\Carbon::now());
    }
}
