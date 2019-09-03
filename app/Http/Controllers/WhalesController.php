<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Whale;
use App\Models\Holder;
use App\Models\Addedtransfer;
use App\Models\Removedtransfer;
use App\Models\Transfer;

class WhalesController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id = 1)
    {
        ini_set('memory_limit','-1');
        if (Whale::where('id', $id)->count() == 0) {
            return redirect()->action('MainController@index');
        }
        return view('whale', [
            'whale' => Whale::getWhale($id),
            'data' => Holder::getHolder($id),
//            'transfers' => Transfer::getTransfers($id),
            'transfers' => Whale::getTransfers($id),
//            'added' => Addedtransfer::getAddedTransfers($id),
//            'removed' => Removedtransfer::getRemovedTransfers($id)
        ]);
    }
}
