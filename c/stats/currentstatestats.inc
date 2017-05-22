<?php
global $code_dir;
global $relPath;
require_once ($relPath.'dpinit.php');
require_once ($relPath.'project_states.inc');
require_once ($code_dir.'stats/statestats.inc');
require_once ($relPath.'gettext_setup.inc');

$sql = "
    SELECT EXTRACT(YEAR_MONTH FROM FROM_UNIXTIME(TIMESTAMP)) emonth,
           SUM(CASE WHEN event_type = 'creation' THEN 1 ELSE 0 END) AS createcount,
           SUM(CASE WHEN event_type = 'transition' 
                AND details1 = 'F2.proj_done' THEN 1 ELSE 0 END) AS proofcount,
           SUM(CASE WHEN event_type = 'transition' 
                AND details2 = 'proj_post_complete' THEN 1 ELSE 0 END) AS ppcount,
           SUM(CASE WHEN event_type = 'transition' 
                AND details2 = 'proj_submit_pgposted' THEN 1 ELSE 0 END) AS pgcount
     FROM project_events
    WHERE EXTRACT(YEAR_MONTH FROM FROM_UNIXTIME(TIMESTAMP)) 
        = EXTRACT(YEAR_MONTH FROM CURRENT_DATE())";

$row = $dpdb->SqlOneRow($sql);

echo "
<div style='width: 100%; text-align: center'>
	<table width='75%' border='4' rules='all' align=center>
	  <tbody>
	  <tr class='navbar'>
	      <th class='navbar' colspan='4'>"
	._("Projects this month").
		"</th>
	  <tr>\n";


cell("created", $row["createcount"]);
cell("proofread", $row["proofcount"]);
cell("ppd", $row["ppcount"]);
cell("posted", $row["pgcount"]);

echo "
	  </tr>
	  </tbody>
	</table>
<br><hr width='75%'></div><br>";
exit;

function cell($which, $n) {
    global $theme;
    global $code_url;
	$graphs_url = "$code_url/stats/projects_Xed_graphs.php?which=$which";

	echo "<td align=center bgcolor='".$theme['color_mainbody_bg']."'>";
	echo "<a href='$graphs_url'>$which</a>: <b>$n</b>";
	echo "</td>";
}

/*
// display project count progress - here for the moment, can be moved to stats bar later
$cday = date('d'); $cmonth = date('m'); $cyear = date('Y');
$today = date('Y-m-d');

if ($cday != 1) {
    $start_date = $cyear."-".$cmonth."-01";
    $descrip = "this month";
} else {
    $descrip = "last month";
    if ($cmonth != 1) {
	$temp = $cmonth -1;
	$start_date = $cyear."-".$temp."-01";
    } else {
	$temp = $cyear - 1;
 	$start_date = $temp."-12-01";
    }
}
*/

