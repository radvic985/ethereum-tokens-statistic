<?php

namespace App\Http\Controllers;

use App\Models\General;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function index(Request $request)
    {
        $params = $request->all();
        $sortBy = 'balance';
        $method = 'desc';
        $period = 'today';
        $view = 20;
        if (isset($params['view'])) {
            $view = $params['view'];
        }
        if (isset($params['period'])) {
            $period = $params['period'];
        }
        return view('main', [
            'whales' => $this->custom_paginate(General::sortData(General::getData($period), $params, $sortBy, $method), $view),
            'view' => $view,
            'sortBy' => $sortBy,
            'method' => $method
        ]);
    }

    private function custom_paginate($items, $perPage)
    {
        $pageStart = request('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $itemsForCurrentPage = array_slice($items, $offSet, $perPage, TRUE);
        return new LengthAwarePaginator(
            $itemsForCurrentPage, count($items), $perPage,
            Paginator::resolveCurrentPage(),
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    public function favorite(Request $request)
    {
        if (Auth::check()) {
            $params = $request->all();
            $sortBy = 'balance';
            $method = 'desc';
            $period = 'today';
            $view = 20;
            if (isset($params['view'])) {
                $view = $params['view'];
            }
            if (isset($params['period'])) {
                $period = $params['period'];
            }
            return view('favorite', [
                'whales' => $this->custom_paginate(General::sortData(General::getFavoriteData($period, Auth::user()->id), $params, $sortBy, $method), $view),
                'view' => $view,
                'sortBy' => $sortBy,
                'method' => $method
            ]);
        } else {
            return redirect('login');
        }
    }
}
