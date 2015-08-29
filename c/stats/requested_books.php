<?PHP
$relPath='../pinc/';
include_once($relPath.'dpinit.php');

$title = _("Most Requested Books");
theme($title, 'header');

echo _("<h2 class='headerbar'>$title</h2>
<p>You can sign up for notifications by following the &quot;Click here to
register for automatic email notification of when this has been posted to
Project Gutenberg Canada&quot; link on the Project Comments page when
proofreading.</p>");

echo _("<h3 class='headerbar'>Most Requested Books Being Proofread</h3>\n");
$tbl = new DpTable();
$tbl->AddColumn("<Title", "nameofwork", "etitle");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("<Genre", "genre");
$tbl->AddColumn("^Language", "language");
$tbl->AddColumn("^Requests", "ncount");
$tbl->SetRows(Query("WHERE phase IN ('P1', 'P2', 'P3', 'F1', 'F2')"));
$tbl->EchoTableNumbered();


echo _("<h3 class='headerbar'>Most Requested Books In Post-Processing</h3>\n");
$tbl = new DpTable();
$tbl->AddColumn("<Title", "nameofwork", "etitle");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("<Genre", "genre");
$tbl->AddColumn("^Language", "language");
$tbl->AddColumn("^Requests", "ncount");
$tbl->SetRows(Query("WHERE phase IN ('PP', 'PPV')"));
$tbl->EchoTableNumbered();


echo _("<h3 class='headerbar'>Most Requested Books Posted to FadedPage.com</h3>\n");
$tbl = new DpTable();
$tbl->AddColumn("<Title", "nameofwork", "eposted");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("<Genre", "genre");
$tbl->AddColumn("^Language", "language");
$tbl->AddColumn("^Requests", "ncount");
$tbl->SetRows(Query("WHERE phase = 'POSTED'"));
$tbl->EchoTableNumbered();

theme("","footer");
exit;

function etitle($title, $row) {
    return link_to_project($row["projectid"], $title);
}

function eposted($title, $row) {
    return link_to_fadedpage_catalog($row["postednum"], $title);
}

function Query($where) {
    global $dpdb;
    return $dpdb->SqlRows("
        SELECT 
            n.projectid,
            p.nameofwork,
            p.authorsname,
            p.genre,
            p.language,
            p.postednum,
            COUNT(1) ncount
        FROM notify n
        JOIN projects p
        ON n.projectid = p.projectid 
        $where
        GROUP BY n.projectid 
        ORDER BY ncount DESC 
        LIMIT 50");
}

?>
