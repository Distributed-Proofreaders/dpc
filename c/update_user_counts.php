<?php require "/sharehome/htdocs/crontab2/dpinit.php";

// Each L_<interval> field gives the number of distinct users
// who logged in sometime in the <interval> preceding the row's timestamp.

// Each A_<interval> field gives the number of distinct users
// who were active sometime in the <interval> preceding the row's timestamp.

/*
$n = $dpdb->SqlExecute("
    INSERT INTO user_active_log
        ( year, month, day, hour, time_stamp,
          L_hour, L_day, L_week, L_4wks,
          A_hour, A_day, A_week, A_4wks )
    SELECT
        YEAR(NOW()),
        MONTH(NOW()),
        DAYOFMONTH(NOW()),
        HOUR(NOW()),
        UNIX_TIMESTAMP(),

        SUM( last_login > UNIX_TIMESTAMP() - 60 * 60 ),
        SUM( last_login > UNIX_TIMESTAMP() - 60 * 60 * 24 ),
        SUM( last_login > UNIX_TIMESTAMP() - 60 * 60 * 24 * 7 ),
        SUM( last_login > UNIX_TIMESTAMP() - 60 * 60 * 24 * 7 * 4 ),

        SUM( t_last_activity > UNIX_TIMESTAMP() - 60 * 60 ),
        SUM( t_last_activity > UNIX_TIMESTAMP() - 60 * 60 * 24 ),
        SUM( t_last_activity > UNIX_TIMESTAMP() - 60 * 60 * 24 * 7 ),
        SUM( t_last_activity > UNIX_TIMESTAMP() - 60 * 60 * 24 * 7 * 4 )

    FROM users
    WHERE    t_last_activity > UNIX_TIMESTAMP() - 60 * 60 * 24 * 7 * 4");

say("User activity log updated (return: $n)");
*/

$n = $dpdb->SqlExecute("
	REPLACE INTO user_round_pages
		( username, round_id, count_time, page_count )
	SELECT  username,
            	round_id,
            	UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(TIMESTAMP))),
            	COUNT(1)
    	FROM page_events_save pe
	WHERE timestamp >= UNIX_TIMESTAMP(DATE_SUB(CURRENT_DATE(), INTERVAL 2 DAY))
    	GROUP BY username, round_id, UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(TIMESTAMP)))");

dump("$n user round counts inserted/updated over two days.");

$dpdb->SqlExecute("TRUNCATE TABLE total_user_round_pages");

$n = $dpdb->SqlExecute("
    INSERT INTO total_user_round_pages
        ( username, round_id, count_time, page_count)
    SELECT username, round_id, UNIX_TIMESTAMP(DATE(CURRENT_DATE())), SUM(page_count)
    FROM user_round_pages
    WHERE count_time < UNIX_TIMESTAMP(CURRENT_DATE())
    GROUP BY username,round_id");

dump("$n user/round counts before today summed in total_user_round_pages.");

?>
