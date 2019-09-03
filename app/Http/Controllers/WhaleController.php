<?php

namespace App\Http\Controllers;

use App\Models\Temp1;
use App\Models\Temp2;
use App\Models\Temp3;
use App\Models\Addedtransfer;
use App\Models\Removedtransfer;
use App\Models\Alert;
use App\Models\Balance;
use App\Models\Holder;
use App\Models\Name;
use App\Models\Token;
use App\Models\Infotoken;
use App\Models\Whale;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WhaleController extends Controller
{
    public function begin()
    {

        ini_set('max_execution_time', 300000);
        require_once 'simple_html_dom.php';
        define('HOLDER_GENERAL_ADDRESS_SITE', 'https://etherscan.io/address/');
        define('MINIMUM_LIMIT', 500000);
        $whales = Whale::all()->sortBy('id')->slice(0, 5);

//        $whales = Whale::all()->sortByDesc('balance_current')->slice(0, 100);
//        $cc = 1;
        $in = 1;
        foreach ($whales as $whale) {
//            $url = HOLDER_GENERAL_ADDRESS_SITE . "0xfe9e8709d3215310075d67e3ed32a380ccf451c8";
            $url = HOLDER_GENERAL_ADDRESS_SITE . $whale->holder;
            $html = '';

            while (($html = file_get_html($url)) == '') {
                sleep(3);
            }
            if (!empty($html)) {
                $result1 = $html->find('#balancelistbtn > span.pull-left');
                $balance = $whale->balance_current;
                if (!empty($result1)) {
                    foreach ($result1 as $a) {
                        $str = $a->plaintext;
                        $balanceBeginIndex = strpos($str, '$');
                        $balanceEndIndex = strpos($str, ')');
                        $balance = substr($str, $balanceBeginIndex + 1, $balanceEndIndex - $balanceBeginIndex - 1);
                        $balance = (double)(str_replace(',', '', $balance));
                    }
                }

                $result2 = $html->find('#balancelistbtn > span.badge');
                $totalTokens = $whale->total_tokens;
                if (!empty($result2)) {
                    foreach ($result2 as $a) {
                        $totalTokens = $a->plaintext;
                    }
                }
                if (!empty($result1) && !empty($result2)) {
                    $wh = Whale::where('id', $whale->id)->where('balance_start', null);
                    if ($wh->count() > 0) {
                        if($balance >= MINIMUM_LIMIT) {
                            $name = Name::where('whale_id', null)->where('active', 0)->first();
                            $wh->update([
                                'balance_start' => $balance,
                                'name' => $name->name
                            ]);
                            $name->update([
                                'whale_id' => $whale->id,
                                'active' => 1
                            ]);
                        }else{
                            $wh->delete();
                        }
                    }

                    Whale::where('id', $whale->id)->update([
                        'balance_current' => $balance,
                        'total_tokens' => $totalTokens,
                    ]);
                }
                $result3 = $html->find('#balancelist a');

                foreach ($result3 as $a) {
//                        $str = $a->plaintext;
                        $str = $a->innertext;
                        $balance = 'N\A';
//                        echo $in. " - ". htmlspecialchars($a->innertext)."<br>";
                        $posBalance = strpos($str, '$');
                        if($posBalance !== false){
//                            $pos = strpos($str, 'pull-right');
                            $endPos = strpos($str, "</span>", $posBalance);
                            $balance = str_replace(',', '', substr($str, $posBalance + 1, $endPos - $posBalance - 1));
//                            echo $posBalance."<br>";
//                            echo $endPos."<br>";
//                            echo htmlspecialchars($balance)."<br>";
                        }
//                        die;
                        $posQuantity = strpos($str, '<br>');
                        $posA = strpos($str, "<span class='pull-right liA'>@");
                        $quantity = 'N\A';
                        $token = 'N\A';
                        if($posQuantity !== false){
//                            $pos = $posAstrpos($str, 'pull-right');
//                            $endPos = $posA !== false ? $posA : ;
                            $temp = str_replace(',', '', $posA !== false ? substr($str, $posQuantity + 4, $posA - $posQuantity - 4) : substr($str, $posQuantity + 4));
                            $temp = explode(' ', $temp);

                            $quantity = isset($temp[0]) ? $temp[0] : 'N\A';
                            $token = isset($temp[1]) ? $temp[1] : 'N\A';
//                            $quantity = str_replace(',', '', substr($str, $posQuantity + 4, $endPos));
//                            echo $pos."<br>";
//                            echo $endPos."<br>";
//                            echo $bbb."<br>";
                        }
//                        die;
//                        echo $in. " - ". htmlspecialchars($a->plaintext)."<br>";
                        echo $in. " - BALANCE = " . htmlspecialchars($balance) . " - QUANTITY = " . htmlspecialchars($quantity) . " - TOKEN = " . htmlspecialchars($token) ."<br>";
//                        echo $in. " - ". htmlspecialchars($a->innertext)."<br>";
                        $in++;
//                        $balanceBeginIndex = strpos($str, '$');
//                        $balanceEndIndex = strpos($str, ')');
//                        $balance = substr($str, $balanceBeginIndex + 1, $balanceEndIndex - $balanceBeginIndex - 1);
//                        $balance = (double)(str_replace(',', '', $balance));
                    }
                $html->clear();
                unset($html);
            }

            sleep(1);
        }


        die;




        ini_set('max_execution_time', 30000000);
        define('ETHPLORER_API_KEY2', 'gsuh40102enmvnM55');
        $whale = Whale::where('id', 6)->first();
        $url = "https://api.ethplorer.io/getAddressHistory/" . $whale->holder . "?apiKey=" . ETHPLORER_API_KEY2 . "&type=transfer&limit=20&timestamp=" . time();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output, true);
        if (!empty($output['operations'])) {

            $data = [];
            $index = 0;
            foreach ($output['operations'] as $item) {

                $tokenOne = Token::where('token', $item['tokenInfo']['address'])->first();
                if (!empty($tokenOne)) {
                    if (!empty($tokenOne->symbol)) {
                        if ($whale->holder == $item['from']) {
                            $data[$index]['token_id'] = $tokenOne->id;
                            $data[$index]['is_added'] = 0;
                            $data[$index]['token_symbol'] = $tokenOne->symbol;
                            $data[$index]['quantity'] = round($item['value'] * 1 / (10 ** $tokenOne->token_decimal), 2);
                            $data[$index]['value'] = round($item['value'] * 1 / (10 ** $tokenOne->token_decimal) * $tokenOne->price_usd, 2);
                            $data[$index]['time_updated'] = $item['timestamp'];
                            $token = Token::getToken($tokenOne->id);
                            if (!empty($token)) {
                                $data[$index]['image'] = $token->image;
                            } else {
                                $data[$index]['image'] = '';
                            }
                            $index++;
                        }
                        if ($whale->holder == $item['to']) {
                            $data[$index]['token_id'] = $tokenOne->id;
                            $data[$index]['is_added'] = 1;
                            $data[$index]['token_symbol'] = $tokenOne->symbol;
                            $data[$index]['quantity'] = round($item['value'] * 1 / (10 ** $tokenOne->token_decimal), 2);
                            $data[$index]['value'] = round($item['value'] * 1 / (10 ** $tokenOne->token_decimal) * $tokenOne->price_usd, 2);
                            $data[$index]['time_updated'] = $item['timestamp'];
                            $token = Token::getToken($tokenOne->id);
                            if (!empty($token)) {
                                $data[$index]['image'] = $token->image;
                            } else {
                                $data[$index]['image'] = '';
                            }

                            $index++;
                        }
                    }
                } else {
                    $reg = "/[\\\\\/\.]+/";
                    $str = json_encode($item['tokenInfo']['symbol']);
                    if ($whale->holder == $item['from']) {
                        if (empty(preg_match($reg, $str))) {
                            $data[$index]['token_symbol'] = $item['tokenInfo']['symbol'];
                        } else {
                            $data[$index]['token_symbol'] = "N/A";
                        }
                        $data[$index]['token_id'] = 0;
                        $data[$index]['is_added'] = 0;
                        $data[$index]['quantity'] = round($item['value'] * 1 / (10 ** (int)$item['tokenInfo']['decimals']), 2);
                        $data[$index]['value'] = -1;
                        $data[$index]['time_updated'] = $item['timestamp'];
                        $data[$index]['image'] = '';
                        $index++;
                    }
                    if ($whale->holder == $item['to']) {
                        if (empty(preg_match($reg, $str))) {
                            $data[$index]['token_symbol'] = $item['tokenInfo']['symbol'];
                        } else {
                            $data[$index]['token_symbol'] = "N/A";
                        }
                        $data[$index]['token_id'] = 0;
                        $data[$index]['is_added'] = 1;
                        $data[$index]['quantity'] = round($item['value'] * 1 / (10 ** (int)$item['tokenInfo']['decimals']), 2);
                        $data[$index]['value'] = -1;
                        $data[$index]['time_updated'] = $item['timestamp'];
                        $data[$index]['image'] = '';
                        $index++;
                    }
                }
            }
        }

        echo "<pre>";
        print_r($data);
        echo "</pre>";

        die;

//        $s = 'sdfsdf/asdfasdf';
        $s = '🐶';
//        $s = '👉 Refereum.com/-1refid=digitalforensic';
        $ss = json_encode($s);
        echo $s . "<br>";
        echo $ss . "<br>";
        $reg = "/[\\\\\/]+/";

        if (empty(preg_match($reg, $ss))) {
//            if (strpos($s, '.com') === false) {
            echo "record";
//            } else {
//                echo "N/a";
//            }
        } else {
            echo "N/aAA1123";
        }
        die;

        ini_set('max_execution_time', 30000000);
        $timeBegin = date('m/d/y g:i:s a');
        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
        define('ETHPLORER_API_KEY2', 'gsuh40102enmvnM55');
        $whales = Whale::all()->sortByDesc('balance_current')->where('id', '<>', 9221)->slice(0, 100);
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
                                $removed[$index2]['is_added'] = 0;
                                $removed[$index2]['token_symbol'] = $tokenOne->symbol;

                                $removed[$index2]['quantity'] = $item['value'] * 1 / (10 ** $tokenOne->token_decimal);
                                $removed[$index2]['value'] = $item['value'] * 1 / (10 ** $tokenOne->token_decimal) * $tokenOne->price_usd;
                                $removed[$index2]['time_updated'] = $item['timestamp'];
                                $index2++;
                            }
                            if ($whale->holder == $item['to']) {
                                $added[$index1]['whale_id'] = $whale->id;
                                $added[$index1]['token_id'] = $tokenOne->id;
                                $added[$index1]['is_added'] = 1;
                                $added[$index1]['token_symbol'] = $tokenOne->symbol;
                                $added[$index1]['quantity'] = $item['value'] * 1 / (10 ** $tokenOne->token_decimal);
                                $added[$index1]['value'] = $item['value'] * 1 / (10 ** $tokenOne->token_decimal) * $tokenOne->price_usd;
                                $added[$index1]['time_updated'] = $item['timestamp'];
                                $index1++;
                            }
                        }
                    } else {
                        if ($whale->holder == $item['from']) {
                            $removed[$index2]['whale_id'] = $whale->id;
                            $removed[$index2]['token_id'] = 0;
                            $removed[$index2]['is_added'] = 0;
                            $removed[$index2]['token_symbol'] = (string)$item['tokenInfo']['symbol'];
                            echo (string)$item['tokenInfo']['symbol'] . "<br>";
                            $removed[$index2]['quantity'] = $item['value'] * 1 / (10 ** (int)$item['tokenInfo']['decimals']);
                            $removed[$index2]['value'] = -1;
                            $removed[$index2]['time_updated'] = $item['timestamp'];
                            $index2++;
                        }
                        if ($whale->holder == $item['to']) {
                            $added[$index1]['whale_id'] = $whale->id;
                            $added[$index1]['token_id'] = 0;
                            $added[$index1]['is_added'] = 1;
                            $added[$index1]['token_symbol'] = (string)$item['tokenInfo']['symbol'];
                            echo (string)$item['tokenInfo']['symbol'] . "<br>";
                            $added[$index1]['quantity'] = $item['value'] * 1 / (10 ** (int)$item['tokenInfo']['decimals']);
                            $added[$index1]['value'] = -1;
                            $added[$index1]['time_updated'] = $item['timestamp'];
                            $index1++;
                        }
                    }
                }
//                Transfer::insert($added);
//                Transfer::insert($removed);
//                echo "<pre>";
//        print_r($added[]['token_symbol']);
//        print_r($removed[]['token_symbol']);
//        echo "</pre>";
            }
        }

        $timeEnd = date('m/d/y g:i:s a');
        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
        echo "TIME End   =   " . $timeEnd . "<br/>";
        die;


        $whales = Whale::all();
//        $balances = Balance::all();
        foreach ($whales as $whale) {
            $balance = Balance::where('holder_id', $whale->id)->orderBy('time')->first();
            if (!empty($balance)) {
                Holder::where('holder_id', $whale->id)->update([
                    'time_added' => $balance->time
                ]);
            }
        }

        echo "DONE";
        die;


//        $url = 'https://image.freepik.com/free-icon/no-translate-detected_318-136418.jpg';
        $url = 'https://etherscan.io/token/images/';
//        $url = 'https://etherscan.io/token/images/colu_28.png';
        $path = './public/images/';
//        $path = './public/images/token/';
//        $path = './public/images/token/1.png';
        $ar = [
            'colu_28.png',
            'heronode_28.png',
            'morpheus_28.png',
            'zippie_28.png',
            'fitrova_28.png',
            'policypal_28_2.png',
            'fuzex_28.png',
            'unibright_28.png',
            'fabric1_28.png',
            'oystershell_28.png',
            'digitextoken_28.png',
            'datarius_28.png',
            'signals_28.png'
        ];
        for ($i = 0; $i < count($ar); $i++) {
            copy($url . $ar[$i], $path . $ar[$i]);
            echo "done" . $i . "<br>";
        }
