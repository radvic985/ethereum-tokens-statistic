$(document).ready(function () {
    var input1 = $(".autocomplete1");
    input1.autocomplete({
        source: "search-whale",
        open: function (event, ui) {
            input1.autocomplete("widget").css({
                "width": input1.css('width')
            });
        },
        minLength: 1
    });
    var input2 = $(".autocomplete2");
    input2.autocomplete({
        source: "search-token",
        open: function (event, ui) {
            input2.autocomplete("widget").css({
                "width": input2.css('width')
            });
        },
        minLength: 1
    });

    $('input[type=radio][name=optradio]').change(function () {
        $(".input-autocomplete").hide();
        var index = $(this).prop('id').replace('radio', '');
        $("#option" + index).show();
    });

    $("input[name=inc-dec-least]").focus(function () {
        $(this).prop('placeholder', '').css({'font-size': '20px', 'padding': '0', 'padding-left': '5px'});
    });
});