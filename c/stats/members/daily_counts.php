<?PHP
/* enabling column sort

1. include the js file -- /c/js/sort.js.
    <script type='text/javascript' src='/c/js/sort.js'></script>
    (Could this be embedded in DpTable?)
2. php needs to add body onload that calls makeSortable on the table by id.
3. need a way to embed current state in table - styles?
4. How to initialize?
5. Add row windowing.
6. numeric vs alpha vs other.
7. how to include in AddColumn?
8. Google Charts?
*/

$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');

$User->IsLoggedIn()
    or die("Please log in.");

$id = "tblcounts";

$username       = Arg("username", $User->Username());
//$days           = Arg("days", 7);
$page_number    = Arg("page_number", "1");
$rows_per_page  = Arg("rows_per_page", "50");
$page_ahead     = IsArg("page_ahead");
$page_back      = IsArg("page_back");

if($page_ahead) {
    $page_number++;
}
if($page_back && ($page_number > 1)) {
    $page_number--;
}

$minrow = $rows_per_page * ($page_number - 1);
$numrows = $rows_per_page;

$tbl = new DpTable($id, "dptable w75 center");
$rows = rowfunc();
$rows = array_slice($rows, $minrow, $numrows);
$tbl->SetRows($rows);

theme("Page Count History", "header");

echo _("<h1 class='center'>Page Count History for $username</h1>\n");

echo "<form name='formcount' method='POST'>\n";

echo "
<input type='hidden' name='rows_per_page' value='$rows_per_page'>
<input type='hidden' name='page_number' value='$page_number'>
<div class='w75 center'>
<input class='lfloat' type='submit' name='page_back' value='Prev Page'>
<input class='rfloat' type='submit' name='page_ahead' value='Next Page'>\n";

$tbl->EchoTableNumbered();

echo "
</form>\n";

theme("", "footer");
exit;

function rowfunc() {
    global $dpdb;
    global $User;
//    global $days;

    $username = $User->Username();
    $rows = $dpdb->SqlRows("
          SELECT DATE(FROM_UNIXTIME(count_time)) count_date,
            IFNULL(SUM(CASE WHEN phase = 'P1' THEN page_count ELSE 0 END), 0) P1,
            IFNULL(SUM(CASE WHEN phase = 'P2' THEN page_count ELSE 0 END), 0) P2,
            IFNULL(SUM(CASE WHEN phase = 'P3' THEN page_count ELSE 0 END), 0) P3,
            IFNULL(SUM(CASE WHEN phase = 'F1' THEN page_count ELSE 0 END), 0) F1,
            IFNULL(SUM(CASE WHEN phase = 'F2' THEN page_count ELSE 0 END), 0) F2,
            SUM(page_count) total
        FROM user_round_pages
        WHERE username = '$username'
        GROUP BY count_time
        ORDER BY count_time DESC");

    $today = $dpdb->SqlOneRow("
        SELECT CURRENT_DATE() count_date,
            IFNULL(SUM(CASE WHEN pe.phase = 'P1' THEN 1 ELSE 0 END), 0) P1,
            IFNULL(SUM(CASE WHEN pe.phase = 'P2' THEN 1 ELSE 0 END), 0) P2,
            IFNULL(SUM(CASE WHEN pe.phase = 'P3' THEN 1 ELSE 0 END), 0) P3,
            IFNULL(SUM(CASE WHEN pe.phase = 'F1' THEN 1 ELSE 0 END), 0) F1,
            IFNULL(SUM(CASE WHEN pe.phase = 'F2' THEN 1 ELSE 0 END), 0) F2,
            SUM(1) total
        FROM page_events pe
        LEFT JOIN page_events pe0
        ON pe.projectid = pe0.projectid
            AND pe.pagename = pe0.pagename
            AND pe.phase = pe0.phase
            AND pe.event_time < pe0.event_time
        WHERE pe.username = '$username'
            AND pe.event_type = 'saveAsDone'
            AND pe.event_time > UNIX_TIMESTAMP(CURRENT_DATE())
            AND pe0.event_id IS NULL");

    // prepend today's count to $rows
    array_unshift($rows, $today);
    return $rows;
}

