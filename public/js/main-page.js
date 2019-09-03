$(document).ready(function () {
    changeWidth();
    $(window).resize(changeWidth);
    var thBalance = $(".ch3");
    var tdBalance = $(".c3");
    var thTop = $(".ch4");
    var tdTop = $(".c4");
    var thTokens = $(".ch5");
    var tdTokens = $(".c5");
    var thActive = $(".ch6");
    var tdActive = $(".c6");
    var thPercent = $(".ch7");
    var tdPercent = $(".c7");
    $(window).scroll(function () {
    // $('html, body').scroll(function () {
        var tableHeader = $('.table-absolute-header');
        var tableMain = $(".table-first");
        var tableMainOffset = tableMain.offset().top;
        var sticky = tableHeader.offset().top;
        if ($(window).scrollTop() >= sticky) {
            tableHeader.addClass("sticky");
        } else {
            tableHeader.removeClass("sticky");
        }
        // console.log(sticky);
    // console.log($(window).scrollTop());
    // console.log($("#app-navbar-collapse").height());
        if ($(window).scrollTop() <= tableMainOffset) {
        // if ($(window).scrollTop() <= sticky - $("#app-navbar-collapse").height()) {
            tableHeader.removeClass("sticky");
        }
    });

    // thBalance.width(tdBalance.width() + 10);
    // thTop.width(tdTop.width() + 10);
    // thTokens.width(tdTokens.width() + 10);
    // thActive.width(tdActive.width() + 10);
    // thPercent.width(tdPercent.width() + 10);

    // thTop.width(tdTop.css('width'));
    // thBalance.css('width', tdBalance.css('width'));
    // thTop.css('width', (tdTop.css('width') - 1));
    // // thTop.width(tdTop.width() + 10);
    // thTokens.css('width', tdTokens.css('width'));
    // thActive.css('width', tdActive.css('width'));
    // thPercent.css('width', tdPercent.css('width'));
    // console.log(thTop.width());
    // console.log(tdTop.width());
    // console.log(thTop.css('width'));
    // console.log(tdTop.css('width'));


    var tablesWrapper = $('.tables-wrapper');
    var top = tablesWrapper.offset().top;

    tablesWrapper.scroll(function () {
        var tableHeader = $('.table-absolute-header');
        var top = $(this).offset().top;
        var table = $(this).children('div').children('table');
        var tableTop = table.offset().top;
        var headerHeight = tableHeader.height();
        var relativeTableTop = tableTop - top - headerHeight;
        if (relativeTableTop < 0) {
            tableHeader.addClass('fix');
            table.css('margin-top', headerHeight);
            thBalance.width(tdBalance.width() + 10);
            thTop.width(tdTop.width() + 10);
            thTokens.width(tdTokens.width() + 10);
            thActive.width(tdActive.width() + 10);
            thPercent.width(tdPercent.width() + 10);
        } else {
            tableHeader.removeClass('fix');
            table.css('margin-top', 0);
        }
        tableHeader.css({
            'top': top
        });
    });
    $('.table-first').scroll(function () {
        var fLeftPos = $(this).children('table').offset().left;
        $('.table-first .table-absolute-header').css('left', fLeftPos);
    });
});

function changeWidth(){
    var table = $('.table-first').children('table');
    var tableBody = table.find('tbody');
    var tableHeader = $(table).parent().children('.table-absolute-header').find('thead');
    tableHeader.find('th').each(function(i, e){
       // $(e).width($($(tableBody).find('td')[i]).outerWidth());
       $(e).width($($(tableBody).find('td')[i]).width());
    });

    if ($(window).width() < 1200 && $(window).width() >= 970) {
        $(".span-percent").text("Percent");
    }
    if ($(window).width() < 992) {
        $(".span-balance").text("$(m)");
        $(".span-tokens").text("#Tkns");
        $(".span-percent").text("Percent");
        $(".span-top").text("Top Tokens");
        $(".span-active").text("Active");
    //     thBalance.width(tdBalance.width() + 10);
    // thTop.width(tdTop.width() + 10);
    // thTokens.width(tdTokens.width() + 10);
    // thActive.width(tdActive.width() + 10);
    // thPercent.width(tdPercent.width() + 10);

        // console.log(thTop.width());
    // console.log(tdTop.width());
    // console.log(thTop.css('width'));
    // console.log(tdTop.css('width'));


    // thTop.width(tdTop.css('width'));
    // thBalance.css('width', tdBalance.css('width'));
    // thTop.css('width', (tdTop.css('width')));
    // thTokens.css('width', tdTokens.css('width'));
    // thActive.css('width', tdActive.css('width'));
    // thPercent.css('width', tdPercent.css('width'));


        // console.log(thTop.width());
    // console.log(tdTop.width());
    // console.log(thTop.css('width'));
    // console.log(tdTop.css('width'));
    }
}
