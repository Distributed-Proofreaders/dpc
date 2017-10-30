<?php
/**
 * Created by PhpStorm.
 * User: don
 * Date: 2/19/2015
 * Time: 1:38 PM
 */

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath . 'dpinit.php');

if(! $User->IsLoggedIn()) {
	redirect_to_home();
	exit;
}

if($User->IsAdmin()) {
	$username = Arg( "username", $User->Username() );
}
else {
	$username = $User->Username();
}

$no_stats = 1;
theme("PPer: Post Processor Report", "header");

// -----------------------------------------------------------------------------

echo "
<h1 class='center'>Post-Processor ($username)</h1>\n";

if($User->IsSiteManager() || $User->IsProjectFacilitator()) {
	echo "
    <form name='frmpp' id='frmpp' method='POST' action=''>
	<div class='left'>
		<label> Username:
		<input type='text' id='username' name='username' value='{$username}'>
		</label>
		<input type='submit' value='submit'>
	</div>
	</form>\n";
}

echo_pper_projects($username);

$no_stats = 1;
theme("", "footer");
exit;

function echo_pper_projects($username) {
	global $dpdb;

    if (empty($username)) {
        $whereClause = "WHERE p.phase = ?";
        $phase = "PP";
        $args = array(&$phase);
    } else {
        $whereClause = "WHERE p.postproofer = ?";
        $args = array(&$username);
    }
    $sql = "
		SELECT
			p.projectid,
			p.nameofwork,
			p.authorsname,
			p.language,
			p.seclanguage,
			l1.name langname,
			l2.name seclangname,
			p.genre,
			p.n_pages,
			p.username AS pm,
			LOWER(p.postproofer) AS pper,
			LOWER(p.ppverifier) AS ppver,
			DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(p.phase_change_date)) AS days_avail,
			p.phase,
			ph.sequence
		FROM projects p
		LEFT JOIN languages l1 ON p.language = l1.code
		LEFT JOIN languages l2 ON p.seclanguage = l2.code
		JOIN phases ph ON p.phase = ph.phase
        $whereClause
		ORDER BY ph.sequence, days_avail";

	$rows = $dpdb->SqlRowsPS($sql, $args);
	$nprojects = count($rows);
//	foreach($rows as $row) {
//		$projectid = $row['projectid'];
		//        $isfile = file_exists(project_pp_file($projectid)) ? "Yes" : "No";
//		$row["isppfile"] = eupload($projectid);
//	}


	$tbl = new DpTable("tblpper dptable w95 em90");
	//$tbl->SetTitle("PP Projects for {$username} (count: $nprojects)");
	$tbl->AddColumn("<Phase", "phase", null, "sortkey=sequence");
	$tbl->AddColumn("<Title", "nameofwork", "etitle");
	$tbl->AddColumn("<Author", "authorsname");
	$tbl->AddColumn("<Language", "langname", "elangname");
	$tbl->AddColumn("<Proj mgr", "pm", "euser");
	$tbl->AddColumn("<PPer", "pper", "euser");
	$tbl->AddColumn("<PPVer", "ppver", "euser");
	$tbl->AddColumn("^Days", "days_avail", "edays");
//	$tbl->AddColumn("^Upload", "projectid", "eupload");
//	$tbl->AddColumn("^Manage", "projectid", "emanage");
	$tbl->SetRows($rows);

	$tbl->EchoTable();
}

function elangname($langname) {
	return $langname
	       . ( empty($row['seclangname'])
		? ""
		: "/". $row['seclangname']);
}

function etitle($title, $row) {
	$projectid = $row['projectid'];
	return link_to_project($projectid, $title);
}

function eauthor($author) {
	return $author;
}

function euser($username) {
	return link_to_pm($username);
}



function esmooth($num) {
	return $num < 0 ? "" : edays($num);
}

function edays($num) {
	return number_format($num);
}


// vim: sw=4 ts=4 expandtab

