
<?PHP
$relPath = "./../../pinc/";
include_once $relPath.'dpinit.php';

$projectid = Arg('projectid');
$project = new DpProject($projectid);

html_head("Pages for $projectid");

$rows = $dpdb->SqlRows("SELECT projectid,
                               pagename,
                               imagefile
                            FROM pages
                            WHERE projectid = '$projectid'
                            ORDER BY pagename");
for($i = 0; $i < count($rows); $i++) {
        $rows[$i]["size"]  = ProjectImageFileSize($projectid, $rows[$i]["imagefile"]);
}

$tbl = new DpTable("tblpages", "dptable wauto lfloat");
$tbl->AddColumn(">Page", "pagename", "epage");
$tbl->AddColumn("^Size (bytes)", "imagefile", "eimage");
$tbl->SetRows($rows);

echo "<div style='padding: 3em;' class='lfloat'>\n";
$tbl->EchoTable();
echo "</div>\n";

html_foot();
exit;

function eimage($size) {
    return "<i>$size</i>";
}

function epage($pagename, $row) {
    $projectid = $row["projectid"];
    return link_to_view_image($projectid, $pagename);
}
    // echo link_to_view_image($projectid, $page->image);
    // echo " <i>($size bytes)</i><br>\n";

