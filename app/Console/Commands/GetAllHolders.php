<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Token;
use App\Models\Holder;
use App\Models\Whale;

class GetAllHolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all holders from Etherscan';

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
        \Log::info('Holder List start - ' . \Carbon\Carbon::now());
        define('MINIMUM_LIMIT', 500000);
        $tokens = Token::all('id', 'token', 'price_usd');
        $index = 0;
        $holdersArray = [];
        foreach ($tokens as $token) {
            if ($token->price_usd === null) {
                continue;
            }
            $url = 'https://etherscan.io/token/generic-tokenholders2?a=' . $token->token;
            $html = file_get_html($url);
            $pagination = $html->find('#PagingPanel > a');
            $pagesCount = 1;
            foreach ($pagination as $a) {
                if ($a->plaintext == "Last") {
                    $first_index = strpos($a->href, 'p=');
                    $last_index = strpos($a->href, '\')');
                    $pagesCount = substr($a->href, $first_index + 2, $last_index - $first_index - 2);
                }
            }
            $html->clear();
            unset($html);

            for ($j = 1; $j <= $pagesCount; $j++) {
                $html = '';
                while (($html = file_get_html($url . "&p=" . $j)) == '') {
                    sleep(5);
                }
                if (!empty($html)) {
                    $result = $html->find('a[href^="/token/0x"]');
                    $flag = false;
                    foreach ($result as $a) {
                        $holdersArray[$index]['holder'] = $a->plaintext;
                        $holdersArray[$index]['token_id'] = $token->id;
                        $holdersArray[$index]['quantity'] = (int)$a->parent()->parent()->next_sibling()->plaintext;
                        $holdersArray[$index]['balance_current'] = $token->price_usd * $holdersArray[$index]['quantity'];

                        if ($holdersArray[$index]['balance_current'] < MINIMUM_LIMIT) {
                            $flag = true;
                            break;
                        }
                        $index++;
                    }
                    if ($flag) {
                        $html->clear();
                        unset($html);
                        break;
                    }
                    $html->clear();
                    unset($html);
                    sleep(3);
                }
            }
        }

        $countHoldersArray = count($holdersArray);
        for ($index = 0; $index < $countHoldersArray; $index++) {
            Whale::firstOrCreate(
                ['holder' => $holdersArray[$index]['holder']],
                [
                ]);
            $holder_id = Whale::where('holder', $holdersArray[$index]['holder'])->first(['id']);
            Holder::firstOrCreate(
                [
                    'token_id' => $holdersArray[$index]['token_id'],
                    'holder_id' => $holder_id->id
                ],
                [
                    'balance_start' => round($holdersArray[$index]['balance_current'], 2),
                    'time_added' => time()
                ]);
            Holder::updateOrCreate(
                [
                    'token_id' => $holdersArray[$index]['token_id'],
                    'holder_id' => $holder_id->id
                ],
                [
                    'balance_current' => round($holdersArray[$index]['balance_current'], 2),
                    'quantity' => $holdersArray[$index]['quantity']
                ]);
        }
        \Log::info('Holders List End - ' . \Carbon\Carbon::now());
    }
}
