$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    var performance = $("#performance");
    var number = performance.text().replace('%', '');
    if (parseFloat(number) < 0) {
        performance.addClass('bg-danger').removeClass('bg-success');
    }
    else {
        performance.addClass('bg-success').removeClass('bg-danger');
    }

    $("#performance-view-list").change(function () {
        var parameters = {
            'id': $("#holder-id").val(),
            'balance_current': $("#balance-current").val(),
            'period': $(this).val()
        };

        $.post("ajax/performance", parameters, function (data) {
            performance.text(data + '%');
            if (parseFloat(data) < 0) {
                performance.addClass('bg-danger').removeClass('bg-success');
            }
            else {
                performance.addClass('bg-success').removeClass('bg-danger');
            }
        });
    });

    var button = $(".btn");
    var today = $("#d7");
    var dataArray = [];
    button.click(function () {
        button.removeClass('bg-primary').css('color', 'inherit');
        $(this).addClass('bg-primary').css('color', 'white');

        var parameters = {
            'id': $("#holder-id").val(),
            'period': $(this).text()
        };

        $.post("ajax/linechart", parameters, function (data) {
            dataArray = JSON.parse(data);
            // console.log(dataArray);
            google.charts.load('current', {'packages': ['line']});
            google.charts.setOnLoadCallback(drawChart);
        });
    });

    today.trigger("click");

    function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string');
        data.addColumn('number');
        data.addRows(dataArray);
        var options = {
            chart: {
                title: 'Balance',
                subtitle: 'in billions of dollars (USD)'
            },
            legend: {position: 'none'},
            vAxis: {
                title: 'BALANCE',
                format: '$#,###.#####B'
            },
            axisTitlesPosition: 'in',
            height: 420
        };
        var chart = new google.charts.Line(document.getElementById('linechart'));
        chart.draw(data, google.charts.Line.convertOptions(options));
    }
});