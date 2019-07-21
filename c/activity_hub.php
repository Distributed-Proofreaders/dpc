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

$cp_link = link_to_url("$code_url/faq/cp.php", "Find out how!");

echo "
    <li>" . _("Providing Content") . "
    <br> "
    . _("Want to help out the site by providing material for us to proofread? ")
    . "$cp_link</li>\n";

/** @var Round $round */
foreach ( $Context->Rounds() as $round ) {
	$roundid = $round->RoundId();
    $phase_icon_path = "$dyn_dir/stage_icons/$roundid.jpg";
    $phase_icon_url  = "$dyn_url/stage_icons/$roundid.jpg";
    if ( file_exists($phase_icon_path) ) {
        $round_img = "<img src='$phase_icon_url' alt='($roundid)' align='middle'>";
    }
    else {
        $round_img = "($roundid)";
    }
	$rname = RoundIdName($roundid);
    $rdesc = RoundIdDescription($roundid);
    $rlink = link_to_round($roundid, $rname);

    echo "
        <li> 
        <hr class='w75'>
        $round_img $rlink <br> $rdesc <br /><br />\n";

    summarize_projects($roundid);
}

$phase = "PP";
$rname = NameForPhase($phase);
$rdesc = DescriptionForPhase($phase);
$rlink = link_to_pp($rname);
echo "
        <li>
        <hr class='w75'>
        ($phase) $rlink <br> $rdesc <br /><br />\n";
summarize_projects( $phase );

$n_checked_out = $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM projects
        WHERE postproofer='{$User->Username()}'
            AND phase='PP'");
if ($n_checked_out) {
    echo sprintf( _("You currently have %d projects checked out for Post Processing."), $n_checked_out );
    echo "<br>\n";
}


$phase = "SR";
$rname = _("Smooth Reading");
$rdesc = _("Nearly completed projects are often made available for reading and checkproofing before posting.");
$rlink = link_to_smooth_reading($rname);
echo "
        <li>
        <hr class='w75'>
        ($phase) $rlink <br> $rdesc <br /><br />\n";


// ----------------------------------------------------

$phase = "PPV";
$rname = NameForPhase($phase);
$rdesc = DescriptionForPhase($phase);
$rlink = link_to_ppv($rname);
echo "
        <hr class='w75'>
        <li> ($phase) $rlink <br> $rdesc <br /><br />\n";
summarize_projects( $phase );

$n_checked_out = $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM projects
        WHERE ppverifier='{$User->Username()}'
            AND phase='PPV'");
if ($n_checked_out) {
    echo sprintf( _("You currently have %d projects checked out for PP Verification."), $n_checked_out );
    echo "<br>\n";
}

echo "
    </li>
</ul>\n";


theme("", "footer");

function summarize_projects( $phase) {
    global $dpdb;

    if($phase == "P1" || $phase == "P2" || $phase == "P3" || $phase == "F1" || $phase == "F2") {
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

        echo _("
            <table class='bordered hub_table'>
            <tr class='navbar'><td rowspan='2'>All projects</td>
                               <td>On Hold</td>
                               <td>Available</td>
                               <td>Total<br>Projects</td></tr>

            <tr><td>$nwaiting</td><td>$navail</td><td>$ntotal</td></tr>
            </table>\n");
        return;
    }


    if($phase == "PP") {
        $row = $dpdb->SqlOneRow("
             SELECT SUM(IFNULL(postproofer, '') = '') navail,
                    COUNT(1) ntotal,
                    SUM(smoothread_deadline > UNIX_TIMESTAMP() ) nsmooth
             FROM projects where phase = '$phase'");
        $navail = $row["navail"];
        $ntotal = $row["ntotal"];
        $nchecked_out = $ntotal - $navail;
        $nsmooth = $row["nsmooth"];
        echo _("
        <table class='bordered hub_table'>
        <tr class='navbar'><td rowspan='2'>All projects</td>
                           <td>Available<br>for $phase</td>
                           <td>Checked<br>Out</td>
                           <td>Avail for<br>Smooth Reading</td>
                           <td>Total<br>Projects</td></tr>\n");
        echo "
        <tr><td>$navail</td><td>$nchecked_out</td><td>$nsmooth</td><td>$ntotal</td></tr>
        </table>\n";
    }
    else {
        $row = $dpdb->SqlOneRow("
             SELECT SUM(CASE WHEN IFNULL(ppverifier, '') = '' THEN 1 ELSE 0 END) navail,
                    COUNT(1) ntotal
             FROM projects where phase = '$phase'");
        $navail = $row["navail"];
        $ntotal = $row["ntotal"];
        // If there are none in PPV, then we get an empty row.
        if (empty($navail))
            $navail = 0;
        if (empty($ntotal))
            $ntotal = 0;
        $nchecked_out = $ntotal - $navail;
        echo _("
        <table class='bordered hub_table'>
        <tr class='navbar'><td rowspan='2'>All projects</td>
                           <td>Available<br>for $phase</td>
                           <td>Checked<br>Out</td>
                           <td>Total<br>Projects</td></tr>\n");
        echo "
        <tr><td>$navail</td><td>$nchecked_out</td><td>$ntotal</td></tr>
        </table>\n";
    }
}
