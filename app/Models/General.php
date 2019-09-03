<?php

namespace App\Models;

class General
{
    public $timestamps = false;
    protected $guarded = [];

    public static function searchWhale($search)
    {
        $str = $search . "%";
        $whales = Whale::where('name', 'like', $str)->orderBy('name')->get();
        $response = [];
        foreach ($whales as $whale) {
            $response[] = $whale->name;
        }
        return $response;
    }

    public static function searchToken($search)
    {
        $str = "%" . $search . "%";
        $tokens = Token::where('name', 'like', $str)->where('symbol', '<>', null)->orderBy('name')->get();
        $response = [];
        foreach ($tokens as $token) {
            $response[] = $token->name;
        }
        return $response;
    }

    public static function searchHeader($search)
    {
        $response = [];
        if ($search == '0x') {
            return $response;
        }
        $str = $search . "%";
        $whale_names = Whale::where('name', 'like', $str)->orderBy('name')->pluck('name');
        $whale_names = $whale_names->all();
        $whale_addresses = Whale::where('holder', 'like', $str)->orderBy('holder')->pluck('holder');
        $whale_addresses = $whale_addresses->all();
        $token_names = Token::where('name', 'like', '%' . $str)->orderBy('name')->pluck('name');
        $token_names = $token_names->all();
        $response = array_merge($whale_names, $token_names, $whale_addresses);
        return $response;
    }

    public static function getData($period)
    {
        $whales = Whale::all()
            ->where('balance_start', '<>', 0)
            ->where('balance_current', '<>', null)
            ->where('id', '<>', 9221)
            ->sortByDesc('balance_current');
        $data = [];
        $index = 0;
        $column = General::getColumn($period);
        foreach ($whales as $whale) {
            $data[$index]['id'] = $whale->id;
            $data[$index]['name'] = $whale->name;
            $data[$index]['holder'] = $whale->holder;
            $data[$index]['balance'] = $whale->balance_current;
            $data[$index]['top_holdings'] = $whale->top_holdings;
            $data[$index]['total_tokens'] = $whale->total_tokens[0] == '&' ? substr($whale->total_tokens, 4) : $whale->total_tokens;
            $data[$index]['last_active'] = $whale->last_active;
            $data[$index]['performance'] = $whale->$column;
            $index++;
        }
        return $data;
    }

    public static function getColumn($period = 'today')
    {
        switch ($period) {
            case 'today':
                $column = 'percent_1';
                break;
            case 'week': {
                $column = 'percent_7';
                break;
            }
            case 'month': {
                $column = 'percent_30';
                break;
            }
            case 'year': {
                $column = 'percent_y';
                break;
            }
            case 'yeartd': {
                $column = 'percent_ytd';
                break;
            }
            case 'all': {
                $column = 'percent_all';
                break;
            }
            default:
                $column = 'percent_1';
        }
        return $column;
    }

    public static function getFavoriteData($period, $userId)
    {
        $data = [];
        $index = 0;
        $whaleIds = Favorite::all()->where('user_id', $userId)->pluck('whale_id')->toArray();
        $whales = Whale::whereIn('id', $whaleIds)->orderBy('balance_current', 'desc')->get();
        $column = General::getColumn($period);
        foreach ($whales as $whale) {
            $data[$index]['id'] = $whale->id;
            $data[$index]['name'] = $whale->name;
            $data[$index]['holder'] = $whale->holder;
            $data[$index]['balance'] = $whale->balance_current;
            $data[$index]['top_holdings'] = $whale->top_holdings;
            $data[$index]['total_tokens'] = $whale->total_tokens[0] == '&' ? substr($whale->total_tokens, 4) : $whale->total_tokens;
            $data[$index]['last_active'] = $whale->last_active;
            $data[$index]['performance'] = $whale->$column;
            $index++;
        }
        return $data;
    }

    public static function sortData($data, $params, &$sortBy, &$method)
    {
        if (isset($params['name'])) {
            if ($params['name'] == 'asc') {
                $sortBy = 'name';
                $method = $params['name'];
                usort($data, function ($a, $b) {
                    return strnatcmp($a["name"], $b["name"]);
                });
            }
            if ($params['name'] == 'desc') {
                $sortBy = 'name';
                $method = $params['name'];
                usort($data, function ($a, $b) {
                    return strnatcmp($b["name"], $a["name"]);
                });
            }
        } elseif (isset($params['balance'])) {
            if ($params['balance'] == 'asc') {
                $sortBy = 'balance';
                $method = $params['balance'];
                usort($data, function ($a, $b) {
                    return strnatcmp($a["balance"], $b["balance"]);
                });
            }
            if ($params['balance'] == 'desc') {
                $sortBy = 'balance';
                $method = $params['balance'];
                usort($data, function ($a, $b) {
                    return strnatcmp($b["balance"], $a["balance"]);
                });
            }
        } elseif (isset($params['total_tokens'])) {
            if ($params['total_tokens'] == 'asc') {
                $sortBy = 'total_tokens';
                $method = $params['total_tokens'];
                usort($data, function ($a, $b) {
                    return strnatcmp($a["total_tokens"], $b["total_tokens"]);
                });
            }
            if ($params['total_tokens'] == 'desc') {
                $sortBy = 'total_tokens';
                $method = $params['total_tokens'];
                usort($data, function ($a, $b) {
                    return strnatcmp($b["total_tokens"], $a["total_tokens"]);
                });
            }
        } elseif (isset($params['last_active'])) {
            if ($params['last_active'] == 'asc') {
                $sortBy = 'last_active';
                $method = $params['last_active'];
                usort($data, function ($a, $b) {
                    return strnatcmp($a["last_active"], $b["last_active"]);
                });
            }
            if ($params['last_active'] == 'desc') {
                $sortBy = 'last_active';
                $method = $params['last_active'];
                usort($data, function ($a, $b) {
                    return strnatcmp($b["last_active"], $a["last_active"]);
                });
            }
        } elseif (isset($params['performance'])) {
            if ($params['performance'] == 'asc') {
                $sortBy = 'performance';
                $method = $params['performance'];
                usort($data, function ($a, $b) {
                    return $a['performance'] - $b['performance'];
                });
            }
            if ($params['performance'] == 'desc') {
                $sortBy = 'performance';
                $method = $params['performance'];
                usort($data, function ($a, $b) {
                    return $b['performance'] - $a['performance'];
                });
            }
        }
        return $data;
    }

    public static function search($search)
    {
        $whale = Whale::where('name', $search['search'])->orWhere('holder', $search['search'])->first();
        if(!empty($whale)){
            return "/whale/" . $whale->id;
        }
        $token = Token::where('name', $search['search'])->first();
        if(!empty($token)){
            return "/token/" . $token->id;
        }
        return "ERROR1";
    }
}