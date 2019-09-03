<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public static function getTransfers($id)
    {
        $transfers = Transfer::all()->where('whale_id', $id)->sortByDesc('time_updated')->slice(0, 20);
        $data = [];
        if(!empty($transfers)) {
            $index = 0;
            foreach ($transfers as $transfer) {
                $data[$index]['id'] = $transfer->id;
                $data[$index]['token_id'] = $transfer->token_id;
                $data[$index]['is_added'] = $transfer->is_added;
                $data[$index]['token_symbol'] = $transfer->token_symbol;
                $data[$index]['value'] = $transfer->value;
                $data[$index]['quantity'] = $transfer->quantity;
                $data[$index]['time_updated'] = $transfer->time_updated;
                if($transfer->token_id == 0){
                    $data[$index]['image'] = '';
                }
                else{
                $token = Token::getToken($transfer->token_id);
                $data[$index]['image'] = $token->image;
                }
                $index++;
            }
        }
        return $data;
    }
}