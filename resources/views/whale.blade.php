<?php
define('HOLDER_GENERAL_ADDRESS_SITE', 'https://etherscan.io/address/');
?>
@extends('layouts.app')
<script src="/public/js/loader.js"></script>
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-4 pull-left">
                <h1 class="whale-name"><a
                            href="{{HOLDER_GENERAL_ADDRESS_SITE . $whale['holder']}}">{{$whale['name']}}</a></h1>
            </div>
            <div class="price-right col-md-8  pull-right">
                <div class="whale-la"><span>Last updated: {{date('m/d/y g:ia', $whale['last_active'])}}</span></div>
                <div class="whale-balance"><h1>${{number_format($whale['balance_current'],2)}}</h1></div>
                <div class="whale-performance">
                    <span>Portfolio Balance </span>
                    <select name="performance-view" id="performance-view-list">
                        <option value="today">Last 24 Hours</option>
                        <option value="week">Week</option>
                        <option value="month">Month</option>
                        <option value="year">Year</option>
                        <option value="yeartd">Year to date</option>
                        <option value="all">All time</option>
                    </select>
                    <input id="balance-current" type="hidden" value="{{$whale['balance_current']}}">
                    <input id="holder-id" type="hidden" value="{{$whale['id']}}">
                    <span id="performance"
                          class="performance"><?php echo number_format(\App\Models\Whale::getPerformanceByPeriod($whale['balance_current'], $whale['id'], 'today'), 1)?>
                        %</span>
                    <a href="https://twitter.com/share?ref_src=twsrc%5Etfw" class="twitter-share-button"
                       data-size="large" data-show-count="false">Tweet</a>
                    <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
                </div>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-3 pull-left">
                <h2>Portfolio Activity</h2>
            </div>
            <div class="col-md-7 pull-left">
                <span>Zoom</span>
                <button id="d7" type="button" class="btn bg-primary">7d</button>
                <button id="m1" type="button" class="btn">1m</button>
                <button id="y1" type="button" class="btn">1y</button>
                <button id="ytd" type="button" class="btn">YTD</button>
                <button id="all" type="button" class="btn">ALL</button>
            </div>
        </div>
        <div id="linechart">
            <div class="loader-wrapper">
                <div class="loader"></div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <ul class="nav nav-pills">
                    <li class="active"><a data-toggle="pill" href="#holdings">Top Holdings</a></li>
                    <li><a data-toggle="pill" href="#transfers">Transfers</a></li>
                </ul>
            </div>
            <div class="tab-content">
                <div id="holdings" class="col-md-12 tab-pane fade in active">
                    <div class="panel panel-default">
                        <div class="table-responsive">
                            <table class="table-top-holdings table table-bordered table-hover table-condensed">
                                <thead>
                                <tr class="all-tr">
                                    <th></th>
                                    <th>Token</th>
                                    <th>Quantity</th>
                                    <th>Value</th>
                                    <th>Position Growth</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $index = 1;?>
                                @foreach($data as $item)
                                    <tr class="all-tr tr-visible">
                                        <td class="td-whale-icon">
                                            <img class="whale-icon" src="/public/images/token/{{$item['image']}}"
                                                 alt="Icon">
                                        </td>
                                        <td><a href="/token/{{$item['token_id']}}">{{$item['symbol']}}</a></td>
                                        <td>{{number_format($item['quantity'], 3)}}</td>
                                        <td>${{number_format($item['balance_current'], 2)}}</td>
                                        @if(\App\Models\Whale::getPerformanceRound1($item['balance_start'], $item['balance_current']) >= 0)
                                            <td class="high-performance">{{\App\Models\Whale::getPerformanceRound1($item['balance_start'], $item['balance_current'])}}
                                                %
                                            </td>
                                        @else
                                            <td class="low-performance">{{\App\Models\Whale::getPerformanceRound1($item['balance_start'], $item['balance_current'])}}
                                                %
                                            </td>
                                        @endif
                                    </tr>
                                    <?php $index++;?>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div id="transfers" class="tab-pane fade">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="table-responsive">
                                <table class="table-top-holdings table table-bordered table-hover table-condensed">
                                    <thead>
                                    <tr class="all-tr">
                                        <th>#</th>
                                        <th>IN/OUT</th>
                                        <th></th>
                                        <th>Token</th>
                                        <th>Volume</th>
                                        <th>Value ($)</th>
                                        <th>Time</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $index = 1;?>
                                    @foreach($transfers as $item)
                                        <tr class="all-tr tr-visible">
                                            <td>{{$index}}</td>
                                            @if($item['is_added'] == 0)
                                                <td><span class="token-out">OUT</span></td>
                                            @elseif($item['is_added'] == 1)
                                                <td><span class="token-in">IN</span></td>
                                            @endif
                                            <td class="td-whale-icon">
                                                <img class="whale-icon" src="/public/images/token/{{$item['image']}}"
                                                     alt="Icon">
                                            </td>
                                            @if($item['token_id'] == 0)
                                                <td>{{$item['token_symbol']}}</td>
                                            @else
                                                <td><a href="/token/{{$item['token_id']}}">{{$item['token_symbol']}}</a>
                                                </td>
                                            @endif
                                            <td>{{number_format($item['quantity'], 2)}}</td>
                                            @if($item['value'] == -1)
                                                <td>N/A</td>
                                            @else
                                                <td>{{number_format($item['value'], 2)}}</td>
                                            @endif

                                            <td>{{date('m/d/y g:i:sa', $item['time_updated'])}}</td>
                                        </tr>
                                        <?php $index++;?>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/public/js/whale.js"></script>
@endsection