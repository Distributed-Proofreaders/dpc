<?PHP

// update_user_counts.php

ini_set("display_errors", 1);
error_reporting(E_ALL);

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
if (strpos($_SERVER['DOCUMENT_ROOT'], "sandbox") !== false)
	$_SERVER['SERVER_NAME'] = "sandbox.pgdpcanada.net";
else
	$_SERVER['SERVER_NAME'] = "pgdpcanada.net";

$relPath = $_SERVER['DOCUMENT_ROOT'] . "/c/pinc/";
require $relPath . "/dpinit.php";


$dt = $dpdb->SqlOneValue("SELECT CURRENT_TIMESTAMP()");

$n1 = $dpdb->SqlExecute("
	REPLACE INTO user_round_pages
                ( username, phase, count_time, dateval, page_count )
        SELECT  username,
                PHASE,
                UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(version_time))),
                DATE(FROM_UNIXTIME(version_time)),
                COUNT(1)
        FROM page_versions pv
        WHERE pv.state = 'C'
        AND phase IN ('P1', 'P2', 'P3', 'F1', 'F2')
            AND version_time >= UNIX_TIMESTAMP(DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY))
        GROUP BY username, phase, UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(version_time)))
        ");


$dpdb->SqlExecute("
    TRUNCATE TABLE total_user_round_pages
    ");

$n2 = $dpdb->SqlExecute("
    INSERT INTO total_user_round_pages
        ( username, phase, count_time, page_count, dateval)
    SELECT username, phase, UNIX_TIMESTAMP(DATE(CURRENT_DATE())), SUM(page_count), CURRENT_DATE()
    FROM user_round_pages
    WHERE count_time < UNIX_TIMESTAMP(CURRENT_DATE())
    GROUP BY username, phase");

echo "

$dt

$n1 user round counts inserted/updated over 90 days.

$n2 user/round counts before today summed in total_user_round_pages.

==========================================================================
";

$n3 = $dpdb->SqlExecute("
    DELETE FROM team_round_pages
    ");

$n4 = $dpdb->SqlExecute("
    INSERT INTO team_round_pages
        ( team_id, PHASE, dateval, page_count )

    SELECT
        ut.team_id,
        urp.phase,
        urp.dateval,
        SUM(urp.page_count)
    FROM
        users_teams ut
        LEFT JOIN user_round_pages urp
            ON ut.username = urp.username

    GROUP BY ut.team_id, urp.phase, urp.dateval
    ");


$dpdb->SqlExecute("
    TRUNCATE TABLE total_team_round_pages
    ");

$n2 = $dpdb->SqlExecute("
    INSERT INTO total_team_round_pages
        ( team_id, phase, count_time, page_count, dateval)
    SELECT team_id, phase, UNIX_TIMESTAMP(DATE(CURRENT_DATE())), SUM(page_count), CURRENT_DATE()
    FROM team_round_pages
    WHERE dateval < CURRENT_DATE()
    GROUP BY team_id, phase
    ");


?>
// vim: sw=4 ts=4 expandtab
