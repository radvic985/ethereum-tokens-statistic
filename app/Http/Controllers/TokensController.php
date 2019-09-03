<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Token;
use App\Models\Holder;

class TokensController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id = 1, Request $request)
    {
        $params = $request->all();
        $view = 20;
        if(isset($params['view'])){
            $view = $params['view'];
        }
        $token = Token::getToken($id);
        return view('token', [
            'holders' => Holder::getHoldersByTokenPaginate($token->id, $view),
            'token' => $token,
            'view' => $view
        ]);
    }
}
