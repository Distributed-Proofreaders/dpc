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

// Variables we're going to accumulate various data in, to be formatted later
$nproj = [];
$nproj[] = [ "round", "projects" ];
$navail_page_round = [];
$navail_page_round[] = [ "round", "pages available" ];
$stats[] = [];
$pages_last_month = [];
$pages_last_month[] = [ "round", "pages completed last month" ];

// Number of projects in Prep
// Number of projects waiting for QC
// Number of projects without pages
$prepInfo = projectsInPrep();

$newProjInfo = newProjects();
$transitionInfo = transitionCount();
$ppInfo = ppInfo();
$ppvInfo = ppvInfo();

// Accumulate the round data into $stats
foreach ( $Context->Rounds() as $round ) {
    $roundid = $round->RoundId();
    $row = summarize_projects($roundid);

    $phase_icon_path = "$dyn_dir/stage_icons/$roundid.jpg";
    $phase_icon_url  = "$dyn_url/stage_icons/$roundid.jpg";
    if ( file_exists($phase_icon_path) ) {
        $row['round_img'] = "<img src='$phase_icon_url' alt='($roundid)' align='middle'>";
    } else {
        $row['round_img'] = "($roundid)";
    }
    $row['rname'] = RoundIdName($roundid);
    $row['rdesc'] = RoundIdDescription($roundid);
    $row['rlink'] = link_to_round($roundid, $row['rname']);
    $stats[$roundid] = $row;
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

    <table id='charts-table' style='width:100%'>
        <tr>
            <td style='width:33%'>
                <div class='dpchart'>
                    <div id='projects-in-round'></div>
                </div>
            </td>
            <td style='width:33%'>
                <div class='dpchart'>
                    <div id='pages-in-round'></div>
                </div>
            </td>
            <td style='width:33%'>
                <div class='dpchart'>
                    <div id='pages-last-month'></div>
                </div>
            </td>
        </tr>
    </table>

<hr class='w75'>
<ul>\n";

// Start of List formatting

if ( $User->IsProjectManager() ) {
    echo "
    <li>" . link_to_project_manager("Manage My Projects") . "</li>\n";
}

$cp_link = link_to_url("/forumdpc/viewforum.php?f=10", "Providing Content");

echo "
    <li>" . _("Providing Content") . "
    <br> "
    . _("Want to help out the site by providing material for us to proofread? ")
    . "Check out the $cp_link forum and leave a message that you want to help.</li>\n";

// Nested list: Various interesting information.
echo "
    <li style='margin-top:1em; margin-bottom:1em'> What’s Going On?<br> In the Last Week...
        <ul>
";
if ($newProjInfo['n'] > 0)
    echo "<li> Content Providers have created {$newProjInfo['n']} new projects.</li>";

if ($transitionInfo['qc'] > 0)
    echo "<li> QC has released {$transitionInfo['qc']} projects into the P1 queue.</li>";

if ($transitionInfo['queue'] > 0)
    echo "<li> {$transitionInfo['queue']} projects have been released into P1 to start Proofing.</li>";

if ($transitionInfo['p1p2'] > 0)
    echo "<li>{$transitionInfo['p1p2']} projects have finished P1 and entered P2.</li>";

if ($transitionInfo['p2p3'] > 0)
    echo "<li>{$transitionInfo['p2p3']} projects have finished P2 and entered P3.</li>";

if ($transitionInfo['p3f1'] > 0)
    echo "<li>{$transitionInfo['p3f1']} projects have finished P3 and started Formatting.</li>";

if ($transitionInfo['f1f2'] > 0)
    echo "<li>{$transitionInfo['f1f2']} projects have finished F1 and entered F2.</li>";

if ($transitionInfo['f2pp'] > 0)
    echo "<li>{$transitionInfo['f2pp']} projects have finished Formatting and started Post-Processing, to be turned into real books.</li>";

if ($transitionInfo['ppppv'] > 0)
    echo "<li>{$transitionInfo['ppppv']} projects have been turned into books, and start the final verification sanity check.</li>";

if ($transitionInfo['ppvposted'] > 0)
    echo "<li>{$transitionInfo['ppvposted']} projects finished their long, weary journey, and have finally been posted!</li>";

$stats_url = link_to_url('/c/stats/stats_central.php', 'Statistics Central');
echo "
        </ul>
        More statistics at {$stats_url}
    </li>
";

// Emit the prep table
$prep_url = link_to_url("/c/tools/prep.php", _("Project Preparation"));
echo "<li>Pre-rounds project preparation<br>\n";
echo "
    <table class='bordered hub_table'>
    <tr class='navbar'><td></td>
                       <td>Total Projects</td>
                       <td>Awaiting<br>PM</td>
                       <td>Awaiting<br>QC</td>
                       <td>Awaiting<br>Copyright<br>Clearance</td>
                       <td>Awaiting<br>Upload of<br>Page Scans</td>
    </tr>
    <tr>
        <td>(PREP) $prep_url <br>
            Projects are created, copyright clearance obtained, scans uploaded.
        </td>
        <td>{$prepInfo['n']}</td>
        <td>{$prepInfo['pm']}</td>
        <td>{$prepInfo['qc']}</td>
        <td>{$prepInfo['noclear']}</td>
        <td>{$prepInfo['haspages']}</td>
    </tr>
</table>";

// Emit the round statistics table
echo "<li>Page-based Round Processing<br>\n";
echo "
    <table class='bordered hub_table'>
    <tr class='navbar'><td>Round</td>
                       <td>On Hold</td>
                       <td>Available</td>
                       <td>Total<br>Projects</td>
                       <td>Today's<br>Pages<br>Processed</td>
                       <td>Today's<br>Active<br>Users</td>
    </tr>";

foreach ($Context->Rounds() as $round) {
    $phase = $round->RoundId();
    $row = $stats[$phase];
    echo "
        <tr>
            <td>{$row['round_img']} {$row['rlink']} <br> {$row['rdesc']}</td>
            <td>{$row['nwaiting']}</td>
            <td>{$row['navail']}</td>
            <td>{$row['ntotal']}</td>
            <td>{$row['today']}</td>
            <td>{$row['users']}</td>
        </tr>
    ";
}
echo "
    </table>
    </li>
";
// End of round statistics table

echo "
    <li>Book Creation<br>
    <table class='bordered hub_table'>
";

$phase = "PP";
$rname = NameForPhase($phase);
$rdesc = DescriptionForPhase($phase);
$rlink = link_to_pp($rname);

$msg = "";
if ($ppInfo['my_checked_out']) {
    $msg = "You currently have {$ppInfo['my_checked_out']} projects checked out for Post Processing.";
}

echo "
    <tr>
        <td></td>
        <td class='navbar'>Available<br>for PP</td>
        <td class='navbar'>Checked<br>Out</td>
        <td class='navbar'>Total<br>Projects</td>
    </tr>
    <tr>
        <td>($phase) $rlink <br> $rdesc <br> $msg</td>
        <td>{$ppInfo['navail']}</td>
        <td>{$ppInfo['nchecked_out']}</td>
        <td>{$ppInfo['ntotal']}</td>
    </tr>
";


$phase = "SR";
$rname = _("Smooth Reading");
$rdesc = _("Nearly completed projects are often made available for reading and checkproofing before posting.");
$rlink = link_to_smooth_reading($rname);

echo "
    <tr>
        <td rowspan='2'>($phase) $rlink <br> $rdesc</td>
        <td class='navbar'></td>
        <td class='navbar'>Available for<br>Smooth Reading</td>
        <td class='navbar'></td>
    </tr>
    <tr>
        <td></td>
        <td>{$ppInfo['nsmooth']}</td><td></td>
    </tr>
";


// ----------------------------------------------------

$phase = "PPV";
$rname = NameForPhase($phase);
$rdesc = DescriptionForPhase($phase);
$rlink = link_to_ppv($rname);
$msg = "";
if ($ppvInfo['my_checked_out']) {
    $msg = "You currently have {$ppvInfo['my_checked_out']} projects checked out for PP Verification.";
}

echo "
    <tr>
        <td rowspan='2'>($phase) $rlink <br> $rdesc <br> $msg</td>
        <td class='navbar'>Available<br>for PPV</td>
        <td class='navbar'>Checked<br>Out</td>
        <td class='navbar'>Total<br>Projects</td>
    </tr>
    <tr>
        <td>{$ppvInfo['navail']}</td>
        <td>{$ppvInfo['nchecked_out']}</td>
        <td>{$ppvInfo['ntotal']}</td>
    </tr>
";

echo "
    </table>
</li>
";
// End of post-round table

echo "
</ul>
";

makeColumnChart(json_encode($nproj), "Projects Available in Round", "projects-in-round");
makeColumnChart(json_encode($navail_page_round), "Pages Available in Round", "pages-in-round");
makeColumnChart(json_encode($pages_last_month), "Pages Completed Last Month", "pages-last-month");


theme("", "footer");

function summarize_projects($phase) {
    global $dpdb;
    global $nproj;
    global $navail_page_round;
    global $pages_last_month;

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
    $navail_page = get_avail_page($phase);
    $last_month = pagesLastMonth($phase);

    $nproj[] = [ $phase, (int)$navail ];
    $navail_page_round[] = [ $phase, (int)$navail_page ];
    $pages_last_month[] = [ $phase, (int)$last_month ];

    return [
        "nwaiting" => $nwaiting,
        "navail" => $navail,
        "ntotal" => $ntotal,
        "today" => $today,
        "users" => $users
    ];
}

function ppInfo() {
    global $dpdb;
    global $User;
    global $nproj;

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

    $nproj[] = [ "PP", (int)$ntotal ];
    $nproj[] = [ "SR", (int)$nsmooth ];

    return [
        "navail" => $navail,
        "ntotal" => $ntotal,
        "nchecked_out" => $nchecked_out,
        "nsmooth" => $nsmooth,
        "my_checked_out" => $my_checked_out,
    ];
}

function ppvInfo() {
    global $dpdb;
    global $User;
    global $nproj;

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
    $nproj[] = [ "PPV", (int)$ntotal ];
    $nchecked_out = $ntotal - $navail;
    $u = $User->Username();
    $args = [ &$u ];
    $my_checked_out = $dpdb->SqlOneValuePS("
            SELECT COUNT(1) FROM projects
            WHERE ppverifier=?
                AND phase='PPV'", $args);

    return [
        "navail" => $navail,
        "ntotal" => $ntotal,
        "nchecked_out" => $nchecked_out,
        "my_checked_out" => $my_checked_out,
    ];
}

function get_avail_page($phase) {
    global $dpdb;

    // Note we tried this across all phases, and it is considerably slower
    // just because of the way we have the indexes setup.
    $sql = "
        SELECT sum(1)
            FROM page_versions pv
            LEFT JOIN project_holds ph
                ON ph.projectid = pv.projectid AND ph.phase = pv.phase
            WHERE ph.projectid IS NULL
                AND state = 'A'
                and pv.phase='$phase'
    ";
    return $dpdb->SqlOneValue($sql);
}

function pagesLastMonth($phase) {
    global $dpdb;

    $today = strtotime("last month");
    $year = date("Y", $today);
    $month = date("m", $today);
    $sql = "
        SELECT sum(page_count) pc
            FROM user_round_pages
            WHERE phase='$phase'
                AND YEAR(dateval) = '$year'
                AND MONTH(dateval) = '$month'
    ";
    return $dpdb->SqlOneValue($sql);
}

function projectsInPrep() {
    global $dpdb;

    $sql = "
        SELECT
            COUNT(*) nInPrep,
            SUM(CASE WHEN phpm.id IS NULL THEN 0 ELSE 1 END) nPMHolds,
            SUM(CASE WHEN clearance IS NULL THEN 1 ELSE 0 END) NoClear,
            SUM(
                CASE WHEN
                        phqc.id IS NOT NULL
                    AND phpm.id IS NULL
                    AND NOT clearance IS NULL
                THEN 1 ELSE 0 END) nQCWait,
            SUM(
                CASE WHEN
                    NOT EXISTS (
                        SELECT 1 FROM pages WHERE projectid = p.projectid
                    )
                THEN 1 ELSE 0 END) HasPages

            FROM projects p

            LEFT JOIN project_holds phqc 
                ON p.projectid = phqc.projectid
                AND phqc.phase = 'PREP'
                AND phqc.hold_code = 'qc'

            LEFT JOIN project_holds phpm 
            ON p.projectid = phpm.projectid
                AND phpm.phase = 'PREP'
                AND phpm.hold_code = 'pm'

            WHERE p.phase = 'PREP'
    ";
    $row = $dpdb->SqlOneRow($sql);
    return [
        "n"=>$row['nInPrep'],
        "pm"=>$row['nPMHolds'],
        "qc"=>$row['nQCWait'],
        "noclear"=>$row['NoClear'],
        "haspages"=>$row['HasPages'],
    ];
}

function newProjects() {
    global $dpdb;

    $sql = "
        SELECT count(*) n FROM projects WHERE
            createtime > UNIX_TIMESTAMP(DATE_ADD(CURRENT_DATE(), INTERVAL -7 DAY))
    ";
    $row = $dpdb->SqlOneRow($sql);
    return [
        "n" => $row['n'],
    ];
}

function transitionCount() {
    global $dpdb;

    // Transition to P1 (probably also release qc hold)
    // Don't need projects! unless we eventually want title?
    $sql = "
        SELECT
            SUM(CASE WHEN to_phase = 'P1' THEN 1 ELSE 0 END) qcHoldRelease,
            SUM(
                CASE WHEN
                        pe.phase = 'P1'
                    AND event_type = 'release_hold'
                    AND details1 = 'release queue Hold'
                THEN 1 ELSE 0 END) releaseQueue,
            SUM(
                CASE WHEN
                    pe.phase = 'P1' and to_phase = 'P2'
                THEN 1 ELSE 0 END) p1p2,
            SUM(
                CASE WHEN
                    pe.phase = 'P2' and to_phase = 'P3'
                THEN 1 ELSE 0 END) p2p3,
            SUM(
                CASE WHEN
                    pe.phase = 'P3' and to_phase = 'F1'
                THEN 1 ELSE 0 END) p3f1,
            SUM(
                CASE WHEN
                    pe.phase = 'F1' and to_phase = 'F2'
                THEN 1 ELSE 0 END) f1f2,
            SUM(
                CASE WHEN
                    pe.phase = 'F2' and to_phase = 'PP'
                THEN 1 ELSE 0 END) f2pp,
            SUM(
                CASE WHEN
                    pe.phase = 'PP' and to_phase = 'PPV'
                THEN 1 ELSE 0 END) ppppv,
            SUM(
                CASE WHEN
                    pe.phase = 'PPV' and to_phase = 'POSTED'
                THEN 1 ELSE 0 END) ppvposted
            FROM projects p
            JOIN project_events pe ON pe.projectid = p.projectid
            WHERE
                event_time > UNIX_TIMESTAMP(DATE_ADD(CURRENT_DATE(), INTERVAL -7 DAY))
    ";
    $row = $dpdb->SqlOneRow($sql);

    return [
        "qc" => $row['qcHoldRelease'],
        "queue" => $row['releaseQueue'],
        "p1p2" => $row['p1p2'],
        "p2p3" => $row['p2p3'],
        "p3f1" => $row['p3f1'],
        "f1f2" => $row['f1f2'],
        "f2pp" => $row['f2pp'],
        "ppppv" => $row['ppppv'],
        "ppvposted" => $row['ppvposted'],
    ];
}

function makeColumnChart($data, $caption, $div) {

    echo "
<script type='text/javascript' src='https://www.google.com/jsapi'></script>
<script type='text/javascript'>
  google.load('visualization', '1', {packages:['corechart']});
  google.setOnLoadCallback(drawChart);
  function drawChart() {
    // var data = new google.visualization.DataTable();
    var data = new google.visualization.arrayToDataTable(
        {$data}
    );
    var options = {
        title: '$caption',
        vAxis: {baseline: 0},
        legend: {position: 'none'},
    };

    var div = document.getElementById('$div');
    var chart = new google.visualization.ColumnChart(div);
    function resizeChart(event) {
      if (this.resizeTO)
          clearTimeout(this.resizeTO);
      this.resizeTO = setTimeout(function() {
          drawChart();
      }, 1000)
    }
    chart.draw(data, options);
    window.addEventListener('resize', resizeChart, false);
  }

</script>\n";
}


// vim: sw=4 ts=4 expandtab
