<?php

namespace App\Http\Controllers;

use App\Models\Infotoken;
use App\Models\Token;
use App\Models\Whale;
use App\Models\Populartoken;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $period = 'today';
        $params = $request->all();
        if (isset($params['period'])) {
            $period = $params['period'];
        }
        return view('stats', [
            'gainers' => Whale::getGainers($period),
            'losers' => Whale::getLosers($period),
//            'tokensInfo1' => Infotoken::getInfoTokens(),
//            'tokensInfo2' => Infotoken::getInfoTokensCMC(),
            'tokens' => Token::getMostActiveTokens(),
            'popular' => Populartoken::getPopularTokens()
        ]);
    }
}
