<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Infotoken extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public static function getInfoTokens()
    {
        while (empty($info = Infotoken::all()->where('is_cmc', 0))) {
            sleep(1);
        }
        $tokensInfo = [];
        foreach ($info as $item) {
            $tokensInfo[] = [$item->token, $item->percent];
        }
        return json_encode($tokensInfo);
    }

    public static function getInfoTokensCMC()
    {
        while (empty($info = Infotoken::all()->where('is_cmc', 1))) {
            sleep(1);
        }
        $tokensInfo = [];
        foreach ($info as $item) {
            $tokensInfo[] = [$item->token, $item->percent];
        }
        return json_encode($tokensInfo);
    }
}