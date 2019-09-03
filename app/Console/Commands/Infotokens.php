<?php

namespace App\Console\Commands;

use App\Models\Holder;
use Illuminate\Console\Command;
use App\Models\Token;
use App\Models\Whale;
use App\Models\Infotoken;
use Illuminate\Support\Facades\DB;

class Infotokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'info_tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Info tokens';

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
//        \Log::info('InfoTOKEN START - ' . \Carbon\Carbon::now());
        $sql = "SELECT `token_id`, COUNT(*) as count_holders FROM `holders` GROUP BY `token_id` ORDER BY count_holders DESC LIMIT 30";
        $tokens = DB::select($sql);
        $countAllHolders = Holder::all()->count();
        $tokensInfo1[0]['is_cmc'] = 0;
        $tokensInfo1[0]['token'] = 'Token';
        $tokensInfo1[0]['percent'] = 'Percent';
        $sumPercents = 0;
        $index = 1;
        foreach ($tokens as $token) {
            $tkn = Token::where('id', $token->token_id)->first();
            $percent = round(($token->count_holders * 100) / $countAllHolders, 1);
            $tokensInfo1[$index]['is_cmc'] = 0;
            $tokensInfo1[$index]['token'] = $tkn->symbol . " - " . $percent . "%";
            $tokensInfo1[$index]['percent'] = $percent;
            $sumPercents += $percent;
            $index++;
        }
        $otherPercent = 100 - $sumPercents;
        $countTokensInfo1 = count($tokensInfo1);
        $tokensInfo1[$countTokensInfo1]['is_cmc'] = 0;
        $tokensInfo1[$countTokensInfo1]['token'] = 'Other tokens';
        $tokensInfo1[$countTokensInfo1]['percent'] = round($otherPercent, 1);

        $url = "https://api.coinmarketcap.com/v2/global/";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output, true);
        $totalMarketCap = 1;
        if (isset($output['data']['quotes']['USD']['total_market_cap'])) {
            $totalMarketCap = (double)$output['data']['quotes']['USD']['total_market_cap'];
        }
        $marketCapTokens = Token::all()->sortByDesc('balance')->slice(0, 30);
        $tokensInfo2[0]['is_cmc'] = 1;
        $tokensInfo2[0]['token'] = 'Token';
        $tokensInfo2[0]['percent'] = 'Percent';
        $sumPercents = 0;
        $index = 1;
        foreach ($marketCapTokens as $token) {
            $percent = round(($token->balance * 100) / $totalMarketCap, 1);
            $tokensInfo2[$index]['is_cmc'] = 1;
            $tokensInfo2[$index]['token'] = $token->symbol . " - " . $percent . "%";
            $tokensInfo2[$index]['percent'] = $percent;
            $sumPercents += $percent;
            $index++;
        }
        $otherPercent = 100 - $sumPercents;
        $countTokensInfo2 = count($tokensInfo2);
        $tokensInfo2[$countTokensInfo2]['is_cmc'] = 1;
        $tokensInfo2[$countTokensInfo2]['token'] = 'Other cryptocurrencies';
        $tokensInfo2[$countTokensInfo2]['percent'] = round($otherPercent, 1);

        Infotoken::truncate();
        Infotoken::insert($tokensInfo1);
        Infotoken::insert($tokensInfo2);

//        \Log::info('INFOtoken END ' . " - " . \Carbon\Carbon::now());
    }
}
