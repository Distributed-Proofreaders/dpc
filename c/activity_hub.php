<?PHP
// This page covers all project-related activities of the site.
// For each, it:
// -- describes the activity;
// -- briefly summarizes its current state; and
// -- gives a link to the particular page for that activity.
//
// (Leaves out non-project-related activities like:
// forums, documentation/faqs, development, admin.)

ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "./pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'site_news.inc');
include_once($relPath.'RoundsInfo.php');

$User->IsLoggedIn()
	or RedirectToLogin();

$pagename = "activityhub";
$link_projectid = Arg("link_projectid");

if(IsArg('cmdprojectid') && $link_projectid != "") {
    divert( url_for_project( $link_projectid ) );
    exit;
}

$ahtitle = _("Activity Hub");

theme($ahtitle, "header");

echo "
    <div class='center overflow'>
        <img src='$code_url/graphics/Activity_Hub.jpg' alt='$ahtitle'>
        <p class='center'>
        ".link_to_metal_list("Gold", "Recently Published eBooks")."
        </p>
    </div>\n";


// Site News
echo "
<div>
<hr class='w75'>\n";

show_news_for_page("HUB");

echo "</div>\n";

echo "
<div>
<hr class='w75'>\n"
._("<h4>New Proofreaders</h4>")
."<p>"
    . link_to_feedback("What did you think of the Mentor feedback you received?", true)
."</p>
</div>

<hr class='w75'>
<ul>\n";

if ( $User->IsProjectManager() ) {
    echo "
    <li>" . link_to_project_manager("Manage My Projects") . "</li>\n";
}

// ----------------------------------

$cp_link = link_to_url("/forumdpc/viewforum.php?f=10", "Providing Content");

echo "
    <li>" . _("Providing Content") . "
    <br> "
    . _("Want to help out the site by providing material for us to proofread? ")
    . "Check out the $cp_link forum and leave a message that you want to help.</li>\n";

echo "<li>Page-based Round Processing<br>\n";

echo _("
    <table class='bordered hub_table'>
    <tr class='navbar'><td>Round</td>
                       <td>On Hold</td>
                       <td>Available</td>
                       <td>Total<br>Projects</td>
                       <td>Today's<br>Pages<br>Processed</td>
                       <td>Today's<br>Active<br>Users</td>
    </tr>");
/** @var Round $round */
foreach ( $Context->Rounds() as $round ) {
    $roundid = $round->RoundId();
    $phase_icon_path = "$dyn_dir/stage_icons/$roundid.jpg";
    $phase_icon_url  = "$dyn_url/stage_icons/$roundid.jpg";
    if ( file_exists($phase_icon_path) ) {
        $round_img = "<img src='$phase_icon_url' alt='($roundid)' align='middle'>";
    } else {
        $round_img = "($roundid)";
    }
    $rname = RoundIdName($roundid);
    $rdesc = RoundIdDescription($roundid);
    $rlink = link_to_round($roundid, $rname);

    echo "
        <tr>
        <td>$round_img $rlink <br> $rdesc</td>\n";

    summarize_projects($roundid);
    echo "</tr>\n";
}

echo "</table>\n";
echo "</li><li>Book Creation<br>\n";
echo "<table class='bordered hub_table'>";

$phase = "PP";
$rname = NameForPhase($phase);
$rdesc = DescriptionForPhase($phase);
$rlink = link_to_pp($rname);

$u = $User->Username();
$args = [ &$u ];
$my_checked_out = $dpdb->SqlOneValuePS("
        SELECT COUNT(1) FROM projects
        WHERE postproofer=?
            AND phase='PP'", $args);
$row = $dpdb->SqlOneRow("
     SELECT SUM(IFNULL(postproofer, '') = '') navail,
            COUNT(1) ntotal,
            SUM(smoothread_deadline > UNIX_TIMESTAMP() ) nsmooth
     FROM projects where phase = 'PP'");
$navail = $row["navail"];
$ntotal = $row["ntotal"];
$nchecked_out = $ntotal - $navail;
$nsmooth = $row["nsmooth"];
$msg = "";
if ($my_checked_out) {
    $msg = "You currently have $my_checked_out projects checked out for Post Processing.";
}
echo "
    <tr>
        <td></td>
        <td class='navbar'>Available<br>for $phase</td>
        <td class='navbar'>Checked<br>Out</td>
        <td class='navbar'>Total<br>Projects</td>
    </tr>
    <tr>
        <td>($phase) $rlink <br> $rdesc <br> $msg</td>
        <td>$navail</td><td>$nchecked_out</td><td>$ntotal</td>
    </tr>
";


$phase = "SR";
$rname = _("Smooth Reading");
$rdesc = _("Nearly completed projects are often made available for reading and checkproofing before posting.");
$rlink = link_to_smooth_reading($rname);
$nsmooth = $dpdb->SqlOneValue("
     SELECT SUM(smoothread_deadline > UNIX_TIMESTAMP() ) nsmooth
     FROM projects where phase = 'PP'");
echo "
    <tr>
        <td rowspan='2'>($phase) $rlink <br> $rdesc</td>
        <td class='navbar'></td>
        <td class='navbar'>Available for<br>Smooth Reading</td>
        <td class='navbar'></td>
    </tr>
    <tr>
        <td></td><td>$nsmooth</td><td></td>
    </tr>
";


// ----------------------------------------------------

$phase = "PPV";
$rname = NameForPhase($phase);
$rdesc = DescriptionForPhase($phase);
$rlink = link_to_ppv($rname);
$row = $dpdb->SqlOneRow("
     SELECT SUM(CASE WHEN IFNULL(ppverifier, '') = '' THEN 1 ELSE 0 END) navail,
            COUNT(1) ntotal
     FROM projects where phase = 'PPV'");
$navail = $row["navail"];
$ntotal = $row["ntotal"];
// If there are none in PPV, then we get an empty row.
if (empty($navail))
    $navail = 0;
if (empty($ntotal))
    $ntotal = 0;
$nchecked_out = $ntotal - $navail;
$n_checked_out = $dpdb->SqlOneValuePS("
        SELECT COUNT(1) FROM projects
        WHERE ppverifier=?
            AND phase='PPV'", $args);
$msg = "";
if ($n_checked_out) {
    $msg = "You currently have $n_checked_out projects checked out for PP Verification.";
}
echo "
    <tr>
        <td rowspan='2'>($phase) $rlink <br> $rdesc <br> $msg</td>
        <td class='navbar'>Available<br>for $phase</td>
        <td class='navbar'>Checked<br>Out</td>
        <td class='navbar'>Total<br>Projects</td>
    </tr>
    <tr>
        <td>$navail</td><td>$nchecked_out</td><td>$ntotal</td>
    </tr>
";

echo "</table>\n";

echo "
    </li>
</ul>\n";


theme("", "footer");

function summarize_projects( $phase) {
    global $dpdb;

    $row = $dpdb->SqlOneRow("
        SELECT SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM project_holds
                                         WHERE projectid = p.projectid
                                             AND phase = '$phase')
                        THEN 1 ELSE 0
                   END) navail,
               COUNT(1) ntotal
        FROM projects p WHERE p.phase = '$phase'");
    $navail = $row["navail"];
    $ntotal   = $row["ntotal"];
    $nwaiting = $ntotal - $navail;
    $today = PhaseCountToday($phase);
    $users = PhaseUsersActiveToday($phase);

    echo "<td>$nwaiting</td><td>$navail</td><td>$ntotal</td><td>$today</td><td>$users</td>";
}

// vim: sw=4 ts=4 expandtab
