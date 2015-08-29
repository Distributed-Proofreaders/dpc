<?PHP

$relPath = "./pinc/";
require_once $relPath . "dpinit.php";

$phases = $dpdb->SqlObjects("
        SELECT  p.phase, 
                COUNT(1) nprojects, 
                SUM(n_pages) npages,
                ROUND(SUM(n_pages) / pg.goal) ndays
        FROM projects p
        JOIN phases ph ON p.phase = ph.phase
        JOIN phase_goals pg ON p.phase = pg.phase
            AND pg.goal_date = CURRENT_DATE()
        WHERE p.phase IN ('P1', 'P2', 'P3', 'F1', 'F2')
        GROUP BY p.phase
        ORDER BY sequence");


?>
<html>
  <head>
    <script type='text/javascript' src='/c/js/dp.js'></script>
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);

      function drawChart() {
        var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Phase');
        data.addColumn('number', 'Days (@ Goal)');
        data.addColumn('number', 'Pages');
<?php
        foreach($phases as $phase) {
            echo "      data.addRow(['$phase->phase', 
                                  $phase->ndays, 
                                  $phase->npages]);\n";
        }
?>
        chart.draw(data, {
                  title: "Round Pool Size (Pages and Days at Goal)",
                  curveType: "function",
                  width: 300,
                  height: 200,
                  vAxes: {0: {logScale: false},
                          1: {logScale: false}},
                  series:{
                     0:{targetAxisIndex:0},
                     1:{targetAxisIndex:1}}}
        );
      }
    </script>
  </head>
  <body>
    <div id="chart_div" style="width: 300px; height: 200px;"></div>
  </body>
</html>
