<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.10/css/all.css"
          integrity="sha384-+d0P83n9kaQMCwj8F4RJB66tzIwOKmrdb46+porD/OvrJ+37WqIM7UoBtwHO6Nlg" crossorigin="anonymous">
    <link rel="stylesheet" href="/public/css/jquery-ui.css">
    <link href="{{ asset('css/normalize.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/main-layout.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto+Mono" rel="stylesheet">
    <script src="/public/js/jquery.min.js"></script>
    <script defer src="/public/js/jquery-ui.js"></script>
    <script defer src="/public/js/jquery.fixedheadertable.min.js"></script>
    <!-- Hotjar Tracking Code for https://whalewatchapp.com/ -->
    <script>
        (function(h,o,t,j,a,r){
            h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
            h._hjSettings={hjid:895807,hjsv:6};
            a=o.getElementsByTagName('head')[0];
            r=o.createElement('script');r.async=1;
            r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
            a.appendChild(r);
        })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
    </script>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-119980905-1"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'UA-119980905-1');
    </script>
</head>
<?php
use Illuminate\Support\Facades\Auth;
function get_gravatar( $email, $s = 50, $d = 'identicon', $r = 'g', $img = false, $atts = array() ) {
    $url = 'https://www.gravatar.com/avatar/';
    $url .= md5( strtolower( trim( $email ) ) );
    $url .= "?s=$s&d=$d&r=$r";
    if ( $img ) {
        $url = '<img src="' . $url . '"';
        foreach ( $atts as $key => $val )
            $url .= ' ' . $key . '="' . $val . '"';
        $url .= ' />';
    }
    return $url;
}
?>
<body>
<div id="app">
    <nav class="navbar navbar-default navbar-static-top">

        <div class="container-fluid">
                    <div class="navbar-header">

            <!-- Collapsed Hamburger -->
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#app-navbar-collapse">
                <span class="sr-only">Toggle Navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

            <!-- Branding Image -->
            <a class="navbar-brand" title="Whale Watch" href="{{ url('/') }}"><img src="/public/images/temp-logo.png" alt="Whale Watch"/>
            </a>
        </div>
            <div class="collapse navbar-collapse" id="app-navbar-collapse">
                <!-- Left Side Of Navbar -->
                <ul class="nav navbar-nav">
                    &nbsp;
                    <li>
                        <input id="header_search" type="text" class="" placeholder="Search tokens or whales">
                        <div class="error-message">Sorry, token or whale not found.</div>
                    </li>
                </ul>

                <!-- Right Side Of Navbar -->
                <ul class="nav navbar-nav navbar-right">
                    <li class="menu-icons"><a href="/">Whales</a></li>
                    <li class="menu-icons"><a href="/stats">Market Overview</a></li>

                    <!-- Authentication Links -->
                    @if (Auth::guest())
                        <li class="menu-icons"><a href="/alerts">Alerts</a></li>

                        <li class="login">
                            <a href="{{ route('login') }}"><img src="/public/images/default_user.png" class="user-avatar"> Log In</a>
                        </li>
                        {{--<li><a href="{{ route('register') }}">Register</a></li>--}}
                    @else
                        <li class="menu-icons alerts-li">
                            <a href="/alerts">Alerts</a>
                            {{--<a href="#" >--}}
                            {{--<span class="alert-number" data-toggle="popover" data-trigger="focus" data-placement="bottom" data-html="true"--}}
                            {{--data-content="Click anywhere in the document to close this popover">--}}
                            {{--<span class="alert-number" data-toggle="popover" data-trigger="focus"--}}
                            {{--data-content="Click anywhere in the document to close this popover">--}}
                            @if(\App\Models\Alert::getAlertsCount(\Illuminate\Support\Facades\Auth::id()) !=0)
                            <span class="alert-number">
                                    {{\App\Models\Alert::getAlertsCount(\Illuminate\Support\Facades\Auth::id())}}
                                </span>
                            @endif
                            <div id="alerts-popover" style="display: none">
                                <table class="table-alerts table table-striped table-bordered table-hover table-condensed">
                                    <thead>
                                    <tr>
                                        <th class="alert-th">
                                            <div class="pull-left">ALERTS</div>
                                            <div class="pull-right">
                                                <span class="span-plus">+</span>
                                                <button class="close-popover btn btn-danger btn-xs">Close &times;</button>
                                            </div>
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach(\App\Models\Alert::getActiveAlertsForView(\Illuminate\Support\Facades\Auth::id()) as $alert)
                                        <tr>
                                            <td>{!! $alert->message !!}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            {{--</a>--}}
                        </li>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                               aria-expanded="false">
                                <img src="{{get_gravatar(Auth::user()->email)}}" class="user-avatar">
                                {{--<img src="/public/images/user.png" class="user-avatar">--}}
                                {{ Auth::user()->name }} <span class="caret"></span>
                            </a>

                            <ul class="dropdown-menu" role="menu">
                                <li>
                                    <a href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        Logout
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                          style="display: none;">
                                        {{ csrf_field() }}
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>


    @yield('content')
