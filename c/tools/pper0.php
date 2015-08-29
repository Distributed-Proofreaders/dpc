<?php
/**
 * User: don kretz
 * Date: 2/19/2015
 * Time: 1:38 PM
 */

// -----------------------------------------------------------------------------
// Init
// -----------------------------------------------------------------------------


$relPath="./../pinc/";
include_once $relPath . 'dpinit.php';

// verify permissions

if(! $User->IsLoggedIn()) {
	redirect_to_home();
	exit;
}

// admins can view other peoples' pages

if($User->IsAdmin()) {
	$username = Arg( "username", $User->Username() );
}
else {
	$username = $User->Username();
}

// Pattern
//      1. define query,
//      2. generate rows,
//      3. define table,
//      4. assign source to table,
//      5. display table

// -----------------------------------------------------------------------------
// Data
// -----------------------------------------------------------------------------

$rows = $dpdb->SqlRows("
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
		WHERE  p.postproofer = '$username'
		ORDER BY ph.sequence, days_avail");

$nprojects = count($rows);

// -----------------------------------------------------------------------------
// Table Definition
// -----------------------------------------------------------------------------


$tbl = new DpTable("tblpper", "dptable w95 em90 sortable");
$tbl->SetTitle("PP Projects for {$username} (count: $nprojects)");

$tbl->AddColumn("<Phase",       "phase",        null,       "sortkey=sequence");
$tbl->AddColumn("<Title",       "nameofwork",   "etitle");
$tbl->AddColumn("<Author",      "authorsname");
$tbl->AddColumn("<Language",    "langname",     "elangname");
$tbl->AddColumn("<Proj mgr",    "pm",           "euser");
$tbl->AddColumn("<PPer",        "pper",         "euser");
$tbl->AddColumn("<PPVer",       "ppver",        "euser");

// assign data to table definition

$tbl->SetRows($rows);

// -----------------------------------------------------------------------------
// Display
// -----------------------------------------------------------------------------

theme("PPer: Post Processor Report", "header");

echo "
    <h1 class='center'>Post-Processor ($username)</h1>\n";

// maybe prompt for another user's username

if($User->IsAdmin()) {
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

// display table

$tbl->EchoTable();

theme("", "footer");
exit;

// -----------------------------------------------------------------------------
// Functions - cell display
// -----------------------------------------------------------------------------


// language
function elangname($langname) {
	return $langname
	       . ( empty($row['seclangname'])
		? ""
		: "/". $row['seclangname']);
}

// title with link to project page
function etitle($title, $row) {
	$projectid = $row['projectid'];
	return link_to_project($projectid, $title);
}

// any username with link to email composition
function euser($username) {
	return link_to_pm($username);
}

// display function for days column

function edays($num) {
	return number_format($num);
}


// vim: sw=4 ts=4 expandtab