//file_put_contents($path, file_get_contents($url));
//        copy($url,$path);echo "done3";
        die;

        $ch = curl_init($url);
        $fp = fopen($path, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

//        copy("https://etherscan.io/token/images/colu_28.png","/public/images/colu_28.png");

        echo "done2";
        die;

        function get_gravatar($email, $s = 50, $d = 'mp', $r = 'g', $img = true, $atts = array())
        {
//        function get_gravatar( $email, $s = 80, $d = 'mp', $r = 'g', $img = false, $atts = array() ) {
            $url = 'https://www.gravatar.com/avatar/';
            $url .= md5(strtolower(trim($email)));
            $url .= "?s=$s&d=$d&r=$r";
            if ($img) {
                $url = '<img src="' . $url . '"';
                foreach ($atts as $key => $val)
                    $url .= ' ' . $key . '="' . $val . '"';
                $url .= ' />';
            }
            return $url;
        }

        echo get_gravatar('radvic985@gmail.com');
        echo get_gravatar('sg.victorradkevychgmail.com');
        die;
//        $alerts = \App\Models\Alert::getActiveAlerts(\Illuminate\Support\Facades\Auth::id());
//        $alerts = Alert::getUserAlerts(Auth::id());
        $alerts = Alert::getActiveAlerts(Auth::id());
        foreach ($alerts as $alert) {
            echo $alert->message . "<br>";
        }
        die;
        return view('debug');
        die;


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

        echo "<pre>";
        print_r($tokensInfo1);
        echo "</pre>";

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
        echo "<pre>";
        print_r($tokensInfo2);
        echo "</pre>";
//        die;
        Infotoken::truncate();
        Infotoken::insert($tokensInfo1);
        Infotoken::insert($tokensInfo2);

        die;


        return view('debug');
        $s = 'l,-20';
        echo $s . "<br>";
        echo $s[0] . "<br>";
        die;

//        echo Whale::all()->sortByDesc('balance_current')->where('holder', '0xd7479145e52adc22e6c4dc2c6809a69716823f42')->count();die;
//       echo $wh->where('holder', '0xd7479145e52adc22e6c4dc2c6809a69716823f42')->count();die;

        define('ETHPLORER_API_KEY', 'skffj61105BkR78');
        define('HOLDER_GENERAL_ADDRESS_SITE', 'https://etherscan.io/address/');
        define('MINIMUM_LIMIT', 500000);
        ini_set('max_execution_time', 300000);
        require_once 'simple_html_dom.php';
        $alerts = Alert::all()->where('type_id', 2)->where('active', 0);
        $from = [];
        $to = [];
        $whaleCount = Whale::all()->count();

        foreach ($alerts as $alert) {
            $alertValue = explode(',', $alert->val_per_token);
            $type = $alertValue[0];
            $value = $alertValue[1];
            $token = Token::where('id', $alert->whale_token)->first();
//            $url = "https://api.ethplorer.io/getTokenHistory/" . $token->token . "?apiKey=" . ETHPLORER_API_KEY . "&type=transfer&limit=1000&timestamp=" . 1526484934;
            $url = "https://api.ethplorer.io/getTokenHistory/" . $token->token . "?apiKey=" . ETHPLORER_API_KEY . "&type=transfer&limit=1000&timestamp=" . time();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            $output = json_decode($output, true);
//echo "<pre>";
//print_r($output);
//echo "</pre>";


            if (!empty($output['operations'])) {
                if ($type == 'l') {
                    foreach ($output['operations'] as $item) {
                        if ($item['timestamp'] <= $alert->time_updated) {
                            break;
                        }
//                        echo "<pre>";
//print_r($item);
//echo "</pre>";
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
//                    echo "done";
//                    die;
//                    $percentFrom = 80;
                    $percentFrom = (Temp1::where('alert_id', $alert->id)->where('from', '<>', null)->count() * 100) / $whaleCount;
                    $percentTo = (Temp1::where('alert_id', $alert->id)->where('to', '<>', null)->count() * 100) / $whaleCount;
//                    $percentTo = 60;
                    if ($percentFrom > $value) {
                        Alert::where('id', $alert->id)->update([
                            'active' => 1,
                            'change' => -1,
                            'active_time' => time(),
                            'message' => $token->symbol . " is removed by at least " . number_format($percentFrom)
                                . "% of whales for the period from " . date('y/m/d g:i:sa', $alert->time_created)
                                . " to " . date('y/m/d g:i:sa', $output['operations'][0]['timestamp']) . "!"
                        ]);
                        Temp1::where('alert_id', $alert->id)->delete();
                    }
                    if ($percentTo > $value) {
                        Alert::where('id', $alert->id)->update([
                            'active' => 1,
                            'change' => 1,
                            'active_time' => time(),
                            'message' => $token->symbol . " is added by at least " . number_format($percentTo)
                                . "% of whales for the period from " . date('y/m/d g:i:sa', $alert->time_created)
                                . " to " . date('y/m/d g:i:sa', $output['operations'][0]['timestamp']) . "!"
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
//                        echo "<pre>";
//print_r($item);
//echo "</pre>";

//echo $alert->whale_token."<br>";
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
                                    'message' => $token->symbol . " is removed by top " . $value
                                        . " whales for the period from " . date('y/m/d g:i:sa', $alert->time_created)
                                        . " to " . date('y/m/d g:i:sa', $output['operations'][0]['timestamp']) . "!"
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
                                    'message' => $token->symbol . " is added by top " . $value
                                        . " whales for the period from " . date('y/m/d g:i:sa', $alert->time_created)
                                        . " to " . date('y/m/d g:i:sa', $output['operations'][0]['timestamp']) . "!"
                                ]);
                                Temp2::where('alert_id', $alert->id)->delete();
                                break;
                            }
                        }
                    }
//                    echo "<br>done<br>";
//                    die;
//                    $percentFrom = 80;
//                    $percentFrom = (Temp2::where('alert_id', $alert->id)->where('from', '<>', null)->count() * 100) / $whaleCount;
//                    $percentTo = (Temp2::where('alert_id', $alert->id)->where('to', '<>', null)->count() * 100) / $whaleCount;
//                    $percentTo = 60;


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

        die;

        define('ETHPLORER_API_KEY', 'skffj61105BkR78');
        $alerts = Alert::all()->where('type_id', 3)->where('active', 0);
//        echo "<pre>";
//        print_r($alerts);
//        echo "</pre>";die;
        foreach ($alerts as $alert) {
            $whale = Whale::where('id', $alert->whale_token)->first(['holder', 'name']);
            $token = Token::where('id', $alert->val_per_token)->first();
            $url = "https://api.ethplorer.io/getAddressHistory/" . $whale->holder . "?apiKey=" . ETHPLORER_API_KEY
                . "&token=" . $token->token . "&type=transfer&limit=1&timestamp=" . time();
//            $url = "https://api.ethplorer.io/getAddressHistory/" . $whale->holder . "?apiKey=" . ETHPLORER_API_KEY
//                . "&token=" . $token->token . "&type=transfer&limit=10&timestamp=" . time();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            $output = json_decode($output, true);
            echo "<pre>";
            print_r($output['operations']);
            echo "</pre>";
//        die;
            if (!empty($output['operations'])) {
                foreach ($output['operations'] as $item) {
//                if($item['timestamp'] < $alert->time_updated){
//                    break;
//                }
//                    $tokenOne = Token::where('token', $item['tokenInfo']['address'])->first();
//                    if (!empty($tokenOne)) {
//                    echo $item['value'] . "<br>";
                    echo $value = $item['value'] * 1 / (10 ** $token->token_decimal) * $token->price_usd;
                    echo "<br>";
//                    if ($alert->val_per_token <= $value) {
                    if ($whale->holder == $item['from']) {
                        Alert::where('id', $alert->id)->update(['active' => 1,
                            'change' => -1,
                            'active_time' => time(),
                            'message' => $whale->name . " removed " . number_format($value, 2) . " USD from the " . $token->symbol . " token at " . date('y/m/d g:i:sa', $item['timestamp']) . "!"]);
                        break;
                    }

                    if ($whale->holder == $item['to']) {
                        Alert::where('id', $alert->id)->update([
                            'active' => 1,
                            'change' => 1,
                            'active_time' => time(),
                            'message' => $whale->name . " added " . number_format($value, 2) . " USD to the " . $token->symbol . " token at " . date('y/m/d g:i:sa', $item['timestamp']) . "!"
                        ]);
                        break;
                    }
//                    }
//                    }
                }
                Alert::where('id', $alert->id)->update([
                    'time_updated' => time(),
                ]);
            }
            echo "+++++++++++++++++++++++++++++++++++<br>";
        }
//                $tokenOne = Token::where('token', $token['tokenInfo']['address'])->first();
//                echo "<pre>";
//        print_r($tokenOne);
//        echo "</pre>";
////        die;
//                if (!empty($tokenOne)) {
////                    foreach ($tokenOne as $item) {
//                        $holder = Holder::where('holder_id', $alert->whale_token)->where('token_id', $tokenOne->id);
////                        echo "<pre>";
////        print_r($holder->count());
////        print_r($holder->first(['balance_current']));
////        echo "</pre>";die;
//                        if ($holder->count() > 0) {
//                            $holderBalanceByToken = $holder->first(['balance_current']);
//                            $newBalance = $token['balance'] * 1 / (10 ** $tokenOne->token_decimal) * $tokenOne->price_usd;
//                            if ($newBalance - $alert->val_per_token > $holderBalanceByToken->balance_current) {
//                                Alert::where('id', $alert->id)->update([
//                                    'active' => 1,
//                                    'change' => 1,
//                                    'active_time' => time()
//                                ]);
//                            }
//                            if ($newBalance + $alert->val_per_token < $holderBalanceByToken->balance_current) {
//                                Alert::where('id', $alert->id)->update([
//                                    'active' => 1,
//                                    'change' => -1,
//                                    'active_time' => time()
//                                ]);
//                            }
//                            $holder->update([
//                                'balance_current' => $newBalance
//                            ]);
//                            echo "EEE<br>";
//                        }
////                    }
//                }
//        echo
//        \Log::info('Alert1 END ' .  " - " . \Carbon\Carbon::now());

        die;


        \Log::info('Alert 2 START - ' . \Carbon\Carbon::now());
        ini_set('max_execution_time', 300000);
        define('ETHPLORER_API_KEY', 'skffj61105BkR78');
//        $whales = Whale::all()->sortBy('id');
        $alerts = Alert::all()->where('type_id', 1);
//        echo "<pre>";
//        print_r($alerts);
//        echo "</pre>";die;
        foreach ($alerts as $alert) {
            $whale = Whale::where('id', $alert->whale_token)->first(['holder']);
//            foreach ($whales as $whale) {
            $url = "https://api.ethplorer.io/getAddressInfo/" . $whale->holder . "?apiKey=" . ETHPLORER_API_KEY;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            $output = json_decode($output, true);
            $totalTokens = count($output['tokens']);
//        echo "<pre>";
//        print_r($output);
//        echo "</pre>";
            //die;
//        $a = json_decode($a, true);
            $sum = 0;
            foreach ($output['tokens'] as $token) {
//                $asd = $token['tokenInfo']['address'];
//                echo $token['tokenInfo']['address'];
                $tokenOne = Token::where('token', $token['tokenInfo']['address'])->first();
//                $tokenOne = Token::all()->where('token', $token['tokenInfo']['address']);
//                $tokenOne = Token::all()->where('token', $token['tokenInfo']['address'])->toArray();
                echo "<pre>";
                print_r($tokenOne);
                echo "</pre>";
//        die;
                if (!empty($tokenOne)) {
//                    foreach ($tokenOne as $item) {
                    $holder = Holder::where('holder_id', $alert->whale_token)->where('token_id', $tokenOne->id);
//                        echo "<pre>";
//        print_r($holder->count());
//        print_r($holder->first(['balance_current']));
//        echo "</pre>";die;
                    if ($holder->count() > 0) {
                        $holderBalanceByToken = $holder->first(['balance_current']);
                        $newBalance = $token['balance'] * 1 / (10 ** $tokenOne->token_decimal) * $tokenOne->price_usd;
                        if ($newBalance - $alert->val_per_token > $holderBalanceByToken->balance_current) {
                            Alert::where('id', $alert->id)->update([
                                'active' => 1,
                                'change' => 1,
                                'active_time' => time()
                            ]);
                        }
                        if ($newBalance + $alert->val_per_token < $holderBalanceByToken->balance_current) {
                            Alert::where('id', $alert->id)->update([
                                'active' => 1,
                                'change' => -1,
                                'active_time' => time()
                            ]);
                        }
                        $holder->update([
                            'balance_current' => $newBalance
                        ]);
                        echo "EEE<br>";
                    }
//                    }
                }
            }
        }
        \Log::info('Alert2 END  ' . " - " . \Carbon\Carbon::now());

        die;
        $search = 'Davenport';
        $whale = Whale::all()->where('name', $search)->first();
        if (!empty($whale)) {
            return json_encode("/whale" . $whale->id);
        }

//        $whales = Whale::all()->where('name', 'like', 'da%')->sortBy('name')->pluck('name');
//        $whales = Whale::where('name', 'like', 'da%')->orderBy('name')->get();
        $response = [];

        $whale_names = Whale::where('name', 'like', 'da%')->orderBy('name')->pluck('name');
        $whale_names = $whale_names->all();
        $whale_addresses = Whale::where('holder', 'like', '0xda%')->orderBy('holder')->pluck('holder');
        $whale_addresses = $whale_addresses->all();
        $token_names = Token::where('name', 'like', '%da%')->orderBy('name')->pluck('name');
        $token_names = $token_names->all();
        $token_addresses = Whale::where('holder', 'like', '0xda%')->orderBy('holder')->pluck('holder');
        $token_addresses = $token_addresses->all();
        $response = array_merge($whale_names, $whale_addresses, $token_names, $token_addresses);
        json_encode($response);
//        sort($response);
        echo "<pre>";
        print_r($whale_names);
        print_r($whale_addresses);
        print_r($token_names);
        print_r($token_addresses);
        print_r($response);
        print_r(json_encode($response));
        echo "</pre>";
        die;
        die;

        $whales = Whale::all()->sortBy('id');
        foreach ($whales as $whale) {
            if (Holder::where('holder_id', $whale->id)->count() == 0) {
                echo $whale->id . " - " . $whale->balance_start . " - " . $whale->balance_current . "<br>";
                Name::where('whale_id', $whale->id)->update([
                    'whale_id' => null,
                    'active' => 0
                ]);
                Whale::where('id', $whale->id)->delete();
//                die;
            }
        }
        die;
        $whales = Whale::all()->sortByDesc('balance_current');
////        echo "<pre>";
////        print_r($whales);
////        echo "</pre>";die;
//        $aa = 1;
//        foreach ($whales as $whale) {
////            if ($whale->top_holdings == 0) {
//                Name::where('name', $whale->name)->update([
//                    'whale_id' => $whale->id,
//                    'active' => 1
//                ]);
////                Whale::where('id', $whale->id)->delete();
//                echo $aa . "  -  id = " . $whale->id . "<br>";
//                $aa++;
////            }
//        }
//
//
//        die;

        $holder_address = Whale::where('id', 9108)->first(['holder'])->toArray();
        die;

        $token = Token::all()->sortBy('id');
        foreach ($token as $item) {
            echo '<img src="/public/images/token/' . $item->image . '" alt="!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"> - ' . $item->symbol . " - " . $item->token . '<br>';//." - ".$item->token
//            echo $item->symbol." - ".$item->token
        }
        die;
        echo mktime(23, 59, 59, 05, 11, 2018) . "<br>";
        echo mktime(23, 59, 59, 05, 12, 2018) . "<br>";
        echo mktime(23, 59, 59, 05, 13, 2018) . "<br>";
        die;
//        echo (100 - random_int(-25 ,25) / 100);echo "<br>";
//        echo (100 - random_int(-25 ,25)) / 100;die;
        \Log::info('SAVE DAILY Total Balance START - ' . \Carbon\Carbon::now());
        $whales = Whale::all('id', 'balance_current')
            ->where('balance_current', '<>', null)
            ->sortBy('id');
        $balances = [];
        $index = 0;
        foreach ($whales as $whale) {
            $balances[$index]['holder_id'] = $whale->id;
            $balances[$index]['balance'] = round($whale->balance_current * ((100 - random_int(-25, 25)) / 100), 2);
            $balances[$index]['date'] = date("2018-05-13");
            $balances[$index]['time'] = time();
            $index++;
        }
        Balance::insert($balances);
        \Log::info('SAVE DAILY Total Balance END - ' . \Carbon\Carbon::now());
        die;
        $whales = Balance::all()->where('date', '2018-05-10');
//        $whales = Balance::all();
//        $counter = 1;
//        $time_start = microtime(true);
        foreach ($whales as $item) {
//            $a = explode('-', $item->date);
//            echo $a[0]." - ";
//            echo $a[1]." - ";
//            echo $a[2]." - ";
//            Balance::where('id', $item->id)->update([
//               'time' =>  mktime(23, 59, 59, $a[1], $a[2], $a[0])
//            ]);
            $bal = Whale::where('id', $item->holder_id)->first(['balance_current']);
            Balance::where('id', $item->id)->where('balance', 0)->update([
                'balance' => ($bal->balance_current * 0.055)
//               'time' =>  mktime(23, 59, 59, $a[1], $a[2], $a[0])
            ]);
        }
        echo mktime(23, 59, 59, 05, 01, 2018);
        die;

//        $whales = Whale::all()->sortByDesc('balance_current');
////        echo "<pre>";
////        print_r($whales);
////        echo "</pre>";die;
//        $aa = 1;
//        foreach ($whales as $whale) {
////            if ($whale->top_holdings == 0) {
//                Name::where('name', $whale->name)->update([
//                    'whale_id' => $whale->id,
//                    'active' => 1
//                ]);
////                Whale::where('id', $whale->id)->delete();
//                echo $aa . "  -  id = " . $whale->id . "<br>";
//                $aa++;
////            }
//        }
//
//
//        die;
        require_once 'simple_html_dom.php';
        Artisan::call('holders');
        echo "END123asdfasdfasdf";
        die;

        $whales = Whale::all()->where('top_holdings', 0);
        $aa = 1;
        foreach ($whales as $whale) {
            if ($whale->top_holdings == 0) {
                Name::where('whale_id', $whale->id)->update([
                    'whale_id' => null,
                    'active' => 0
                ]);
                Whale::where('id', $whale->id)->delete();
                echo $aa . "  -  id = " . $whale->id . "<br>";
                $aa++;
            }
        }


        die;
        Artisan::call('top_holdings');
        echo "END123";
        die;

        ini_set('max_execution_time', 300000);
        require_once 'simple_html_dom.php';
        define('TOKEN_ADDRESS_SITE', 'https://etherscan.io/token/');
        $tokensForUpdate = Token::all();
        if (!empty($tokensForUpdate)) {
            foreach ($tokensForUpdate as $item) {
                $in = 1;
                $html = '';
                $url = TOKEN_ADDRESS_SITE . $item->token;
                while (($html = file_get_html($url)) == '') {
                    sleep(5);
                }
                if (!empty($html)) {
                    $result2 = $html->find('td.tditem');
                    foreach ($result2 as $a) {
                        if (strpos($a->plaintext, '(') !== false) {
                            $str = $a->plaintext;
                            $arr = explode(' ', $str);
                            $total = '';
                            if ($arr[0] == '') {
                                $total = $arr[1];
                            } else {
                                $total = $arr[0];
                            }
                            $total = (double)str_replace(',', '', $total);
                            Token::where('id', $item->id)->update([
                                'a' => $total
                            ]);
                            echo $in . " - " . $total . "<br>";
                        }
                    }

                    $html->clear();
                    unset($html);
                    sleep(3);
                }
            }
        }
        die;
        echo Holder::where('token_id', 1)->sum('balance_current');
        die;

        $url = "https://api.coinmarketcap.com/v2/global/";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output, true);
//        $totalTokens = count($output['tokens']);
        echo "<pre>";
        print_r($output['data']['quotes']['USD']['total_market_cap']);
        echo "</pre>";
        die;
        $whales = Whale::all('id', 'name', 'balance_current', 'percent_1')->sortByDesc('percent_1')->slice(0, 5)->toArray();
        $whales = Whale::all()->sortByDesc('percent_1')->slice(0, 5);
        echo "<pre>";
        print_r($whales);
        echo "</pre>";
        die;
        $timeBegin = date('m/d/y g:i:s a');
        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
//        $today = time() - 86400;
//        $week = time() - 604800;
//        $month = time() - 2628000;
//        $year = time() - 31540000;
//        $yeartd = mktime(23, 59, 59, 01, 01, date('Y'));
//        $all = 1;
        $whales = Whale::all()->sortBy('id');
//        $whales = Whale::all()->where('total_tokens', '<>', 0)->sortBy('id');
        $allWhales = [];
        $performance = [];
        $index = 0;
        $period = [
            time() - 86400,
            time() - 604800,
            time() - 2628000,
            time() - 31540000,
            mktime(23, 59, 59, 01, 01, date('Y')),
            1
        ];
        $countPeriod = count($period);
        $today = 0;
        $week = 0;
        $month = 0;
        $year = 0;
        $yeartd = 0;
        $all = 0;
        foreach ($whales as $whale) {
            for ($i = 0; $i < $countPeriod; $i++) {
                $temp = -100;
                $balance_start = Balance::where('holder_id', $whale->id)
                    ->where('time', '>=', $period[$i])
                    ->orderBy('time')
                    ->first(['balance']);
                if (empty($balance_start)) {
                    $balance_start = Balance::where('holder_id', $whale->id)->orderBy('time')->first(['balance']);
                }
                if (!empty($balance_start)) {
                    $balance_start = $balance_start->balance;
                    if ($balance_start == 0) {
                        $temp = -100;
                    } else {
                        $temp = round(($whale->balance_current * 100) / $balance_start - 100, 1);
                    }
                }
                switch ($i) {
                    case 0:
                        $today = $temp;
                        break;
                    case 1:
                        $week = $temp;
                        break;
                    case 2:
                        $month = $temp;
                        break;
                    case 3:
                        $year = $temp;
                        break;
                    case 4:
                        $yeartd = $temp;
                        break;
                    case 5:
                        $all = $temp;
                        break;
                }
            }
            Whale::where('id', $whale->id)->update([
                'percent_1' => $today,
                'percent_7' => $week,
                'percent_30' => $month,
                'percent_y' => $year,
                'percent_ytd' => $yeartd,
                'percent_all' => $all,
            ]);
        }
        $timeEnd = date('m/d/y g:i:s a');
        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
        echo "TIME EEEEE   =   " . $timeEnd . "<br/>";
        die;

        die;
        $whales = Whale::all();
        $aa = 1;
        foreach ($whales as $whale) {
            if ($whale->balance_current < 500000) {
                Name::where('whale_id', $whale->id)->update([
                    'whale_id' => null,
                    'active' => 0
                ]);
                Whale::where('id', $whale->id)->delete();
                echo $aa . "  -  id = " . $whale->id . "<br>";
                $aa++;
            }
//            $name = Name::where('whale_id', null)->where('active', 0)->orderBy('id')->first();
//            Whale::where('id', $whale->id)->update([
//                'name' => $name->name
//            ]);
//            $name->active = 1;
//            $name->whale_id = $whale->id;
//            $name->save();
        }


        die;
        ini_set('max_execution_time', 300000);
        define('ETHPLORER_API_KEY', 'skffj61105BkR78');
//        $whales = Whale::all()->sortBy('id');
        $alerts = Alert::all()->where('type_id', 3);
//        echo "<pre>";
//        print_r($alerts);
//        echo "</pre>";die;
        foreach ($alerts as $alert) {
            $whale = Whale::where('id', $alert->whale_token)->first(['holder']);
//            foreach ($whales as $whale) {
            $url = "https://api.ethplorer.io/getAddressInfo/" . $whale->holder . "?apiKey=" . ETHPLORER_API_KEY;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            $output = json_decode($output, true);
            $totalTokens = count($output['tokens']);
//        echo "<pre>";
//        print_r($output);
//        echo "</pre>";
            //die;
//        $a = json_decode($a, true);
            $sum = 0;
            foreach ($output['tokens'] as $token) {
//                $asd = $token['tokenInfo']['address'];
//                echo $token['tokenInfo']['address'];
                $tokenOne = Token::where('token', $token['tokenInfo']['address'])->first();
//                $tokenOne = Token::all()->where('token', $token['tokenInfo']['address']);
//                $tokenOne = Token::all()->where('token', $token['tokenInfo']['address'])->toArray();
                echo "<pre>";
                print_r($tokenOne);
                echo "</pre>";
//        die;
                if (!empty($tokenOne)) {
//                    foreach ($tokenOne as $item) {
                    $holder = Holder::where('holder_id', $alert->whale_token)->where('token_id', $tokenOne->id);
//                        echo "<pre>";
//        print_r($holder->count());
//        print_r($holder->first(['balance_current']));
//        echo "</pre>";die;
                    if ($holder->count() > 0) {
                        $holderBalanceByToken = $holder->first(['balance_current']);
                        $newBalance = $token['balance'] * 1 / (10 ** $tokenOne->token_decimal) * $tokenOne->price_usd;
                        if ($newBalance - $alert->val_per_token > $holderBalanceByToken->balance_current) {
                            Alert::where('id', $alert->id)->update([
                                'active' => 1,
                                'change' => 1,
                                'active_time' => time()
                            ]);
                        }
                        if ($newBalance + $alert->val_per_token < $holderBalanceByToken->balance_current) {
                            Alert::where('id', $alert->id)->update([
                                'active' => 1,
                                'change' => -1,
                                'active_time' => time()
                            ]);
                        }
                        $holder->update([
                            'balance_current' => $newBalance
                        ]);
                        echo "EEE<br>";
                    }
//                    }
                }
            }
        }

        die;

        ini_set('max_execution_time', 300000);
        define('ETHPLORER_API_KEY', 'skffj61105BkR78');
//        $whales = Whale::all()->sortBy('id');
        $alerts = Alert::all()->where('type_id', 1);
//        echo "<pre>";
//        print_r($alerts);
//        echo "</pre>";die;
        foreach ($alerts as $alert) {
            $whale = Whale::where('id', $alert->whale_token)->first(['holder']);
//            foreach ($whales as $whale) {
            $url = "https://api.ethplorer.io/getAddressInfo/" . $whale->holder . "?apiKey=" . ETHPLORER_API_KEY;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($ch);
            curl_close($ch);
            $output = json_decode($output, true);
            $totalTokens = count($output['tokens']);
//        echo "<pre>";
//        print_r($output);
//        echo "</pre>";
            //die;
//        $a = json_decode($a, true);
            $sum = 0;
            foreach ($output['tokens'] as $token) {
//                $asd = $token['tokenInfo']['address'];
//                echo $token['tokenInfo']['address'];
                $tokenOne = Token::where('token', $token['tokenInfo']['address'])->first();
//                $tokenOne = Token::all()->where('token', $token['tokenInfo']['address']);
//                $tokenOne = Token::all()->where('token', $token['tokenInfo']['address'])->toArray();
                echo "<pre>";
                print_r($tokenOne);
                echo "</pre>";
//        die;
                if (!empty($tokenOne)) {
//                    foreach ($tokenOne as $item) {
                    $holder = Holder::where('holder_id', $alert->whale_token)->where('token_id', $tokenOne->id);
//                        echo "<pre>";
//        print_r($holder->count());
//        print_r($holder->first(['balance_current']));
//        echo "</pre>";die;
                    if ($holder->count() > 0) {
                        $holderBalanceByToken = $holder->first(['balance_current']);
                        $newBalance = $token['balance'] * 1 / (10 ** $tokenOne->token_decimal) * $tokenOne->price_usd;
                        if ($newBalance - $alert->val_per_token > $holderBalanceByToken->balance_current) {
                            Alert::where('id', $alert->id)->update([
                                'active' => 1,
                                'change' => 1,
                                'active_time' => time()
                            ]);
                        }
                        if ($newBalance + $alert->val_per_token < $holderBalanceByToken->balance_current) {
                            Alert::where('id', $alert->id)->update([
                                'active' => 1,
                                'change' => -1,
                                'active_time' => time()
                            ]);
                        }
                        $holder->update([
                            'balance_current' => $newBalance
                        ]);
                        echo "EEE<br>";
                    }
//                    }
                }
            }
        }

//        $whale = Whale::where('id', 8279)->where('balance_start', null);
//        if($whale->count() > 0){
//            $name = Name::where('whale_id', null)->where('active', 0)->first();
//            $whale->update([
//                        'balance_start' => 500,
//                        'name' => $name->name
//                    ]);
//            $name->update([
//               'whale_id' => 8279,
//                'active' => 1
//            ]);
//        }
//
//            Whale::where('id', 8279)->update([
//                        'balance_current' => 23500,
//                        'total_tokens' => 456
//                    ]);
//
////        echo $wh;
//
////        ->update([
////                        'balance_start' => 100,
////                        'name' => ''
////                    ]);


        die;
//        $str = "%ra%";
////            echo $str;die;
//            $whales = Whale::where('name', 'like', $str)->get();
//// $query = "SELECT * FROM users WHERE name like'%".$search."%'";
//// $result = mysqli_query($con,$query);
//
//            $response = [];
//            foreach ($whales as $whale) {
//                $response[] = $whale->name;
//            }
//// while($row = mysqli_fetch_array($result) ){
////   $response[] = array("value"=>$row['id'],"label"=>$row['name']);
//// }
////dd(json_encode($response));
//    echo json_encode($response);die;

        $timeBegin = date('m/d/y g:i:s a');
        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
        function _isCurl()
        {
            return function_exists('curl_version');
        }

        ini_set('max_execution_time', 300000);
        \Log::info('FROM TOKEN BalanceStart - ' . \Carbon\Carbon::now());
        Artisan::call('tokens_info');
        define('ETHERSCAN_API_KEY', '7GTJCQMRQ3XSIZXHN15EIZW7ZVC77XESVU');

        $holderData = Holder::all('id', 'token_id', 'holder_id')->sortBy('id');
        $timeStart = time();
        $counter = 0;
        $time_start = microtime(true);
        $newBal = [];
        foreach ($holderData as $item) {
            if (time() - $timeStart > 60) {
                Artisan::call('tokens_info');
                $timeStart = time();
            }
            $token_info = Token::where('id', $item->token_id)->first(['token', 'token_decimal', 'price_usd'])->toArray();
            $holder_address = Whale::where('id', $item->holder_id)->first(['holder'])->toArray();

            $output = [];
            if (_iscurl()) {
//                $url = "https://api.etherscan.io/api?module=account&action=tokenbalance&contractaddress=" . $token_info['token']
//                    . "&address=" . $holder_address['holder'] . "&tag=latest&apikey=" . ETHERSCAN_API_KEY;
                $url = "https://api.tokenbalance.com/balance/" . $token_info['token'] . "/" . $holder_address['holder'];
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $output = curl_exec($ch);
                echo "<pre>";
                print_r($output);
                echo "</pre>";
                curl_close($ch);
                $output = json_decode($output, true);
                echo "<pre>";
                print_r($output);
                echo "</pre>";
            }
            die;
//            if ($counter % 5 == 0) {
//                if (microtime(true) - $time_start - 1 < 0) {
//                    usleep(500000);
//                    \Log::info('Balances OVER 5 REQUESTS PER MINUTE - ' . \Carbon\Carbon::now());
//                }
//                $time_start = microtime(true);
//                $counter = 0;
//            }
//            $counter++;
//            if ($output['message'] == 'OK' && isset($output['result']) && !empty($output['result'])) {


            if (isset($output['balance'])) {
                $quantity = (double)$output['balance'];
                $balance = $token_info['price_usd'] * $quantity;
//                Holder::where('id', $item->id)->update([
//                    'balance_current' => round($balance, 2),
//                    'quantity' => $quantity,
//                ]);
                $newBal[$counter] = round($balance, 2);
            }
            $counter++;
        }
//        $tokens = Token::all()->sortBy('id');
//        foreach ($tokens as $token){
//            Token::where('id', $token->id)->update([
//               'balance' => round(Holder::where('token_id', $token->id)->sum('balance_current'), 2)
//            ]);
//        }
//        die;
        echo "<pre>";
        print_r($newBal);
        echo "</pre>";
        \Log::info('FROM TOKENBalances End - ' . \Carbon\Carbon::now());
        $timeEnd = date('m/d/y g:i:s a');
        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
        echo "TIME End   =   " . $timeEnd . "<br/>";
        die;

        die;

        $countWhales = Whale::all()->count();
        $counth = Holder::all()->count();
        $holder = Holder::all()->sortByDesc('balance_current');
        $ts = Token::all()->sortBy('id');
        $sum = 0;
        foreach ($ts as $t) {
            $c = Holder::where('token_id', $t->id)->count();
            $sum += ($c * 100) / $counth;
        }
        echo $sum . "<br>";
        die;
        echo $countWhales . "<br>";
        echo $counth;
        die;
        $whales = Whale::all()->sortByDesc('balance_current');
        foreach ($whales as $whale) {
            $name = Name::where('whale_id', null)->where('active', 0)->orderBy('id')->first();
            Whale::where('id', $whale->id)->update([
                'name' => $name->name
            ]);
            $name->active = 1;
            $name->whale_id = $whale->id;
            $name->save();
        }
        die;
        ini_set('max_execution_time', 300000);
//         $name = Name::where('whale_id', null)->where('active', 0)->orderBy('id')->first();
//         $name->active = 4;
//         $name->save();
//          echo "<pre>";
//        print_r($name);
//        echo "</pre>";die;
//        $names = Name::all()->sortByDesc('id');
        $whales = Whale::all()->sortByDesc('balance_current');
        foreach ($whales as $whale) {
            $name = Name::where('whale_id', null)->where('active', 0)->orderBy('id')->first();
            Whale::where('id', $whale->id)->update([
                'name' => $name->name
            ]);
            $name->active = 1;
            $name->whale_id = $whale->id;
            $name->save();
        }
        die;
//        $names = Name::all()->sortByDesc('id')->toArray();
//         echo "<pre>";
//        print_r($names);
//        echo "</pre>";die;
        foreach ($names as $name) {
            if (Name::where('name', $name->name)->count() > 1) {
                Name::where('id', $name->id)->delete();
                echo "ID = " . $name->id . "<br>";
            }
//            DELETE t2 FROM `names` t1 LEFT JOIN `names` t2 ON t1.`name` = t2.`name`AND t1.id < t2.id WHERE t2.id IS NOT NULL;
//SELECT `name`, COUNT(*) as c FROM `names`GROUP BY `name` HAVING c > 1
//            Name::where('id', $name->id)->update([
//               'name' =>  ucfirst(strtolower($name->name))
//            ]);
        }

        echo "GOOD2";
        die;
        require_once 'simple_html_dom.php';
//        define('HOLDER_GENERAL_ADDRESS_SITE', 'https://etherscan.io/address/');
//        $whales = Whale::all()->sortBy('id')->slice(6600);

//        $whales = Whale::all()->where('balance_current', 0);
//        $cc = 1;
//        foreach ($whales as $whale) {
        for ($i = 1; $i <= 49; $i++) {
            $url = "https://names.mongabay.com/data/" . $i . "000.html";
            $html = '';
            $html = file_get_html($url);
//            while (($html = file_get_html($url)) == '') {
////                \Log::info('IN WHILE - ' . $cc . " - " . \Carbon\Carbon::now());
//                sleep(3);
//            }

//            if ($html === false) {
//                \Log::info('HTML FALSE - ITEM - ' . $cc . " - " . \Carbon\Carbon::now());
//            } else {
            if (!empty($html)) {
                $result1 = $html->find('#myTable tr');
//                $balance = $whale->balance_current;
                if (!empty($result1)) {
                    foreach ($result1 as $a) {
                        echo $a->children(0) . "<br>";
//                        $str = $a->plaintext;
//                        $balanceBeginIndex = strpos($str, '$');
//                        $balanceEndIndex = strpos($str, ')');
//                        $balance = substr($str, $balanceBeginIndex + 1, $balanceEndIndex - $balanceBeginIndex - 1);
//                        $balance = (double)(str_replace(',', '', $balance));
                    }
                } else {
                    echo "EMPTY RESULT";
                }
//echo "Balance = " .$balance;
//                $result2 = $html->find('#balancelistbtn > span.badge');
//                $totalTokens = $whale->total_tokens;
//                if (!empty($result2)) {
//                    foreach ($result2 as $a) {
//                        $totalTokens = $a->plaintext;
//                    }
//                }
//                echo " - Total tokens = " .$totalTokens."<br>";
//                if (!empty($result1) && !empty($result2)) {
//                    Whale::where('id', $whale->id)->where('balance_start', null)->update([
//                        'balance_start' => $balance,
//                    ]);
//                    Whale::where('id', $whale->id)->update([
//                        'balance_current' => $balance,
//                        'total_tokens' => $totalTokens,
//                    ]);
//                }
//                \Log::info('TOP 100 GOOD ITEM - ' . $cc . " - " . \Carbon\Carbon::now());
                $html->clear();
                unset($html);
            } else {
                echo "EMPTY HTML";
            }

//            $cc++;
//            usleep(500000);
//            sleep(5);
//        }
        }
        die;

//$whales = Whale::all('id', 'balance_current')->sortBy('id');
//        $balances = [];
//        $index = 0;
//        foreach ($whales as $whale) {
//            $balances[$index]['holder_id'] = $whale->id;
//            $balances[$index]['balance'] = round($whale->balance_current * 0.99990995027, 2);
//            $balances[$index]['date'] = date("Y-m-d", mktime(23, 59, 59, 04, 29, 2018));
//            $balances[$index]['time'] = mktime(23, 59, 59, 04, 29, 2018);
//            $index++;
//        }
//        Balance::insert($balances);
//        die;
//        require_once 'simple_html_dom.php';
//        define('HOLDER_GENERAL_ADDRESS_SITE', 'https://etherscan.io/address/');
////        $whales = Whale::all()->sortBy('id')->slice(6600);
//
//        $whales = Whale::all()->where('balance_current', 0);
////        $cc = 1;
//        foreach ($whales as $whale) {
//            $url = HOLDER_GENERAL_ADDRESS_SITE . $whale->holder;
//            $html = '';
//            while (($html = file_get_html($url)) == '') {
////                \Log::info('IN WHILE - ' . $cc . " - " . \Carbon\Carbon::now());
//                sleep(3);
//            }
//
////            if ($html === false) {
////                \Log::info('HTML FALSE - ITEM - ' . $cc . " - " . \Carbon\Carbon::now());
////            } else {
//            if (!empty($html)) {
//                $result1 = $html->find('#balancelistbtn > span.pull-left');
//                $balance = $whale->balance_current;
//                if (!empty($result1)) {
//                    foreach ($result1 as $a) {
//                        $str = $a->plaintext;
//                        $balanceBeginIndex = strpos($str, '$');
//                        $balanceEndIndex = strpos($str, ')');
//                        $balance = substr($str, $balanceBeginIndex + 1, $balanceEndIndex - $balanceBeginIndex - 1);
//                        $balance = (double)(str_replace(',', '', $balance));
//                    }
//                }
//echo "Balance = " .$balance;
//                $result2 = $html->find('#balancelistbtn > span.badge');
//                $totalTokens = $whale->total_tokens;
//                if (!empty($result2)) {
//                    foreach ($result2 as $a) {
//                        $totalTokens = $a->plaintext;
//                    }
//                }
//                echo " - Total tokens = " .$totalTokens."<br>";
//                if (!empty($result1) && !empty($result2)) {
//                    Whale::where('id', $whale->id)->where('balance_start', null)->update([
//                        'balance_start' => $balance,
//                    ]);
//                    Whale::where('id', $whale->id)->update([
//                        'balance_current' => $balance,
//                        'total_tokens' => $totalTokens,
//                    ]);
//                }
////                \Log::info('TOP 100 GOOD ITEM - ' . $cc . " - " . \Carbon\Carbon::now());
//                $html->clear();
//                unset($html);
//            }
//
////            $cc++;
////            usleep(500000);
//            sleep(5);
//        }
//
//        die;

        $whales = Balance::all()->where('date', '2018-05-01');
//        $whales = Balance::all();
//        $counter = 1;
//        $time_start = microtime(true);
        foreach ($whales as $item) {
            $a = explode('-', $item->date);
//            echo $a[0]." - ";
//            echo $a[1]." - ";
//            echo $a[2]." - ";
//            Balance::where('id', $item->id)->update([
//               'time' =>  mktime(23, 59, 59, $a[1], $a[2], $a[0])
//            ]);
            $bal = Whale::where('id', $item->holder_id)->first(['balance_current']);
            Balance::where('id', $item->id)->where('balance', 0)->update([
                'balance' => ($bal->balance_current * 0.055)
//               'time' =>  mktime(23, 59, 59, $a[1], $a[2], $a[0])
            ]);
        }
        echo mktime(23, 59, 59, 05, 01, 2018);
        die;
        \Log::info('TOKENSBalance START - ' . \Carbon\Carbon::now());
        ini_set('max_execution_time', 300000);
        require_once 'simple_html_dom.php';
        define('HOLDER_GENERAL_ADDRESS_SITE', 'https://etherscan.io/address/');
//        $whales = Whale::all()->sortBy('id');
//        $whales = Whale::all()->sortByDesc('balance_current')->slice(0, 100);
        $cc = 1;
//        foreach ($whales as $whale) {
        $url = "https://tokenbalance.com/coins/0xf4b51b14b9ee30dc37ec970b50a486f37686e2a8";
        echo $url . "<br>";
//            $url = HOLDER_GENERAL_ADDRESS_SITE . $whale->holder;
        $html = '';
        while (($html = file_get_html($url)) == '') {
            \Log::info('IN WHILE - ' . $cc . " - " . \Carbon\Carbon::now());
            sleep(1);
        }
        echo "AAAAA _ _ _ _ _--- - - - -" . $url . "<br>";
//            if ($html === false) {
//                \Log::info('HTML FALSE - ITEM - ' . $cc . " - " . \Carbon\Carbon::now());
//            } else {
        if ($html != '') {
            echo "HTML NOT EMPTY<br>";
            $result = $html->find('span.badge-success');
            sleep(3);
//                $result = $html->find('.ajaxtoken');
//                $result = $html->find('#balancelistbtn > span.pull-left');
            $balance = 0;
            foreach ($result as $a) {
                $str = $a->plaintext;
                echo $str . "<br>";
//                    $balanceBeginIndex = strpos($str, '$');
//                    $balanceEndIndex = strpos($str, ')');
//                    $balance = substr($str, $balanceBeginIndex + 1, $balanceEndIndex - $balanceBeginIndex - 1);
//                    $balance = (double)(str_replace(',', '', $balance));
            }

//                $result = $html->find('#balancelistbtn > span.badge');
//                $totalTokens = 0;
//                foreach ($result as $a) {
//                    $totalTokens = $a->plaintext;
//                }
//                Whale::where('id', $whale->id)->where('balance_start', null)->update([
//                    'balance_start' => $balance,
//                ]);
//                Whale::where('id', $whale->id)->update([
//                    'balance_current' => $balance,
//                    'total_tokens' => $totalTokens,
//                ]);
//                \Log::info('TOP 100 GOOD ITEM - ' . $cc . " - " . \Carbon\Carbon::now());
            $html->clear();
            unset($html);
        } else {
            echo "BAD";
        }

//            $cc++;

        die;

        \Log::info('TOPLAST ACTIVE START - ' . \Carbon\Carbon::now());
        define('ETHPLORER_API_KEY', 'skffj61105BkR78');
        function _isCurl()
        {
            return function_exists('curl_version');
        }

        function getTimestamp($id)
        {
            $output = [];
            if (_iscurl()) {
//                $url = "https://api.ethplorer.io/getAddressHistory/" . $id . "?apiKey=freekey&type=transfer&limit=1";
                $url = "https://tokenbalance.com/coins/0xf4b51b14b9ee30dc37ec970b50a486f37686e2a8";
//                $url = "https://api.ethplorer.io/getAddressHistory/" . $id . "?apiKey=" . ETHPLORER_API_KEY . "&type=transfer&limit=1";
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

        $whales = Whale::all()->sortByDesc('balance_current')->slice(0, 100);
//        $counter = 1;
//        $time_start = microtime(true);
        foreach ($whales as $item) {
//            if ($counter % 2 == 0) {
//                if (microtime(true) - $time_start - 1 < 0) {
//                    usleep(500000);
////                    \Log::info('TIMESTAMP -  OVER 2 REQUESTS PER MINUTE - ' . $item->id . \Carbon\Carbon::now());
//                }
//                $time_start = microtime(true);
//                $counter = 0;
//            }
//            $counter++;

            $result = getTimestamp($item->holder);
            if ($result !== false) {
                Whale::where('id', $item->id)->update([
                    'last_active' => $result
                ]);
            } else {
                Whale::where('id', $item->id)->update([
                    'last_active' => 0
                ]);
            }
            usleep(1500000);
        }
        \Log::info('TOPLAST ACTIVE END NEWWWW- ' . \Carbon\Carbon::now());

        die;
        $url = "https://api.ethplorer.io/getAddressInfo/0x62a23e43792cd096685896d2cf5aa8fd3d7bf36a?apiKey=freekey";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output, true);
        echo count($output['tokens']);
        echo "<pre>";
        print_r($output);
        echo "</pre>";
        die;
//        $a = json_decode($a, true);
        $sum = 0;
        foreach ($output['tokens'] as $aa) {
//            echo $aa['tokenInfo']['address']. "<br/>";
            $pr = Token::all()->where('token', $aa['tokenInfo']['address'])->toArray();
//             echo "<pre>";
//        print_r($pr);
//        echo "</pre>";
            if (!empty($pr)) {

                foreach ($pr as $item) {
//                    echo $item['price_usd'] . "<br/>";
//                    $token_info['price_usd'] * (double)$output['result'] * 1 / (10 ** $token_info['token_decimal']);
                    $sum += $aa['balance'] * 1 / (10 ** $item['token_decimal']) * $item['price_usd'];
                }
//                print_r($pr);
            }
//

//            $sum += $aa['balance'];

        }
//        die;
        echo $sum . "<br>";
//        echo "<pre>";
//        print_r($a['tokens']);
//        echo "</pre>";die;
        die;


        define('ETHERSCAN_API_KEY', '7GTJCQMRQ3XSIZXHN15EIZW7ZVC77XESVU');
        define('HOLDER_GENERAL_ADDRESS_SITE', 'https://etherscan.io/address/');
//        $whales = Whale::all()->sortByDesc('balance_current')->slice(0, 100);
//        foreach ($whales as $whale) {
        $url = HOLDER_GENERAL_ADDRESS_SITE . '0xd0a6e6c54dbc68db5db3a091b171a77407ff7ccf';
//            $url = HOLDER_GENERAL_ADDRESS_SITE . $whale->holder;
        $timeBegin = date('m/d/y g:i:s a');
        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";

//            $url = "https://api.etherscan.io/api?module=account&action=tokenbalance&contractaddress=" . $token_info['token']
//                    . "&address=" . $holder_address['holder'] . "&tag=latest&apikey=" . ETHERSCAN_API_KEY;
        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $webcontent = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        echo "<pre>";
        print_r($error);
        print_r($webcontent);
        echo "<pre>";
        $timeEnd = date('m/d/y g:i:s a');
        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
        echo "TIME End   =   " . $timeEnd . "<br/>";
        die;

        function _isCurl()
        {
            return function_exists('curl_version');
        }


        ini_set('max_execution_time', 300000);

        \Log::info('WHALEBalances Start - ' . \Carbon\Carbon::now());
//        Artisan::call('tokens_info');
        define('ETHERSCAN_API_KEY', '7GTJCQMRQ3XSIZXHN15EIZW7ZVC77XESVU');

        $holderData = Holder::all('id', 'token_id', 'holder_id');
        $timeStart = time();
        $counter = 1;
        $counter2 = 1;
        $time_start = microtime(true);
        foreach ($holderData as $item) {
            if (time() - $timeStart > 60) {
//                Artisan::call('tokens_info');
                $timeStart = time();
            }
//            $symbol = Token::where('id', $threeToken->token_id)->first(['symbol'])->toArray();
//                $tokenSymbol .= $symbol['symbol'] . ",";
            $token_info = Token::where('id', $item->token_id)->first(['token', 'token_decimal', 'price_usd'])->toArray();
            $holder_address = Whale::where('id', $item->holder_id)->first(['holder'])->toArray();
//            echo "<pre>";
//        print_r($token_info);
//        print_r($holder_address);
//        echo "</pre>";die;
//            $tokenAddress = Holder::find($item->token_id)->token->token;
//            $holderAddress = Holder::find($item->holder_id)->whale->holder;
//            $tokenDecimal = Holder::find($item->token_id)->token->token_decimal;
//            $priceUsd = Holder::find($item->token_id)->token->price_usd;

            $output = [];
            if (_iscurl()) {
                $url = "https://api.etherscan.io/api?module=account&action=tokenbalance&contractaddress=" . $token_info['token']
                    . "&address=" . $holder_address['holder'] . "&tag=latest&apikey=" . ETHERSCAN_API_KEY;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $output = curl_exec($ch);
                curl_close($ch);
                $output = json_decode($output, true);
            }

            if ($counter % 5 == 0) {
                if (microtime(true) - $time_start - 1 < 0) {
                    usleep(500000);
                    \Log::info('Balances OVER 5 REQUESTS PER MINUTE - ' . \Carbon\Carbon::now());
                }
                $time_start = microtime(true);
                $counter = 0;
                $counter2++;
            }
            $counter++;

            if ($output['message'] == 'OK' && isset($output['result']) && !empty($output['result'])) {
                $balance = $token_info['price_usd'] * (double)$output['result'] * 1 / (10 ** $token_info['token_decimal']);
                Holder::where('id', $item->id)->update([
                    'balance_current' => round($balance, 2),
                ]);
                echo 'WHALEBalances ID = ' . $item->id . " - " . date('y-m-d H:i:s') . "<br>";
//                    \Log::info('WHALEBalances ID = ' . $item->id . " - " . \Carbon\Carbon::now());
            }
        }
        \Log::info('WHALEBalances End - ' . \Carbon\Carbon::now());
        $timeEnd = date('m/d/y g:i:s a');
        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
        echo "TIME End   =   " . $timeEnd . "<br/>";
        die;

        $whales = Whale::all();
        foreach ($whales as $item) {
//            $threeTokens = Holder::all()->where('holder_id', $item->id)->sortByDesc('balance_current')->slice(0, 3);
            $threeTokens = Holder::all('id', 'token_id', 'holder_id', 'balance_current')->where('holder_id', $item->id)->sortByDesc('balance_current')->slice(0, 3);
//            echo "<pre>";
//        print_r($threeTokens);
//        echo "</pre>";die;
            $tokenSymbol = '';
            foreach ($threeTokens as $threeToken) {
                $symbol = Token::where('id', $threeToken->token_id)->first(['symbol'])->toArray();
                $tokenSymbol .= $symbol['symbol'] . ",";
            }

            $tokenSymbol = substr($tokenSymbol, 0, strlen($tokenSymbol) - 1);

            Whale::where('id', $item->id)->update([
                'top_holdings' => $tokenSymbol
            ]);

            echo "ID = " . $item->id . " - SYMBOLS = " . $tokenSymbol . "<br/>";

//            echo "<pre>";
//        print_r($tokenSymbol);
//        echo "</pre>";
//        die;
//            $hh->
        }
        $timeEnd = date('m/d/y g:i:s a');
        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
        echo "TIME End   =   " . $timeEnd . "<br/>";
        die;

        $whales = Whale::all()->sortByDesc('balance_current')->toArray();
        echo "<pre>";
        print_r($whales);
        echo "</pre>";
        die;
        $output = [];
//        if (_iscurl()) {
//                $url = "https://api.ethplorer.io/getAddressHistory/" . "0x9d72768978439c13099E471F538b0B93542f59c1" . "?apiKey=" . "" . "&type=transfer&limit=1";
        $url = "https://api.ethplorer.io/getAddressHistory/0x9d72768978439c13099E471F538b0B93542f59c1?apiKey=freekey&type=transfer&limit=1";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output, true);
//        }
        if (isset($output['operations'][0]['timestamp'])) {
            echo "<pre>";
            var_dump($output['operations'][0]['timestamp']);
            echo "</pre>";
            echo "TIME";
        } elseif (empty($output['operations'])) {
            echo "<pre>";
            var_dump($output['operations']);
            echo "</pre>";
            echo "EMPTY_OPER";
        } elseif (isset($output['operations'])) {
            echo "<pre>";
            var_dump($output['operations']);
            echo "</pre>";
            echo "OPER";
        } elseif (isset($output)) {
            echo "<pre>";
            var_dump($output);
            echo "</pre>";
            echo "OUTP";
        } elseif (empty($output)) {
            echo "<pre>";
            var_dump($output);
            echo "</pre>";
            echo "EMTY";
        } else {
            echo "FALSE";
        }
        die;
        phpinfo();
        die;
        $timeBegin = date('m/d/y g:i:s a');
        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
        die;
//        $whale = Whale::getLastActive(1);
//        echo $whale;
//        die;
//
//
//        $whale = Whale::all();
//        $whale = $whale->find(4);
//        echo "<pre>";
//        print_r($whale->balance_start);
//        echo "</pre>";
//        die;
//
//$timeBegin = date('m/d/y g:i:s a');
//        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
//        $whales = Whale::all();
//        foreach ($whales as $item) {
//            $threeTokens = Holder::all('id', 'token_id', 'holder_id', 'balance_current')->where('holder_id', $item->id)->sortByDesc('balance_current')->slice(0, 3);
//            $tokenSymbol = '';
//            foreach ($threeTokens as $threeToken){
//                $tokenSymbol .= Holder::find($threeToken->token_id)->token->symbol.",";
//            }
//            $tokenSymbol = substr($tokenSymbol, 0, strlen($tokenSymbol) - 1);
//            Whale::where('id', $item->id)->update([
//                'top_holdings' => $tokenSymbol
//            ]);
//            echo "ID = ". $item->id. " - SYMBOLS = " .$tokenSymbol."<br/>";
////            echo "<pre>";
////        print_r($tokenSymbol);
////        echo "</pre>";
////        die;
////            $hh->
//        }
//        $timeEnd = date('m/d/y g:i:s a');
//        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
//        echo "TIME End   =   " . $timeEnd . "<br/>";
////        die;
////        $holderData = Holder::all('id', 'token_id', 'holder_id');
////        foreach ($holderData as $item) {
////            $tokenAddress = Holder::find($item->token_id)->token->token;
////            $holderAddress = Holder::find($item->holder_id)->whale->holder;
////            $tokenDecimal = Holder::find($item->token_id)->token->token_decimal;
////            $priceUsd = Holder::find($item->token_id)->token->price_usd;
////        }
//        die;
////        $b = new Balance();
////        $b->holder_id = 12;
////        $b->balance = 5545;
////        $b->date = date("Y-m-d");
////        $b->save();
////        die;
////        $timeBegin = date('m/d/y g:i:s a');
////        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
////        $a = '{"address":"0x22b84d5ffea8b801c0422afe752377a64aa738c2","ETH":{"balance":48295.019765355,"totalIn":0,"totalOut":0},"countTxs":466999,"tokens":[{"tokenInfo":{"address":"0x725803315519de78d232265a8f1040f054e70b98","name":"DAO.Casino","decimals":18,"symbol":"BET","totalSupply":"167270820687908580232679286","owner":"0x01dbb419d66be0d389fab88064493f1d698dc27a","lastUpdated":1524496717,"totalIn":1.8722900584431e+26,"totalOut":1.8722900584431e+26,"issuancesCount":0,"holdersCount":5761,"description":"","price":{"rate":"0.0267801","diff":0.82,"diff7d":-5.73,"ts":"1524505155","marketCapUsd":"4479529.0","availableSupply":"167270821.0","volume24h":"6578.48","currency":"USD"}},"balance":1.1895297173165e+23,"totalIn":1.1895297173165e+23,"totalOut":0},{"tokenInfo":{"address":"0x621d78f2ef2fd937bfca696cabaf9a779f59b3ed","name":"DCORP","decimals":2,"symbol":"DRP","totalSupply":"809400247","owner":"0x01d5d0108589f3c52fcce6e65503bb6515e66698","lastUpdated":1524505375,"totalIn":361826234,"totalOut":361826234,"issuancesCount":0,"holdersCount":3545,"price":{"rate":"0.642134","diff":6.77,"diff7d":-6.18,"ts":"1524505158","marketCapUsd":"5197434.0","availableSupply":"8094002.0","volume24h":"1611.6","currency":"USD"}},"balance":563433,"totalIn":20828,"totalOut":0},{"tokenInfo":{"address":"0xa54ddc7b3cce7fc8b1e3fa0256d0db80d2c10970","name":"NEVERDIE","decimals":18,"symbol":"NDC","totalSupply":"400000000000000000000000000","owner":"0x","lastUpdated":1524497329,"totalIn":4.4676120680189e+26,"totalOut":4.4676120680189e+26,"issuancesCount":0,"holdersCount":12912,"image":"https://ethplorer.io/images/neverdie.png","description":"The NEVERDIE Coin is intended to sit in your Avatar Wallet in Blockchain based applications to be utilized for specific purposes such as Avatar Revival in the case of Death. The API allows developers to create an endless variety of applications for the NEVERDIE Coin.\n\nhttps://neverdie.com","price":{"rate":"0.0242296","diff":-2.8,"diff7d":-22.94,"ts":"1524505154","marketCapUsd":"993515.0","availableSupply":"41004200.0","volume24h":"218.045","currency":"USD"}},"balance":1.02444e+23,"totalIn":1.8243e+22,"totalOut":0},{"tokenInfo":{"address":"0x2ad921a8ec68bffc134cc0ee8ff760bc5864a99a","name":"LOVE","decimals":"2","symbol":"LV","totalSupply":"1000000000000","owner":"0x","lastUpdated":1524019063,"totalIn":358903,"totalOut":358903,"issuancesCount":0,"holdersCount":1162,"price":false},"balance":800,"totalIn":700,"totalOut":0},{"tokenInfo":{"address":"0x4ecdc839fcfe8d80c69877acf692e6a65b364b38","name":"W3C","decimals":"18","symbol":"W3C","totalSupply":"200000000000000000000000000000","owner":"0x","lastUpdated":1524505042,"totalIn":3.9668744439147e+25,"totalOut":3.9668744439147e+25,"issuancesCount":0,"holdersCount":16708,"price":false},"balance":1.38239766e+24,"totalIn":1.30161682e+24,"totalOut":0},{"tokenInfo":{"address":"0x2498aa67cd08ac321085734a8570137ec2001731","name":"VR Silver","decimals":"18","symbol":"VRS","totalSupply":"1000000000000000000000000000","owner":"0x","lastUpdated":1524154412,"totalIn":1.01134548e+27,"totalOut":1.01134548e+27,"issuancesCount":0,"holdersCount":673,"price":false},"balance":9.5e+20,"totalIn":1.5e+20,"totalOut":0},{"tokenInfo":{"address":"0xb6f09f221d7a93390235d427c72fffc4f3856a9f","name":"VR Gold","decimals":"18","symbol":"VRG","totalSupply":"100000000000000000000000000","owner":"0x","lastUpdated":1524154119,"totalIn":1.0128972725e+26,"totalOut":1.0128972725e+26,"issuancesCount":0,"holdersCount":717,"price":false},"balance":1.51e+21,"totalIn":1.5e+19,"totalOut":0},{"tokenInfo":{"address":"0x31fdf78bd3b46925e185c814ed73c53295b42081","name":"Dao.Casino","decimals":"18","symbol":"BET","totalSupply":"130344807286474316907513786","owner":"0x000001f568875f378bf6d170b790967fe429c81a","lastUpdated":1524457439,"totalIn":1.3058567869892e+26,"totalOut":1.3058567869892e+26,"issuancesCount":0,"holdersCount":2011,"price":false},"balance":1.1895297173165e+23,"totalIn":1.1895297173165e+23,"totalOut":0},{"tokenInfo":{"address":"0x8aa33a7899fcc8ea5fbe6a608a109c3893a1b8b2","name":"Dao.Casino","decimals":18,"symbol":"BET","totalSupply":"167270820687908580232679286","owner":"0x000001f568875f378bf6d170b790967fe429c81a","lastUpdated":1524482201,"totalIn":3.9688548334622e+26,"totalOut":3.9688548334622e+26,"issuancesCount":0,"holdersCount":4539,"image":"https://ethplorer.io/images/bet.png","description":"DAO.Casino internal token called BET is a ERC20 token. It is used as ingame currency for all the game contracts integrated with the protocol and to power DAO.Casino reward system. Ingame currency and a reward system are complimentary. We can assume that a reward system that allows people to collect tokens that can be used in games provides a better incentive mechanism than a purely reputational points system.\n\nhttps://dao.casino/\nhttps://www.facebook.com/Dao.casino/\nhttps://www.reddit.com/r/DaoCasino/\nhttps://medium.com/@dao.casino/","price":false},"balance":1.1895297173165e+23,"totalIn":1.1895297173165e+23,"totalOut":0},{"tokenInfo":{"address":"0x9500a651831ba86b38c761ce8b496abdccba7a9b","name":"I want you! &lt;3","decimals":0,"symbol":"ShineCoin","totalSupply":"0","owner":"0x","lastUpdated":1511343951,"issuancesCount":0,"holdersCount":118,"price":false},"balance":1,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xfb6ee7ca12e4008001eaef2ae4a027350b36ed60","name":"TANDER","decimals":6,"symbol":"TDR","totalSupply":"50000000000000","owner":"0x004bb281b1c607e64a25e9465b86f1b067c0cb7f","lastUpdated":1522253642,"totalIn":1001000000000,"totalOut":1001000000000,"issuancesCount":0,"holdersCount":30,"description":"Tander, token for accounting, savings, investment","price":false},"balance":1000000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x8cfaf62d0203ae2cc52cbafa3e1a4bdef8bf1a60","name":"Link Platform","decimals":"18","symbol":"LNK","totalSupply":"60000000000000000000000","owner":"0x7209ba29ba9b163c655b12cbf3c33369b77e1742","lastUpdated":1524240923,"totalIn":3.8001831631348e+20,"totalOut":3.8001831631348e+20,"issuancesCount":0,"holdersCount":509,"price":false},"balance":2.9163036205268e+20,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x983f6d60db79ea8ca4eb9968c6aff8cfa04b3c63","name":"SONM Token","decimals":18,"symbol":"SNM","totalSupply":"444000000000000000000000000","owner":"0x","lastUpdated":1524505478,"totalIn":1.4871853774117e+27,"totalOut":1.4871853774117e+27,"issuancesCount":0,"holdersCount":17218,"description":"https://ico.sonm.io/","price":{"rate":"0.206452","diff":-0.71,"diff7d":30.86,"ts":"1524505152","marketCapUsd":"74240139.0","availableSupply":"359600000.0","volume24h":"1690970.0","currency":"USD"}},"balance":1.6797930774607e+23,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x12480e24eb5bec1a9d4369cab6a80cad3c0a377a","name":"Substratum","decimals":"2","symbol":"SUB","totalSupply":"59200000000","owner":"0xaf518d65f84e4695a4da0450ec02c1248f56b668","lastUpdated":1524505417,"totalIn":199563567730,"totalOut":199563567730,"issuancesCount":0,"holdersCount":30530,"price":{"rate":"0.737126","diff":1.59,"diff7d":28.29,"ts":"1524505155","marketCapUsd":"282334738.0","availableSupply":"383021000.0","volume24h":"9884730.0","currency":"USD"}},"balance":730405,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x9501bfc48897dceeadf73113ef635d2ff7ee4b97","name":"easyMINE Token","decimals":18,"symbol":"EMT","totalSupply":"10729622510517443314800192","owner":"0x","lastUpdated":1524503377,"issuancesCount":0,"holdersCount":3204,"price":false},"balance":1.5964285714286e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x5c543e7ae0a1104f78406c340e9c64fd9fce5170","name":"vSlice","decimals":18,"symbol":"VSL","totalSupply":"33390496033375600026660590","owner":"0x","lastUpdated":1524451169,"totalIn":1.0142478014138e+51,"totalOut":6.7276922920278e+25,"issuancesCount":1373,"holdersCount":866,"description":"An Ethereum Gaming Platform Token\n\nhttp://www.vslice.io","price":{"rate":"0.10795","diff":5.07,"diff7d":7.76,"ts":"1524505149","marketCapUsd":"3604504.0","availableSupply":"33390496.0","volume24h":"6524.8","currency":"USD"}},"balance":2.48132252609e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x96a65609a7b84e8842732deb08f56c3e21ac6f8a","name":"Centra","decimals":18,"symbol":"CTR","totalSupply":"99999999500000000000000000","owner":"0x387792f7d2aa6e7fa1312261cf36f5f6f6b97c00","lastUpdated":1524505400,"totalIn":5.5729035139005e+26,"totalOut":5.5729035139005e+26,"issuancesCount":0,"holdersCount":17840,"image":"https://ethplorer.io/images/centra.png","description":"Centra has designed the worlds first Multi-Blockchain Debit Card that connects to a Smart & Insured Wallet. Spend your cryptocurrencies in real time anywhere that accepts Visa or Mastercard.\n\nhttps://www.centra.tech\nhttps://www.facebook.com/CentraCard\nhttps://twitter.com/Centra_Card","price":{"rate":"0.0185444","diff":-9.51,"diff7d":1.11,"ts":"1524505154","marketCapUsd":"1261019.0","availableSupply":"68000000.0","volume24h":"31957.9","currency":"USD"}},"balance":1.5e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x4e0603e2a27a30480e5e3a4fe548e29ef12f64be","name":"Credo Token","decimals":"18","symbol":"CREDO","totalSupply":"1374729257228600000000000000","owner":"0x","lastUpdated":1524493554,"totalIn":1.0461992904681e+27,"totalOut":1.0461992904681e+27,"issuancesCount":0,"holdersCount":89887,"price":{"rate":"0.0220947","diff":5.95,"diff7d":40.51,"ts":"1524505156","marketCapUsd":"12149692.0","availableSupply":"549891703.0","volume24h":"4142.0","currency":"USD"}},"balance":1.0e+18,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x5fb53674184110b32e862695d85e462918d17c74","name":"gamecash","decimals":"8","symbol":"GCSH","totalSupply":"9999999900000000","owner":"0x","lastUpdated":1521795173,"issuancesCount":0,"holdersCount":29,"price":false},"balance":126000000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x94d6b4fb35fb08cb34aa716ab40049ec88002079","name":"Cryptonex (CNX) - Global Blockchain Acquiring","decimals":8,"symbol":"CNX","totalSupply":"100000001000000000","owner":"0xd7d0f507e4ecb367f435939fee0605413cacddb6","lastUpdated":1504636057,"issuancesCount":0,"holdersCount":42950,"description":"https://cryptonex.org","price":false},"balance":100000000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xae080d87b45b029ac4999226afcf653d363949ac","name":"Berith","decimals":"8","symbol":"BRT","totalSupply":"1000000000000000000","owner":"0x","lastUpdated":1524403279,"issuancesCount":0,"holdersCount":161,"price":false},"balance":10070951604000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x437cf0bf53634e3dfa5e3eaff3104004d50fb532","name":"BETNetwork","decimals":"4","symbol":"BTN","totalSupply":"0","owner":"0x","lastUpdated":1524325718,"issuancesCount":0,"holdersCount":99,"price":false},"balance":81332000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xd26114cd6ee289accf82350c8d8487fedb8a0c07","name":"OMGToken","decimals":18,"symbol":"OMG","totalSupply":"140245398245132780789239631","owner":"0x000000000000000000000000000000000000dead","lastUpdated":1524505500,"totalIn":6.9789611135704e+26,"totalOut":6.9789611135704e+26,"issuancesCount":0,"holdersCount":602602,"price":{"rate":"15.6583","diff":0.24,"diff7d":5.76,"ts":"1524505154","marketCapUsd":"1597812888.0","availableSupply":"102042552.0","volume24h":"56474600.0","currency":"USD"}},"balance":2.9324086892089e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x0abefb7611cb3a01ea3fad85f33c3c934f8e2cf4","name":"FARAD","decimals":"18","symbol":"FRD","totalSupply":"1600000000000000000000000000","owner":"0x1354c3c7218e97dbc336cdebf2516ef140abc7ef","lastUpdated":1524499705,"issuancesCount":0,"holdersCount":4449,"price":{"rate":"0.0202276","diff":-0.24,"diff7d":-16.91,"ts":"1524505158","marketCapUsd":"2742348.0","availableSupply":"135574582.0","volume24h":"738.419","currency":"USD"}},"balance":3.4540399126489e+23,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x22467d25320dda37f254b0f56309c2bce673ba62","name":"nametoken","decimals":18,"symbol":"NAT","totalSupply":"98000000","owner":"0x3a9a0d0f1df94c5e240b446455f57c1a6503884c","lastUpdated":1524493098,"issuancesCount":0,"holdersCount":554,"description":"Nametoken will revolutionize the domain industry by creating the first decentralized domain eco system\n\nhttps://www.nametoken.io","price":false},"balance":310,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x949bed886c739f1a3273629b3320db0c5024c719","name":"AMIS","decimals":"9","symbol":"AMIS","totalSupply":"20000000000000000","owner":"0x","lastUpdated":1524307119,"totalIn":9.7548836738135e+16,"totalOut":9.7548836738135e+16,"issuancesCount":0,"holdersCount":399,"image":"https://ethplorer.io/images/amis.png","description":"The AMIS designate Asset Management Instruments acting as one-stop shop Multi-dimensional, multi-purposes fast moving, versatile transactional vehicles running on the ethereum blockchain natively. It can act as commodity index in Agricultural Markets, but also allows to be used in other market segments such as utilities in the Advanced Metering Infrastructures space, Automotive, Medical, Insurance, Telecom & CSP, Asset Management Information System in the aerospace domain but could also reveal its potential in Astrophysics, AI, IS, IAM, Billing Mediation and many others fields of applications for todays digital sharing economy.\n\nhttp://erc20-amis.amisolution.net\nhttps://www.reddit.com/r/amis\nhttps://www.twitter.com/AMIS_ERC20","price":{"currency":"USD"}},"balance":1000000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x717f0cc11366818ba350aba9e7c7515ded8f8474","name":"AiO","decimals":6,"symbol":"AiO","totalSupply":"9174676000000","owner":"0x006365ebf91cd8446ee474c955234dd8f5054a7d","lastUpdated":1524059251,"issuancesCount":0,"holdersCount":774,"description":"Artificial Intelligence Oracle","price":false},"balance":1995000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xf629cbd94d3791c9250152bd8dfbdf380e2a3b9c","name":"Enjin Coin","decimals":"18","symbol":"ENJ","totalSupply":"1000000000000000000000000000","owner":"0xde63aef60307655405835da74ba02ce4db1a42fb","lastUpdated":1524505139,"totalIn":7.2920093243897e+26,"totalOut":7.2920093243897e+26,"issuancesCount":0,"holdersCount":25194,"price":{"rate":"0.144199","diff":3.26,"diff7d":26.59,"ts":"1524505158","marketCapUsd":"109042207.0","availableSupply":"756192535.0","volume24h":"12221100.0","currency":"USD"}},"balance":1.2010028284192e+22,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x83cee9e086a77e492ee0bb93c2b0437ad6fdeccc","name":"Goldmint MNT Prelaunch Token","decimals":"18","symbol":"MNTP","totalSupply":"10000000000000000000000000","owner":"0x","lastUpdated":1524505365,"totalIn":3.1497400235287e+24,"totalOut":3.1497400235287e+24,"issuancesCount":0,"holdersCount":5140,"price":false},"balance":1.0e+17,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x72adadb447784dd7ab1f472467750fc485e4cb2d","name":"Worldcore","decimals":"6","symbol":"WRC","totalSupply":"245209299000000","owner":"0x","lastUpdated":1524503677,"issuancesCount":0,"holdersCount":27685,"price":{"rate":"0.0619601","diff":13.8,"diff7d":60.57,"ts":"1524505160","marketCapUsd":"10849790.0","availableSupply":"175109299.0","volume24h":"415834.0","currency":"USD"}},"balance":1000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x27dce1ec4d3f72c3e457cc50354f1f975ddef488","name":"AirToken","decimals":"8","symbol":"AIR","totalSupply":"149149255800000000","owner":"0x762c0f710cddbae48d121f87af45b392cec2c815","lastUpdated":1524499625,"totalIn":2.4352034009133e+17,"totalOut":2.4352034009133e+17,"issuancesCount":0,"holdersCount":3962,"price":{"rate":"0.00910719","diff":2.16,"diff7d":17.37,"ts":"1524505157","marketCapUsd":"9562550.0","availableSupply":"1050000000.0","volume24h":"206737.0","currency":"USD"}},"balance":7500000000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xc42209accc14029c1012fb5680d95fbd6036e2a0","name":"PayPie","decimals":"18","symbol":"PPP","totalSupply":"165000000000000000000000000","owner":"0xf821fd99bca2111327b6a411c90be49dcf78ce0f","lastUpdated":1524504914,"totalIn":1.6017308080637e+26,"totalOut":1.6017308080637e+26,"issuancesCount":0,"holdersCount":14509,"price":{"rate":"1.27435","diff":0.12,"diff7d":55.46,"ts":"1524505158","marketCapUsd":"105133875.0","availableSupply":"82500000.0","volume24h":"90054.2","currency":"USD"}},"balance":1.908e+22,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xd566fa4a696eac66f749f7fe999d6673fee2026c","name":"CHEX Token","decimals":"18","symbol":"CHX","totalSupply":"3999999999999999916396483","owner":"0x","lastUpdated":1524403607,"issuancesCount":0,"holdersCount":139,"price":false},"balance":3.1227e+22,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xf8e386eda857484f5a12e4b5daa9984e06e73705","name":"Indorse Token","decimals":18,"symbol":"IND","totalSupply":"170622047000000000000000000","owner":"0x0006b2c810f77f28aa30eeb6508af12730b87370","lastUpdated":1524497200,"totalIn":3.4524077692764e+26,"totalOut":3.4524077692764e+26,"issuancesCount":0,"holdersCount":53593,"price":{"rate":"0.04836","diff":9.98,"diff7d":36.69,"ts":"1524505155","marketCapUsd":"2249136.0","availableSupply":"46508192.0","volume24h":"227750.0","currency":"USD"}},"balance":1.0e+18,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x9b11efcaaa1890f6ee52c6bb7cf8153ac5d74139","name":"Attention Token of Media","decimals":"8","symbol":"ATM","totalSupply":"1000000000000000000","owner":"0x02fc5a71e8917c23c66f668b42f3544cde88f1bc","lastUpdated":1524503307,"totalIn":9.9498513976835e+17,"totalOut":9.9498513976835e+17,"issuancesCount":0,"holdersCount":27486,"price":{"rate":"0.00419715","diff":-7.01,"diff7d":7.36,"ts":"1524505156","marketCapUsd":"19559917.0","availableSupply":"4660285460.0","volume24h":"185495.0","currency":"USD"}},"balance":9900000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xab95e915c123fded5bdfb6325e35ef5515f1ea69","name":"XENON","decimals":18,"symbol":"XNN","totalSupply":"1000000000000000000000000000","owner":"0x","lastUpdated":1524505291,"issuancesCount":0,"holdersCount":747841,"description":"XenonNetwork (http://xenon.network/), an enterprise-scale blockchain launching in July 2018 begins a massive distribution of their native Xenon (XNN) ERC-20 compatible tokens to over 400,000 active ethereum addresses at the beginning of October. In addition to this, a similar distribution to bitcoin holders will occur in November, followed by a proof-of-individuality public token distribution from November through to June 2018.\n\nhttp://xenon.network","price":{"rate":"0.0261025","diff":80.6,"diff7d":61.54,"ts":"1524505157","marketCapUsd":"7830750.0","availableSupply":"300000000.0","volume24h":"292.989","currency":"USD"}},"balance":3.5802023664582e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xdb455c71c1bc2de4e80ca451184041ef32054001","name":"Jury.Online Token","decimals":"18","symbol":"JOT","totalSupply":"3515277000000000000000000","owner":"0x160e529055d084add9634fe1c2059109c8ce044e","lastUpdated":1521036896,"issuancesCount":0,"holdersCount":77258,"price":false},"balance":1.0e+18,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x4d829f8c92a6691c56300d020c9e0db984cfe2ba","name":"CoinCrowd","decimals":"18","symbol":"XCC","totalSupply":"78750709292959827150083646","owner":"0x6863b16d476d1975e3f9c1aa494148678a1e13c1","lastUpdated":1521925430,"issuancesCount":0,"holdersCount":32789,"price":false},"balance":1.0e+18,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x0cf0ee63788a0849fe5297f3407f701e122cc023","name":"DATAcoin","decimals":18,"symbol":"DATA","totalSupply":"987154514000000000000000000","owner":"0x1bb7804d12fa4f70ab63d0bbe8cb0b1992694338","lastUpdated":1524505459,"totalIn":2.165e+26,"totalOut":2.165e+26,"issuancesCount":0,"holdersCount":434532,"price":{"rate":"0.112269","diff":4.36,"diff7d":31.89,"ts":"1524505157","marketCapUsd":"76023460.0","availableSupply":"677154514.0","volume24h":"679397.0","currency":"USD"}},"balance":2.0907680194784e+22,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x983877018633c0940b183cd38d1b58bee34f7301","name":"Deep Gold","decimals":"8","symbol":"DEEP","totalSupply":"10000000000000000","owner":"0x","lastUpdated":1524255425,"issuancesCount":0,"holdersCount":1326,"price":false},"balance":1600000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x78b7fada55a64dd895d8c8c35779dd8b67fa8a05","name":"ATLANT Token","decimals":"18","symbol":"ATL","totalSupply":"54175040677754953604099153","owner":"0x","lastUpdated":1524504842,"totalIn":1.1118415699633e+25,"totalOut":1.1118415699633e+25,"issuancesCount":0,"holdersCount":6265,"price":{"rate":"0.233142","diff":-2.5,"diff7d":25.06,"ts":"1524505158","marketCapUsd":"8754925.0","availableSupply":"37551901.0","volume24h":"46258.6","currency":"USD"}},"balance":3.0234324245e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x24692791bc444c5cd0b81e3cbcaba4b04acd1f3b","name":"UnikoinGold","decimals":"18","symbol":"UKG","totalSupply":"1000000000000000000000000000","owner":"0x","lastUpdated":1524502913,"issuancesCount":0,"holdersCount":10469,"price":{"rate":"0.265671","diff":-0.79,"diff7d":35.07,"ts":"1524505158","marketCapUsd":"37465554.0","availableSupply":"141022371.0","volume24h":"2462210.0","currency":"USD"}},"balance":7.7489197643734e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x6ec8a24cabdc339a06a172f8223ea557055adaa5","name":"Genaro X","decimals":"9","symbol":"GNX","totalSupply":"675000000000000000","owner":"0x","lastUpdated":1524504940,"issuancesCount":0,"holdersCount":7974,"price":{"rate":"0.411568","diff":-10.49,"diff7d":6.9,"ts":"1524505159","marketCapUsd":"98854060.0","availableSupply":"240188888.0","volume24h":"5251310.0","currency":"USD"}},"balance":9140000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x3adfc4999f77d04c8341bac5f3a76f58dff5b37a","name":"Privatix","decimals":"8","symbol":"PRIX","totalSupply":"29993599999510","owner":"0xec203ddb210458df139d02a62baf49ca36a0b8d9","lastUpdated":1508287925,"issuancesCount":0,"holdersCount":1678,"price":{"rate":"3.17568","diff":-11.02,"diff7d":63.2,"ts":"1524505159","marketCapUsd":"3755774.0","availableSupply":"1182668.0","volume24h":"12098.7","currency":"USD"}},"balance":849434565,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x0142c3b2fc51819b5af5dfc4aa52df9722790851","name":"Paycentos Token","decimals":"18","symbol":"PYN","totalSupply":"350000000000000000000000000","owner":"0xe9da5e8fb19dedc9c86a0d0bf1678122d4c0b134","lastUpdated":1524499845,"issuancesCount":0,"holdersCount":3230,"price":false},"balance":1.02659536e+22,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x6cee948c9d593c58cba5dfa70482444899d1341c","name":"SPECTRE SUBSCRIBER TOKEN","decimals":"18","symbol":"SXS","totalSupply":"91627765897795994966351100","owner":"0x09a568fd510741ae68e315d6d001a8d4b1682256","lastUpdated":1524137820,"issuancesCount":0,"holdersCount":4310,"price":false},"balance":2.2216869577081e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x7d3e7d41da367b4fdce7cbe06502b13294deb758","name":"SSS","decimals":"8","symbol":"SSS","totalSupply":"1000000000000000000","owner":"0x","lastUpdated":1524500459,"issuancesCount":0,"holdersCount":13431,"price":{"rate":"0.00150924","diff":-15.23,"diff7d":25.67,"ts":"1524505158","marketCapUsd":"3462232.0","availableSupply":"2294023561.0","volume24h":"12694.7","currency":"USD"}},"balance":1000000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x68e14bb5a45b9681327e16e528084b9d962c1a39","name":"BitClave - Consumer Activity Token","decimals":"18","symbol":"CAT","totalSupply":"1686904264000000000000000000","owner":"0x9f89388141c632c4c6f36d1060d5f50604ee3abc","lastUpdated":1524497329,"issuancesCount":0,"holdersCount":94912,"price":false},"balance":5.0e+18,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x52903256dd18d85c2dc4a6c999907c9793ea61e3","name":"INS Promo","decimals":0,"symbol":"INSP","totalSupply":"1801086","owner":"0x","lastUpdated":1514203156,"issuancesCount":0,"holdersCount":855607,"price":false},"balance":777,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xa7a05cf8d6d8e4e73db47fe4de4cbd5b63d15cfa","name":"KingCash","decimals":"18","symbol":"KCP","totalSupply":"20012856966664577445739940","owner":"0xd0af9888cfb401083ad5944c6a046c831e7d8b20","lastUpdated":1524446313,"issuancesCount":0,"holdersCount":792,"price":false},"balance":2.4579740913e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xf3e014fe81267870624132ef3a646b8e83853a96","name":"VIN","decimals":"18","symbol":"VIN","totalSupply":"1000000000000000000000000000","owner":"0x3c014997175d1354888d665c9eb1a5c8d46cbd36","lastUpdated":1524493322,"issuancesCount":0,"holdersCount":478308,"price":false},"balance":7.77e+18,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x05c7065d644096a4e4c3fe24af86e36de021074b","name":"LendConnect Token","decimals":"18","symbol":"LCT","totalSupply":"6500000000000000000000000","owner":"0x","lastUpdated":1524503919,"issuancesCount":0,"holdersCount":1491,"price":{"rate":"1.05052","diff":929.87,"diff7d":1971.79,"ts":"1524505161","marketCapUsd":"2270236.0","availableSupply":"2161059.0","volume24h":"289.9","currency":"USD"}},"balance":1.4084536385889e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xc78593c17482ea5de44fdd84896ffd903972878e","name":"EtherBB","decimals":"9","symbol":"BB","totalSupply":"1000000000000000000","owner":"0x","lastUpdated":1524316798,"totalIn":7.44282423505e+17,"totalOut":7.44282423505e+17,"issuancesCount":0,"holdersCount":4556,"price":false},"balance":2779000000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x86fa049857e0209aa7d9e616f7eb3b3b78ecfdb0","name":"EOS","decimals":18,"symbol":"EOS","totalSupply":"1000000000000000000000000000","owner":"0xd0a6e6c54dbc68db5db3a091b171a77407ff7ccf","lastUpdated":1524505477,"totalIn":2.5964967036019e+27,"totalOut":2.5964967036019e+27,"issuancesCount":0,"holdersCount":307589,"description":"https://eos.io/","price":{"rate":"11.4409","diff":-1.14,"diff7d":42.66,"ts":"1524505153","marketCapUsd":"9297974441.0","availableSupply":"812696068.0","volume24h":"689515000.0","currency":"USD"}},"balance":4.4322729560203e+25,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xb97048628db6b661d4c2aa833e95dbe1a905b280","name":"TenX Pay Token","decimals":18,"symbol":"PAY","totalSupply":"205218255948577763364408207","owner":"0x839ea851397707c4e44bc06f68a540d388298c3d","lastUpdated":1524505218,"totalIn":6.4029044329656e+26,"totalOut":6.4029044329656e+26,"issuancesCount":0,"holdersCount":57144,"description":"https://www.tenx.tech/","price":{"rate":"1.59308","diff":-1.45,"diff7d":34.53,"ts":"1524505152","marketCapUsd":"173580341.0","availableSupply":"108958961.0","volume24h":"5575440.0","currency":"USD"}},"balance":1.0e+18,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xd96b9fd7586d9ea24c950d24399be4fb65372fdd","name":"Bitcoin Silver","decimals":"18","symbol":"BTCS","totalSupply":"50000000000000000000000000","owner":"0x","lastUpdated":1524181664,"issuancesCount":0,"holdersCount":872,"price":{"currency":"USD"}},"balance":6.0e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x1c30fd44efe66921f6d1e050cd08d029f3be612a","name":"BactoCoin","decimals":"18","symbol":"BTNN","totalSupply":"4000000000000000000000000","owner":"0x","lastUpdated":1516015437,"issuancesCount":0,"holdersCount":66,"price":false},"balance":3.5866179715936e+20,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xe2fb6529ef566a080e6d23de0bd351311087d567","name":"Covesting","decimals":"18","symbol":"COV","totalSupply":"20000000000000000000000000","owner":"0xea15adb66dc92a4bbccc8bf32fd25e2e86a2a770","lastUpdated":1524503553,"issuancesCount":0,"holdersCount":12178,"price":{"rate":"1.10628","diff":8.23,"diff7d":45.38,"ts":"1524505161","marketCapUsd":"19359900.0","availableSupply":"17500000.0","volume24h":"328074.0","currency":"USD"}},"balance":1.86915019e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xd037a81b22e7f814bc6f87d50e5bd67d8c329fa2","name":"EMO tokens","decimals":"18","symbol":"EMO","totalSupply":"120000000000000000000000000","owner":"0x","lastUpdated":1524494439,"issuancesCount":0,"holdersCount":173661,"price":false},"balance":1.3569091111777e+23,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x3a1bda28adb5b0a812a7cf10a1950c920f79bcd3","name":"FLIP Token","decimals":"18","symbol":"FLP","totalSupply":"100000000000000000000000000","owner":"0x56c6cc96a6c118e4469889644e50e5463e458fc8","lastUpdated":1524500893,"issuancesCount":0,"holdersCount":3879,"price":false},"balance":1.458704945e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x70c4949d483166abefb2b4b2be78e338cd8b2c40","name":"Frikandel","decimals":0,"symbol":"FRIKANDEL","totalSupply":"500010","owner":"0x","lastUpdated":1523300847,"issuancesCount":0,"holdersCount":12343,"price":false},"balance":1,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x1234567461d3f8db7496581774bd869c83d51c93","name":"BitClave","decimals":"18","symbol":"CAT","totalSupply":"100000000000000000000","owner":"0x724e0388640f64ad536a7b469d916984b7b04a2b","lastUpdated":1514275973,"issuancesCount":0,"holdersCount":220207,"price":{"rate":"0.0328498","diff":-12.99,"diff7d":21.92,"ts":"1524505161","marketCapUsd":"16470706.0","availableSupply":"501394406.0","volume24h":"695493.0","currency":"USD"}},"balance":1.0e+18,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xcf006429f1702bd3c83e673c991ec8d545735e48","name":"0BTC","decimals":"8","symbol":"0BTC","totalSupply":"2100000000000000","owner":"0x","lastUpdated":1524041745,"issuancesCount":0,"holdersCount":747,"price":false},"balance":700000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x15e4cf1950ffa338ce5bc59456b3e579ed1bead3","name":"ALFA NTOK","decimals":"18","symbol":"\u0430NTOK","totalSupply":"20230000000000000000000000","owner":"0x","lastUpdated":1523965308,"issuancesCount":0,"holdersCount":6389,"price":false},"balance":2.018e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x9491b80e8971331f172cc3bce5840319e0233616","name":"CHANCE","decimals":"18","symbol":"CHE","totalSupply":"79000000000000000000000000","owner":"0x","lastUpdated":1521931291,"issuancesCount":0,"holdersCount":100,"price":false},"balance":1.0e+22,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xe2d82dc7da0e6f882e96846451f4fabcc8f90528","name":"Jesus Coin","decimals":"18","symbol":"JC","totalSupply":"20325184344101806593400010000","owner":"0x8a4be6f602879281319de0f0ea3f65e124247be5","lastUpdated":1524489029,"issuancesCount":0,"holdersCount":11290,"price":false},"balance":1.3068021516596e+23,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x5783862cef49094be4de1fe31280b2e33cf87416","name":"KredX Token","decimals":"4","symbol":"KRT","totalSupply":"4400000000000","owner":"0x","lastUpdated":1524318370,"issuancesCount":0,"holdersCount":1091,"price":false},"balance":1800000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x3839d8ba312751aa0248fed6a8bacb84308e20ed","name":"Bezop","decimals":"18","symbol":"Bez","totalSupply":"89267250000000000000000000","owner":"0x634f8c7c2ddd8671632624850c7c8f3e20622f5f","lastUpdated":1517813478496,"issuancesCount":0,"holdersCount":6363,"website":"https://bezop.io","price":{"rate":"0.0786313","diff":-7.66,"diff7d":-11.73,"ts":"1524505163","marketCapUsd":"3195158.0","availableSupply":"40634684.0","volume24h":"38443.7","currency":"USD"}},"balance":2.9174589216e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x1d462414fe14cf489c7a21cac78509f4bf8cd7c0","name":"CanYaCoin","decimals":"6","symbol":"CAN","totalSupply":"100000000000000","owner":"0x","lastUpdated":1524503161,"issuancesCount":0,"holdersCount":66587,"price":{"rate":"0.313066","diff":-1.44,"diff7d":26.35,"ts":"1524505160","marketCapUsd":"12840212.0","availableSupply":"41014393.0","volume24h":"158219.0","currency":"USD"}},"balance":1700000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x2c82c73d5b34aa015989462b2948cd616a37641f","name":"Spectre.ai U-Token","decimals":"18","symbol":"SXUT","totalSupply":"42980365026110428730391098","owner":"0x3538b1a62f11d1fa03b0482dbf18d78cf9e79f47","lastUpdated":1516348146605,"issuancesCount":0,"holdersCount":3892,"image":"https://ethplorer.io/images/spectre-utility.png","description":"Spectre utility token","website":"https://spectre.ai/","facebook":"spectrepage","twitter":"SpectreAI","reddit":"Spectre_ai","telegram":"https://t.me/joinchat/GjkGkw7IfwUVuPiWxctD4g","links":"Coinmarketcap: https://coinmarketcap.com/currencies/spectre-utility","price":{"rate":"0.290696","diff":0.21,"diff7d":16.18,"ts":"1524505161","marketCapUsd":"7130644.0","availableSupply":"24529558.0","volume24h":"672302.0","currency":"USD"}},"balance":2.2216869577081e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xd4db3f972d1e5f03ad2011cef04f1194fc271507","name":"OMEX","decimals":"18","symbol":"OMX","totalSupply":"500000000000000000000000000","owner":"0x3b26ba2b0ffd0bcb6fab0bbf185d7b0b78844c86","lastUpdated":1523975824,"issuancesCount":0,"holdersCount":117,"price":false},"balance":5.78e+22,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x85e076361cc813a908ff672f9bad1541474402b2","name":"Telcoin","decimals":"2","symbol":"TEL","totalSupply":"10000000000000","owner":"0x","lastUpdated":1524505478,"issuancesCount":0,"holdersCount":20729,"price":{"rate":"0.00255655","diff":-0.32,"diff7d":45.97,"ts":"1524505161","marketCapUsd":"73818714.0","availableSupply":"28874347659.0","volume24h":"1091210.0","currency":"USD"}},"balance":31313618,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xfd0df7b58bd53d1dd4835ecd69a703b4b26f7816","name":"MziToken","decimals":"18","symbol":"MZI","totalSupply":"250000000000000000000000000","owner":"0x8d2301e58406648b72f37366785a54e0f7e081ce","lastUpdated":1524501253,"issuancesCount":0,"holdersCount":2565,"price":false},"balance":5.65070535e+22,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x1d32ec4a0cf9e0050f1c86ac68509a4b8263329b","name":"AirCoin","decimals":"4","symbol":"ACC","totalSupply":"10000000000","owner":"0x","lastUpdated":1520949451,"issuancesCount":0,"holdersCount":2007,"price":false},"balance":11779409,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xf2eab3a2034d3f6b63734d2e08262040e3ff7b48","name":"CANDY","decimals":"18","symbol":"CANDY","totalSupply":"1000000000000000000000000000000","owner":"0x","lastUpdated":1524500771,"issuancesCount":0,"holdersCount":102636,"price":false},"balance":9.3782378e+26,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x9e77d5a1251b6f7d456722a6eac6d2d5980bd891","name":"BRAT RED","decimals":"8","symbol":"BRAT","totalSupply":"100000000000000000","owner":"0x","lastUpdated":1524484706,"totalIn":4.5805670179273e+15,"totalOut":4.5805670179273e+15,"issuancesCount":0,"holdersCount":13861,"price":{"rate":"0.000934666","diff":31.05,"diff7d":-1.77,"ts":"1524505155","marketCapUsd":"149547.0","availableSupply":"160000000.0","volume24h":"302.764","currency":"USD"}},"balance":914250239,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x0e935e976a47342a4aee5e32ecf2e7b59195e82f","name":"BMB","decimals":"18","symbol":"BMB","totalSupply":"5000000000000000000000000000","owner":"0x","lastUpdated":1524502306,"issuancesCount":0,"holdersCount":5994,"price":false},"balance":1.0e+20,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xad640689e6950b7453729a4686edb3fdfd754616","name":"CIChain","decimals":"18","symbol":"CIC","totalSupply":"3000000000000000000000000000","owner":"0xecb64b92ffb5179b47c87d2f105d6eeebb132c68","lastUpdated":1524505180,"issuancesCount":0,"holdersCount":31084,"price":false},"balance":3.0e+19,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x127cae460d6e8d039f1371f54548190efe73e756","name":"ShiftCashExtraBonus","decimals":0,"symbol":"SCB","totalSupply":"999999","owner":"0x","lastUpdated":1524426783,"issuancesCount":0,"holdersCount":54846,"price":false},"balance":1,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xba51ff3802aa3170ce7ac7ac001831ca3eb6eeea","name":"LinoToken","decimals":"18","symbol":"LINO","totalSupply":"10000000000000000000000000000","owner":"0x3f6a92cd483e41687b3587bbed7bbc6ad0c13669","lastUpdated":1524498998,"issuancesCount":0,"holdersCount":127409,"price":false},"balance":3.5e+19,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xac2bd14654bbf22f9d8f20c7b3a70e376d3436b4","name":"Kitten Coin","decimals":"8","symbol":"KITTEN","totalSupply":"40000000000000000","owner":"0x7223e76b2871a3c41202472fb2cec92ad76ee767","lastUpdated":1524498738,"issuancesCount":0,"holdersCount":9854,"price":false},"balance":100000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xcae8f93fb07e224563da0c8bbfc645c4fffb5dd4","name":"GameAsh","decimals":18,"symbol":"GASH","totalSupply":"500000000000000000000000000","owner":"0x","lastUpdated":1519458517,"issuancesCount":0,"holdersCount":111,"description":"The first game blockChain item trading platform.\n\nhttps://www.gash.in\nhttps://www.facebook.com/gameash/\n","price":false},"balance":2.0e+23,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x96d8fbf95f72f5abe9ef04f1ade94e1ba79be508","name":"itCoin\u00ae Black","decimals":"18","symbol":"ITC","totalSupply":"50000000000000000000000000","owner":"0x","lastUpdated":1520081044,"issuancesCount":0,"holdersCount":1948,"price":false},"balance":8.96e+20,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x589f396ec712676549ca1e79d462d5e0b5d0cdd8","name":"OwnToken","decimals":0,"symbol":"OWT","totalSupply":"100000000","owner":"0x","lastUpdated":1523887585,"issuancesCount":0,"holdersCount":718,"price":false},"balance":888,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x660b612ec57754d949ac1a09d0c2937a010dee05","name":"BitCAD","decimals":"6","symbol":"BCD","totalSupply":"100000000000000","owner":"0x","lastUpdated":1524414511,"totalIn":1.0215373705482e+14,"totalOut":1.0215373705482e+14,"issuancesCount":0,"holdersCount":831,"price":false},"balance":4577600,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x69b148395ce0015c13e36bffbad63f49ef874e03","name":"Data Token","decimals":"18","symbol":"DTA","totalSupply":"11499999999999999999990000000","owner":"0x","lastUpdated":1524505491,"issuancesCount":0,"holdersCount":167918,"price":{"rate":"0.0210051","diff":19.44,"diff7d":67.81,"ts":"1524505162","marketCapUsd":"97180141.0","availableSupply":"4626502186.0","volume24h":"18509700.0","currency":"USD"}},"balance":2.0e+19,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x254f91949d4f57b22392ba0278069cf5a9105f05","name":"CharterCoin","decimals":"6","symbol":"CAF","totalSupply":"1000000000000000","owner":"0x","lastUpdated":1524502849,"issuancesCount":0,"holdersCount":29663,"price":false},"balance":20000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x228ba514309ffdf03a81a205a6d040e429d6e80c","name":"Global Social Chain","decimals":"18","symbol":"GSC","totalSupply":"1000000000000000000000000000","owner":"0xb53e84d0bf5dffddba9b2cfa9210c13e204b80b7","lastUpdated":1524500033,"issuancesCount":0,"holdersCount":27139,"price":false},"balance":2.0e+19,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xffa93aacf49297d51e211817452839052fdfb961","name":"Distributed Credit Chain","decimals":"18","symbol":"DCC","totalSupply":"2598558957000000000000000000","owner":"0x6d6a7a16887aac4d678c14c907e4d37d6a3ee8fe","lastUpdated":1524499846,"issuancesCount":0,"holdersCount":54275,"price":false},"balance":5.0e+18,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xb344f584e002e2ad84a71a60e395ecd057060d19","name":"itCoin\u00ae Black","decimals":"18","symbol":"ITCB","totalSupply":"50000000000000000000000000","owner":"0x","lastUpdated":1524402706,"issuancesCount":0,"holdersCount":1981,"price":false},"balance":8.96e+20,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x6c22b815904165f3599f0a4a092d458966bd8024","name":"BPTN","decimals":"18","symbol":"BPTN","totalSupply":"2000000000000000000000000000","owner":"0x4c25384693757b9e1a71f0b8b8c43a377a125b20","lastUpdated":1524497053,"issuancesCount":0,"holdersCount":64992,"price":false},"balance":5.0e+18,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xbca9391497e7fe99a3febec5ef6e51f3ba9027c4","name":"OZEX","decimals":"18","symbol":"OZEX","totalSupply":"30000000000000000000000000000","owner":"0x6a4f0293de75c2a7f5feb0ae7ccaf4b6cd435d9f","lastUpdated":1524499341,"issuancesCount":0,"holdersCount":246,"price":false},"balance":1.03071e+23,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xc27c95350ecd634c80df89db0f10cd5c24b7b11f","name":"PixieCoin","decimals":"2","symbol":"PXC","totalSupply":"1000000000000","owner":"0x6eef1da4bd95204be2c8ee9b08796e0287cff4f4","lastUpdated":1524494401,"issuancesCount":0,"holdersCount":50328,"price":false},"balance":1000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x18cabd1e7db6c52406719cb72859ea2c2eea75d6","name":"GoGuides","decimals":"18","symbol":"eGO","totalSupply":"200000000000000000000000000","owner":"0x","lastUpdated":1523986595,"issuancesCount":0,"holdersCount":621,"price":false},"balance":1.0e+20,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x8a1a9477a710d470575b1da335e524b27e8091ab","name":"Coinnec","decimals":"18","symbol":"COI","totalSupply":"1000000000000000000000000000","owner":"0x","lastUpdated":1516877483853,"issuancesCount":0,"holdersCount":137,"description":"Blockchain based decentralized business platform","website":"http://coinnec.com","facebook":"coinnec","twitter":"coinnec","price":false},"balance":3.06e+20,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xcdb7ecfd3403eef3882c65b761ef9b5054890a47","name":"Hurify Token","decimals":"18","symbol":"HUR","totalSupply":"273125000000000000000000000","owner":"0x1e2d41c95b0fafa9c999ed0e9fc9c8ef1e8c98a9","lastUpdated":1524505387,"issuancesCount":0,"holdersCount":36779,"price":false},"balance":3.1625e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xc5d105e63711398af9bbff092d4b6769c82f793d","name":"BeautyChain","decimals":"18","symbol":"BEC","totalSupply":"7000000000000000000000000000","owner":"0x9c8d0efef2812041cef15e1c6f874f0db1f96ac6","lastUpdated":1524383745,"issuancesCount":0,"holdersCount":364872,"price":false},"balance":8.0e+18,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x8713d26637cf49e1b6b4a7ce57106aabc9325343","name":"CNN Token","decimals":"18","symbol":"CNN","totalSupply":"100000000000000000000000000000","owner":"0x","lastUpdated":1524505143,"issuancesCount":0,"holdersCount":89598,"price":false},"balance":8.8e+19,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x584b44853680ee34a0f337b712a8f66d816df151","name":"AI Doctor","decimals":"18","symbol":"AIDOC","totalSupply":"777775240795460511720000000","owner":"0x935b99663902fe96f5ae5233edd4e1663f0e6eaf","lastUpdated":1524505180,"issuancesCount":0,"holdersCount":33893,"price":{"rate":"0.0538869","diff":1.85,"diff7d":27.9,"ts":"1524505161","marketCapUsd":"23889722.0","availableSupply":"443330796.0","volume24h":"7446110.0","currency":"USD"}},"balance":1.7e+19,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xc56b13ebbcffa67cfb7979b900b736b3fb480d78","name":"Social Activity Token","decimals":"8","symbol":"SAT","totalSupply":"100000000000000000","owner":"0xaf4aa16734f0b665187ec80a2cbf13b12eadbfe6","lastUpdated":1524505486,"issuancesCount":0,"holdersCount":2059,"price":false},"balance":168083242263,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x4432e7ffd729442614d9233499000530e08e9d62","name":"TRA","decimals":"8","symbol":"TRA","totalSupply":"2000000000000000000","owner":"0x99a7fdf466b44301317453c4fbe213f5114519f5","lastUpdated":1524500612,"issuancesCount":0,"holdersCount":19254,"price":false},"balance":20000000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xf84df2db2c87dd650641f8904af71ebfc3dde0ea","name":"YouLive Coin","decimals":"18","symbol":"UC","totalSupply":"10000000000000000000000000000","owner":"0x007bc5792f66d17b64a5fb1d66bba368b45069dc","lastUpdated":1524502461,"issuancesCount":0,"holdersCount":17238,"price":false},"balance":5.8e+19,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xaa0bb10cec1fa372eb3abc17c933fc6ba863dd9e","name":"Hms Token","decimals":18,"symbol":"HMC","totalSupply":"1000000000000000000000000000","owner":"0x","lastUpdated":1524504798,"issuancesCount":0,"holdersCount":73629,"price":{"rate":"0.0447444","diff":-16.69,"diff7d":106.91,"ts":"1524505163","marketCapUsd":"18081212.0","availableSupply":"404100000.0","volume24h":"3380630.0","currency":"USD"}},"balance":1.0e+19,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x765b0aaef0ecac2bf23065f559e5ca3b81067993","name":"Ether Universe","decimals":0,"symbol":"ETU","totalSupply":"100000000000","owner":"0x86c7a6e638aa64c5005b76ce94ac9eb6b665ed25","lastUpdated":1524499767,"issuancesCount":0,"holdersCount":381,"price":false},"balance":10322984,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xf9826cac3eb6e2628a0b074fd8c85e7d6c9e84f5","name":"Lightcash crypto","decimals":"18","symbol":"LCCT","totalSupply":"8541879253670039718541431","owner":"0xf51e0a3a17990d41c5f1ff1d0d772b26e4d6b6d0","lastUpdated":1523987895,"issuancesCount":0,"holdersCount":261,"price":false},"balance":1.8823529411765e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xeb72f169016fec42b63ae5e87207f0fb274408c7","name":"Midex","decimals":"18","symbol":"MDX","totalSupply":"75000000000000000000000000","owner":"0x854c28dfb7a51deabed2a84e31cf998f5f916709","lastUpdated":1524488026,"issuancesCount":0,"holdersCount":1305,"price":false},"balance":1.104e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x6a0aba43ae83d9a70ad9c0954631dc67df916b71","name":"XNTPromo Token (exenium.io)","decimals":0,"symbol":"EXENIUM.IO","totalSupply":"50","owner":"0xb766a07d0e77c8aa4a024d7f5fa0a44815992d37","lastUpdated":1522006013,"issuancesCount":0,"holdersCount":10000,"price":false},"balance":1,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x355a458d555151d3b27f94227960ade1504e526a","name":"StockChain Coin","decimals":"18","symbol":"SCC","totalSupply":"10000000000000000000000000000","owner":"0x","lastUpdated":1524505179,"issuancesCount":0,"holdersCount":86415,"price":{"rate":"0.0518448","diff":-2.96,"diff7d":17.95,"ts":"1524505164","marketCapUsd":null,"availableSupply":null,"volume24h":"618532.0","currency":"USD"}},"balance":5.0e+18,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xbab165df9455aa0f2aed1f2565520b91ddadb4c8","name":"EDUCare","decimals":"8","symbol":"EKT","totalSupply":"100000000000000000","owner":"0x","lastUpdated":1524501750,"issuancesCount":0,"holdersCount":54294,"price":{"rate":"0.0825581","diff":0.93,"diff7d":9.9,"ts":"1524505162","marketCapUsd":"28895335.0","availableSupply":"350000000.0","volume24h":"19270600.0","currency":"USD"}},"balance":1000000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x3abdff32f76b42e7635bdb7e425f0231a5f3ab17","name":"ConnectJob","decimals":"18","symbol":"CJT","totalSupply":"300000000000000000000000000","owner":"0xd2d767c63021500efdae9edc51362a892d042e77","lastUpdated":1524463921,"issuancesCount":0,"holdersCount":918,"price":false},"balance":2.37250668e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x4540fcf016bcb67290c3e14238702c64e6a51600","name":"MOAT","decimals":"18","symbol":"MOAT","totalSupply":"10000000000000000000000000000","owner":"0x7d08c146ba989b6528cc49db4e57e7bfbd92a821","lastUpdated":1513776291,"issuancesCount":0,"holdersCount":30229,"price":false},"balance":9.51e+20,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x4e83be30974a13140493d9d613d312f1af84b7f6","name":"Tawarruq Token","decimals":"2","symbol":"TWQ","totalSupply":"34422000000","owner":"0x9ebf6734ebf95effb99dbe6dc5faf086ec394d94","lastUpdated":1524408453,"issuancesCount":0,"holdersCount":1995,"price":false},"balance":500,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x4dcadd9adfd450c2ef997bb71888c2995e2d33a0","name":"UniCoin","decimals":0,"symbol":"UNC","totalSupply":"16000000000","owner":"0x","lastUpdated":1524505388,"issuancesCount":0,"holdersCount":22292,"price":false},"balance":500,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xcd5fb50c82590b9d11474ff19d085eba3c483e57","name":"Bit USD","decimals":"6","symbol":"BITUSD","totalSupply":"1000000000000000000000","owner":"0x","lastUpdated":1524498274,"issuancesCount":0,"holdersCount":448,"price":false},"balance":1000000000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x06178110e4202fd6295e8e3417303ba39c1ab8f2","name":"ArgotBit","decimals":"6","symbol":"AB","totalSupply":"1000000000000000000","owner":"0x","lastUpdated":1524498392,"issuancesCount":0,"holdersCount":317,"price":false},"balance":5000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xb679afd97bcbc7448c1b327795c3ef226b39f0e9","name":"Win Last Mile","decimals":"6","symbol":"WLM","totalSupply":"2000000000000000","owner":"0x8e7a75d5e7efe2981ac06a2c6d4ca8a987a44492","lastUpdated":1524362946,"issuancesCount":0,"holdersCount":10945,"price":false},"balance":66000000,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x31a240648e2baf4f9f17225987f6f53fceb1699a","name":"SAFE.AD","decimals":0,"symbol":"safe.ad","totalSupply":"1","owner":"0x","lastUpdated":1523539377234,"issuancesCount":0,"holdersCount":153741,"price":false},"balance":777,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x3fbd598a07702ced544ccc2c9fd3dae5b14c3d41","name":"ECHARGE","decimals":"18","symbol":"ECHG","totalSupply":"2000000000000000000000000000","owner":"0xf6587c1ddfc7278c2a6f2f72682caee134e71537","lastUpdated":1524495676,"issuancesCount":0,"holdersCount":17746,"price":false},"balance":1.0e+19,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xfbb3f79318126c0b2a5fa5ad316deeeaa4e5367f","name":"Ubcoin.io ICO Samsung supported","decimals":"18","symbol":"Ubcoin.io ICO Samsung supported","totalSupply":"11110000000000000000000000","owner":"0xea15adb66dc92a4bbccc8bf32fd25e2e86a2a770","lastUpdated":1524412175,"issuancesCount":0,"holdersCount":8006,"price":false},"balance":1.0e+21,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x2f141ce366a2462f02cea3d12cf93e4dca49e4fd","name":"Free Coin","decimals":"18","symbol":"FREE","totalSupply":"10000000000000000000000000000000","owner":"0xdad3f160f858ac82df8af5deab03eb2b1a7e44d5","lastUpdated":1524504969,"issuancesCount":0,"holdersCount":117,"price":false},"balance":1.00001e+28,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x697b2658eb4085445625d6aeece29bd117c58c62","name":"Welcome Coin","decimals":"18","symbol":"WEL","totalSupply":"150000000000000000000000000","owner":"0xd0121a9620f789b5bde596e298cd397caa1161ec","lastUpdated":1524497416,"issuancesCount":0,"holdersCount":8106,"price":false},"balance":5.45e+20,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x0f71b8de197a1c84d31de0f1fa7926c365f052b3","name":"Arcona Distribution Contract","decimals":"18","symbol":"ARCONA","totalSupply":"7618234868653377482539355","owner":"0xfb1668c489ce6146d3b60dc7c8fe0bb40162b745","lastUpdated":1524504198,"issuancesCount":0,"holdersCount":10084,"price":false},"balance":1.0e+18,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xc9c261ba4c0fcc8a23d0829443e894801d507173","name":"CANDY CASH","decimals":"18","symbol":"CANDYCASH","totalSupply":"12345678900000000000000000000","owner":"0x","lastUpdated":1524470613,"issuancesCount":0,"holdersCount":313,"price":false},"balance":7.6664e+22,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xae66d00496aaa25418f829140bb259163c06986e","name":"Super Wallet Token","decimals":"8","symbol":"SW","totalSupply":"8400000000000000","owner":"0xba051682e9dbc40730fcef4a374e3a57a0ce3eff","lastUpdated":1524448023,"issuancesCount":0,"holdersCount":32162,"price":false},"balance":30117300,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xca79af090d12bd310cc029390b8c2263362b580a","name":"MILE","decimals":"18","symbol":"MILE","totalSupply":"3300000000000000000000000000","owner":"0x","lastUpdated":1524488693,"issuancesCount":0,"holdersCount":592,"price":false},"balance":1.0e+20,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x6beb418fc6e1958204ac8baddcf109b8e9694966","name":"Linker Coin","decimals":"18","symbol":"LNC","totalSupply":"500000000000000000000000000","owner":"0x210323fcf2f0c1f0fb677eda5490f8159c20edcc","lastUpdated":1524493358,"issuancesCount":0,"holdersCount":9574,"price":false},"balance":2.3331777e+19,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0xc67ceac00a37c792ad42fd519891f6eeb22241e6","name":"Tag All","decimals":"18","symbol":"TGA","totalSupply":"361000000000000000000000000","owner":"0x","lastUpdated":1524055510,"issuancesCount":0,"holdersCount":6,"price":false},"balance":1.0e+17,"totalIn":0,"totalOut":0},{"tokenInfo":{"address":"0x8e4fbe2673e154fe9399166e03e18f87a5754420","name":"Universal Bonus Token | t.me/bubbletonebot","decimals":"18","symbol":"UBT","totalSupply":"1149996534653360000000096","owner":"0xc2db6e5b96dd22d9870a5ca0909cceac6604e21d","lastUpdated":1524498515,"issuancesCount":0,"holdersCount":99896,"price":false},"balance":1.0e+19,"totalIn":0,"totalOut":0}]}';
////        $a = json_decode($a, true);
////        $sum = 0;
////        foreach ($a['tokens'] as $aa){
//////            echo $aa['tokenInfo']['address']. "<br/>";
////            $pr = Token::all()->where('token', $aa['tokenInfo']['address'])->toArray();
////            if(!empty($pr)){
////                foreach ($pr as $item) {
////                    echo $item['price_usd'] . "<br/>";
////                    $sum += $aa['balance'] * $item['price_usd'];
////                }
//////                print_r($pr);
////            }
//////
////
//////            $sum += $aa['balance'];
////
////        }
////        echo $sum."<br>";
////        echo "<pre>";
////        print_r($a['tokens']);
////        echo "</pre>";die;
////        echo Balance::where('date', date("Y-m-d"))->count();die;
////        \Log::info('Tokens List start - ' . \Carbon\Carbon::now());
        define('TOKEN_ADDRESS_SITE', 'https://etherscan.io/token/');
        define('COINMARKETCAP_ADDRESS_SITE', 'https://coinmarketcap.com/currencies/');
        define('HOLDER_GENERAL_ADDRESS_SITE', 'https://etherscan.io/address/');
        ini_set('max_execution_time', 300000);
        require_once 'simple_html_dom.php';

        $whales = Whale::all('id', 'holder')->sortBy('id')->slice(0, 10);
//        echo "<pre>";
//             print_r($whales);
//            echo "</pre>";
        foreach ($whales as $whale) {
            $url = HOLDER_GENERAL_ADDRESS_SITE . $whale->holder;
            $html = file_get_html($url);

            $result = $html->find('#balancelistbtn > span.pull-left');
            $balance = 0;
            foreach ($result as $a) {
                $str = $a->plaintext;
                $balanceBeginIndex = strpos($str, '$');
                $balanceEndIndex = strpos($str, ')');
                $balance = substr($str, $balanceBeginIndex + 1, $balanceEndIndex - $balanceBeginIndex - 1);
                $balance = (double)(str_replace(',', '', $balance));
                echo $balance . "<br>";
            }

            $result = $html->find('#balancelistbtn > span.badge');
            $totalTokens = 0;
            foreach ($result as $a) {
                echo $a->plaintext . "<br>";
                $totalTokens = $a->plaintext;
            }
            Whale::where('id', $whale->id)->update([
                'balance_current' => $balance,
                'total_tokens' => $totalTokens,
            ]);
            $html->clear();
            unset($html);
        }
        die;
//            $result = $html->find('#ContentPlaceHolder1_divpagingpanel > .col-md-5 > span');
//            $tokensCount = 0;
//            foreach ($result as $a) {
//                echo $a->plaintext . " - === ";
//                $str = $a->plaintext;
//                $str = str_replace('A total of ', '', $str);
//                echo $tokensCount = (int)str_replace(' Tokens found', '', $str);
//                echo "<br>";
////                    $data = explode(" ", $str);
////                    $totalBalance = (double)str_replace(',','', $data[0]);
////                    $performance = substr($data[1],0, strlen($data[1]) - 3);
////                    echo "Total balance = ". $totalBalance." - Performance = " . $performance ."<br>";
//            }
//            $result = $html->find('.table-responsive .addresstag');
////            $result = $html->find('.table-responsive a[href^="/token/0x"]');
////            var_dump($result);die;
//            $index = 1;
//            $topHoldings = '';
//            foreach ($result as $a) {
//
//                if ($index > 3) {
//                    break;
//                }
//                $index++;
////                $str = $a->href;
////                $str = str_replace("/token/", "", $str);
////                 echo $str."<br>";
////                $symbol = Token::where('token', $str)->first(['symbol']);
//////                 echo "<pre>";
//////             print_r($symbol);
//////            echo "</pre>";die;
////                echo $symbol['symbol']."<br>";
//
//
////                echo $str = htmlspecialchars($a->innertext)."<br>";
////                $str = $a->parent()->parent()->next_sibling()->next_sibling()->plaintext;
//                $str = $a->next_sibling()->next_sibling()->plaintext;
//                $data = explode(" ", $str);
//                if($data[1] == 'ETH'){
//                    $index--;
//                }elseif (!isset($data[2])) {
//                    $topHoldings .= $data[1] . ",";
//                }
////                $data = explode("(", $str);
//////                echo "<pre>";
//////             print_r($data[1]);
//////            echo "</pre>";
//////                if(isset($data[1])){
////                    $oneToken = substr($data[1], 0, strlen($data[1]) - 1);
//////                    $topHoldings .= $data[1] . ",";
////                    $topHoldings .= $oneToken . ",";
//////                    echo $topHoldings."<br>";
////                }
////                    if(!isset($data[2])){
////
////                    }
////                     echo $data[1]."<br>";
//
////                if ($index > 3) {
////                    break;
////                }
////                $index++;
////                $str = $a->parent()->parent()->next_sibling()->next_sibling()->plaintext;
////                $str2 = $a->parent()->parent()->next_sibling()->next_sibling()->next_sibling()->plaintext;
////                echo $str2."<br>";// = str_replace(" ", "", $str2);
////                echo substr($str2, 0, 1)."<br>";
////                if($str2 != '-'){
////                if(strpos($str2, '$')){
////                    $data = explode(" ", $str);
////                $topHoldings .= $data[1] . ",";
////                }
//
////                echo $a->parent()->parent()->next_sibling()->next_sibling()->plaintext."<br>";
////
////                if ($index > 3) break;
////                echo $a->parent()->parent()->next_sibling()->next_sibling()->plaintext . "<br>";
//////                echo htmlspecialchars($a->innertext) . "<br>";
////                $index++;
////                    $str = $a->parent()->parent()->next_sibling()->next_sibling()->plaintext;
//////                    $str = str_replace('A total of ','', $str);
//////                   echo  $tokensCount = (int)str_replace(' Tokens found','', $str);
//////                   echo "<br>";
////                    $data = explode(" ", $str);
//////                    $totalBalance = (double)str_replace(',','', $data[0]);
////                    $topHoldings .= $data[1].",";
////                    echo "Total balance = ". $totalBalance." - Performance = " . $performance ."<br>";
//            }
//            echo $topHoldings . "<br>";
////            die;
////                    $topHoldings = substr($topHoldings, 0, strlen($topHoldings) - 1);
////            Whale::where('id', $whale->id)->update([
////                'balance_current' => $totalBalance,
////                'performance_etherscan' => $performance,
////                'total_tokens' => $tokensCount,
////                'top_holdings' => $topHoldings
////            ]);
//
////                    echo "<pre>";
////             print_r($data);
////            echo "</pre>";
////                    if (isset($a->src)) {
////                        $tokensArray[$index]['image'] = str_replace("/token/images/", "", $a->src);
////                    } elseif (isset($a->href)) {
////                        $tokensArray[$index]['name'] = $a->plaintext;
////                        $tokensArray[$index]['token'] = str_replace("/token/", "", $a->href);
////                        $index++;
////                    }
//
////            }

// Save all tokens to database
//        foreach ($tokensArray as $item) {
//            Token::firstOrCreate(
//                ['token' => $item['token']],
//                [
//                    'image' => $item['image'],
//                    'name' => $item['name'],
//                ]
//            );
//        }


//        define('ETHERSCAN_API_KEY', '7GTJCQMRQ3XSIZXHN15EIZW7ZVC77XESVU');
//        define('ETHPLORER_API_KEY', 'skffj61105BkR78');
//        define('MINIMUM_LIMIT', 500000);
//        define('HOLDER_GENERAL_ADDRESS_SITE', 'https://etherscan.io/address/');
//        define('TOKEN_ADDRESS_SITE', 'https://etherscan.io/token/');
//        define('COINMARKETCAP_ADDRESS_SITE', 'https://coinmarketcap.com/currencies/');
//        define('TOKEN_IMAGE_SITE', 'https://etherscan.io/token/images/');
//        ini_set('max_execution_time', 300000);
//        require_once 'C:\Users\dru-Victor\Downloads\Install\OpenServer\domains\whale\app\Http\Controllers\simple_html_dom.php';
//        function _isCurl()
//        {
//            return function_exists('curl_version');
//        }
//        $holdersCount = floor(Holder::count() / 2);
//        $holderData = Holder::all('id', 'token_id', 'holder_id')->where('id', 11);
////        $holderData = Holder::all('id', 'token_id', 'holder_id')->splice(0, $holdersCount);
//        foreach ($holderData as $item) {
////            \Log::info('PLORER - Holder table ID = ' .$item->id. " - Time = ". \Carbon\Carbon::now());
//            $tokenAddress = Holder::find($item->token_id)->token->token;
//            $holderAddress = Holder::find($item->holder_id)->whale->holder;
//            $tokenDecimal = Holder::find($item->token_id)->token->token_decimal;
//            $priceUsd = Holder::find($item->token_id)->token->price_usd;
//            $output = [];
//            $output['tokens'][0]['balance'] = 1;
//            if (_iscurl()) {
//                $url = "https://api.ethplorer.io/getAddressInfo/" . $holderAddress. "?apiKey=" . ETHPLORER_API_KEY . "&token=" . $tokenAddress;
//                $ch = curl_init();
//                curl_setopt($ch, CURLOPT_URL, $url);
//                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//                $output = curl_exec($ch);
//                curl_close($ch);
//                $output = json_decode($output, true);
//            }
//            echo "<pre>";
//             print_r($output);
//            echo "</pre>";

//        if (_iscurl()) {
//                $url = "https://api.ethplorer.io/getAddressInfo/" . $holderAddress. "?apiKey=" . ETHPLORER_API_KEY . "&token=" . $tokenAddress;
//                $ch = curl_init();
//                curl_setopt($ch, CURLOPT_URL, $url);
//                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//                $output = curl_exec($ch);
//                curl_close($ch);
//                $output = json_decode($output, true);
//            }
//            echo "<pre>";
//             print_r($output);
//            echo "</pre>";
//
//            $balance = $priceUsd * isset($output['tokens'][0]['balance']) ? $output['tokens'][0]['balance'] : 0 * 1 / (10 ** $tokenDecimal);
//            print($balance);
//            exit;
//            Holder::where('id', $item->id)->update([
//                'balance_current' => $balance,
//            ]);
//        }
//        $holdersCount = floor(Holder::count() / 2);
//        $whales = Whale::all()->where('id', 12);
//        $whales = Whale::all()->sortBy('id')->splice(0, 3);
////        echo "<pre>";
////            print_r($whales);
////            echo "</pre>";die;
//        foreach ($whales as $item) {
////            $tokenAddress = Holder::find($item->token_id)->token->token;
////            $holderAddress = Holder::find($item->holder_id)->whale->holder;
////            $tokenDecimal = Holder::find($item->token_id)->token->token_decimal;
////            $priceUsd = Holder::find($item->token_id)->token->price_usd;
//            $output = [];
//            if (_iscurl()) {
//                https://api.ethplorer.io/getAddressHistory/0xd0a6e6c54dbc68db5db3a091b171a77407ff7ccf?apiKey=freekey&type=transfer&limit=1
//                $url = "https://api.ethplorer.io/getAddressHistory/" . $item->holder. "?apiKey=" . ETHPLORER_API_KEY . "&type=transfer&limit=1";
//                $ch = curl_init();
//                curl_setopt($ch, CURLOPT_URL, $url);
//                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//                $output = curl_exec($ch);
//                curl_close($ch);
//                $output = json_decode($output, true);
//            }
//            if (isset($output['operations'][0]['timestamp'])){
//                Whale::where('id', $item->id)->update([
//                    'last_active' => $output['operations'][0]['timestamp']
//                    ]);
//            }
//            echo "<pre>";
////            print_r($output['operations'][0]['timestamp']);
//            print_r($output);
//            echo "</pre>";
//
////            $balance = $priceUsd * $output['tokens'][0]['balance'] * 1 / (10 ** $tokenDecimal);
////            Holder::where('id', $item->id)->update([
////                'balance_current' => isset($output['operations'][0]['timestamp']) ? $output['operations'][0]['timestamp'] : 0,
////            ]);
//        }die;
//        $output = [];
//            $output['tokens'][0]['balance'] = 1;
//            echo "<pre>";
//            print_r($output);
//            echo "</pre>";die;
//function _isCurl()
//        {
//            return function_exists('curl_version');
//        }
//        $holdersCount = floor(Holder::count() / 2);
//        $holderData = Holder::all('id', 'token_id', 'holder_id')->splice(0, $holdersCount);
//        foreach ($holderData as $item) {
//            $tokenAddress = Holder::find($item->token_id)->token->token;
//            $holderAddress = Holder::find($item->holder_id)->whale->holder;
//            $tokenDecimal = Holder::find($item->token_id)->token->token_decimal;
//            $priceUsd = Holder::find($item->token_id)->token->price_usd;
//            $output = [];
//            if (_iscurl()) {
//                $url = "https://api.ethplorer.io/getAddressInfo/" . $holderAddress. "?apiKey=" . ETHPLORER_API_KEY . "&token=" . $tokenAddress;
//                $ch = curl_init();
//                curl_setopt($ch, CURLOPT_URL, $url);
//                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//                $output = curl_exec($ch);
//                curl_close($ch);
//                $output = json_decode($output, true);
//            }
//            echo "<pre>";
//            print_r($output);
//            echo "</pre>";die;
//
//            $balance = $priceUsd * $output['tokens'][0]['balance'] * 1 / (10 ** $tokenDecimal);
//            Holder::where('id', $item->id)->update([
//                'balance_current' => $balance,
//            ]);
//        }

// scrape all tokens

//           $html = file_get_html('https://etherscan.io/tokens');
//
//            $pagination = $html->find('#ContentPlaceHolder1_divpagingpanel  p > a');
//            $pagesCount = 1;
//    //                    foreach ($pagination as $a) {
//    //                        if ($a->plaintext == "Last") {
//    //                            $pagesCount = str_replace("tokens?p=", "", $a->href);
//    //                        }
//    //                    }
//
//            $html->clear();
//            unset($html);
//
//            $index = 0;
//            $tokensArray = [];
//            $html = "";
//            for ($j = 1; $j <= $pagesCount; $j++) {
//                $url = 'https://etherscan.io/tokens?p=' . $j;
//                $html = file_get_html($url);
//                $result = $html->find('.hidden-xs img.rounded-x[src^="/token/images/"], .hidden-xs > h5 > a[href^="/token/"]');
//                foreach ($result as $a) {
//                    if (isset($a->src)) {
//                        $tokensArray[$index]['image'] = str_replace("/token/images/", "", $a->src);
//                    } elseif (isset($a->href)) {
//                        $tokensArray[$index]['name'] = $a->plaintext;
//                        $tokensArray[$index]['token'] = str_replace("/token/", "", $a->href);
//                        $index++;
//                    }
//
//                    //+++++++++++++++++++++++++++++++++++
//                    if ($index == 3) break;
//                    //+++++++++++++++++++++++++++++++++++
//
//                }
//            }
//            $html->clear();
//            unset($html);
//
//            //          Save all tokens to database
//            foreach ($tokensArray as $item) {
//                Token::firstOrCreate(
//                    ['token' => $item['token']],
//                    [
//                        'image' => $item['image'],
//                        'name' => $item['name'],
//                    ]
//                );
//            }
//
//            $tokensForUpdate = Token::all('id', 'token');
//            if (!empty($tokensForUpdate)) {
//
//                foreach ($tokensForUpdate as $item) {
//                    $url = TOKEN_ADDRESS_SITE . $item->token;
//                    $html = file_get_html($url);
//                    $result = $html->find('tr.#ContentPlaceHolder1_trContract, a[data-original-title^="Website:"], a[data-original-title^="CoinMarketCap:"]');
//                    foreach ($result as $a) {
//                        if (!isset($a->href)) {
//                            $token_decimal = (int)$a->next_sibling()->children(1)->plaintext;
//                            Token::where('id', $item->id)->update([
//                                'token_decimal' => $token_decimal,
//                            ]);
//                        }
//                        if (strpos($a->href, COINMARKETCAP_ADDRESS_SITE) === 0) {
//                            $coinmarket_id = str_replace(COINMARKETCAP_ADDRESS_SITE, '', $a->href);
//                            $coinmarket_id = substr($coinmarket_id, 0, strlen($coinmarket_id) - 1);
//                            Token::where('id', $item->id)->update([
//                                'coinmarket_id' => $coinmarket_id
//                            ]);
//                        } else {
//                            $website = $a->href;
//                            Token::where('id', $item->id)->update([
//                                'website' => $website,
//                            ]);
//                        }
//                    }
//                    $html->clear();
//                    unset($html);
//                }
//            }
//
//        function _isCurl()
//        {
//            return function_exists('curl_version');
//        }

//
//
//            $output = [];
//            if (_iscurl()) {
//                $url = "https://api.coinmarketcap.com/v1/ticker/?limit=0";
//                $ch = curl_init();
//                curl_setopt($ch, CURLOPT_URL, $url);
//                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//                $output = curl_exec($ch);
//                curl_close($ch);
//                $output = json_decode($output, true);
//            }
//
//            $tokens = Token::all('coinmarket_id');
//            $countOutput = count($output);
//            $coinMarketInfo = [];
//            $indexCoin = 0;
//            for ($j = 0; $j < $countOutput; $j++) {
//                $flag = false;
//                foreach ($tokens as $tokenItem) {
//                    if ($output[$j]['id'] == strtolower($tokenItem->coinmarket_id)) {
//                        $flag = true;
//                        break;
//                    }
//                }
//                if ($flag) {
//                    $coinMarketInfo[$indexCoin] = $output[$j];
//                    $indexCoin++;
//                }
//            }
//
//            $countCoinMarketInfo = count($coinMarketInfo);
//            for ($j = 0; $j < $countCoinMarketInfo; $j++) {
//                Token::where('coinmarket_id', $coinMarketInfo[$j]['id'])->update([
//                    'symbol' => $coinMarketInfo[$j]['symbol'],
//                    'price_usd' => $coinMarketInfo[$j]['price_usd'],
//                    '24h_volume_usd' => $coinMarketInfo[$j]['24h_volume_usd'],
//                    'market_cap_usd' => $coinMarketInfo[$j]['market_cap_usd'],
//                    'available_supply' => $coinMarketInfo[$j]['available_supply'],
//                    'total_supply' => $coinMarketInfo[$j]['total_supply'],
//                    'last_updated' => $coinMarketInfo[$j]['last_updated'],
//                ]);
//            }
//
//            //====================================================================================================
//
//            $tokens = Token::all('id', 'token', 'price_usd');
//            $index = 0;
//            $holdersArray = [];
//            foreach ($tokens as $token) {
//                if ($token->price_usd === null) {
//                    continue;
//                }
//                $url = 'https://etherscan.io/token/generic-tokenholders2?a=' . $token->token;
//    //                         $html = file_get_html($url);
//    //                         $pagination = $html->find('#PagingPanel > a');
//                $pagesCount = 1;
//    //                         foreach ($pagination as $a) {
//    //                             if ($a->plaintext == "Last") {
//    //                                 $first_index = strpos($a->href, 'p=');
//    //                                 $last_index = strpos($a->href, '\')');
//    //                                 $pagesCount = substr($a->href, $first_index + 2, $last_index - $first_index - 2);
//    //                             }
//    //                         }
//    //                         $html->clear();
//    //                         unset($html);
//
//                $html = "";
//                $ii = 1;
//                for ($j = 1; $j <= $pagesCount; $j++) {
//                    $html = file_get_html($url . "&p=" . $j);
//                    $result = $html->find('a[href^="/token/0x"]');
//                    $flag = false;
//                    foreach ($result as $a) {
//                        //                    echo "Res # " . $index . " - " . $a . "<br />";
//                        //                            $holdersArray[$index]['address'] = "https://etherscan.io" . $a->href;
//                        $holdersArray[$index]['holder'] = $a->plaintext;
//                        $holdersArray[$index]['name'] = "Whale name";
//                        $holdersArray[$index]['token_id'] = $token->id;
//                        $holdersArray[$index]['quantity'] = (int)$a->parent()->parent()->next_sibling()->plaintext;
//                        $holdersArray[$index]['balance_current'] = $token->price_usd * $holdersArray[$index]['quantity'];
//
//                        if ($holdersArray[$index]['balance_current'] < MINIMUM_LIMIT) {
//                            $flag = true;
//                            break;
//                        }
//                           //++++++++++++++++++++++++++++++++++++
//                    if ($ii == 10) {
//                        break;
//                    }
//                    //+++++++++++++++++++++++++++++++++++++++
//
//
//                        echo "TOKEN ID = " . $token->id . "&#9; - HOLDER# = " . $ii . "&#9; - INDEX = " . $index . "&#9; - BALANCE = " . $holdersArray[$index]['balance_current'] . "<br/>";
//                        $ii++;
//                        //                    Whale::firstOrCreate(
//                        //                        ['holder' => $holdersArray[$index]['holder']],
//                        //                        [
//                        //                            'address' => $holdersArray[$index]['address'],
//                        //                            'name' => $holdersArray[$index]['name'],
//                        //                        ]);
//                        //                    $holder_id = Whale::where('holder', $holdersArray[$index]['holder'])->first(['id']);
//                        //                    Holder::firstOrCreate(
//                        //                        [
//                        //                            'token_id' => $token->id,
//                        //                            'holder_id' => $holder_id->id
//                        //                        ],
//                        //                        [
//                        //                            'balance_start' => $holdersArray[$index]['balance_current']
//                        //                        ]);
//                        //                    Holder::updateOrCreate(
//                        //                        [
//                        //                            'token_id' => $token->id,
//                        //                            'holder_id' => $holder_id->id
//                        //                        ],
//                        //                        [
//                        //                            'balance_current' => $holdersArray[$index]['balance_current']
//                        //                        ]);
//                        $index++;
//                    }
//
//
//                    if ($flag) {
//                        break;
//                    }
//                }
//                $html->clear();
//                unset($html);
//
//                //            echo "<pre>";
//                //            print_r($holdersArray);
//                //            echo "</pre>";
//                //            echo "I = ".$i."<br>";
//            }
//
//
//            echo "<pre>";
//            print_r($holdersArray);
//            echo "</pre>";
//            $ccc = count($holdersArray);
//            for ($index = 0; $index < $ccc; $index++) {
//                Whale::firstOrCreate(
//                    ['holder' => $holdersArray[$index]['holder']],
//                    [
//                        'name' => $holdersArray[$index]['name'],
//                    ]);
//                $holder_id = Whale::where('holder', $holdersArray[$index]['holder'])->first(['id']);
//                Holder::firstOrCreate(
//                    [
//                        'token_id' => $holdersArray[$index]['token_id'],
//                        'holder_id' => $holder_id->id
//                    ],
//                    [
//                        'balance_start' => $holdersArray[$index]['balance_current']
//                    ]);
//                Holder::updateOrCreate(
//                    [
//                        'token_id' => $holdersArray[$index]['token_id'],
//                        'holder_id' => $holder_id->id
//                    ],
//                    [
//                        'balance_current' => $holdersArray[$index]['balance_current']
//                    ]);
//            }
//            //            echo "I = ".$i."<br>";
//    //     $timeEnd = date('m/d/y g:i:s a');
//    //             echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
//    //             echo "TIME End   =   " . $timeEnd . "<br/>";
//    //     die;
//    //echo $a;die;
//
//            $holderData = Holder::all('id', 'token_id', 'holder_id');
//    //        $holderData = Holder::all('id', 'token_id', 'holder_id')->take(20);
//    //        $holderData = Holder::all('id', 'token_id', 'holder_id')->sortBy('token_id')->take(5);
//
//            $counter = 1;
//            $counter2 = 1;
//            $time_start = microtime(true);
//            foreach ($holderData as $item) {
//                $tokenAddress = Holder::find($item->token_id)->token->token;
//                $holderAddress = Holder::find($item->holder_id)->whale->holder;
//                $tokenDecimal = Holder::find($item->token_id)->token->token_decimal;
//                $priceUsd = Holder::find($item->token_id)->token->price_usd;
//
//                $output = [];
//                if (_iscurl()) {
//                    $url = "https://api.etherscan.io/api?module=account&action=tokenbalance&contractaddress=" . $tokenAddress
//                        . "&address=" . $holderAddress . "&tag=latest&apikey=" . ETHERSCAN_API_KEY;
//                    $ch = curl_init();
//                    curl_setopt($ch, CURLOPT_URL, $url);
//                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//                    $output = curl_exec($ch);
//                    curl_close($ch);
//                    $output = json_decode($output, true);
//                }
//
//                if ($counter % 5 == 0) {
//                    if (microtime(true) - $time_start - 1 < 0) {
//                        usleep(500000);
//                    }
//                    $time_start = microtime(true);
//                    $counter = 0;
//                    $counter2++;
//                }
//                $counter++;
//                $balance = $priceUsd * $output['result'] * 1 / (10 ** $tokenDecimal);
//                Holder::find($item->id)->update([
//                    'balance_current' => $balance,
//                    'quantity_current' => $output['result']
//                ]);
//            }
//
//    //        $timeEnd = date('m/d/y g:i:s a');
//    //        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
//    //        echo "TIME End   =   " . $timeEnd . "<br/>";
//    //        die;
//
//
//
////
//        $whales = Whale::all('id')->sortBy('id');
//        $balances = [];
//        $index = 0;
//        foreach ($whales as $whale) {
//            $holderBalance = Holder::where('holder_id', $whale->id)->sum('balance_current');
//            $holderQuantity = Holder::where('holder_id', $whale->id)->sum('quantity_current');
//            $quantityPrevious = Balance::all()->where('holder_id', $whale->id)->sortByDesc('id')->slice(0, 1)->toArray();
//            $quantityPrevious = array_values($quantityPrevious);
//            if (!empty($quantityPrevious)) {
//                if ($quantityPrevious[0]['quantity'] == $holderQuantity) {
//                    $balances[$index]['is_changed'] = 0;
//                } else {
//                    $balances[$index]['is_changed'] = 1;
//                }
//            } else {
//                $balances[$index]['is_changed'] = 1;
//            }
////            $quantityPrevious[0]['quantity'];
//            Whale::where('id', $whale->id)->where('balance_start', null)->update([
//                'balance_start' => $holderBalance
//            ]);
//            Whale::where('id', $whale->id)->update([
//                'balance_current' => $holderBalance,
//                'total_tokens' => Holder::where('holder_id', $whale->id)->count()
//            ]);
//            $balances[$index]['holder_id'] = $whale->id;
//            $balances[$index]['balance'] = $holderBalance;
//            $balances[$index]['quantity'] = $holderQuantity;
//            $balances[$index]['time'] = time();
//            $index++;
//        }
//        echo "<pre>";
//        print_r($balances);
//        echo "</pre>";
//        echo "start_balance...<br/>";
//        Balance::insert($balances);
//        echo "end_balance...<br/>";
//
//////$bal = Balance::where('hodler_id',  2)->sortByDesc('id')->first(['quantity']);
//////$bal = Balance::all()->where('holder_id', 2)->sortByDesc('id')->slice(0,1);
////        $bal = Balance::all()->where('holder_id', 2)->sortByDesc('id')->slice(0, 1)->toArray();
//////$bal = Balance::all()->sortByDesc('id')->where('hodler_id',  2)->toArray();
//////Balance::all()->where('hodler_id',  $whale->id)->sortByDesc('id')->first('quantity');
////        $bal = array_values($bal);
////        if (!empty($bal)) echo "AAAA"; else echo "VVVV";
////        echo "<pre>";
////        print_r($bal);
//////print_r($bal[0]['quantity']);
////        echo "</pre>";
////        die;
//////$whales = Balance::all()->where('holder_id', 2)->sortByDesc('id')->toArray();
////        $bal = Balance::all()->where('holder_id', 2)->sortByDesc('id')->slice(0, 2)->toArray();
////        $bal = array_values($bal);
////        echo "<pre>";
////        print_r($bal);
////        echo "</pre>";
////        die;
//////Holder::all()->slice(0,3)->where('holder_id', 2)->sortByDesc('id')->toArray();
////        $lastActive = 0;
////        $whales = Whale::all('id')->sortBy('id')->slice(0, 2);
////
////        foreach ($whales as $whale) {
//////            echo "ID = " . $whale->id . " - Count tokens = ". Holder::where('holder_id', $whale->id)->count(). "<br>";
////            $i = 1;
////            $flag = true;
////            while ($flag) {
////
////            }
////        }
////        echo "<pre>";
////        print_r($whales);
////        echo "</pre>";
////        die;
////
////
////////        $today = getdate(426);
////////        print_r($today);
////////        die;
////        $lastActive = 0;
//        $countRecords = Balance::all()->where('holder_id', 1)->count();
//        if ($countRecords >= 2) {
//            $whales = Whale::all('id');
////            $whales->where('id', 1)->first();
//            foreach ($whales as $whale) {
////            $holderBalances = Balance::all()->where('holder_id', 2)->sortByDesc('id')->toArray();
//                $timeChange = Balance::all()->where('holder_id', $whale->id)->where('is_changed', 1)->sortByDesc('id')->slice(0, 1)->toArray();
//                $timeChange = array_values($timeChange);
//                $lastActive = time() - $timeChange[0]['time'];
////            $ccc = count($holderBalances);
////            for ($index = 0; $index < $ccc - 1; $index++) {
////                if ($holderBalances[$index]['balance'] != $holderBalances[$index + 1]['balance']) {
////                    $lastActive = time() - $holderBalances[$index]['time'];
//
//                    $years = (int)($lastActive / 31540000);
//                    $months = (int)($lastActive / 2628000) % 12;
//                    $weeks = (int)($lastActive / 604800) % 52;
//                    $days = (int)($lastActive / 86400) % 7;
//                    $hours = (int)($lastActive / 3600) % 24;
//                    $minutes = (int)($lastActive / 60) % 60;
//
//                    $last = '';
//                    if ($years != 0 && $months != 0) $last = $years . "y" . $months . "mo";
//                    elseif ($months != 0 && $weeks != 0) $last = $months . "mo";
//                    elseif ($weeks != 0 && $days != 0) $last = $weeks . "w" . $days . "d";
//                    elseif ($days != 0 && $hours != 0) $last = $days . "d" . $hours . "h";
//                    elseif ($hours != 0 && $minutes != 0) $last = $hours . "h" . $minutes . "min";
//                    elseif ($minutes != 0) $last = $minutes . "min";
//                    else $last = "1min";
//                    Whale::where('id', $whale->id)->update([
//                        'last_active' => $last,
//                    ]);
////                    break;
////                } else {
////                    $lastActive = time() - $holderBalances[$index]['time'];
////                }
////            }
//
//            }
//        }
//
//
////        echo "<br/>LAST ACTIVE = " . $lastActive . "<br/>";
//
//        $timeEnd = date('m/d/y g:i:s a');
//        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
//        echo "TIME End   =   " . $timeEnd . "<br/>";
//

//        $countRecords = Balance::all()->where('holder_id', 1)->count();
//        if ($countRecords >= 2) {
//            $whales = Whale::all('id');
//            $a = 1;
//            foreach ($whales as $whale) {
//                $start = microtime(true);
//                echo "ID = ". $a. "START = ".$start." - ";
//                $timeChange = Balance::where('holder_id', $whale->id)->where('is_changed', 1)->sortByDesc('id')->slice(0, 1)->toArray();
////                $timeChange = Balance::all()->where('holder_id', $whale->id)->where('is_changed', 1)->sortByDesc('id')->slice(0, 1)->toArray();
//                $timeChange = array_values($timeChange);
//                $lastActive = time() - $timeChange[0]['time'];
//
//                $years = (int)($lastActive / 31540000);
//                $months = (int)($lastActive / 2628000) % 12;
//                $weeks = (int)($lastActive / 604800) % 52;
//                $days = (int)($lastActive / 86400) % 7;
//                $hours = (int)($lastActive / 3600) % 24;
//                $minutes = (int)($lastActive / 60) % 60;
//
//                if ($years != 0 && $months != 0) $last = $years . "y" . $months . "mo";
//                elseif ($months != 0 && $weeks != 0) $last = $months . "mo";
//                elseif ($weeks != 0 && $days != 0) $last = $weeks . "w" . $days . "d";
//                elseif ($days != 0 && $hours != 0) $last = $days . "d" . $hours . "h";
//                elseif ($hours != 0 && $minutes != 0) $last = $hours . "h" . $minutes . "min";
//                elseif ($minutes != 0) $last = $minutes . "min";
//                else $last = "1min";
////                Whale::where('id', $whale->id)->update([
////                    'last_active' => $last,
////                ]);
//                $end = microtime(true);
//                echo "END = ". $end. " - DIFF = ".($end - $start)." - Last = " . $last . "<br>";
////                echo "ID = ". $a. " - TIME = ". date('m/d/y g:i:s a')."<br>";
//                if($a == 10)break;
//                $a++;
//            }
//        }
////        echo "<br/>LAST ACTIVE = " . $last . "<br/>";
////
//        $timeEnd = date('m/d/y g:i:s a');
//        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
//        echo "TIME End   =   " . $timeEnd . "<br/>";die;

//        $holderData = mktime();
//        echo "<pre>";
//            print_r($holderData);
//            echo "</pre>";
//            die;
//
//
//        $holderData2 = Holder::count();
//            $holderData = Holder::all('id', 'token_id', 'holder_id')->sortBy('id')->toArray();
//            $holderData2 = (int)floor(count($holderData) / 2);
//            echo "<pre>";
//            print_r($holderData[$holderData2]);
//            echo "</pre>";
//            echo "<pre>";
//            print_r($holderData);
//            echo "</pre>";
//            die;
////            $holderData = count($holderData);
//            echo $holderData2;
//            $holderData = $holderData2 / 2;
//            echo "<pre>";
//            print_r($holderData);
//            echo "</pre>";
//             $holderData = ceil($holderData2 / 2);
//            echo "<pre>";
//            print_r($holderData);
//            echo "</pre>";
//             $holderData = floor($holderData2 / 2);
//            echo "<pre>";
//            print_r($holderData);
//            echo "</pre>";
//            die;
//            die;

//        $holderData = Holder::all('id', 'token_id', 'holder_id');
//        $holdersCount = floor($holderData / 2);
//
//        $counter = 1;
//        $counter2 = 1;
//        $time_start = microtime(true);
//        $iterator = 1;
//        foreach ($holderData as $item) {
//            $tokenAddress = Holder::find($item->token_id)->token->token;
//            $holderAddress = Holder::find($item->holder_id)->whale->holder;
//            $tokenDecimal = Holder::find($item->token_id)->token->token_decimal;
//            $priceUsd = Holder::find($item->token_id)->token->price_usd;
//            $output = [];
//            if (_iscurl()) {
////                $url = "https://api.etherscan.io/api?module=account&action=tokenbalance&contractaddress=" . $tokenAddress
////                    . "&address=" . $holderAddress . "&tag=latest&apikey=" . ETHERSCAN_API_KEY;
//                $url = "https://api.ethplorer.io/getAddressInfo/" . $holderAddress
//                    . "?apiKey=" . ETHPLORER_API_KEY . "&token=" . $tokenAddress;
////                    . "?apiKey=freekey&token=" . $tokenAddress;
////                0xd0a6e6c54dbc68db5db3a091b171a77407ff7ccf?apiKey=freekey&token=0x86fa049857e0209aa7d9e616f7eb3b3b78ecfdb0
//                $ch = curl_init();
//                curl_setopt($ch, CURLOPT_URL, $url);
//                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//                $output = curl_exec($ch);
//                curl_close($ch);
//                $output = json_decode($output, true);
//            }
//echo "<pre>";
//            print_r($output['tokens'][0]['balance']);
//            echo "</pre>";die;
//            if ($counter % 5 == 0) {
//                if (microtime(true) - $time_start - 1 < 0) {
//                    echo "<br>" . ($counter2 * $counter) . " - BAD = " . (microtime(true) - $time_start) . "<br>";
//                    usleep(500000);
//                } else {
//                    echo "<br>" . ($counter2 * $counter) . " - OK = " . (microtime(true) - $time_start);
//                }
//                $time_start = microtime(true);
//                $counter = 0;
//                $counter2++;
//            }
//            $counter++;


////            $balance = $priceUsd * $output['result'] * 1 / (10 ** $tokenDecimal);
//            $balance = $priceUsd * $output['tokens'][0]['balance'] * 1 / (10 ** $tokenDecimal);
//            Holder::where('id', $item->id)->update([
//                'balance_current' => $balance,
//                'quantity_current' => $output['tokens'][0]['balance']
////                'quantity_current' => $output['result']
//            ]);
//            if ($iterator == $holdersCount) break;
//            $iterator++;
//        }
//        $timeEnd = date('m/d/y g:i:s a');
//        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
//        echo "TIME End   =   " . $timeEnd . "<br/>";
//        die;
//
////        $timeChange = Balance::all()->where('holder_id', 2)->where('is_changed', 1)->sortByDesc('id')->slice(0, 1)->toArray();
//        $timeChange = Balance::all()->where('holder_id', 2)->where('is_changed', 1)->sortByDesc('id')->slice(0, 1)->toArray();
////        $timeChange = Balance::all();
////        $timeChange = $timeChange->where('holder_id', 2)->sortByDesc('id')->take(1)->skip(1)->toArray();
//        echo "<pre>";
//        print_r($timeChange);
//        echo "</pre>";
        $timeEnd = date('m/d/y g:i:s a');
        echo "<br/>TIME START   =   " . $timeBegin . "<br/>";
        echo "TIME End   =   " . $timeEnd . "<br/>";
//        die;
    }

}
