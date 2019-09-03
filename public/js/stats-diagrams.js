google.charts.load('current', {'packages': ['corechart']});
google.charts.setOnLoadCallback(drawChart);

function drawChart() {
    var tokenInfo1 = $("#chart-data1").text();
//        console.log(tokenInfo1);
    var arr1 = JSON.parse(tokenInfo1);
//        console.log(arr1);
    var count = parseInt($("#popularList option[label='active']").val());
    arr1.splice(count + 1, arr1.length - count - 2);
    var sumPercent = 0;
    for (var i = 1; i < arr1.length - 1; i++) {
        arr1[i][1] = parseFloat(arr1[i][1]);
        sumPercent += arr1[i][1];
    }
    arr1[arr1.length - 1][1] = 100 - sumPercent;
    var data1 = google.visualization.arrayToDataTable(arr1);

    var tokenInfo2 = $("#chart-data2").text();
    var arr2 = JSON.parse(tokenInfo2);
    arr2.splice(count + 1, arr2.length - count - 2);
    sumPercent = 0;
    for (i = 1; i < arr2.length - 1; i++) {
        arr2[i][1] = parseFloat(arr2[i][1]);
        sumPercent += arr2[i][1];
    }
    arr2[arr2.length - 1][1] = 100 - sumPercent;
    var data2 = google.visualization.arrayToDataTable(arr2);
    var options = {
        chartArea: {height: '100%'},
        is3D: true,
        legend: {alignment: 'center', maxLines: 2},
        tooltip: {text: 'percentage'},
//            title: 'Token percentage',
//            legend: {alignment: 'center', position: 'labeled'},
//            sliceVisibilityThreshold: .25
//            pieSliceText: 'value',
//            chartArea:{width:'100%',height:'90%'}
    };

    var chart1 = new google.visualization.PieChart(document.getElementById('chart1'));
    var chart2 = new google.visualization.PieChart(document.getElementById('chart2'));
    chart1.draw(data1, options);
    chart2.draw(data2, options);
}
