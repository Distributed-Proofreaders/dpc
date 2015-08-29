<?PHP

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');

echo $basic_header;

echo "<h1>Anomalies</h1>\n";

echo "<h2>Projects with live smooth reading dates but are not in PP phase</h2>\n";
$sql = "SELECT projectid,
                nameofwork as title,
                phase,
                username as pm,
                postproofer,
                from_unixtime(smoothread_deadline) deadline
        FROM projects
        WHERE smoothread_deadline > UNIX_TIMESTAMP()
            AND (
                IFNULL(postproofer, '') = ''
                OR phase != 'PP'
            )";

anomaly($sql);

// ----------------------

echo "<h2>Projects with Posted # but not POSTED, or vice versa.</h2>\n";
$sql = "SELECT projectid,
                nameofwork,
                phase,
                username as pm,
                postednum
        FROM projects
        WHERE (phase = 'POSTED' AND IFNULL(postednum,'') = '')
          OR (phase != 'POSTED' AND IFNULL(postednum,'') != '')";

anomaly($sql);

// ----------------------

echo "<h2>Projects with duplicate titles</h2>\n";
$sql = "SELECT p.phase, p.projectid, p.nameofwork, p.username
FROM projects p
JOIN ( SELECT COUNT(1), nameofwork FROM projects
    WHERE phase != 'DELETED'
    GROUP BY nameofwork HAVING COUNT(1) > 1
    ) a ON p.nameofwork = a.nameofwork
    WHERE p.phase != 'DELETED'
    ORDER BY nameofwork, projectid";
anomaly($sql);

// -----------------------

echo "<h2>Projects in PPV without PPer; or POSTED without PPer or PPVer.</h2>\n";
$sql = "SELECT phase, postproofer, ppverifier, projectid, nameofwork, postednum
FROM projects WHERE phase = 'PPV' AND ( IFNULL(postproofer,'') = '')
                 OR (postednum > 0 AND IFNULL(ppverifier, '') = '')";
anomaly($sql);
exit;

function anomaly($sql) {
    global $dpdb;
    dump($sql);
    $rows = $dpdb->SqlRows($sql);
    echo "<div>\n";

    if(count($rows) == 0) {
        echo "<p1>No projects.</p>\n";
    }
    else {
        $tbl = new DpTable();
        $tbl->SetRows($rows);
        $tbl->EchoTable();
    }
    echo "</div>\n";
}
