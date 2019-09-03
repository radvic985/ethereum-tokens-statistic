<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holder extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public static function getHolder($id)
    {
        $holderTokens = Holder::all()->where('holder_id', $id)->where('quantity', '<>', 0);
        $data = [];
        $index = 0;
        foreach ($holderTokens as $holderToken) {
            $data[$index]['id'] = $holderToken->id;
            $data[$index]['token_id'] = $holderToken->token_id;
            $data[$index]['balance_start'] = $holderToken->balance_start;
            $data[$index]['balance_current'] = $holderToken->balance_current;
            $data[$index]['quantity'] = $holderToken->quantity;
            $token = Token::getToken($holderToken->token_id);
            $data[$index]['symbol'] = $token->symbol;
            $data[$index]['image'] = $token->image;
            $index++;
        }
        return $data;
    }
    public static function getHoldersByTokenPaginate($id, $view)
    {
        return Holder::where('token_id', $id)->orderBy('balance_current', 'desc')->paginate($view);
    }

    public function token()
    {
        return $this->belongsTo('App\Models\Token', 'id');
    }

    public function whale()
    {
        return $this->belongsTo('App\Models\Whale', 'id');
    }
}