<?php
function getProjects($phase, $orderby = "ORDER BY days_avail, nameofwork") {
    global $dpdb;

    $sql = "
	SELECT  p.projectid,
	    CASE WHEN p.nameofwork LIKE '[BEGIN]%'
		THEN CONCAT('**', p.nameofwork)
		ELSE p.nameofwork
	    END AS nameofwork,
	    p.authorsname, p.language, p.genre,
	    p.difficulty, p.username,
	    LOWER(p.username) pmsort,
	    SUM(1) n_pages,
	    CASE WHEN p.nameofwork LIKE '[BEGIN]%'
		THEN 0
		ELSE IFNULL(
		    DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(
			(
			    -- Look for the largest release hold event for
			    -- this phase
			    SELECT MAX(event_time) FROM project_events
				WHERE projectid = p.projectid
				AND phase = p.phase
				AND event_type='release_hold'
			)
		    )),
		    DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(p.phase_change_date))
		)
	    END AS days_avail
--      DATEDIFF(current_date(), FROM_UNIXTIME(MAX(pe.event_time))) AS since_hold,
--      DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(p.phase_change_date)) AS days_avail,
	FROM projects p

	WHERE p.phase = ?

	    -- Exclude any project with a hold in this phase
	    AND NOT EXISTS (
            SELECT 1 FROM project_holds
            WHERE   projectid = p.projectid
            AND     phase = p.phase
	    )
	GROUP BY p.projectid
    $orderby
	";

    // We split the calculation of the columns which use the page_versions table
    // out of the above SQL statement.  This allows when sorting with a limit
    // (as is done in the embedded table in the Activity Hub) to not have to
    // compute these values for the projects which end up not being used (due to the
    // LIMIT 5 that takes only the five oldest projects).
    // So we populate them only for the individual pages, and really it isn't
    // much slower anyway. (Actually, faster since we added the phase match when
    // we did this (which presumably could have been done with the huge statement.))
    // Anyhow, this is much cleaner and can be understood!

    //echo html_comment($sql);
    //$t1 = microtime(true);
    $args = [&$phase];
    $rows = $dpdb->SqlRowsPS($sql, $args);

    $sql = "
        SELECT
        COUNT(*) n_pages,
	    SUM(pv.state = 'A') n_available_pages,
	    DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(MAX(pv.version_time))) AS last_save_days
        FROM page_versions pv
        WHERE projectid = ? AND phase = ?

	    -- Exclude any page which already exists in a later round
	    AND NOT EXISTS (
            SELECT 1 FROM page_versions
            WHERE projectid = pv.projectid
                AND pagename = pv.pagename
                AND version > pv.version
	    )
        GROUP BY projectid
    ";

    // It would be nice to use a prepared statement and just change the parameter,
    // but I tried it, and it doesn't make any difference at all.
    foreach ($rows as &$row) {
        $args = [ &$row['projectid'], &$phase ];
        $r = $dpdb->SqlOneRowPS($sql, $args);
        $row['n_pages'] = $r['n_pages'];
        $row['n_available_pages'] = $r['n_available_pages'];
        $row['last_save_days'] = $r['last_save_days'];
    }
    //$t2 = microtime(true);
    //$x = (string) ($t2 - $t1);
    //echo "<h1>$x</h1><br>\n";
    return $rows;
}

function echoProjects($rows, $class = NULL, $filters = false) {
    if (is_null($class))
        $tbl = new DpTable();
    else
        $tbl = new DpTable("", $class);
    $f = "";
    if ($filters)
        $f = "filter";
    // (caption, colname, template, class)
	$tbl->AddColumn("<Title", "nameofwork", "etitle");
	$tbl->AddColumn("<Author", "authorsname");
	$tbl->AddColumn("<Language", "language", "elang", $f);
	$tbl->AddColumn("^Diff", "difficulty", null, $f);
	$tbl->AddColumn("<Genre", "genre", null, $f);
	$tbl->AddColumn("<Project<br>Mgr", "username", "epm", "sortkey=pmsort ".$f);
	$tbl->AddColumn("^Available<br>Pages", "n_available_pages", "enumber");
	$tbl->AddColumn("^Total<br>Pages", "n_pages", "enumber");
	$tbl->AddColumn(">Days in<br>Round", "days_avail", "enumber");
	$tbl->AddColumn(">Last Save", "last_save_days", "enumber");

	$tbl->SetRows($rows);
	$tbl->EchoTable();
}

function etitle($title, $row) {
    $projectid = $row["projectid"];
    return link_to_project($projectid, $title);
}

function elang($lang) {
    $n = strlen($lang);
    if ($n == 2 || ($n > 2 && substr($lang, 2, 1) == '-')) {
		$lang = LanguageName($lang);
	}
	return $lang;
}

function epm($username) {
    return link_to_pm($username);
}

function enumber($val) {
    return $val;
}

// vim: sw=4 ts=4 expandtab

?>
