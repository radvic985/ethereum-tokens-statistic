<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Token;
use Illuminate\Support\Facades\Artisan;

class TokensList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save all ethereum tokens and all their additional info';

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
        function _isCurl()
        {
            return function_exists('curl_version');
        }

        \Log::info('Tokens List start - ' . \Carbon\Carbon::now());
        define('TOKEN_ADDRESS_SITE', 'https://etherscan.io/token/');
        define('COINMARKETCAP_ADDRESS_SITE', 'https://coinmarketcap.com/currencies/');
        ini_set('max_execution_time', 300000);
        require_once 'simple_html_dom.php';

        // scrape all tokens
        $html = file_get_html('https://etherscan.io/tokens');
        $pagination = $html->find('#ContentPlaceHolder1_divpagingpanel  p > a');
        $pagesCount = 1;
        foreach ($pagination as $a) {
            if ($a->plaintext == "Last") {
                $pagesCount = str_replace("tokens?p=", "", $a->href);
            }
        }
        $html->clear();
        unset($html);

        $index = 0;
        $tokensArray = [];
        for ($j = 1; $j <= $pagesCount; $j++) {
            $html = '';
            $url = 'https://etherscan.io/tokens?p=' . $j;
            while (($html = file_get_html($url)) == '') {
                sleep(5);
            }
            if (!empty($html)) {
                $result = $html->find('.hidden-xs img.rounded-x[src^="/token/images/"], .hidden-xs > h5 > a[href^="/token/"]');
                foreach ($result as $a) {
                    if (isset($a->src)) {
                        $tokensArray[$index]['image'] = str_replace("/token/images/", "", $a->src);
                    } elseif (isset($a->href)) {
                        $tokensArray[$index]['name'] = $a->plaintext;
                        $tokensArray[$index]['token'] = str_replace("/token/", "", $a->href);
                        $index++;
                    }
                }
            }
            $html->clear();
            unset($html);
            sleep(10);
        }

        // Save all tokens to database
        foreach ($tokensArray as $item) {
            Token::firstOrCreate(
                ['token' => $item['token']],
                [
                    'image' => $item['image'],
                    'name' => $item['name'],
                ]
            );
        }

        $tokensForUpdate = Token::all()->where('symbol', null);
        if (!empty($tokensForUpdate)) {
            foreach ($tokensForUpdate as $item) {
                $imageUrl = 'https://etherscan.io/token/images/';
                $imagePath = './public/images/token/';
                copy($imageUrl . $item->image, $imagePath . $item->image);
                $html = '';
                $url = TOKEN_ADDRESS_SITE . $item->token;
                while (($html = file_get_html($url)) == '') {
                    sleep(5);
                }
                if (!empty($html)) {
                    $result = $html->find('tr.#ContentPlaceHolder1_trContract, a[data-original-title^="Website:"], a[data-original-title^="CoinMarketCap:"]');
                    foreach ($result as $a) {
                        if (!isset($a->href)) {
                            $token_decimal = (int)$a->next_sibling()->children(1)->plaintext;
                            Token::where('id', $item->id)->update([
                                'token_decimal' => $token_decimal,
                            ]);
                        }
                        if (strpos($a->href, COINMARKETCAP_ADDRESS_SITE) === 0) {
                            $coinmarket_id = str_replace(COINMARKETCAP_ADDRESS_SITE, '', $a->href);
                            $coinmarket_id = substr($coinmarket_id, 0, strlen($coinmarket_id) - 1);
                            Token::where('id', $item->id)->update([
                                'coinmarket_id' => $coinmarket_id
                            ]);
                        } else {
                            $website = $a->href;
                            Token::where('id', $item->id)->update([
                                'website' => $website,
                            ]);
                        }
                    }
                    $result2 = $html->find('td.tditem');
                    foreach ($result2 as $a) {
                        if(strpos($a->plaintext, '(') !== false) {
                            $str = $a->plaintext;
                            $arr = explode(' ', $str);
                            $total = '';
                            if($arr[0] == ''){
                                $total = $arr[1];
                            }
                            else{
                                $total = $arr[0];
                            }
                            $total = (double)str_replace(',', '', $total);
                            Token::where('id', $item->id)->update([
                                'total_supply_eth' => $total
                            ]);
                        }
                    }

                    $html->clear();
                    unset($html);
                    sleep(10);
                }
            }
        }
        \Log::info('Tokens List End - ' . \Carbon\Carbon::now());
        Artisan::call('tokens_info');
        Artisan::call('holders');
    }
}
