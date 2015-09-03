<?PHP



/*
      |
    ct|    round
      |________________________
                 w
*/

function PhaseMonthsChart($phase) {
    return "
    <div class='dpchart' id='div_monthly_{$phase}' style='width: 750px; height: 400px;'>
    " . makeMonthlyChart($phase). "
    </div> <!-- div_{$phase}' -->\n";
}

function makeMonthlyChart($phase) {
    global $dpdb;
    $psql = monthly_sql($phase);
    $obj = $dpdb->SqlObjects($psql);
    $data = array();
    foreach($obj as $o) {
        $moyr = "{$o->mo}/{$o->yr}";
        $data[$moyr] = $o->pages;
    }
    return echoMonthlyChart($phase, $data, "div_monthly_{$phase}");
}

function echoMonthlyChart($phase, $data, $div_id) {
    $str = "
        <script type='text/javascript' src='https://www.google.com/jsapi'></script>
        <script type='text/javascript'>
          google.load('visualization', '1', {packages:['corechart']});
          google.setOnLoadCallback(drawMonthlyChart);

          function drawMonthlyChart() {
            var data = new google.visualization.DataTable();
    data.addColumn('string', 'Month')
    data.addColumn('number', 'Pages')\n";

    foreach($data as $moyr => $val) {
        $str .= "data.addRow(['$moyr', $val])\n";
    }

    $str .= "
            var options = {
                title: 'Monthly {$phase} Pages',
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

function monthly_sql($phase) {
    if($phase == "All") {
        return "
        SELECT
            MONTH(FROM_UNIXTIME(count_time)) mo
            , YEAR(FROM_UNIXTIME(count_time)) yr
            , SUM(page_count) pages
        FROM
            user_round_pages
        WHERE count_time < UNIX_TIMESTAMP(CAST(DATE_FORMAT(CURRENT_DATE() ,'%Y-%m-01') as DATE))
        GROUP BY
            MONTH(FROM_UNIXTIME(count_time)),
            YEAR(FROM_UNIXTIME(count_time))
        ORDER BY
            YEAR(FROM_UNIXTIME(count_time)),
            MONTH(FROM_UNIXTIME(count_time))";
    }
    else {
        return "
        SELECT
            MONTH(FROM_UNIXTIME(count_time)) mo
            , YEAR(FROM_UNIXTIME(count_time)) yr
            , SUM(page_count) pages
        FROM
            user_round_pages
        WHERE phase = '$phase'
            AND count_time < UNIX_TIMESTAMP(CAST(DATE_FORMAT(CURRENT_DATE() ,'%Y-%m-01') as DATE))
        GROUP BY 
            phase,
            MONTH(FROM_UNIXTIME(count_time)), 
            YEAR(FROM_UNIXTIME(count_time))
        ORDER BY 
            phase,
            YEAR(FROM_UNIXTIME(count_time)),
            MONTH(FROM_UNIXTIME(count_time))";
    }
}
