<?PHP
$relPath = '../../pinc/';

include $relPath . "dpinit.php";

echo "<h1>Cases where multiple projects have the same postednum</h1>\n";

echo "<p>
    When multiple projects have the same postednum,
    this usually means that a single book was split into multiple projects
    to go through the rounds.
    And when this is easy to detect (titles differ only by a digit),
    this page skips over the set.
    However, a shared postednum could also happen
    when a project was mistakenly assigned another project's postednum.
    This page should help find those cases.
</p>
";

$rows = $dpdb->SqlRows("
    SELECT projectid,
           nameofwork,
           postednum,
           FROM_UNIXTIME(modifieddate),
           phase
    FROM projects
    WHERE postednum IN (
        SELECT postednum FROM projects
        WHERE NULLIF(postednum, '') IS NOT NULL
        GROUP BY postednum
        HAVING COUNT(1) > 1)");

$tbl = new DpTable();
$tbl->SetRows($rows);
$tbl->EchoTable();

// vim: sw=4 ts=4 expandtab
?>
