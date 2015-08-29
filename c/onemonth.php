<?PHP


$relPath = "../c/pinc/";
require $relPath . "dpinit.php";

/*
      |
    ct|    round
      |________________________
                 w
*/


$rows = $dpdb->SqlObjects("
SELECT DATE_FORMAT(d.dateval, '%m-%d') mmdd,
        SUM(CASE WHEN round_id = 'P1' THEN page_count ELSE 0 END) p1count,
        SUM(CASE WHEN round_id = 'P2' THEN page_count ELSE 0 END) p2count,
        SUM(CASE WHEN round_id = 'P3' THEN page_count ELSE 0 END) p3count,
        SUM(CASE WHEN round_id = 'F1' THEN page_count ELSE 0 END) f1count,
        SUM(CASE WHEN round_id = 'F2' THEN page_count ELSE 0 END) f2count
FROM days d
JOIN user_round_pages urp
    ON urp.count_time BETWEEN d.min_unixtime AND d.max_unixtime
WHERE d.dateval > DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)
    AND d.dateval < DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)
GROUP BY d.dateval
ORDER BY d.dateval");

$data = array();
$data[0] = array("Day", "P1", "P2", "P3", "F1", "F2");

foreach($rows as $row) {
    $data[] = array($row->mmdd, $row->p1count, $row->p2count, 
                    $row->p3count, $row->f1count, $row->f2count);
}


$dary = json_encode($data);
$dary = preg_replace("/\"(\d\d\d)\"/", "$1", $dary);

// $ary = array($keys);
//ksort($data);
//foreach($data as $week => $vals) {
//    $ary[] = array_merge(array($week), array_values($vals));
//}
//
//$zary = json_encode($ary);
//
//dump($zary);

/*
$dary = '
[["Week","P1","P2","P3","F1","F2"],
["10-Nov",1918,2993,2816,2038,1809],
["17-Nov",2014,2313,2161,2850,1850],
["24-Nov",1383,2575,2750,2310,1333],
["01-Dec",1660,2349,2842,1569,1366],
["08-Dec",1678,2202,2565,2570,2510],
["15-Dec",2287,1095,1598,1657,1215],
["22-Dec",2244,2465,3092,3594,1441],
["29-Dec",2421,4587,3579,3651,2514],
["05-Jan",2411,2299,3663,3683,2658],
["12-Jan",2608,2279,2855,3039,2148],
["19-Jan",2330,2680,3397,4122,2093],
["26-Jan",2150,3307,2406,2781,1599],
["02-Feb",1291,1155,1574,1395,1458]]
';
*/
  


/*
$bary = array(
array('Week',   'F1', 'F2', 'P1', 'P2', 'P3'),
array('11-24',  2310, 1336, 1384, 2580, 2753),
array('12-01',  1582, 1366, 1662, 2352, 2881),
array('12-08',  2580, 2511, 1678, 2203, 2580),
array('12-15',  1691, 1220, 2289, 1097, 1600),
array('12-22',  3611, 1441, 2247, 2467, 3106),
array('12-29',  3653, 2517, 2428, 4588, 3660),
array('01-05',  3683, 2658, 2432, 2299, 3664),
array('01-12',  2318, 1651, 2198, 1734, 2564)
);
*/

