<?php
use App\Models\Notice;
use App\Models\Whale;
use App\Models\Favorite;
use Illuminate\Support\Facades\Auth;
if (Auth::check()) {
    $user_id = Auth::user()['id'];
}
?>
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
        <div class="pull-left">
            <div class="main-title">
                <h1 class="hidden-xs, hidden-sm">Whale Watch</h1>
            </div>
        </div>
        <div class="pull-right">
                      <div class="pull-right time-selector">
                    <span>Show data for: </span>
                    <select name="view" id="show-data">
                        <option value="today">Today</option>
                        <option value="week">1 week</option>
                        <option value="month">1 month</option>
                        <option value="year">1 year</option>
                        <option value="yeartd">YTD</option>
                        <option value="all">All</option>
                    </select>
                </div>
        </div>
        </div>
        <div class="row main-row">
            <div class="tables-wrapper">
                <div class="absolute-table-wrapper">
                    <div class="table-absolute-header">
                        <table class="table-bordered">
                            <thead>
                            <tr>
                                <th class="ch1">#</th>
                                <th id="name" class="td-sort ch2">
                                    Whale
                                    <span class="sort-icon"><i class="fas fa-sort"></i></span>
                                </th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                    <?php
                    $page = 1;
                    if (isset($_SERVER['argv'][0])) {
                        if (strpos($_SERVER['argv'][0], 'page=') !== false) {
                            $page = (int)str_replace('page=', '', $_SERVER['argv'][0]);
                        }
                    }
                    $index = 1 + ($page - 1) * $view;
                    ?>
                    <table class="table-bordered">
                        <tbody>
                        @foreach($whales as $whale)
                            <tr>
                                <td class="c1">{{$index}}</td>
                                <td class="c2" id="w{{$whale['id']}}">
                                    <a href="/whale/{{$whale['id']}}">{{$whale['name']}}</a>
                                </td>
                            </tr>
                            <?php $index++; ?>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="table-first">
                    <div class="table-absolute-header">
                        {{--<table>--}}
                        <table class="table-bordered">
                            <thead>
                            <tr>
                                <th id="balance" class="td-sort ch3"><span class="span-balance">Balance(mil)</span>
                                    <span class="sort-icon">
                                        <i class="fas fa-sort"></i>
                                    </span>
                                </th>
                                <th class="ch4">
                                    <span class="span-top">Top Holdings</span>
                                </th>
                                <th id="total_tokens" class="td-sort ch5">
                                    <span class="span-tokens">Total Tokens</span>
                                    <span class="sort-icon">
                                        <i class="fas fa-sort"></i>
                                    </span>
                                </th>
                                <th id="last_active" class="td-sort ch6">
                                    <span class="span-active">Last Active</span>
                                    <span class="sort-icon">
                                        <i class="fas fa-sort"></i>
                                    </span>
                                </th>
                                <th id="performance" class="td-sort ch7">
                                    <span class="span-percent">Holdings Balance</span>
                                    <span class="sort-icon">
                                        <i class="fas fa-sort"></i>
                                    </span>
                                </th>
                                <th class="ch8"></th>
                                <th class="ch9"></th>
                                <th class="only-favorites ch10">
                                    @if(Auth::check())
                                        <a href="/favorite"><i class="far fa-heart"></i></a>
                                    @else
                                        <a href=""><i class="far fa-heart"></i></a>
                                    @endif
                                </th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                    <table class="table-whale main-table table-bordered table-hover table-condensed">
                        <tbody>
                        @foreach($whales as $whale)
                            <tr>
                                <td class="c3">{{\App\Models\Whale::getBalanceByMillion($whale['balance'])}}</td>
                                <td class="c4">{!!$whale['top_holdings']!!}</td>
                                <td class="c5"><?=$whale['total_tokens']?></td>
                                <td class="c6">{{\App\Models\Whale::getLastActive($whale['last_active'])}}</td>
                                @if($whale['performance'] >= 0)
                                    <td class="high-performance c7">{{number_format($whale['performance'], 1)}}%</td>
                                @else
                                    <td class="low-performance c7">{{$whale['performance']}}%</td>
                                @endif
                                @if (Auth::check())
                                    @if(\App\Models\Notice::getNoticeCount($user_id, $whale['id']) > 0)
                                        <td id="n{{$whale['id']}}" class="table-icons notices notices-checked c8"><i
                                                    class="fa fa-edit fa-lg"></i>
                                            <input type="hidden"
                                                   value="{{\App\Models\Notice::getNoticeText($user_id, $whale['id'])}}">
                                        </td>
                                    @else
                                        <td id="n{{$whale['id']}}" class="table-icons notices  c8"><i
                                                    class="far fa-edit"></i>
                                            <input type="hidden" value="">
                                        </td>
                                    @endif

                                    <td id="a{{$whale['id']}}" class="table-icons alerts c9"><a href="/create-alert"><i
                                                    class="far fa-bell"></i></a></td>

                                    @if(\App\Models\Favorite::getFavoritesCount($user_id, $whale['id']) > 0)
                                        <td id="f{{$whale['id']}}" class="table-icons favorites favorites-checked c10">
                                            <i
                                                    class="fa fa-lg fa-heart"></i></td>
                                    @else
                                        <td id="f{{$whale['id']}}" class="table-icons favorites c10"><i
                                                    class="far fa-heart"></i>
                                        </td>
                                    @endif
                                @else
                                    <td id="n{{$whale['id']}}" class="table-icons notices c8"><i
                                                class="far fa-edit"></i>
                                    </td>
                                    <td id="a{{$whale['id']}}" class="table-icons alerts c9"><i class="far fa-bell"></i>
                                    </td>
                                    <td id="f{{$whale['id']}}" class="table-icons favorites c10"><i
                                                class="far fa-heart"></i>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6 pull-right">
                <div class="select-top pull-right">
                    {{$whales->links()}}
                    <select name="view" id="viewList">
                        <option value="20">View 20</option>
                        <option value="30">View 30</option>
                        <option value="40">View 40</option>
                        <option value="50">View 50</option>
                        <option value="100">View 100</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal notice fade" id="noticeModal" role="dialog">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header" style="padding:5px 5px;">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <div class="title-modal"><i class="far fa-edit"></i><span id="notice-caption"></span></div>
                </div>
                <div class="modal-body" style="padding:10px">
                    <textarea name="notice-text" id="notice-text" rows="5">Please, enter your notice</textarea>
                </div>
                <div class="modal-footer">
                    <button id="s" type="button" class="btn save-notice btn-success pull-left"><i
                                class="far fa-save"></i>
                        Save
                    </button>
                    <button id="deleteButton" type="button" class="btn btn-danger pull-right" data-dismiss="modal"><i
                                class="fas fa-times"></i> Delete
                    </button>
                    <button id="cancelButton" type="submit" class="btn btn-danger pull-right" data-dismiss="modal"><i
                                class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    <input id="sort-by" type="hidden" value="{{$sortBy}}">
    <input id="method" type="hidden" value="{{$method}}">
    <script src="/public/js/main-page.js"></script>
@endsection
