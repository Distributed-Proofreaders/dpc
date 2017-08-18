<?PHP

$relPath = "./../../pinc/";
include_once($relPath.'dpinit.php');

define( 'DEFAULT_N_RESULTS_PER_PAGE', 100 );
// -------------------------------------------------------------------

$rows_per_page      = Arg('n_results_per_page');
$show               = Arg("show");
$results_offset     = intval(Arg('results_offset'));
$todo               = Arg("todo");
$projectid          = ArgProjectId();
$phase              = Arg("phase");

if($User->IsSiteManager() || $User->IsProjectFacilitator()) {
    $username           = Arg("username", $User->Username());
}
else if(! $User->IsProjectManager()) {
	redirect_to_activity_hub();
	exit;
}
else {
    $username = $User->Username();
}


// -----------------------------------------------------------------------------

if($projectid != "" && $todo != "" && $phase != "") {
    $project = new DpProject($projectid);
	$project->RecalcPageCounts();

    switch($todo) {
        case "sethold":
            $project->SetUserHold($phase);
            break;
        case "unhold":
            $project->ClearUserHold($phase);
            break;
        default:
            die("todo error: $todo");
    }
}

if(! $show) {
	$show = "user_active";
    $show = $User->PMDefault() == 0
        ? "user_all"
        : "user_active";
}

$is_all = ($show == "user_all" ? '1' : '0');
    
if ( $rows_per_page == 0 ) 
    $rows_per_page = DEFAULT_N_RESULTS_PER_PAGE;

$sql = "
    SELECT  p.projectid,
            p.nameofwork,
            p.authorsname,
            p.difficulty,
            CASE WHEN p.phase = 'POSTED' THEN ''
            ELSE p.n_available_pages
            END n_available_pages,
            CASE WHEN p.phase = 'POSTED' THEN ''
            ELSE p.n_pages
            END n_pages,
            p.username,
            p.postproofer,
            p.ppverifier,
            p.phase,
            IFNULL(myph.id, 0) holdid,
            phases.sequence,
            GROUP_CONCAT(DISTINCT CONCAT(ph.phase, '/', ph.hold_code)
            ORDER BY phphases.sequence, ph.hold_code
            SEPARATOR ',\\n') holdlist,
            IFNULL(SUM(p.phase = ph.phase), 0) active_hold_count,
            CASE WHEN p.phase = 'POSTED' THEN ''
            ELSE DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(MAX(pv.version_time)))
            END AS last_save_days
    FROM projects p
    JOIN phases ON p.phase = phases.phase
    LEFT JOIN project_holds ph
        ON p.projectid = ph.projectid
    LEFT JOIN phases phphases ON ph.phase = phphases.phase
    LEFT JOIN project_holds myph
    ON p.projectid = myph.projectid
        AND p.phase = myph.phase
        AND myph.hold_code = 'user'
        AND myph.set_by = '$username'
	LEFT JOIN page_versions AS pv
	ON p.projectid = pv.projectid
            AND pv.state = 'C'
    WHERE p.username = '$username'
    " . ($is_all ? "" : "
        AND p.phase IN ('PREP', 'P1', 'P2', 'P3', 'F1', 'F2')") ."
    GROUP BY p.projectid
    ORDER BY phases.sequence, active_hold_count";

echo(html_comment($sql));
$rows = $dpdb->SqlRows($sql);
$numrows = count($rows);
$qual = $is_all ? " (All)" : " (Active)";


global $no_stats;
$no_stats = 1;

theme(_("Project Manager"), "header");

echo "
    <form name='frmpp' id='frmpp' method='POST' action=''>\n";
if($User->IsSiteManager() || $User->IsProjectFacilitator()) {
    echo "
	<div class='left'>
		<label> Username:
		<input type='text' id='username' name='username' value='{$username}'>
		</label>
		<input type='submit' value='submit'>
	</div>\n";
}
echo "
    <div class='center'>
        <div class='center clear'>
			<a href='{$pm_url}/image_sources.php'>Manage Image Sources</a>
					| " .  link_to_create_project() . "
			</div>
    </div>\n";

echo html_comment($sql);

if($username == $User->Username()) {
    echo "<h1 class='center'>Your PM Projects $qual</h1>\n";
}
else {
    echo "<h1 class='center'>PM Projects for {$username} $qual</h1>\n";
}

if ( count($rows) == 0 ) {
    echo _("<b>No projects matched the criteria.</b>");
    echo "</form>\n";
    theme("","footer");
    return;
}