// $cary = json_encode($bary);
// dump($cary);
// die();
// exit;
/*

Want an array,
Array(
Array($leftCaption, $varname1, $varname2, ... $varname_n),
Array($x1, $y1, $y2, ..., $y_n),
Array($x2, $y1, $y2, ..., $y_n),
...
Array($x_n, $y1, $y2, ..., $y_n),
)



First row enumerates the different lines (but the first value is name of dimension)
Then for each value on the dimension (e.g. each month
    there is a row with the value for the dimension followed by values for the lines
i.e. a Year dimension (2008) followed by 12 counts, one for each of the months

Want one line per round.
Dimension is week, i.e. past 8 weeks
['Round', 'date', 'date', 'date', 'date', ´date'],
['P1'   value, ...
...
['Week',   'F1', 'F2', 'P1', 'P2', 'P3'],
['11-24',  2310, 1336, 1384, 2580, 2753],
['12-01',  1582, 1366, 1662, 2352, 2881],
['12-08',  2580, 2511, 1678, 2203, 2580],
['12-15',  1691, 1220, 2289, 1097, 1600],
['12-22',  3611, 1441, 2247, 2467, 3106],
['12-29',  3653, 2517, 2428, 4588, 3660],
['01-05',  3683, 2658, 2432, 2299, 3664],
['01-12',  2318, 1651, 2198, 1734, 2564]


['Week',   'F1', 'F2', 'P1', 'P2', 'P3'],
['11-24',  2310, 1336, 1384, 2580, 2753],
['12-01',  1582, 1366, 1662, 2352, 2881],
['12-08',  2580, 2511, 1678, 2203, 2580],
['12-15',  1691, 1220, 2289, 1097, 1600],
['12-22',  3611, 1441, 2247, 2467, 3106],
['12-29',  3653, 2517, 2428, 4588, 3660],
['01-05',  3683, 2658, 2432, 2299, 3664],
['01-12',  2318, 1651, 2198, 1734, 2564]











array -- [round][date] count
['Month', '2007', '2008', '2009','2010','2011','2012','2013', '2014'],
['Jan', 0, 22872, 15280, 21234, 33647, 45350, 54199, 46067],
['Feb', 0, 23950, 24947, 26684, 29114, 42743, 48232, 0],
['Mar', 0, 31792, 24741, 29455, 30225, 41928, 56005, 0],
['Apr', 0, 24270, 20905, 28231, 24720, 37345, 40986, 0],

['Year', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
['2008', 22872, 23950,  31792, 24270, 21479, 16127, 19505, 13072, 17421, 13710, 13085, 13858],
['2009', 15280, 24947,  24741, 20905, 25185, 24161, 24750, 23890, 21668, 23844, 34656, 36608],
['2010', 21234, 26684,  29455, 28231, 27624, 23958, 25783, 20780, 25124, 25005, 25539, 25940],
['2011', 33647, 29114,  30225, 24720, 24590, 28037, 33214, 29688, 34023, 33206, 28541, 40759],
['2012', 45350, 42743,  41928, 37345, 42108, 38230, 36550, 38644, 41032, 37613, 42633, 45408],
['2013', 54199, 48232,  56005, 40986, 46708, 45637, 54204, 45596, 39185, 31177, 46834, 49090]

['Pages', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', ˝Nov', 'Dec'],
['2007',     0,     0,      0,     0,     0,     0,     0,     0,     0,    23,   126, 24031],
['2008', 22872, 23950,  31792, 24270, 21479, 16127, 19505, 13072, 17421, 13710, 13085, 13858],
['2009', 15280, 24947,  24741, 20905, 25185, 24161, 24750, 23890, 21668, 23844, 34656, 36608],
['2010', 21234, 26684,  29455, 28231, 27624, 23958, 25783, 20780, 25124, 25005, 25539, 25940],
['2011', 33647, 29114,  30225, 24720, 24590, 28037, 33214, 29688, 34023, 33206, 28541, 40759],
['2012', 45350, 42743,  41928, 37345, 42108, 38230, 36550, 38644, 41032, 37613, 42633, 45408],
['2013', 54199, 48232,  56005, 40986, 46708, 45637, 54204, 45596, 39185, 31177, 46834, 49090],
['2014', 46067,     0,      0,     0,     0,     0,     0,     0,     0,     0,     0,     0],
"
          ['Year', 'Sales', 'Expenses'],
          ['2004',  1000,      400],
          ['2005',  1170,      460],
          ['2006',  660,       1120],
          ['2007',  1030,      540]

['Month', '2007', '2008', '2009','2010','2011','2012','2013', '2014'],
['Jan', 0, 22872, 15280, 21234, 33647, 45350, 54199, 46067],
['Feb', 0, 23950, 24947, 26684, 29114, 42743, 48232, 0],
['Mar', 0, 31792, 24741, 29455, 30225, 41928, 56005, 0],
['Apr', 0, 24270, 20905, 28231, 24720, 37345, 40986, 0]

['Week starting', '11-24', '12-01', '12-08', '12-15', '12-22', '12-29', '1-05', '1-12'],
['F1',  2310, 1582, 2580, 1691, 3611, 3653, 3683, 2318],
['F2',  1336, 1366, 2511, 1220, 1441, 2517, 2658, 1651],
['P1',  1384, 1662, 1678, 2289, 2247, 2428, 2432, 2198],
['P2',  2580, 2352, 2203, 1097, 2467, 4588, 2299, 1734],
['P3',  2753, 2881, 2580, 1600, 3106, 3660, 3664, 2564]
*/

?>
<html>
  <head>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable(

<?php echo $dary; ?>

        );
        var options = {
          title: 'Daily Pages by Round',
          width: 600,
            curveType: 'function',
          height: 175,
          vAxis: { minValue: 0 }
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }

    </script>
  </head>
  <body style='width:375px; height: 175px'>
    <div id="chart_div" style="width:375px; height: 175px;"></div>
  </body>
</html>

