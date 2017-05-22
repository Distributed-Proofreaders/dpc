<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');

$User->IsLoggedIn()
	or RedirectToLogin();

$User->IsAdmin() || $User->IsProjectManager() || $User->MayReleaseHold("queue")
    or die("User not permitted here.");

$release = ArgArray("release");

if(count($release) > 0 && $User->MayReleaseHold("queue")) {
    foreach($release as $key => $value) {
        $project = new DpProject($key);
        $project->ClearQueueHold("P1");
//        $project->SetState("P1.proj_avail");
    }
}

$dpdb->SetEcho();
$dpdb->SetTiming();

$rows = $dpdb->SqlRows("
        SELECT
            p.projectid,
            p.nameofwork,
            p.authorsname,
            p.genre,
            p.language,
            p.n_pages,
            p.username AS pm,
            LOWER(p.username) AS pmsort,
            (   SELECT COUNT(*) FROM project_holds
                WHERE projectid = p.projectid
            ) AS holdcount,
            DATE(FROM_UNIXTIME(p.phase_change_date)) AS moddate,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(p.phase_change_date))
                AS days_avail,
	        IFNULL(l1.name, '') langname,
	        IFNULL(l2.name, '') seclangname
        FROM project_holds ph
        JOIN projects p ON ph.projectid = p.projectid AND ph.phase = p.phase
        LEFT JOIN languages l1 ON p.language = l1.code
        LEFT JOIN languages l2 ON p.seclanguage = l2.code
        WHERE ph.phase = 'P1'
            AND ph.hold_code = 'queue'
        ORDER BY p.genre, p.language, p.username, p.phase_change_date, p.nameofwork");

// .001 sec

foreach($rows as $row) {
    $projectid = $row['projectid'];
    $p = new DpProject($projectid);
    $p->RecalcPageCounts();
}

$tbl = new DpTable();
$tbl->AddColumn("^Genre", "genre");
$tbl->AddColumn("^Language", "langname", "elanguage");
$tbl->AddColumn("^Proj Mgr", "pm", "epm", "sortkey=pmsort");
$tbl->AddColumn("^Mod Date", "moddate");
$tbl->AddColumn("<Title", "nameofwork", "etitle");
if($User->MayReleaseHold("queue")) {
    $tbl->AddColumn("^Release", "projectid", "erelease");
}
// $tbl->AddColumn("<Author", "authorsname", "eauthor");
// $tbl->AddColumn("^Pages", "n_pages", "enpages");
// $tbl->AddColumn("^Days", "days_avail");
$tbl->SetRows($rows);

$projectids = $dpdb->SqlValues("
    SELECT DISTINCT p.projectid FROM projects p
    JOIN project_holds ph
    ON p.projectid = ph.projectid
        AND p.phase = ph.phase
        AND ph.hold_code = 'queue'
    WHERE p.phase = 'P1'");

foreach($projectids as $projectid) {
    $proj = new DpProject($projectid);
    $proj->RecalcPageCounts();
}

// -----------------------------------------------------------------------------

$no_stats = 1;
theme("P1: Project Release", "header");
?>

<h1 class='center'>Round P1 Projects Waiting for Release</h1>

<h4 class='center'>What happens in this stage:</h4>

<p>Projects have been released from the Preparation stage and are now
considered suitable for proofing (having passed the QC check.) They are in the
first round state (P1) but are waiting until DAvid releases them using this
form. To use this form, a user needs to be assigned the Queuer role. (not
enforced yet.)</p>

<p>The old release mechanism is being used for now until we know that DAvid has
the right information available and the necessary conditions are set to really
release these projects. Then, we'll convert this to a "P1 Queue Hold" on the
project (rather than a project state change) and the button will release the
Hold. The behavior appears the same either way.</p>


<h2>Projects waiting in P1</h2>

<?php

echo "<form id='frmprop' action='' method='POST' name='frmprop'>\n";
$tbl->EchoTable();
echo "</form>\n";

theme("", "footer");
exit;

function elanguage($langname, $row) {
	return $langname
			. ($row['seclangname'] == "" ? "" : "/" . $row['seclangname']);
}

function etitle($title, $row) {
    // $title = h($title);
    $projectid = $row['projectid'];
    return link_to_project($projectid, $title);
}

function eauthor($authorsname) {
    return h($authorsname);
}

function epm($pm) {
    return $pm == ""
        ? "<span class='red'>--</span>\n"
        : link_to_pm($pm);
}

function enpages($npages) {
    return $npages > 0
        ? $npages
        : "<span class='red'>0</span>\n";

}

function erelease($projectid) {
    return "<input name='release[$projectid]' type='submit' value='Release'>\n";
    
}

// vim: sw=4 ts=4 expandtab