</div>
    <footer>
        <div class="container">
        <div class="col-md-12"><ul><li>Copyright &copy; 2018 WhaleWatch<li><a href="mailto:contact@whalewatchapp.com">Contact</a></li><li><a href="https://t.me/joinchat/F3IhjRCTRbckO9HXFxgscw" target="_blank"><i class="fab fa-telegram"></i></a></li></ul></div>
        </div>
    </footer>

<!-- Scripts -->
<script>
    $(document).ready(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $(".close-popover").click(function () {
            var id = {
            'id': '{{\Illuminate\Support\Facades\Auth::id()}}'
        };
            $.post("ajax/update-alert-view", id);
            $(".alert-number").hide();
            $('#alerts-popover').hide();
        });
        $('#alerts-popover').scroll(function () {
            var tableHeader = $('.alert-th');
        var sticky = tableHeader.offset().top;
        if ($(this).offset().top + $(this).scrollTop() >= sticky) {
            tableHeader.addClass("alert-sticky");
        } else {
            tableHeader.removeClass("alert-sticky");
        }
//        if ($(window).scrollTop() <= 108) {
//            tableHeader.removeClass("sticky");
//        }
        });
        $(".span-plus").click(function () {
           location = '/create-alert';
        });
        $(".alert-number").click(function () {
//            $('#alerts-popover').show().height('400px');
            $('#alerts-popover').show();
//            $('#alerts-popover').show().css('max-height', '400px');
//            $(this).prop('data-content', $('#s1'));
//            $('[data-toggle="popover"]').popover("show");
        });
        $(".pagination a").prop("href", function () {
            var path = location.search.replace('?', '&');
            if (path.indexOf('&page=') == -1) {
                return $(this).prop('href') + location.search.replace('?', '&');
            }
            if (path.indexOf('&', 3) == -1) {
                return $(this).prop('href');
            }
            return $(this).prop('href') + path.substr(path.indexOf('&', 4));
        });

        var sortBy = $("#sort-by").val();
        var method = $("#method").val();
        var td_icon = $("#" + sortBy + " i");
        if (method == 'asc') {
            td_icon.removeClass('down');
            td_icon.addClass('up td-sort-active');
        }
        if (method == 'desc') {
            td_icon.removeClass('up');
            td_icon.addClass('down td-sort-active');
        }
        var viewList = $("#viewList");
        var showData = $("#show-data");

        var path = location.search;
        var params = ['view', 'period'];
        for (var i = 0; i < params.length; i++) {
            var index = path.indexOf(params[i]);
            if (index != -1) {
                var value = path.substr(index + params[i].length + 1);
                value = value.substr(0, value.indexOf('&') != -1 ? value.indexOf('&') : value.length);
                switch (i) {
                    case 0:
                        viewList.val(value);
                        break;
                    case 1:
                        showData.val(value);
                        break;
                }
            }
        }

        function changeParam(param, value) {
            var path = location.search;
            if (location.search.indexOf(param) == -1) {
                return location.search + '&' + param + '=' + value;
            }
            var positionBegin = path.indexOf(param) + param.length;
            var positionEnd = path.indexOf('&', positionBegin);
            if (positionEnd == -1) {
                return path.substr(0, positionBegin + 1) + value;
            }
            return path.substr(0, positionBegin + 1) + value + path.substr(positionEnd);
        }

        function setLocation(param, value) {
            if (location.search == '') {
                return location.pathname + '?' + param + '=' + value;
            }
            return location.pathname + changeParam(param, value);
        }

        viewList.change(function () {
            window.location = setLocation('view', $(this).val());
        });
        showData.change(function () {
            window.location = setLocation('period', $(this).val());
        });

        $(".only-favorites").click(function () {
            @if(Auth::check())
                location = '/favorite';
            @else
                    alert('Please, log in to show only favorites records!')
            @endif
        });

        $(".favorites").click(function () {

            @if(Auth::check())
            $(this).toggleClass('favorites-checked');
            $(this).children().toggleClass('far fa fa-lg');
            var whale_id = $(this).prop('id');
            whale_id = whale_id.substr(1);
            var user_id = "{{Auth::user()['id']}}";
            var ids = {
                'user_id': user_id,
                'whale_id': whale_id
            };
            $.post("ajax/favorite", ids);
            @else
                alert('Please, log in to add to the favorites!');
            @endif

        });

        $(".alerts").click(function () {
            @if(Auth::check())
                user_id = "{{Auth::user()['id']}}";
            whale_id = $(this).prop('id');
            whale_id = whale_id.substr(1);
            @else
                  alert("Please, log in!");
            @endif
        });

        var user_id = '';
        var whale_id = '';
        $(".notices").click(function () {
            @if(Auth::check())
                user_id = "{{Auth::user()['id']}}";
            whale_id = $(this).prop('id');
            whale_id = whale_id.substr(1);
            $("#notice-caption").text(" Notice for " + $("#w" + whale_id).text());
            if ($(this).hasClass('notices-checked')) {
                $("#notice-text").val($(this).find('input[type="hidden"]').val());
                $("#cancelButton").hide();
                $("#deleteButton").show();
            }
            else {
                $("#notice-text").val('Please, enter your notice');
                $("#deleteButton").hide();
                $("#cancelButton").show();
            }
            $(".notice").modal();
            var saveNoticeId = $(".save-notice");
            saveNoticeId.prop('id', 's' + whale_id);
            @else
                  alert("Please, log in!");
            @endif
        });

        $("#notice-text").focus(function () {
            $(this).css({'border': '1px solid rgb(169, 169, 169)', 'color': 'black'});
            if ($(this).val() == 'Please, enter your notice') {
                $(this).val('');
            }
        });

        $(".save-notice").click(function () {
            var noticeText = $("#notice-text");
            if (noticeText.val() != '') {
                var notice = {
                    'user_id': user_id,
                    'whale_id': whale_id,
                    'text': noticeText.val()
                };
                $.post("ajax/notice", notice, function (data) {
                    data = data.replace(/['"]+/g, '');
                    $("#n" + whale_id).find('input[type="hidden"]').val(data);
                });
                $(this).attr('data-dismiss', 'modal');
                var currentNotice = $("#n" + whale_id);
                currentNotice.addClass('notices-checked');
                currentNotice.children().addClass('fa fa-lg').removeClass('far');
            }
            else {
                noticeText.val('Please, enter your notice');
                noticeText.css({'color': 'red', 'border': '1px solid red'});
            }
        });

        $("#deleteButton").click(function () {
            var notice = {
                'user_id': user_id,
                'whale_id': whale_id,
                'delete': 'ok'
            };

            $.post("ajax/notice", notice, function (data) {
                var currentNotice = $("#n" + whale_id);
                currentNotice.removeClass('notices-checked');
                currentNotice.children().removeClass('fa fa-lg').addClass('far');
            });
        });

        var sort_td = $(".td-sort");
        sort_td.click(function () {
            $(this).find('i').toggleClass('down up');
            $(this).prop('id');
            var direction = '';
            if ($(this).find('i').eq(0).hasClass('up')) {
                direction = 'asc'
            }
            if ($(this).find('i').eq(0).hasClass('down')) {
                direction = 'desc'
            }
            window.location = window.location.pathname + '?' + $(this).prop('id') + '=' + direction;
        });

        var header_search = $("#header_search");
        header_search.autocomplete({
            source: "/search-header",
            open: function (event, ui) {
                header_search.autocomplete("widget").css({
                    "width": header_search.css('width')
                });
            },
            minLength: 2,
            select: function (event, ui) {
                header_search.val(ui.item.label);
                header_search.trigger('change');
            }
        }).change(function () {
            $.post("ajax/search", {'search': header_search.val()}, function (data) {
                if (data != 'ERROR1') {
                    location = data;
                } else {
                    header_search.addClass('search-error');
                    $(".error-message").show();
                }
            });
        });

        header_search.focus(function () {
            header_search.removeClass('search-error');
            $(".error-message").hide();
        });
        var search = $(".fa-search");
        search.click(function () {
        });
    });
</script>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
