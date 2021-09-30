google.charts.load("current", { packages: ["corechart", "bar"] });
google.charts.setOnLoadCallback(drawEnphaseChart);

function drawEnphaseChart() {
  var data = google.visualization.arrayToDataTable(enphaseChartData);

  var options = {
    hAxis: { title: "Date", slantedText: true, slantedTextAngle: 45 },
    vAxis: { title: "Energy (Watt hours)" },
    chartArea: { width: "68%", height: "70%", top: "10", left: "80" },
    legend: { textStyle: { fontSize: 15 } }
  };
  //var chart = new google.charts.Bar(document.getElementById('enphase_chart'));
  var chart = new google.visualization.ColumnChart(
    document.getElementById("enphase_chart")
  );

  //chart.draw(data, google.charts.Bar.convertOptions(options));
  chart.draw(data, options);
}
