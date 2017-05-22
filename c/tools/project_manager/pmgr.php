<?PHP

$relPath = "./../../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'theme.inc');
include_once($relPath.'iso_lang_list.inc');
include_once($relPath.'genres.inc');
include_once('projectmgr.inc');

define( 'DEFAULT_N_RESULTS_PER_PAGE', 100 );
// -------------------------------------------------------------------

$rows_per_page      = Arg('n_results_per_page');
$show               = Arg("show");
$results_offset     = intval(Arg('results_offset'));
$todo               = Arg("todo");
$projectid          = ArgProjectId();
$phase              = Arg("phase");
// $username           = Arg("username", $User->Username());
$username = "alexwhite";
// -----------------------------------------------------------------------------

if($projectid != "" && $todo != "" && $phase != "") {
    $project = new DpProject($projectid);

    switch($todo) {
        case "sethold":
            $project->SetUserHold($phase);
            break;
        case "unhold":
            $project->ClearUserHold($phase);
            break;
        default:
            dump("todo error: $todo");
    }
}

if($User->IsProjectManager() && ! $show) {
    $show = $User->PMDefault() == 0
        ? "user_all"
        : "user_active";
}

theme(_("Project Manager"), "header");

echo "
    <div class='center'>
<a href='http://www.pgdpcanada.net/c/tools/project_manager/show_image_sources.php'>Image Sources Info</a>
|
<a href='http://www.pgdpcanada.net/c/tools/project_manager/manage_image_sources.php?action=show_sources'>Manage Image Sources</a>
|
<a href='http://www.pgdpcanada.net/c/tools/project_manager/manage_image_sources.php?action=add_source'>Propose a new Image Source</a>
<br>
PM links:
<a href='http://www.pgdpcanada.net/c/tools/project_manager/projectmgr.php?show=user_active'>Show Your Active Projects</a>
|
<a href='http://www.pgdpcanada.net/c/tools/project_manager/projectmgr.php?show=user_all'>Show All of Your Projects</a>\n";
echo " | ";
echo link_to_create_project();
echo "
</div>\n";


$is_all = ($show == "user_all" ? '1' : '0');
    
if ( $rows_per_page == 0 ) 
    $rows_per_page = DEFAULT_N_RESULTS_PER_PAGE;

$sql = "
    SELECT  p.projectid,
            p.nameofwork,
            p.authorsname,
            p.difficulty,
            p.n_available_pages,
            p.n_pages,
            p.username,
            p.postproofer,
            p.ppverifier,
            p.phase,
            IFNULL(myph.id, 0) holdid,
            GROUP_CONCAT(CONCAT(ph.phase, '/', ph.hold_code)
            ORDER BY phphases.sequence, ph.hold_code
            SEPARATOR ',\\n') holdlist
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
    WHERE p.username = '$username'
        AND (p.phase != 'DELETED' OR $is_all)
    GROUP BY p.projectid
    ORDER BY phases.sequence, p.nameofwork asc";
echo(html_comment(PrettySql($sql)));
$rows = $dpdb->SqlRows($sql);

$numrows = count($rows);

echo "<h1 class='center'>Your PM Projects</h1>\n";

if ( $numrows == 0 ) {
    echo _("<b>No projects matched the criteria.</b>");
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

results_navigator($rows_per_page, $results_offset, $numrows);

$user_can_see_download_links = ($User->HasRole("PP") || $User->HasRole("PPV"));
$show_options_column = $user_can_see_download_links || $User->IsProjectManager();

$tbl = new DpTable();
$tbl->SetClass("w100 dptable sortable");
$tbl->AddColumn("<Title", "nameofwork", "etitle");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("^Diff", "difficulty", "ediff");
$tbl->AddColumn("^Avail. pages", "n_available_pages");
$tbl->AddColumn("^Total pages", "n_pages");
$tbl->AddColumn("<PM", "username", "epm");
$tbl->AddColumn("<PP", null, "epper");
$tbl->AddColumn("<PPV", null, "eppver");
$tbl->AddColumn("^Status", "phase", "ephase");
$tbl->AddColumn("^Phase/Hold", "holdlist", "eholdlist");
$tbl->AddColumn("^Options", "projectid", "eoptions");
$tbl->AddColumn("^{$phase} pm Hold", "holdid", "eholdid");

$tbl->SetRows($rows);
$tbl->EchoTable();

$numrows = count($rows);

results_navigator($rows_per_page, $results_offset, $numrows);

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

function ephase($phase) {
    return "<p class='center em80'>$phase</p>";
}

function eholdlist($holdlist, $row) {
    $projectid = $row["projectid"];
    $url = "http://www.pgdpcanada.net/c/holdmaster.php?projectid=$projectid";
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
            ."&phase=$phase"
            ."&todo=$todo";
    $clickprompt = $ishold ? "Release" : "Set";

    return link_to_url($url, $clickprompt);
        // . "<span class='likealink' onclick='$clickurl'>$clickprompt</span>";
}


// Present the results of the search query.

function results_navigator($rows_per_page, $results_offset, $numrows) {

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
        echo "<a href='$url'>$t</a> | ";
    }

    echo sprintf(
        _("Projects %d to %d of %d"),
        $results_offset + 1,
        $num_found_rows,
        $numrows);
    echo "\n";

    if ( $results_offset + $num_found_rows < $numrows ) {
        $t = _('Next');
        $next_offset = $results_offset + $rows_per_page;
        $url = $url_base . "results_offset=$next_offset";
        echo " | <a href='$url'>$t</a>";
    }
}
