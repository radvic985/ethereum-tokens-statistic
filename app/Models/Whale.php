<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Whale extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public static function getName($id)
    {
        $name = Whale::where('id', $id)->first(['name']);
        return $name->name;
    }

    public static function getPerformance($balance_start, $balance_current)
    {
        if ($balance_start == 0 || $balance_start === null) return 0;
        return ceil(($balance_current * 100) / $balance_start - 100);
//        return round(($balance_current * 100) / $balance_start - 100, 1);
    }

    public static function getPerformanceRound($balance_start, $balance_current)
    {
        if ($balance_start == 0 || $balance_start === null) return 0;
        return round(($balance_current * 100) / $balance_start - 100, 2);
    }

    public static function getPerformanceRound1($balance_start, $balance_current)
    {
        if ($balance_start == 0 || $balance_start === null) return 0;
        return round(($balance_current * 100) / $balance_start - 100, 1);
    }

    public static function getPerformanceRound3($balance_start, $balance_current)
    {
        if ($balance_start == 0 || $balance_start === null) return 0;
        return round(($balance_current * 100) / $balance_start - 100, 3);
    }

    public static function getPerformanceFormat($balance_start, $balance_current)
    {
        if ($balance_start == 0 || $balance_start === null) return 0;
        return number_format(($balance_current * 100) / $balance_start - 100, 2);
    }

    public static function getPerformanceNoFormat($balance_start, $balance_current)
    {
        if ($balance_start == 0 || $balance_start === null) return 0;
        return ($balance_current * 100) / $balance_start - 100;
    }

    public static function getBalancesInfo($id, $period = 'today')
    {
        ini_set('memory_limit','-1');
        switch ($period) {
            case 'today':
                $date = 'today';
                break;
            case '7d': {
                $date = time() - 604800;
                break;
            }
            case '1m': {
                $date = time() - 2628000;
                break;
            }
            case '1y': {
                $date = time() - 31540000;
                break;
            }
            case 'YTD': {
                $date = mktime(23, 59, 59, 01, 01, date('Y'));
                break;
            }
            case 'ALL': {
                $date = 1;
                break;
            }
            default:
                $date = 0;
        }

        $balance_current = Whale::where('id', $id)->first(['balance_current']);

        $balances = Balance::all()->where('holder_id', $id)->where('time', '>=', $date)->sortBy('time');
        $balanceInfo = [];
        foreach ($balances as $balance) {
            $dateInfo = getdate($balance->time);
            $label = $dateInfo['mday'] . " " . substr($dateInfo['month'], 0, 3);
            $balanceBillions = round($balance->balance / 1000000000, 5);
            $balanceInfo[] = [$label, $balanceBillions];
        }
        $balanceBillions = round($balance_current->balance_current / 1000000000, 5);
        $balanceInfo[] = ['Today', $balanceBillions];
        return json_encode($balanceInfo);
    }

    public static function getPerformanceByPeriod($balance_current, $id, $period = 'today')
    {
//        if ($balance_current == 0 || $balance_current === null) return 0;
        switch ($period) {
            case 'today':
                $date = time() - 86400;
//                $date = 'today';
                break;
            case 'week': {
                $date = time() - 604800;
                break;
            }
            case 'month': {
                $date = time() - 2628000;
                break;
            }
            case 'year': {
                $date = time() - 31540000;
                break;
            }
            case 'yeartd': {
                $date = mktime(23, 59, 59, 01, 01, date('Y'));
                break;
            }
            case 'all': {
                $date = 1;
                break;
            }
            default:
                $date = 0;
        }

//        if ($date == 'today') {
//            $balance = Whale::where('id', $id)->first(['balance_current']);
//            $balance_current = $balance->balance_current;
//        } else {
        $balance_start = Balance::where('holder_id', $id)->where('time', '>=', $date)->orderBy('time')->first(['balance']);
//        $balance_start = Balance::where('holder_id', $id)->where('time', '<=', $date)->orderBy('time', 'desc')->first(['balance']);
        if (empty($balance_start)) {
            $balance_start = Balance::where('holder_id', $id)->orderBy('time')->first(['balance']);
        }
        if (empty($balance_start)) {
            return 0;
        }
        $balance_start = $balance_start->balance;
//        }
//        echo "CURRENT = ".$balance_current."<br>";
//        echo "START = ".$balance_start."<br>";
        if ($balance_start == 0) {
            return 0;
        }
        return round(($balance_current * 100) / $balance_start - 100, 1);
    }

    public static function getBalanceByMillion($balance_current)
    {
        return (int)($balance_current / 1000000) > 100 ? number_format($balance_current / 1000000) : number_format($balance_current / 1000000, 2);
    }

    public static function getLastActive($lastActive)
    {
        if ($lastActive == 0) {
            return "N/A";
        }
        $lastActive = time() - $lastActive;
        $years = (int)($lastActive / 31540000);
        $months = (int)($lastActive / 2628000) % 12;
        $weeks = (int)($lastActive / 604800) % 52 - 4 * $months;
        $days = (int)($lastActive / 86400) % 7;
        $hours = (int)($lastActive / 3600) % 24;
        $minutes = (int)($lastActive / 60) % 60;

        if ($years != 0 && $months == 0 && $weeks == 0 && $days == 0) $last = $years . "y";
        elseif ($years != 0 && $months != 0 && $weeks == 0 && $days == 0) $last = $years . "y " . $months . "mo";
        elseif ($years != 0 && $months == 0 && $weeks != 0) $last = $years . "y " . $weeks . "w";
        elseif ($years != 0 && $months == 0 && $weeks == 0) $last = $years . "y " . $days . "d";
        elseif ($months != 0 && $weeks == 0 && $days == 0  && $hours != 0) $last = $months . "mo " . $hours . "h";
        elseif ($months != 0 && $weeks == 0 && $days == 0) $last = $months . "mo";
        elseif ($months != 0 && $weeks != 0 && $days == 0) $last = $months . "mo " . $weeks . "w";
        elseif ($months != 0 && $weeks != 0 && $days != 0) $last = $months . "mo " . $weeks . "w " . $days . "d";
        elseif ($months != 0 && $weeks == 0 && $days != 0) $last = $months . "mo " . $days . "d";
        elseif ($weeks != 0 && $days == 0  && $hours != 0) $last = $weeks . "w " . $hours . "h";
        elseif ($weeks != 0 && $days == 0) $last = $weeks . "w";
        elseif ($weeks != 0 && $days != 0) $last = $weeks . "w " . $days . "d";
        elseif ($days != 0 && $hours == 0 && $minutes == 0) $last = $days . "d";
        elseif ($days != 0 && $hours != 0 && $minutes == 0) $last = $days . "d " . $hours . "h";
        elseif ($days != 0 && $hours != 0 && $minutes != 0) $last = $days . "d " . $hours . "h " . $minutes . "min";
        elseif ($hours != 0 && $minutes == 0) $last = $hours . "h";
        elseif ($hours != 0 && $minutes != 0) $last = $hours . "h " . $minutes . "min";
        elseif ($minutes != 0) $last = $minutes . "min";
        else $last = "< 1min";

        return $last;
    }


    public static function getGainers($period)
    {
        $column = General::getColumn($period);
        $whales = Whale::all()->sortByDesc($column)->slice(0, 5);
        $gainers = [];
        $index = 0;
        foreach ($whales as $whale) {
            $gainers[$index]['id'] = $whale->id;
            $gainers[$index]['name'] = $whale->name;
            $gainers[$index]['balance'] = $whale->balance_current;
            $gainers[$index]['performance'] = $whale->$column;
            $index++;
        }
        return $gainers;
    }

    public static function getLosers($period)
    {
        $column = General::getColumn($period);
        $whales = Whale::all()
            ->where('balance_start', '<>', null)
            ->where('balance_current', '>', 500000)
            ->sortBy($column)->slice(0, 5);
        $losers = [];
        $index = 0;
        foreach ($whales as $whale) {
            $losers[$index]['id'] = $whale->id;
            $losers[$index]['name'] = $whale->name;
            $losers[$index]['balance'] = $whale->balance_current;
            $losers[$index]['performance'] = $whale->$column;
            $index++;
        }
        return $losers;
    }

    public static function getWhale($id)
    {
        return Whale::where('id', $id)->first()->toArray();
    }

    public static function getTransfers($id)
    {
        define('ETHPLORER_API_KEY2', 'gsuh40102enmvnM55');
        $whale = Whale::where('id', $id)->first();
        $url = "https://api.ethplorer.io/getAddressHistory/" . $whale->holder . "?apiKey=" . ETHPLORER_API_KEY2 . "&type=transfer&limit=20&timestamp=" . time();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output, true);
        $data = [];
        $index = 0;
        if (!empty($output['operations'])) {
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
                                $data[$index]['image'] = 'token-placeholder.svg';
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
                                $data[$index]['image'] = 'token-placeholder.svg';
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
                        $data[$index]['image'] = 'token-placeholder.svg';
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
                        $data[$index]['image'] = 'token-placeholder.svg';
                        $index++;
                    }
                }
            }
        }
        return $data;
    }

    public function holder()
    {
        return $this->hasMany('App\Models\Holder', 'holder_id');
    }
}