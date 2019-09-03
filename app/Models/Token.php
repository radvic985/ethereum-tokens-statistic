<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public static function getMostActiveTokens()
    {
        return Token::where('total_supply_eth', '<>', 0)
            ->where('total_supply_eth', '<>', null)
            ->where('balance', '>', 0)
            ->orderBy('balance', 'desc')->get();
    }
    public static function getToken($id)
    {
        return Token::all()->where('id', $id)->first();
    }

    public function holder()
    {
        return $this->hasMany('App\Models\Holder', 'token_id');
    }
}