// Formerly, a user's search results could only contain projects
// that the user could manage. Now that we've opened up the search page,
// this is no longer true. E.g., the results may contain New projects
// that the user does not have the authority to push to P1.unavail.
// Thus, these links would be confusing/misleading. So comment them out.
//

// -------------------------------------------------------------

echo "<div id='divframe'>\n";
echo results_navigator($rows_per_page, $results_offset, $numrows);

if($is_all) {
   echo "<a class='rfloat' href='{$pm_url}/projectmgr.php?show=user_active&username={$username}'>Show Only Active Projects</a>\n";
}
else {
    echo "<a class='rfloat' href='{$pm_url}/projectmgr.php?show=user_all&username={$username}'>Show All Projects</a>\n";
}

$user_can_see_download_links = ($User->HasRole("PP") || $User->HasRole("PPV"));
$show_options_column = $user_can_see_download_links || $User->IsProjectManager();

$tbl = new DpTable();
$tbl->SetClass("w100 dptable sortable");
$tbl->AddColumn("<Title", "nameofwork", "etitle");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("^Diff", "difficulty", "ediff");
$tbl->AddColumn("^Avail. pages", "n_available_pages");
$tbl->AddColumn("^Total pages", "n_pages");
$tbl->AddColumn(">Last Save", "last_save_days");
$tbl->AddColumn("<PM", "username", "epm");
$tbl->AddColumn("<PP", null, "epper");
$tbl->AddColumn("<PPV", null, "eppver");
$tbl->AddColumn("^Phase", "phase", "ephase", "sortkey=sequence");
$tbl->AddColumn("^Phase/Hold", "holdlist", "eholdlist");
$tbl->AddColumn("^Options", "projectid", "eoptions");
$tbl->AddColumn("^PM Hold", "holdid", "eholdid", "em90");

$tbl->SetRows($rows);
$tbl->EchoTable();

$numrows = count($rows);

echo results_navigator($rows_per_page, $results_offset, $numrows);
echo "</div>  <!-- divframe -->\n";

// }
echo "<br>";
theme("","footer");
exit;

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function etitle($title, $row) {
    $projectid = $row['projectid'];
    return link_to_project($projectid, $title);
}

function ediff($difficulty) {
    return upper(left($difficulty, 1));
}

function epm($who) {
    return link_to_pm($who);
}

function epper($row) {
    return $row['postproofer'] == ""
        ? ""
        : $row['postproofer'];
}

function eppver($row) {
    return $row['ppverifier'] == ""
        ? ""
        : $row['ppverifier'];
}

function ephase($phase, $row) {
	$color = ( $row['active_hold_count'] == 0 ? "green" : "red");
    return "<p class='center em80 $color'>$phase</p>";
}

function eholdlist($holdlist, $row) {
    $projectid = $row["projectid"];
    $url = "/c/holdmaster.php?projectid=$projectid";
    return link_to_url($url, $holdlist);
}

function eoptions($projectid) {
    return link_to_edit_project($projectid, _("Edit"));
}

function eholdid($holdid, $row) {
    $projectid = $row["projectid"];
    $phase = $row['phase'];
    $ishold = ($holdid != false);
    $todo = $ishold ? "unhold" : "sethold";
    $url = "projectmgr.php?projectid=$projectid"
            ."&amp;phase=$phase"
            ."&amp;todo=$todo";
    $clickprompt = $ishold ? "Release" : "Set";

    return link_to_url($url, $clickprompt);
        // . "<span class='likealink' onclick='$clickurl'>$clickprompt</span>";
}


// Present the results of the search query.

function results_navigator($rows_per_page, $results_offset, $numrows) {
	$sret = "";
    $num_found_rows = min($rows_per_page, $numrows - $results_offset); 
    // The REQUEST_URI must have at least one query-string parameter,
    // otherwise the response would have been just the search form,
    // and this function wouldn't have been called.
    $url_base = $_SERVER['REQUEST_URI'] . '&';
    $url_base = preg_replace('/results_offset=[^&]*&/', '', $url_base);

    if ( $results_offset > 0 ) {
        $t = _('Previous');
        $prev_offset = max(0, $results_offset - $rows_per_page );
        $url = $url_base . "results_offset=$prev_offset";
        $sret .= "<a href='$url'>$t</a> | ";
    }

    $sret .= sprintf(
        _("Projects %d to %d of %d\n"),
        $results_offset + 1,
        $num_found_rows,
        $numrows);

    if ( $results_offset + $num_found_rows < $numrows ) {
        $t = _('Next');
        $next_offset = $results_offset + $rows_per_page;
        $url = $url_base . "results_offset=$next_offset";
        $sret .= " | <a href='$url'>$t</a>";
    }
	return $sret;
}
