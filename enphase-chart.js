google.charts.load("current", { packages: ["corechart", "bar"] });
google.charts.setOnLoadCallback(drawEnphaseChart);

function drawEnphaseChart() {
  const dataLength = enphaseChartData[0].length;
  enphaseChartData.forEach((dataPoint, index) => {
    if (
      !enphaseChartData[index] ||
      enphaseChartData[index].length !== dataLength
    ) {
      enphaseChartData.splice(index, 1);
    }
  });

  var data = google.visualization.arrayToDataTable(enphaseChartData);
  var options = {
    hAxis: { title: "Date", slantedText: true, slantedTextAngle: 45 },
    vAxis: { title: "Energy (Watt hours)" },
    legend: {
      position: "top",
      alignment: "end",
      textStyle: { fontSize: 15 }
    },
    chartArea: {
      height: "100%",
      width: "100%",
      top: 70,
      left: 100,
      right: 30,
      bottom: 70
    },
    height: "100%",
    width: "100%"
  };
  //var chart = new google.charts.Bar(document.getElementById('enphase_chart'));
  var chart = new google.visualization.ColumnChart(
    document.getElementById("enphase_chart")
  );

  //chart.draw(data, google.charts.Bar.convertOptions(options));
  chart.draw(data, options);
}
