<?PHP
/*
 *  Full site page with theme - need to include chart.inc
 */
$relPath='./../pinc/';
include_once($relPath.'dpinit.php');

$roundid = Arg('roundid', Arg('roundid'));
if (!$roundid) {
    die("parameter 'roundid' is undefined/empty");
}


// ----------------------------------------------------------------------------
// generate data for charts
// ----------------------------------------------------------------------------

$data1 = Pages30Days($roundid);
$data2 = AllMonthsPages($roundid);

// ----------------------------------------------------------------------------
// build js function to draw a chart
// ----------------------------------------------------------------------------

// ----------------------------------------------------------------------------
// draw page with divs to hold charts
// ----------------------------------------------------------------------------

$is_gchart = true;

$header_title = _("$roundid Charts");

$title = $roundid == "ALL"
    ? _("Charts for Pages Saved-as-Done in All Rounds")
    : _("Charts for Pages Saved-as-Done in Round $roundid");


theme($header_title,'header');

makeColumnChart($data1, _("Daily Pages Last 30 Days"), "div1");
makeColumnChart($data2, _("Monthly Pages"), "div2");

echo "<div class='dpchart'>
      <h1><i>$title</i></h1>

    <div id='div1'></div>
    <div id='div2'></div>

    </div>\n";

theme('','footer');
exit;

function makeColumnChart($data, $caption, $div) {

    echo "
<script type='text/javascript' src='https://www.google.com/jsapi'></script>
<script type='text/javascript'>
  google.load('visualization', '1', {packages:['corechart']});
  google.setOnLoadCallback(drawChart);
  function drawChart() {
    // var data = new google.visualization.DataTable();
    var data = new google.visualization.arrayToDataTable(
        {$data}
    );
    var options = {
        title: '$caption',
        width: 900,
        height: 450,
        vAxis: {baseline: 0},
        legend: {position: 'none'}
    };

    var chart = new google.visualization.ColumnChart(document.getElementById('$div'));
    chart.draw(data, options);
}
</script>\n";
}

function AllMonthsPages($roundid) {
    global $dpdb;
    if($roundid == "ALL") {
        $sql = "
        SELECT  DATE_FORMAT(dateval, '%b-%Y') dateval,
                SUM(IFNULL(page_count, 0)) pages
        FROM user_round_pages
        WHERE  dateval < DATE(DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01'))
        GROUP BY YEAR(dateval), MONTH(dateval)
        ORDER BY YEAR(dateval), MONTH(dateval)";
    }
    else {
        $sql = "
        SELECT  DATE_FORMAT(dateval, '%b-%Y') dateval,
                SUM(IFNULL(page_count, 0)) pages
        FROM user_round_pages
        WHERE round_id = '$roundid'
          AND dateval < DATE(DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01'))
        GROUP BY YEAR(dateval), MONTH(dateval)
        ORDER BY YEAR(dateval), MONTH(dateval)";
    }
    $rows = $dpdb->SqlRows($sql);
    $ary = array(array("date", "pages"));
    foreach($rows as $row) {
        $ary[] = array($row["dateval"], (int) $row["pages"]);
    }
    return json_encode($ary);
}

function Pages30Days($roundid) {
    global $dpdb;
    if($roundid == "ALL") {
        $sql = "
        SELECT  DATE_FORMAT(d.dateval, '%b %e') dateval,
                SUM(IFNULL(page_count, 0)) pages
        FROM days d
        LEFT JOIN user_round_pages urp
        ON d.dateval = urp.dateval
        WHERE d.dateval >=  DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
            AND d.dateval < CURRENT_DATE()
        GROUP BY d.dateval
        ORDER BY d.dateval";
    }
    else {
    $sql = "
        SELECT  DATE_FORMAT(d.dateval, '%b %e') dateval,
                SUM(IFNULL(page_count, 0)) pages
        FROM days d
        LEFT JOIN user_round_pages urp
        ON d.dateval = urp.dateval
        WHERE urp.round_id = '$roundid'
            AND d.dateval >=  DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
            AND d.dateval < CURRENT_DATE()
        GROUP BY d.dateval
        ORDER BY d.dateval";
    }
    $rows = $dpdb->SqlRows($sql);
    $ary = array(array("date", "pages"));
    foreach($rows as $row) {
        $ary[] = array($row["dateval"], (int) $row["pages"]);
    }

    return json_encode($ary);
}
