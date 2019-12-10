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

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');

$User->IsLoggedIn()
    or die("Please log in.");

$User->IsAdmin()
    or die("Not authorized.");

$id = "tblcounts";

$page_number    = Arg("page_number", "0");
$page_ahead     = IsArg("page_ahead");
$page_back      = IsArg("page_back");

if($page_ahead) {
    $page_number++;
}
if($page_back && ($page_number > 0)) {
    $page_number--;
}

$day = new DateTimeImmutable();
if ($page_number != 0) {
    $day = $day->modify("-$page_number day");
    if ($page_number == 1)
        $datestr = "Yesterday";
    else
        $datestr = $day->format("Y-m-d");
    $next = '';
    $prevtext = "Prev Day";
} else {
    $datestr = "Today";
    $next = 'disabled';
    $prevtext = "Yesterday";
}

theme("Round Activity", "header");

echo _("<h1 class='center'>Round Activity for $datestr</h1>\n");

$tbl = new DpTable($id, "dptable w95 center");

$phases = ["P1", "P2", "P3", "F1", "F2"];
foreach ($phases as $phase) {
    $tbl->AddColumn("<User", "username$phase", "euser", "b-left");
    $tbl->AddColumn("^Count", "count$phase");
    $tbl->AddCaption("^$phase", 2);

    $r[$phase] = rowfunc($phase, $day);
}

$trows = [];
$tot = [];
for ($rownum = 0; ; $rownum++) {
    $trow = [];
    $entry = False;
    foreach ($phases as $phase) {
        $rows = $r[$phase];
        if ($rownum >= count($rows)) {
            $trow["username$phase"] = '';
            $trow["count$phase"] = '';
            continue;
        }
        $row = $rows[$rownum];
        $user = $row['username'];
        $count = $row['n'];
        $entry = True;
        $trow["username$phase"] = $user;
        $trow["count$phase"] = $count;
        if (empty($tot[$phase]))
            $tot[$phase] = $count;
        else
            $tot[$phase] += $count;
    }
    if (!$entry)
        break;
    $trows[] = $trow;
}
$trow = ["row_id" => "tblpages"];
foreach ($phases as $phase) {
    $trow["username$phase"] = "";
    $trow["count$phase"] = $tot[$phase];
}
$trows[] = $trow;
$tbl->SetRows($trows);
$tbl->EchoTableNumbered();

echo "<form name='formcount' method='POST'>
<input type='hidden' name='page_number' value='$page_number'>
<div class='w95 center'>
<input class='lfloat' type='submit' name='page_back' $next value='Next Day'>
<input class='rfloat' type='submit' name='page_ahead' value='$prevtext'>
</form>\n";

theme("", "footer");
exit;

function euser($user, $row) {
    return link_to_url(url_for_my_projects() . "?username=" . $user, $user);
}

function rowfunc($phase, $day) {
    global $dpdb;

    $endday = $day->modify("+1 day");

    $startdate = $day->format('Y-m-d');
    $enddate = $endday->format('Y-m-d');

    $rows = $dpdb->SqlRows("
        SELECT  username, COUNT(1) n
            FROM page_versions
        WHERE   version_time >= UNIX_TIMESTAMP(DATE '$startdate')
	    AND version_time < UNIX_TIMESTAMP(DATE '$enddate')
            AND state = 'C'
            AND phase = '$phase'
        GROUP BY username
        ORDER BY n DESC
    ");

    return $rows;
}

