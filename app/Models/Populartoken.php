<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Populartoken extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public static function getPopularTokens()
    {
        while (empty($popular = Populartoken::all())) {
            sleep(1);
        }
        return $popular;
    }
}
