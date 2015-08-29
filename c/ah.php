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
include_once($relPath.'stages.inc');
include_once($relPath.'site_news.inc');
include_once($relPath.'mentorbanner.inc');
include_once($relPath.'RoundsInfo.php');

$rows = $dpdb->SqlObjects("
SELECT DATE_FORMAT(d.dateval, '%m-%d') mmdd,
        SUM(CASE WHEN round_id = 'P1' THEN page_count ELSE 0 END) p1count,
        SUM(CASE WHEN round_id = 'P2' THEN page_count ELSE 0 END) p2count,
        SUM(CASE WHEN round_id = 'P3' THEN page_count ELSE 0 END) p3count,
        SUM(CASE WHEN round_id = 'F1' THEN page_count ELSE 0 END) f1count,
        SUM(CASE WHEN round_id = 'F2' THEN page_count ELSE 0 END) f2count
FROM days d
JOIN user_round_pages urp
    ON urp.count_time BETWEEN d.min_unixtime AND d.max_unixtime
WHERE d.dateval > DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)
GROUP BY d.dateval
ORDER BY d.dateval");

$data = array();
$data[0] = array("Day", "P1", "P2", "P3", "F1", "F2");

foreach($rows as $row) {
    $data[] = array($row->mmdd, $row->p1count, $row->p2count, 
                    $row->p3count, $row->f1count, $row->f2count);
}


$dary = json_encode($data);
$dary = preg_replace("/\"(\d\d\d)\"/", "$1", $dary);


$ahtitle = _("Activity Hub");

echo 
"<!DOCTYPE HTML>
<html lang='en'>
<head>
<meta charset=utf-8>
<link rel='shortcut icon' href='http://www.pgdpcanada.net/c/favicon.ico'>
<title>DPC: Activity Hub</title>
<script type='text/javascript' src='/c/js/sorttable.js'></script>
    <script type='text/javascript' src='https://www.google.com/jsapi'></script>
    <script type='text/javascript'>
      google.load('visualization', '1', {packages:['corechart']});
      google.setOnLoadCallback(drawChart);

      function drawChart() {
        var data = google.visualization.arrayToDataTable(
        $dary
        );
        var options = {
          title: 'Daily Pages by Round',
          width: 600,
            curveType: 'function',
          height: 175,
          vAxis: { minValue: 0 }
        };

        var chart = new google.visualization.LineChart(
            document.getElementById('chart_div')
        );
        chart.draw(data, options);
      }
    </script>
    <style type='text/css'>
    div#chart_div div {
        overflow: hidden;
    }
    </style>

<link type='text/css' rel='Stylesheet' href='/c/css/dp.css'>

</head>
<body >\n";

// theme($ahtitle, "header");
html_logobar($ahtitle);

echo "
    <div class='center overflow'>
        <img src='$code_url/graphics/Activity_Hub.jpg' alt='$ahtitle'>
    </div>\n";


if($User->IsSiteManager() || $User->IsProjectFacilitator() || $User->IsProjectManager()) {
    echo "
    <div id='admin_links' class='lfloat w50'>
      <h5>Admin Links</h5>
      <pre>
        <a href='http://www.pgdpcanada.net/c/tools/prep.php'>Projects Before P1</a>
        <a href='http://www.pgdpcanada.net/c/tools/site_admin/user_roles.php'>Manage Roles for a User</a>
        <a href='http://www.pgdpcanada.net/c/user_pages.php'>User Diffs for a Round</a>
        <a href='http://www.pgdpcanada.net/c/tools/p1release.php'>Project P1 Release</a>
        <a href='http://www.pgdpcanada.net/c/tools/p1counts.php'>P1 Genre Counts</a>
        <a href='http://www.pgdpcanada.net/c/tools/trace.php'>Project Trace</a>
      </pre>
    </div>

    <div id='chart_div' style='width: 700px; height: 200px; margin: 0; padding: 0;
                position: relative;'>
    </div>
    <hr class='left clear hidden' />\n";
}

