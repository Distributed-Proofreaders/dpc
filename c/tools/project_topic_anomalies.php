<?php
/**
 * Created by PhpStorm.
 * User: don
 * Date: 7/30/14
 * Time: 11:45 AM
 */

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath = "../pinc/";
require_once $relPath . "dpinit.php";

$sql = "
SELECT p.projectid,
    p.phase,
    p.nameofwork,
    p.authorsname,
    p.topic_id
FROM projects p
LEFT JOIN forum.bb_topics ft
    ON p.topic_id = ft.topic_id
WHERE p.phase NOT IN ('DELETED')
    AND ft.forum_id IS NULL
ORDER BY p.phase
";

echo_report("Report 1", "descr", $sql);
exit;

function echo_report($title, $description, $sql) {
    global $dpdb;
    echo "<h1>$title</h1>
    <p>$description</p>\n";

    $tbl = new DpTable();
    $tbl->SetRows($dpdb->SqlRows($sql));
    $tbl->EchoTable();
}