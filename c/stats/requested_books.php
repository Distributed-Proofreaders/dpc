<?PHP
$relPath='../pinc/';
include_once($relPath.'dpinit.php');

$title = _("Most Requested Books");
theme($title, 'header');

echo _("<h2 class='headerbar'>$title</h2>
<p>You can sign up for notification when a project is made available for
smooth reading or is posted to Fadedpage from the Project Page (by clicking
the project title in the table below.)</p>");

$rows = QueryRows();
echo _("<h3 class='headerbar'>Notification Requests</h3>\n");
$tbl = new DpTable();
$tbl->AddColumn("<Title", "nameofwork", "etitle");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("<Genre", "genre");
$tbl->AddColumn("^Language", "language");
$tbl->AddColumn("^SR Requests", "srcount");
$tbl->AddColumn("^Post Requests", "postcount");
$tbl->SetRows($rows);
$tbl->EchoTableNumbered();


/*
echo _("<h3 class='headerbar'>Most Requested Books In Post-Processing</h3>\n");
$tbl = new DpTable();
$tbl->AddColumn("<Title", "nameofwork", "etitle");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("<Genre", "genre");
$tbl->AddColumn("^Language", "language");
$tbl->AddColumn("^Requests", "ncount");
$tbl->SetRows(Query("WHERE p.phase IN ('PP', 'PPV')"));
$tbl->EchoTableNumbered();


echo _("<h3 class='headerbar'>Most Requested Books Posted to FadedPage.com</h3>\n");
$tbl = new DpTable();
$tbl->AddColumn("<Title", "nameofwork", "eposted");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("<Genre", "genre");
$tbl->AddColumn("^Language", "language");
$tbl->AddColumn("^Requests", "ncount");
$tbl->SetRows(Query("WHERE p.phase = 'POSTED'"));
$tbl->EchoTableNumbered();
*/

$no_stats = 1;
theme("","footer");
exit;

function etitle($title, $row) {
    return link_to_project($row["projectid"], $title);
}

//function eposted($title, $row) {
//    return link_to_fadedpage_catalog($row["postednum"], $title);
//}

function QueryRows() {
    global $dpdb;
    return $dpdb->SqlRows("
        SELECT 
            n.projectid,
            p.nameofwork,
            p.authorsname,
            p.genre,
            p.language,
            p.postednum,
            SUM(event = 'post') postcount,
            SUM(event = 'smooth') srcount
        FROM projects p
        LEFT JOIN notify n ON p.projectid = n.projectid
        GROUP BY p.projectid
        HAVING postcount > 0 OR srcount > 0");
}

?>
