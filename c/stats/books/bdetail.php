<?
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'theme.inc');
include_once('../includes/book.inc');

$result = mysql_query("SELECT * FROM projects WHERE projectid = '".$_GET['project']."'");
$curProj = mysql_fetch_assoc($result);

theme($curProj['nameofwork']."'s Details", "header");
echo "<br><center>";

if (!empty($curProj['projectid'])) {
	showProjProfile($curProj);
}

echo "</center>";
theme("", "footer");
?>
