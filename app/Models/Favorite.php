<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public static function getFavoritesCount($user_id, $id)
    {
        return Favorite::where('user_id', $user_id)->where('whale_id', $id)->count();
    }

    public static function createOrDelete($ids)
    {
        if (Favorite::where('user_id', $ids['user_id'])->where('whale_id', $ids['whale_id'])->count() > 0) {
            Favorite::where('user_id', $ids['user_id'])->where('whale_id', $ids['whale_id'])->delete();
        } else {
            Favorite::insert([
                'user_id' => $ids['user_id'],
                'whale_id' => $ids['whale_id']
            ]);
        }
    }

}