<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Favorite;
use App\Models\General;
use App\Models\Notice;
use App\Models\Token;
use App\Models\Whale;
use Illuminate\Http\Request;

class AjaxController extends Controller
{
    public function favorite(Request $request)
    {
        $ids = $request->all();
        Favorite::createOrDelete($ids);
        return json_encode($ids);
    }

    public function notice(Request $request)
    {
        $notice = $request->all();
        if (isset($notice['delete'])) {
            Notice::deleteNotice($notice['user_id'], $notice['whale_id']);
            return json_encode($notice['delete']);
        }
        Notice::updateOrCreateNotice($notice['user_id'], $notice['whale_id'], $notice['text']);
        return json_encode($notice['text']);
    }

    public function deleteAlert(Request $request)
    {
        $id = $request->all();
        if (isset($id['id'])) {
            Alert::where('id', $id['id'])->delete();
        }
    }
    public function updateAlertView(Request $request)
    {
        $id = $request->all();
        if (isset($id['id'])) {
            Alert::where('user_id', $id['id'])->where('is_send', 1)->update([
                'is_view' => 1
            ]);
        }
    }

    public function search(Request $request)
    {
        $search = $request->all();
        return General::search($search);
    }

    public function performance(Request $request)
    {
        $parameters = $request->all();
        return Whale::getPerformanceByPeriod($parameters['balance_current'], $parameters['id'], $parameters['period']);
    }

    public function linechart(Request $request)
    {
        $parameters = $request->all();
//        echo "<pre>";
//        print_r(Whale::getBalancesInfo($parameters['id'], $parameters['period']));
//        echo "</pre>";
//        dd($parameters);
//        die;
        return Whale::getBalancesInfo($parameters['id'], $parameters['period']);
    }
}
