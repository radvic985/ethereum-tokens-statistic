<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public static function getUserAlerts($id)
    {
        return Alert::all()->where('user_id', $id);
    }

    public static function saveAlerts($id, $result)
    {
        if (isset($result['optradio'])) {
            $step = $result['optradio'];
            switch ($step) {
                case 1:
                    Alert::alert1($result['whale1'], $result['count1'], $id, $result['notificationradio']);
                    break;
                case 2:
                    $choice = $result['step2'];
                    switch ($choice) {
                        case 'least':
                            $percent = 'l,' . $result['least'];
                            break;
                        case 'top':
                            $percent = 't,' . $result['top'];
                            break;
                        case 'all':
                            $percent = 'a,' . 100;
                            break;
                        default:
                            $percent = 'a,' . 100;
                    }
                    Alert::alert2($result['token2'], $percent, $id, $result['notificationradio']);
                    break;
                case 3:
                    Alert::alert3($result['whale3'], $result['token3'], $id, $result['notificationradio']);
                    break;
                case 4:
                    $balance_current = Whale::where('name', $result['whale4'])->where('balance_current', '<>', null)->first();
                    $value = 1;
                    if ($result['inc-dec'] == 'inc') {
                        $value = $result['inc-dec-least'];
                    }
                    if ($result['inc-dec'] == 'dec') {
                        if ($result['inc-dec-least'] > 100) {
                            $value = -100;
                        } else {
                            $value = -$result['inc-dec-least'];
                        }
                    }
                    $value = $value . "," . $balance_current->balance_current;
                    Alert::alert4($result['whale4'], $value, $id, $result['notificationradio']);
                    break;
            }
        }
    }


    public static function alert1($whale, $count, $id, $notification)
    {
        $whale_id = Whale::where('name', $whale)->first(['id']);
        $alert = new Alert();
        $alert->user_id = $id;
        $alert->type_id = 1;
        $alert->whale_token = $whale_id->id;
        $alert->val_per_token = $count;
        $alert->type_notification = $notification;
        $alert->time_created = time();
        $alert->time_updated = time();
        $alert->message_not_done = "<a href='/whale/" . $whale_id->id . "'>" . $whale . "</a>'s every move, any tokens added or removed worth over $" . number_format($count, 2) . " USD";
        $alert->save();
    }

    public static function alert2($token, $percent, $id, $notification)
    {
        $token_id = Token::where('name', $token)->first(['id']);
        $alert = new Alert();
        $alert->user_id = $id;
        $alert->type_id = 2;
        $alert->whale_token = $token_id->id;
        $alert->val_per_token = $percent;
        $alert->type_notification = $notification;
        $alert->time_created = time();
        $alert->time_updated = time();
        if ($percent[0] == 'l') {
            $alert->message_not_done = "<a href='/token/" . $token_id->id . "'>" . $token . "</a> is added or removed by at least " . substr($percent, 2) . "% of whales";
        }
        if ($percent[0] == 't') {
            $alert->message_not_done = "<a href='/token/" . $token_id->id . "'>" . $token . "</a> is added or removed by top " . substr($percent, 2) . " of whales";
        }
        if ($percent[0] == 'a') {
            $alert->message_not_done = "<a href='/token/" . $token_id->id . "'>" . $token . "</a> is added or removed by all whales";
        }
        $alert->save();
    }

    public static function alert3($whale, $token, $id, $notification)
    {
        $whale_id = Whale::where('name', $whale)->first(['id']);
        $token_id = Token::where('name', $token)->first(['id']);
        $alert = new Alert();
        $alert->user_id = $id;
        $alert->type_id = 3;
        $alert->whale_token = $whale_id->id;
        $alert->val_per_token = $token_id->id;
        $alert->type_notification = $notification;
        $alert->time_created = time();
        $alert->time_updated = time();
        $alert->message_not_done = "<a href='/whale/" . $whale_id->id . "'>" . $whale . "</a> added or removed <a href='/token/" . $token_id->id . "'>" . $token . "</a> to his portfolio";
        $alert->save();
    }

    public static function alert4($whale, $percent, $id, $notification)
    {
        $whale_id = Whale::where('name', $whale)->first(['id']);
        $alert = new Alert();
        $alert->user_id = $id;
        $alert->type_id = 4;
        $alert->whale_token = $whale_id->id;
        $alert->val_per_token = $percent;
        $alert->type_notification = $notification;
        $alert->time_created = time();
        $alert->time_updated = time();
        $per = explode(',', $percent);
        if ($per[0] >= 0) {
            $alert->message_not_done = "<a href='/whale/" . $whale_id->id . "'>" . $whale . "</a>'s portfolio increased by at least " . $per[0] . "%";
        } else {
            $alert->message_not_done = "<a href='/whale/" . $whale_id->id . "'>" . $whale . "</a>'s portfolio decreased by at least " . $per[0] . "%";
        }
        $alert->save();
    }

    public static function getAlertsCount($id)
    {
        return Alert::where('user_id', $id)->where('is_send', 1)->where('is_view', 0)->count();
    }

    public static function getActiveAlerts($id)
    {
        return Alert::all()->where('user_id', $id)->where('is_send', 1);
    }

    public static function getActiveAlertsForView($id)
    {
        return Alert::all()->where('user_id', $id)->where('is_send', 1)->where('is_view', 0);
    }

}