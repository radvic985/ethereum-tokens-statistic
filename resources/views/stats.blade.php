@extends('layouts.app')
{{--<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>--}}
{{--<script defer src="/public/js/stats-diagrams.js"></script>--}}
@section('content')
    <div class="container">
        <div class="title-head row padded-row">
            <h1 class="head-stats pull-left">Market Overview</h1>
            <div class="gainers-top pull-right">
                <span id="last-updated">Last updated: {{date('d/m/y g:ia', (time() - 60 * random_int(2,15)))}}</span>
                <select name="view" id="show-data">
                    <option value="today">Last 24 Hours</option>
                    <option value="week">Week</option>
                    <option value="month">Month</option>
                    <option value="year">Year</option>
                    <option value="yeartd">Year to date</option>
                    <option value="all">All time</option>
                </select>
            </div>
        </div>
        <div class="row padded-row">
            <div class="col-md-6">
                <h2>Top Gainers</h2>
                <div class="panel panel-default">
                <div class="table-responsive">
                    <table class="table-whale table table-bordered table-hover table-condensed">
                        <thead>
                        <tr>
                            <th>Whale</th>
                            <th>Performance</th>
                            <th>Final Balance</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($gainers as $whale)
                            <tr>
                                <td id="w{{$whale['id']}}">
                                    <a href="/whale/{{$whale['id']}}">{{$whale['name']}}</a>
                                </td>
                                <td class="high-performance">{{number_format($whale['performance'], 1)}}%</td>
                                <td>${{number_format($whale['balance'], 2)}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
            <div class="col-md-6">
                <h2>Top Losers</h2>
                 <div class="panel panel-default">
                <div class="table-responsive">
                    <table class="table-whale table table-bordered table-hover table-condensed">
                        <thead>
                        <tr>
                            <th>Whale</th>
                            <th>Performance</th>
                            <th>Final Balance</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($losers as $whale)
                            <tr>
                                <td id="w{{$whale['id']}}">
                                    <a href="/whale/{{$whale['id']}}">{{$whale['name']}}</a>
                                </td>
                                <td class="low-performance">{{number_format($whale['performance'], 1)}}%</td>
                                <td>${{number_format($whale['balance'], 2)}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
        <div class="row padded-row">
            <div class="col-md-12">
                <h2>Most Active Tokens</h2>
                 <div class="panel panel-default">
                <div class="table-responsive">
                    <table class="most-active table-whale table table-bordered table-hover table-condensed">
                        <thead>
                        <tr>
                            <th>#</th>
                            {{--<th>In/Out</th>--}}
                            <th></th>
                            <th>Token</th>
                            <th>Quantity</th>
                            <th>Value in USD</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $iterator = 1;?>
                        @foreach($tokens as $token)
                            @if($iterator <= 5)
                                <tr class="all-tr tr-visible">
                            @else
                                <tr class="all-tr tr-expand tr-hidden">
                                    @endif
                                    <td><?=($iterator)?></td>
                                    {{--@if($token->in_out == 0)--}}
                                        {{--<td><span class="token-out">OUT</span></td>--}}
                                    {{--@elseif($token->in_out == 1)--}}
                                        {{--<td><span class="token-in">IN</span></td>--}}
                                    {{--@endif--}}
                                    <td class="td-whale-icon">
                                        <img class="whale-icon" src="/public/images/token/{{$token->image}}" alt="Icon">
                                    </td>
                                    <td><a href="/token/{{$token->id}}">{{$token->symbol}}</a></td>
                                    <td>{{number_format($token->quantity, 2)}}</td>
                                    <td>${{number_format($token->balance)}}</td>
                                </tr>
                                <?php $iterator++;?>
                                @endforeach
                                <input type="hidden" id="iterator" value="<?=$iterator?>">
                                <tr>
                                    <td class="expand-all" colspan="5">
                                        <span class="expand-text">Expand All</span>
                                        <span class="collapse-text">Collapse</span>
                                    </td>
                                </tr>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
        <div class="row padded-row">
            <div class="col-md-12">
                <h2>Popular Tokens</h2>
                <p>Tokens with higher popularity as a percentage of accounts that hold that token may indicate
                    investment growth potential.
                </p>
                 <div class="panel panel-default">
                <div class="table-responsive">
                    <table class="popular-tokens table-whale table table-bordered table-hover table-condensed">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th></th>
                            <th>Token</th>
                            <th>% of All Whales Holding</th>
                            <th>% Controlled by Whales</th>
                            <th>Largest Holder</th>
                            <th>% of Portfolio</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $iterator2 = 1;?>
                        @foreach($popular as $token)
                            @if($iterator2 <= 10)
                                <tr class="all-tr tr-visible">
                            @else
                                <tr class="all-tr tr-expand tr-hidden">
                                    @endif
                                    <td><?=($iterator2)?></td>
                                    <td class="td-whale-icon">
                                        <img class="whale-icon" src="/public/images/token/{{$token->image}}" alt="Icon">
                                    </td>
                                    <td><a href="/token/{{$token->token_id}}">{{$token->token_name}}</a></td>
                                    <td>{{number_format($token->percent_all, 1)}}%</td>
                                    <td>{{number_format($token->percent_controlled, 1)}}%</td>
                                    <td>
                                        <a href="/whale/{{$token->largest_holder_id}}">{{$token->largest_holder_name}}</a>
                                    </td>
                                    <td>{{$token->percent_portfolio}}%</td>
                                </tr>
                                <?php $iterator2++;?>
                                @endforeach
                                <input type="hidden" id="iterator2" value="<?=$iterator2?>">
                                <tr>
                                    <td class="expand-all" colspan="7">
                                        <span class="expand-text">Expand All</span>
                                        <span class="collapse-text">Collapse</span>
                                    </td>
                                </tr>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/public/js/stats.js"></script>
@endsection
