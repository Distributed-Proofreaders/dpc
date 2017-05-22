<?PHP


$relPath = "../c/pinc/";
require $relPath . "dpinit.php";

/*
      |
    ct|    round
      |________________________
                 w
*/

$objs = array();
foreach(array("P1", "P2", "P3", "F1", "F2") as $phase) {
    makeChart($phase, "div_$phase");
}

function makeChart($phase, $div_id) {
    global $dpdb;
    $psql = sql($phase);
    // $objs[$phase] = $dpdb->SqlObjects($psql);
    $obj = $dpdb->SqlObjects($psql);
    $data = array();
    foreach($obj as $o) {
        $moyr = "{$o->mo}/{$o->yr}";
        $data[$moyr] = $o->pages;
    }
    echoChart($phase, $data, $div_id);
}

// $obj = $objs["P1"];
// $data = array();
// foreach($obj as $o) {
    // $moyr = "{$o->mo}/{$o->yr}";
    // $data[$moyr] = $o->pages;
// }

// echoChart("P1", $data);

exit;

function echoChartFunction($phase, $div_id) {
    global $dpdb;
    $rows = $dpdb->SqlRows(sql($phase));
    echo "
        <script type='text/javascript'>
          google.load('visualization', '1', {packages:['corechart']});
          google.setOnLoadCallback(drawChart);
          function drawChart() {
            var data = new google.visualization.DataTable();\n";
            echo("data.addColumn('string', 'Month')\n");
            echo("data.addColumn('number', 'Pages')\n");
            foreach($rows as $row) {
                $moyr = $row['moyr'];
                $val  = $row['val'];
                echo("data.addRow(['$moyr', $val])\n");
            }
    echo "
            var chart = new google.visualization.LineChart(document.getElementById('$div_id'));
            chart.draw(data);
          }
        </script>
    ";
}

function sql($phase) {
	return "
        SELECT  CONCAT(MONTH(FROM_UNIXTIME(version_time)) ,
                    YEAR(FROM_UNIXTIME(version_time))) moyr,
                COUNT(1) val
        FROM page_versions pv
        JOIN days d
        ON pv.version_time >= d.min_unixtime
            AND pv.version_time <= d.max_unixtime
        WHERE pv.phase = '$phase'
            AND d.dateval > CAST(DATE_FORMAT(NOW(), '%Y-%m-01') AS DATE)
        GROUP BY PHASE, d.monthval, d.yearval
        ORDER BY PHASE, d.yearval, d.monthval
        ";
}
