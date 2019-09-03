<?php
use App\Models\Whale;
?>
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="token-icon"><img src="/public/images/token/{{$token->image}}" alt="Icon"></div>
            <div class="token-name">
                <h1>{{$token->name}}</h1>
                <span class="sub-title pull-left">ERC20</span>
                <span class="sub-title pull-right"><a href="{{$token->website}}">website</a></span>
            </div>
        </div>
        <div class="row row-token-info">
            <div class="token-column">
                <div class="token-title">Market Cap</div>
                <div class="token-info">${{number_format($token->market_cap_usd)}}</div>
            </div>
            <div class="token-column">
                <div class="token-title">Volume</div>
                <div class="token-info">${{number_format($token->volume_usd)}}</div>
            </div>
            <div class="token-column">
                <div class="token-title">Circulating Suply</div>
                <div class="token-info">{{number_format($token->available_supply)}}</div>
            </div>
            <div class="token-column">
                <div class="token-title">Total Supply</div>
                <div class="token-info">{{number_format($token->total_supply)}}</div>
            </div>
        </div>
        <div class="row token-pagination">
            <div class="pull-left">
                <span class="token-last-updated">Last updated: {{date('m/d/y g:ia', (time() - 2000))}}</span>
            </div>
            <div class="select-top pull-right">
                {{$holders->links()}}
                <select name="view" id="viewList">
                    <option value="20">View 20</option>
                    <option value="30">View 30</option>
                    <option value="40">View 40</option>
                    <option value="50">View 50</option>
                    <option value="100">View 100</option>
                </select>
            </div>
        </div>
        <div class="panel panel-default">
        <div class="table-responsive">
            <table class="table-token table table-bordered table-hover table-condensed ">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Top Holder</th>
                    <th>Token Balance</th>
                    <th>Percent Holdings</th>
                    <th>Date Added</th>
                    <th>Hold Period</th>
                    <th>Performance</th>
                    <th class="only-alerts"><a href=""><i class="far fa-bell"></i></a></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $page = 1;
                if (isset($_SERVER['argv'][0])) {
                    if (strpos($_SERVER['argv'][0], 'page=') !== false) {
                        $page = (int)str_replace('page=', '', $_SERVER['argv'][0]);
                    }
                }
                $index = 1 + ($page - 1) * $view;
                ?>
                @foreach($holders as $holder)
                    <tr>
                        <td>{{$index}}</td>
                        <td id="w{{$holder->id}}"><a
                                    href="/whale/{{$holder->holder_id}}">{{\App\Models\Whale::getName($holder->holder_id)}}</a>
                        </td>
                        <td>${{number_format($holder->balance_current)}}</td>
                        <td>{{round(($holder->quantity * 100) / $token->total_supply, 1)}}%</td>
                        <td>{{date('m/d/y', $holder->time_added)}}</td>
                        <td>{{\App\Models\Whale::getLastActive($holder->time_added)}}</td>
                        @if(Whale::getPerformance($holder->balance_start, $holder->balance_current) >= 0)
                            <td class="high-performance">{{Whale::getPerformance($holder->balance_start, $holder->balance_current)}}
                                %
                            </td>
                        @else
                            <td class="low-performance">{{Whale::getPerformance($holder->balance_start, $holder->balance_current)}}
                                %
                            </td>
                        @endif
                        <td id="a{{$holder->holder_id}}" class="table-icons alerts"><a href="/create-alert"><i class="far fa-bell"></i></a></td>
                    </tr>
                    <?php $index++; ?>
                @endforeach
                </tbody>
            </table>
        </div>
        </div>
    </div>
@endsection
