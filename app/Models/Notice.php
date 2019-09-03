<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public static function getNoticeCount($user_id, $id)
    {
        return Notice::where('user_id', $user_id)->where('whale_id', $id)->count();
    }

    public static function getNoticeText($user_id, $id)
    {
        $text = Notice::where('user_id', $user_id)->where('whale_id', $id)->first(['text']);
        return $text->text;
    }

    public static function deleteNotice($user_id, $whale_id)
    {
        Notice::where('user_id', $user_id)->where('whale_id', $whale_id)->delete();
    }

    public static function updateOrCreateNotice($user_id, $whale_id, $text)
    {
        Notice::updateOrCreate(
            ['user_id' => $user_id,
                'whale_id' => $whale_id],
            ['text' => $text]
        );
    }
}