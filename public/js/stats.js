$(document).ready(function () {
    var most = $(".most-active .expand-all");
    var popular = $(".popular-tokens .expand-all");
    if ($("#iterator").val() <= 5) {
        most.hide();
    }
    if ($("#iterator2").val() <= 10) {
        popular.hide();
    }
    most.click(function () {
        $(".most-active .tr-expand").toggleClass('tr-hidden');
        $(this).find('span').toggleClass('expand-text collapse-text');
    });
    popular.click(function () {
        $(".popular-tokens .tr-expand").toggleClass('tr-hidden');
        $(this).find('span').toggleClass('expand-text collapse-text');
    });
    $("#popularList").change(function () {
        $(this).find('option').prop('label', '');
        $(this).find('option[value="' + $(this).val() + '"]').prop('label', 'active').prop('selected', true);
        google.charts.load('current', {'packages': ['corechart']});
        google.charts.setOnLoadCallback(drawChart);
    });
});