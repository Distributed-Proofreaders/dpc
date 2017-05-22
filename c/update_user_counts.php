<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require "/home/pgdpcanada/public_html/crontab2/dpinit.php";

$n = $dpdb->SqlExecute("DELETE FROM user_round_pages
		WHERE count_time >= UNIX_TIMESTAMP(DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY))");

dump("$n user/round counts deleted before recalculating the last 90 days");


$sql = "
		REPLACE INTO user_round_pages
				( username, phase, count_time, page_count )

		SELECT  username,
				phase,
				UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(version_time))),
				COUNT(1)
		FROM page_versions
		WHERE version_time >= UNIX_TIMESTAMP(DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY))
			AND state = 'C'
			AND phase IN ('P1', 'P2', 'P3', 'F1', 'F2')
    	GROUP BY username, phase, UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(version_time)))
    	";
$n = $dpdb->SqlExecute($sql);

dump("$n user/round counts recalculated for the last 90 days");

$dpdb->SqlExecute("TRUNCATE TABLE total_user_round_pages");

$n = $dpdb->SqlExecute("
    INSERT INTO total_user_round_pages
        ( username, phase, count_time, page_count)
    SELECT username, phase, UNIX_TIMESTAMP(DATE(CURRENT_DATE())), SUM(page_count)
    FROM user_round_pages
    WHERE count_time < UNIX_TIMESTAMP(CURRENT_DATE())
    GROUP BY username,phase");

dump("$n user/round counts before today summed in total_user_round_pages.");

?>
