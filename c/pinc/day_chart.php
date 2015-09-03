<?PHP



/*
      |
    ct|    round
      |________________________
                 w
*/

function PhaseDaysChart($phase) {
    return "
    <div class='dpchart' id='div_daily_{$phase}' style='width: 750px; height: 400px;'>
    " . makeDailyChart($phase). "
    </div> <!-- div_{$phase}' -->\n";
}

function makeDailyChart($phase) {
    global $dpdb;
    $psql = daily_sql($phase);
    $obj = $dpdb->SqlObjects($psql);
    $data = array();
    foreach($obj as $o) {
        $dmy = "{$o->mo}/{$o->d}/{$o->yr}";
        $data[$dmy] = $o->pages;
    }
    return echoDailyChart($phase, $data, "div_daily_{$phase}");
}

function echoDailyChart($phase, $data, $div_id) {
    $str = "
        <script type='text/javascript' src='https://www.google.com/jsapi'></script>
        <script type='text/javascript'>
          google.load('visualization', '1', {packages:['corechart']});
          google.setOnLoadCallback(drawDailyChart);

          function drawDailyChart() {
            var data = new google.visualization.DataTable();
    data.addColumn('string', 'Date')
    data.addColumn('number', 'Pages')\n";

    foreach($data as $dmy => $val) {
        $str .= "data.addRow(['$dmy', $val])\n";
    }

    $str .= "
            var options = {
                title: 'Daily {$phase} Pages',
                height: 300,
                width:  700,
                curveType: 'function'
            };
            var chart_{$phase}
                = new google.visualization.LineChart(document.getElementById('$div_id'));
            chart_{$phase}.draw(data, options);
          };
        </script>
    ";
    return $str;
}

function daily_sql($phase) {
    if($phase == "All") {
        return "
        SELECT
            DAY(FROM_UNIXTIME(count_time)) d
            , MONTH(FROM_UNIXTIME(count_time)) mo
            , YEAR(FROM_UNIXTIME(count_time)) yr
            , SUM(page_count) pages
        FROM
            user_round_pages
        WHERE count_time < UNIX_TIMESTAMP(CURRENT_DATE())
            AND count_time > UNIX_TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH))
        GROUP BY
            DAY(FROM_UNIXTIME(count_time)),
            MONTH(FROM_UNIXTIME(count_time)),
            YEAR(FROM_UNIXTIME(count_time))
        ORDER BY
            YEAR(FROM_UNIXTIME(count_time)),
            MONTH(FROM_UNIXTIME(count_time)),
            DAY(FROM_UNIXTIME(count_time))";
    }
    else {
        return "
        SELECT
            DAY(FROM_UNIXTIME(count_time)) d
            , MONTH(FROM_UNIXTIME(count_time)) mo
            , YEAR(FROM_UNIXTIME(count_time)) yr
            , SUM(page_count) pages
        FROM
            user_round_pages
        WHERE phase = '$phase'
            AND count_time < UNIX_TIMESTAMP(CURRENT_DATE())
            AND count_time > UNIX_TIMESTAMP(DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH))
        GROUP BY
            DAY(FROM_UNIXTIME(count_time)),
            MONTH(FROM_UNIXTIME(count_time)),
            YEAR(FROM_UNIXTIME(count_time))
        ORDER BY
            YEAR(FROM_UNIXTIME(count_time)),
            MONTH(FROM_UNIXTIME(count_time)),
            DAY(FROM_UNIXTIME(count_time))";
    }
}