$pagecount = $User->PageCount();
echo _("<p>Welcome to the DP Activity Hub. From this page you can view the
phases of DP production.  Follow the links to the specific areas of the site.</p>");


if ($pagecount <= 300) {
    if ($User->InboxCount() > 0) {
        echo 
        "<hr class='w75'>
        <div class='center'>\n";

        echo _("
        <p style='color: red;'>You have received a private message in your Inbox!</p>");
        echo _("<p>This could be from somebody sending you feedback on some of the
        pages you had proofread earlier. We strongly recommend you READ your
        messages. In the links at the top of this page, there is one that says
        My Inbox. Just click on that to open your Inbox.</p>");
        echo _("<p>(After a while this explanatory paragraph will not appear when
        you have new messages, but the link to your Inbox will always be up
        there and when you have new messages that will be shown in the link)</p>");
        echo "</div>";
    }
}



if ($pagecount <= 100) {
    echo "
        <hr class='w75'>
        <div class='center'>
        <h1 style='color: blue;'>"
        ._("Welcome")
        ."</h1>\n"
        ._("<p>Please see our ") ."<a href='$beginners_site_forum_url'>"
        ._("Beginner's Forum")
        ."</a>". _(" for answers to common questions.</p>
        </div>\n");
}


// Site News
echo "
<div class='center'>
<hr class='w75'>\n";

show_news_for_page("HUB");

echo "</div>\n";

$feedback_url = "$forums_url/viewtopic.php?f=3&t=388";
echo "
<div class='center'>
<hr class='w75'>\n"
._("<h4>New Proofreaders</h4>")
."<p><a href='$feedback_url'>"
._("What did you think of the Mentor feedback you received?")
."</a></p>
</div>\n";


// Show any mentor banners.
if($User->MayMentor()) {
    mentor_banner($round);
}
// =============================================================================

echo "
<hr class='w75'>
<ul>\n";

if ( user_is_PM() ) {
    echo "
    <li>" . link_to_project_manager("Manage My Projects") . "</li>\n";
}

// ----------------------------------

$acounts = array();
$rows = $dpdb->SqlObjects("
    SELECT phase, COUNT(1) AS scount
    FROM projects
    GROUP BY phase
    ORDER BY phase");
foreach($rows as $row) {
    $acounts[$row->phase] = $row->scount;
}



// Providing Content
echo "
    <li>" . _("Providing Content") 
    . "<br>"
    . _("Want to help out the site by providing material for us to proofread? ")
    . "<a href='$code_url/faq/cp.php'>"
    . _("Find out how!")
    . "</a>
    </li>\n";

foreach ( $Context->Rounds() as $roundid ) {
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
    echo sprintf( _("You currently have %d projects checked out in this phase."), $n_checked_out );
    echo "<br>\n";
}

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
    echo sprintf( _("You currently have %d projects checked out in this phase."), $n_checked_out );
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
                               <td>Total Projects</td></tr>

            <tr><td>$nwaiting</td><td>$navail</td><td>$ntotal</td></tr>
            </table>\n");
        return;
    }

    if($phase == "PP") {
        $row = $dpdb->SqlOneRow("
             SELECT SUM(CASE WHEN IFNULL(postproofer, '') = '' THEN 1 ELSE 0 END) navail,
                    COUNT(1) ntotal
             FROM projects where phase = '$phase'");
    }
    else {
        $row = $dpdb->SqlOneRow("
             SELECT SUM(CASE WHEN IFNULL(ppverifier, '') = '' THEN 1 ELSE 0 END) navail,
                    COUNT(1) ntotal
             FROM projects where phase = '$phase'");
    }
    $navail = $row["navail"];
    $ntotal = $row["ntotal"];
    $nchecked_out = $ntotal - $navail;

    echo _("
        <table class='bordered hub_table'>
        <tr class='navbar'><td rowspan='2'>All projects</td>
                           <td>Available for $phase</td>
                           <td>Checked Out</td>
                           <td>Total Projects</td></tr>

        <tr><td>$navail</td><td>$nchecked_out</td><td>$ntotal</td></tr>
        </table>\n");


}

?>
