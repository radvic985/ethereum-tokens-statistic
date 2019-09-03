<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alert;
use App\Models\General;
use Illuminate\Support\Facades\Auth;

class AlertsController extends Controller
{
    public function index($success = 'no', Request $request)
    {
        $param = $request->all();
        if (isset($param['success'])) {
            $success = $param['success'];
        }
        return view('alerts', [
            'alerts' => Alert::getUserAlerts(Auth::id()),
            'success' => $success,
        ]);
    }

    public function create()
    {
        return view('create-alert');
    }

    public function searchWhale(Request $request)
    {
        $parameters = $request->all();
        if (isset($parameters['term'])) {
            return json_encode(General::searchWhale($parameters['term']));
        }
    }

    public function searchHeader(Request $request)
    {
        $parameters = $request->all();
        if (isset($parameters['term'])) {
            return json_encode(General::searchHeader($parameters['term']));
        }
    }

    public function searchToken(Request $request)
    {
        $parameters = $request->all();
        if (isset($parameters['term'])) {
            return json_encode(General::searchToken($parameters['term']));
        }
    }

    public function save(Request $request)
    {
        Alert::saveAlerts(Auth::id(), $request->all());
        return redirect()->action('AlertsController@index', ['success' => 'yes']);
    }
}
