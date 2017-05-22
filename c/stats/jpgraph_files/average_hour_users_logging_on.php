<?
$relPath="./../../pinc/";
include_once($relPath.'dpsql.inc');
include_once($relPath.'dpinit.php');
include_once('common.inc');

///////////////////////////////////////////////////
//Numbers of users logging on in each hour of the day, since the start of stats


//query db and put results into arrays


// $result = mysql_query("
$rows = $dpdb->SqlRows("
    SELECT hour, AVG(L_hour)
    FROM user_active_log
    GROUP BY hour
    ORDER BY hour");

// list($datax, $datay) = dpsql_fetch_columns($result);
list($datax, $datay) = fetch_columns($rows);

draw_simple_bar_graph(
	$datax,
	$datay,
	1,
	_('Average number of users newly logged in each hour'),
	_('Fresh Logons'),
	640, 400,
	58);
?>
