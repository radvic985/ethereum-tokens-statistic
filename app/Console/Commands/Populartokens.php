<?php

namespace App\Console\Commands;

use App\Models\Holder;
use Illuminate\Console\Command;
use App\Models\Token;
use App\Models\Whale;
use App\Models\Populartoken;
use Illuminate\Support\Facades\DB;

class Populartokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'popular_tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Popular tokens';

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
//        \Log::info('Populartoken START - ' . \Carbon\Carbon::now());
       $sql = "SELECT `token_id`, COUNT(*) as count_holders FROM `holders` GROUP BY `token_id` ORDER BY count_holders DESC";
        $tokens = DB::select($sql);
//        echo "<pre>";
//        print_r($tokens);
//        echo "</pre>";die;
        $countAllHolders = Holder::all()->count();
        $popular = [];
        $index = 0;
        foreach ($tokens as $token) {
            $tkn = Token::where('id', $token->token_id)->first();
            if($tkn->total_supply_eth == 0 || $tkn->total_supply_eth === null){
                continue;
            }
            $percent = round(($token->count_holders * 100) / $countAllHolders, 1);
            $popular[$index]['image'] = $tkn->image;
            $popular[$index]['token_id'] = $tkn->id;
            $popular[$index]['token_name'] = $tkn->symbol;
            $popular[$index]['percent_all'] = $percent;
            $popular[$index]['percent_controlled'] = round((Holder::where('token_id', $token->token_id)->sum('quantity') * 100) / $tkn->total_supply_eth, 1);
            $holder = Holder::where('token_id', $token->token_id)->orderBy('balance_current', 'desc')->first();
            $whale = Whale::where('id', $holder->holder_id)->first();
            $popular[$index]['largest_holder_name'] = $whale->name;
            $popular[$index]['largest_holder_id'] = $whale->id;
            $percentPortfolio = !empty($whale->balance_current) ? round(($holder->balance_current * 100) / $whale->balance_current, 1) : 0;
            $popular[$index]['percent_portfolio'] = $percentPortfolio <= 100 ? $percentPortfolio : 100;
            $index++;
        }
//        DB::statement('truncate table populartokens');
//        Populartoken::query()->delete();
        Populartoken::truncate();
        Populartoken::insert($popular);
//        \Log::info('Populartoken END ' . " - " . \Carbon\Carbon::now());
    }
}
