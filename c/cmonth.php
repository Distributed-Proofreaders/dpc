<?PHP
/*

F1 2590 2451 0 1 134 550
F1 2429 2224 0 1 198 0
F2 42295 32398 0 7 9775 252
F2 41915 32098 0 6 9801 0
P1 31586 28766 0 19 2433 16023
P1 4502 2229 0 12 1897 0
P2 9474 6757 0 11 2699 366
P2 9185 6692 0 12 2474 0
P3 15446 12180 0 9 3244 6
P3 15108 12104 0 10 2994 0
PP 147203 118610 162110 0 0 0
PP 147179 118610 161530 0 0 0
PPV 29682 57618 5940 0 0 0
PPV 29706 58806 5940 0 0 0


*/
$relPath = "./pinc/";
require_once $relPath . "dpinit.php";

$rows = $dpdb->SqlRows("
SELECT YEAR(FROM_UNIXTIME(pe.event_time)) phase_year,
        MONTH(FROM_UNIXTIME(pe.event_time)) phase_yearmonth,
        DATE_FORMAT(FROM_UNIXTIME(pe.event_time), '%m%y') mmyy,
        pe.phase, 
        COUNT(1) nprojects,
        SUM(p.n_pages) npages
FROM project_events pe
JOIN phases ph ON pe.phase = ph.phase 
JOIN projects p ON pe.projectid = p.projectid
WHERE event_type = 'transition'
    AND pe.event_time > UNIX_TIMESTAMP(DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR))
GROUP BY phase_year, phase_yearmonth, pe.phase
ORDER BY phase_year, phase_yearmonth, sequence");

$phases = array();
$mmyys = array();
$ary = array();

foreach($rows as $row) {
    $phase = $row["phase"];
    if(! in_array($phase, $phases)) {
        $phases[] = $phase;
    }
    $mmyy = "x" . $row["mmyy"];
    if(! in_array($mmyy, $mmyys)) {
        $mmyys[] = $mmyy;
    }
    $ary[$phase][$mmyy] = $row["nprojects"];
}

$data = array();
$caps = array("Phase");
foreach($mmyys as $mmyy) {
    $caps[] = $mmyy;
}
$data[] = $caps;

foreach($phases as $dphase) {
    $aphase = array($dphase);
    foreach($mmyys as $mmyy) {
        // dump($ary[$dphase]);
        $aphase[] = isset($ary[$dphase][$mmyy]) ? intval($ary[$dphase][$mmyy]) : 0 ;
    }
    $data[] = $aphase;
}

$jdata = json_encode($data);
dump($jdata);

/*
$rows = $dpdb->SqlRows("SELECT pb.phase,
                               FROM_UNIXTIME(pb.count_date) count_date,
                               DATE_FORMAT(FROM_UNIXTIME(pb.count_date), '%m/%d') mmdd,
                               pb.available_count,
                               pb.checked_out_count
                        FROM phase_backlogs pb
                        JOIN phases ph
                        ON pb.phase = ph.phase
                        WHERE pb.count_date >= UNIX_TIMESTAMP(DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY))
                            AND pb.count_date < UNIX_TIMESTAMP(CURRENT_DATE())
                        ORDER BY ph.sequence, pb.count_date");

$phases = array();
$days = array();
$ary = array();
*/

/*
foreach($rows as $row) {
    $phase = $row['phase'];
    if(! in_array($phase, $phases)) {
        $phases[] = $phase;
    }
    $mmdd  = $row['mmdd'];
    if(! in_array($mmdd, $days)) {
        $days[] = $mmdd;
    }
    $ary[$phase][$mmdd] = intval($row['available_count']) + intval($row['checked_out_count']);
}

$data = array();
$caps = array('Phase');
foreach($days as $d) {
    $caps[] = $d;
}
$data[] = $caps;

foreach($phases as $dphase) {
    $aphase = array($dphase);
    foreach($days as $dmmyy) {
        $aphase[] = $ary[$dphase][$dmmyy];
    }
    $data[] = $aphase;
}

$jdata = json_encode($data);
*/
// dump($jdata);
// die();

/*
    ['Phase', '02/16', '02/17'],
    ['P1',  1000,      400],
    ['P2',  1170,      460],
    ['P3',  660,       1120],
    ['F1',  1030,      540],
    ['F2',  1030,      540]
*/

?>
<html>
  <head>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
    google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {

          var data = google.visualization.arrayToDataTable([
          <?php
          echo $jdata;
          /*
              ["Phase","02\/19","02\/20","02\/21","02\/22"],
              ["P1",27513,27451,27139,26927],
              ["P2",6890,7238,7029,7238],
              ["P3",12130,12068,11688,11709],
              ["F1",3509,3440,3682,3363],
              ["F2",30715,30964,30781,31261],
              ["PP",281590,281590,281590,281590],
              ["PPV",64152,64152,62370,61776]
          */
          ?>
              ]);

          var options = {
              title: 'Projects Completed',
              hAxis: {title: 'Month', titleTextStyle: {color: 'red'}}
        };

        var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
  </head>
  <body>
    <div id="chart_div" style="width: 900px; height: 500px;"></div>
  </body>
</html>
<?php
/*
$ridx = array("P1" => 1, "P2" => 2, "P3" => 3, "F1" => 4, "F2" => 5);
$data = array();
$data[0] = array("Week", "P1", "P2", "P3", "F1", "F2");

for($i = -13; $i < 0 ; $i++) {
    $obj = $dpdb->SqlOneObject("
        SELECT DATE_ADD(CURRENT_DATE(), INTERVAL $i WEEK) d,
               DATE_FORMAT(DATE_ADD(CURRENT_DATE(), INTERVAL $i WEEK), '%d-%b') dstr,
               YEARWEEK(DATE_ADD(CURRENT_DATE(), INTERVAL $i WEEK)) w");
    $data[$obj->w] = array($obj->dstr, 0, 0, 0, 0, 0);
}

$w1 = $dpdb->SqlOneValue(" SELECT YEARWEEK(DATE_SUB(CURRENT_DATE(), INTERVAL 13 WEEK))");
$w2 = $dpdb->SqlOneValue(" SELECT YEARWEEK(DATE_SUB(CURRENT_DATE(), INTERVAL 1 WEEK))");

$sql = "
SELECT urp.round_id r, urp.weekval w, SUM(urp.page_count) c
FROM user_round_pages urp
WHERE urp.weekval BETWEEN '$w1' AND '$w2'
GROUP BY urp.round_id, urp.weekval";

$rows = $dpdb->SqlRows($sql);

$i = 1;
foreach($rows as $row) {
    $week = $row['w'];
    $round = $row['r'];
    $col = $ridx[$round];
    $c = $row['c'];
    $data[$week][$col] = (integer) $c;
}

$ary = array();
foreach($data as $dat) {
    $ary[] = $dat;
}

$dary = json_encode($ary);

?>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable('
<?php echo $dary;;
?>
        );

        var options = {
          title: "Weekly Pages by Round",
          curveType: 'function',
          width: 500,
          height: 200,
          vAxis: { minValue: 0 }
        };

        var chart = new google.visualization.LineChart(document.getElementById("chart_div"));
        chart.draw(data, options);
      }';
*/
