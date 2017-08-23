<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
global $relPath;

//$mtime = explode(" ", microtime());
//$starttime = $mtime[1] + $mtime[0];
//
//$time_comment = [];


/** @var DpThisUser $username */

include_once($relPath.'gettext_setup.inc');
include_once($relPath.'stats.php');

function theme($nameofpage, $location, $extra_args = []) {
    global $User, $code_url; // $theme, $theme_name;
    global $Context;
    global $no_stats;

    $t = [];
    $t[] = $Context->TimerInit();

	if(! $User->IsLoggedIn()) {
		$no_stats = 1;
	}

	$statsbar_align = "right";

    if (! $no_stats) {
        // if stats bar on left
        if ($statsbar_align == "left") {

            if ($location == "header") {

                html_header($nameofpage, $extra_args);
                html_logobar();
                echo "
    <table id='tbl1'>
    <tr>
    <td class='left top navback w25'>\n";
                html_statsbar();
                echo "
    </td>
    <td class='right top mainbody'>
    <img src='$code_url/graphics/r_curve.gif'
    alt='Provides a round curve for a navigation bar'></td>
    <td class='left top mainbody'>\n";
            }

// -------------------------------------------------
//  footer when NOT $no_stats and stats bar on left
// -------------------------------------------------

            else if ($location == "footer") {
                echo "</td></tr></table>\n";
                html_footer();
            }
        }
        else {
            if ($location == "header") {

// -------------------------------------------------
//  header when NOT $no_stats and stats bar on right
//  ********** MOST COMMON CASE ***************
// -------------------------------------------------

                html_header($nameofpage, $extra_args);
                html_logobar();
                echo "
    <table id='tbl2' class='w100'>
    <tr>
    <td id='tbl2c1' class='w75 left top'>\n";
            }

// -------------------------------------------------
//  footer when NOT $no_stats and stats bar on right
// -------------------------------------------------

            else if($location == "footer") {
                echo "
    </td>
    <td id='tbl2c2' class='right top'>
        <img src='$code_url/graphics/r_curve.gif' style='position: relative; top: -1px;' alt=''>
    </td>
    <td id='tbl2c3' class='navbar w25 left top'>\n";
                html_statsbar();
                echo "</td></tr></table>\n";
                html_footer();
            }
        }
    }
    else {
        // is no_stats
        if ($location == "header") {

// -----------------------------------------------------
//  header when IS $no_stats so no stats bar to position
// -----------------------------------------------------

            html_header($nameofpage, $extra_args);
            html_logobar();
            echo "
    <table id='tbl3' class='w100'>
    <tr>
    <td class='left top mainbody'>&nbsp;</td>
    <td class='left top w100 mainbody'>\n";
        }
        else if($location == "footer") {

// -----------------------------------------------------
//  footer when IS $no_stats so no stats bar to position
// -----------------------------------------------------

            echo "</td></tr></table>\n";
            html_footer();
        }
    }
}

function html_header($nameofpage, $extra_args = []) {
    global $code_url;

    $incl = "";
    $onload = "";
    foreach($extra_args as $key => $val) {
        switch($key) {
            case "hdr_include":
                break;

            case "css_file":
                $incl .= "<link rel='stylesheet' href='$val'>\n";
                break;

            case "css_data":
                $incl .= "<style type='text/css'>$val</style>\n";
                break;

            case "js_text":
            case "js_data":
                $incl .= "<script type='text/javascript'>$val</script>\n";
                break;

            case "js_file":
                $incl .= "<script type='text/javascript' src='$val'></script>\n";
                break;

            case "body_onload":
                $onload .= " onload='$val'";
                break;
        }
    }

    echo
"<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<link rel='shortcut icon' href='$code_url/favicon.ico'>
<script type='text/javascript' src='/c/js/sorttable.js'></script>\n";

echo "
<script type='text/javascript' src='/c/js/dp.js'></script>
<title>DPC: ". (isset($nameofpage)
                    ? "$nameofpage"
                    : "Distributed Proofreaders Canada" )
. "</title>
<link type='text/css' rel='Stylesheet' href='/c/css/dp.css'>\n";
    echo
"$incl
</head>
<body $onload>\n";
}

function html_logobar() {
    global $Context;
    global $code_url;
    global $User;
    global $completed_books_offset;

    $numproj = $Context->PostedCount();

    echo "
	<table id='tbl_logobar' class='w100'>

    <!--  LOGO BAR -->

    <tr><td class='logobar w50'>
        <a href='/c/default.php'>
            <img src='$code_url/graphics/dpclogo.png'"
                ." width='336' height='68' alt='Distributed Proofreaders'>
        </a>
    </td>

    <td class='w50 center middle logobar'>
        <span class='logobar'>
        " . sprintf( _('%s titles preserved for the world!'), number_format(($numproj + $completed_books_offset))) ."
        </span>
    </td>
    </tr>

	</table>\n";

	// --------------------------------------------------------------------
	//    navbar
	// --------------------------------------------------------------------

	$divider        = " · ";

	// form has no action because all pages start with dpinit.php, which handles the form variables
	echo "
	<form id='frmlogin' name='frmlogin' method='post'>
	<table id='tblnav'>

    <tr>
    <td>&nbsp;</td>
    <td>\n";

    if (! $User->IsLoggedIn()) {
	    $link_to_forums_login = "<a href='/forumdpc/ucp.php?mode=login'>Forums</a>";
	    $items = [link_to_site("DPC")];
	    $items[] = $link_to_forums_login;
	    echo implode($divider, $items);
        echo "
       <td class='right'>ID:
            <input type='text' name='userNM' size='10' tabindex='1' maxlength='50'> &nbsp;" . _("Password:") . "
            <input type='password' name='userPW' size='10' tabindex='2'
            maxlength='50'> &nbsp;
            <input type='submit' name='submit_login' id='submit_login' value='" . _("Sign In") ."' style='color: black;'>\n";
	    echo link_to_registration();
        echo "&nbsp;
        </td>\n";
    }
    else {
	    $items = [link_to_site("DPC")];
	    $items[] = link_to_activity_hub();
	    $items[] = link_to_search("Project Search");
	    $items[] = link_to_my_projects();
	    $items[] = link_to_preferences();

        $inbox_text = _("My Inbox");
	    $numofPMs = $User->InboxCount();
        if($numofPMs[0] > 0) {
            $inbox_text .= sprintf(_(" (%s unread)"), $numofPMs[0]);
		}
	    $items[] = link_to_inbox($inbox_text);
	    $items[] = link_to_forums();
	    $items[] = link_to_wiki();
	    $items[] = link_to_logout("Log out ({$User->Username()})");

	    echo implode($divider, $items);

        echo "</td>\n";

        echo "<td class='right'>\n";
        show_quick_links();
        echo "&nbsp;</td>";
    }


    echo "</tr>\n";
    echo "</table>   <!-- tblnav -->  \n";
	echo "</form>\n";
}

function html_statsbar() {
    global $User;
    global $Context, $t;
    global $code_url;

    $round_id = Arg('roundid');

    if($round_id == "") {
        show_completed_projects();
    }
    else {
        show_round_specific_stats( $round_id );
    }
    $t[] = $Context->TimerGet();

    if($User->IsLoggedIn()) {
        echo "
        <div class='center sidebar'>
        <a href='$code_url/stats/stats_central.php'>
        ". _("More Statistics")."</a>
        </div>\n";

        if(Arg("sr")) {
            show_sr_projects();
        }

        echo "<hr class='w75 center'>\n";
        show_user_teams();
        $t[] = $Context->TimerGet();

        echo "<hr class='w75 center'>\n";
        show_help_links();
	    echo "<hr class='w75 center'>\n";
        show_admin_sidebar();
        $t[] = $Context->TimerGet();
        echo "<hr class='w75 center'>\n";
        echo "\n<!-- Statsbar Timer: " . implode("  ", $t) . "-->\n";
    }
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_quick_links() {
    global $User;

    if (! $User->IsLoggedIn())
        return;

    $items = [];

    if ($User->IsProjectManager() || $User->IsSiteManager()) {
        $items[] = link_to_project_manager("PM");
    }

	foreach(["P1", "P2", "P3", "F1", "F2"] as $phs) {
        if($User->MayWorkInRound($phs)) {
	        $items[] = link_to_round($phs);
        }
    }
	if($User->MayWorkInRound("PP")) {
		$items[] = link_to_pp();
	}
	if($User->MayWorkInRound("PPV")) {
		$items[] = link_to_ppv();
	}
	$items[] = link_to_smooth_reading("SR");

	$divider        = "\n · ";
	echo implode($divider, $items);
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_round_specific_stats( $round_id ) {
    global $User;
    global $Context, $t;

    // Put the whole thing in a table, just so we can put a box around it.
    echo "
    <table id='tbl5' class='center w95 sidebar b111'>
    <tr>
    <td>\n";

    // Show the site statistics

    echo "
        <h5 class='center'>" . _("Pages completed in $round_id") . "</h5>
        <h5 class='center'>" . _("Site Statistics:") . "</h5>\n";

    $todaygoal      = number_format(PhaseGoalToday($round_id));
    $t[] = $Context->TimerGet();
    $yesterdaygoal  = number_format(PhaseGoalYesterday($round_id));
    $t[] = $Context->TimerGet();
    $monthgoal      = number_format(PhaseGoalMonth($round_id));
    $t[] = $Context->TimerGet();
    $todaycount     = number_format(PhaseCountToday($round_id));
    $t[] = $Context->TimerGet();
    $yesterdaycount = number_format(PhaseCountYesterday($round_id));
    $t[] = $Context->TimerGet();
    $monthcount     = number_format(PhaseCountMonth($round_id));
    $t[] = $Context->TimerGet();
    $monthpct       = PhaseGoalMonth($round_id) == 0
                        ? 0
                        : PhaseCountMonth($round_id) / PhaseGoalMonth($round_id) * 100;
    $strnow         = strftime("%R");
    $strmonthgoal   = strftime(_("%B's Goal"));
    $strmonthcount  = strftime(_("%B's Total"));
    $strpct         = number_format($monthpct, 1);

    // Today's Numbers
    echo "<ul class='clean'>\n";
	if ($todaygoal != 0)
		echo _("<li>Today's Goal: $todaygoal</li>\n");
   echo	_("<li>Today's Total: $todaycount ($strnow)</li>\n\n");

	if ($yesterdaygoal != 0)
		echo _("<li>Yesterday's Goal: $yesterdaygoal</li>\n");
	echo _("<li>Yesterday's Total: $yesterdaycount</li>\n\n");

	if ($monthgoal != 0)
		echo _("<li>$strmonthgoal: $monthgoal</li>\n");
	echo _("<li>$strmonthcount: $monthcount ({$strpct}%)</li>\n")

       . "</ul>\n";

    if ($User->IsLoggedIn()) {
        $rankname = honorific_for_count($round_id, $User->RoundPageCount($round_id));

        $t[] = "Start Personal";
        $t[] = $Context->TimerGet();
        echo "<h5>" . _("Personal Statistics:") ."</h5>
            <span> "
                . _("Pages Today: "). number_format($User->RoundTodayCount($round_id))
                ."<br> &nbsp;"
                . _("Total Pages: "). number_format($User->RoundPageCount($round_id))
                ."<br> &nbsp;"
                . _("Current Position: "). number_format($User->RoundRank($round_id))
                ."<br> &nbsp;"
                . _("Current Rank:<br/>") . "$rankname<br>
            </span>
            <div class='center' style='margin-top: 1em;'>"
             .link_to_member_stats($User->Username(), $round_id, "Details")
                    . "</a>
            </div>\n";
        $t[] = $Context->TimerGet();

        // -------------------------------------------------------------
        //Show the Neighbor table if the user has requested so
        if($User->NeighborRadius()) {
            $t[] = "Neighborhood";
            $t[] = $Context->TimerGet();
            $neighbors = $User->RoundNeighborhood($round_id, $User->NeighborRadius());
            $t[] = $Context->TimerGet();
            echo "
            <br>
            <hr class='w75 center'>
            <br>
            <h5>Your Neighborhood:</h5>\n";

            $ntbl = new DpTable("tblneighbors", "center padded sidebar nocaption");
            $ntbl->AddColumn("^Rank", "rank");
            $ntbl->AddColumn("<Name", "username", "ename");
            $ntbl->AddColumn(">Pages", "page_count");
            $ntbl->SetRows($neighbors);
            $ntbl->EchoTable();
        }
    }

    echo "<br>";
    echo "</td></tr></table>";
}

function show_sr_projects() {
    global $dpdb;
    // ---------------------------------------------------------------------

    $sql = "SELECT  nameofwork,
                    authorsname,
                    DATEDIFF(DATE(FROM_UNIXTIME(smoothread_deadline)), CURRENT_DATE()) days_left
            FROM projects
            WHERE DATE(FROM_UNIXTIME(smoothread_deadline)) >= CURRENT_DATE()
            ORDER BY smoothread_deadline";
    $rows = $dpdb->SqlRows($sql);

    echo "<div id='divsr' class='sidebar'>
          <h5>" . _("Available Smooth Reading Projects:") ."</h5>
    <ul>\n";
    foreach($rows as $row) {
        echo "<li><i>{$row['nameofwork']}</i> by {$row['authorsname']}</li>\n";
    }
    echo "</ul></div>\n";

}

function honorific_for_count($round_id, $count) {
    global $Honorifics;
    $dignity = "Novice";
    foreach($Honorifics[$round_id] as $key => $value) {
        $dignity =  $value;
        if($count < $key) {
            break;
        }
    }
    return $dignity;
}

function ename($name) {
    global $User;
    return $name == $User->Username()
        ? "<b>$name</b>"
        : $name;
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_user_teams()
{
    global $User;

    echo "<div id='divteams' class='sidebar margined padded'>
          <h5>" . _("Your Teams") ."</h5>
        <ul class='clean'>\n";

    foreach($User->Teams() as $team) {
        /** @var DpTeam $team */
        echo "<li>" . link_to_team($team->Id(), $team->TeamName(), true) . "</li>\n";
    }

    echo "</ul>
    </div>\n";

    echo "<div class='center'>
    " . link_to_team_list("View all teams", true)
    . "</div>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX


function show_completed_projects() {
    global $dpdb;

    echo _("<h5>Completed Projects:</h5>\n");
    echo "<table id='tbl6' class='sidebar padded margined'>\n";
    $rows = $dpdb->SqlRows("
        SELECT COUNT(1) c, MONTH(d) m, MONTHNAME(d) mname, YEAR(d) y
        FROM (
            SELECT FROM_UNIXTIME(event_time) d
            FROM project_events pe
            WHERE event_type = 'post'
            AND event_time >
                UNIX_TIMESTAMP( DATE( DATE_FORMAT(
                    DATE_ADD( CURRENT_DATE(), INTERVAL -1 YEAR), '%Y-%m-01')))
        ) tbl
        GROUP BY m, y
        ORDER BY y, m");

    foreach($rows as $row) {
        $displaydate = "{$row['mname']} {$row['y']}";
        echo "
        <tr><td class='left'>&nbsp;$displaydate&nbsp;&nbsp;</td>
            <td class='right'>&nbsp;{$row['c']}&nbsp;</td>
        </tr>\n";
    }
    echo "</table>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_help_links() {
    global $wiki_url;

    echo "
        <div id='divhelp' style='padding: 0 1.5em' class='sidebar margined'>
        <h5>"._("Key Help Documents:")."</h5>
		<a href='{$wiki_url}/FAQ_Central'>"._("Frequently Asked Questions")."</a>
		<br>
		".link_to_proofing_guidelines()."
		<br>
		".link_to_formatting_guidelines()."
		</div>\n";
}

function show_admin_sidebar() {
    global $User;
    echo "
    <div id='admin_sidebar' class='sidebar center'>
    <h5>Admin Links</h5>
    <pre class='left'>
    <a href='/c/tools/prep.php'>Projects Before P1</a>
    <a href='/c/tools/p1counts.php'>P1 Genre Counts</a>
    <a href='/c/tools/active_projects.php'>Active Project List</a>
    <a href='/c/tools/holds.php'>Active Projects On Hold</a>
    <a href='/c/tools/qc.php'>Projects On QC Hold</a>
    <a href='/c/search2.php'>Extended Project Search</a>
    <a href='/c/user_pages.php'>Diffs for a Round</a>
    <a href='/c/tools/bad.php'>Bad Pages</a>
    <a href='https://docs.google.com/spreadsheets/d/1Ut-t3zDouO5eSJShQsreG0LTjvFP2dszgXHGa-CUWcY/edit#gid=0'>DoD Spreadsheet</a>
    <a href='https://docs.google.com/spreadsheets/d/1lU5VeCLMIPhkqqGc-g0iE7Ld5EU5b-sI7DcoKiUwZPo/edit#gid=0'>Clearance Spreadsheet</a>\n";
        if($User->IsSiteManager() || $User->IsProjectFacilitator() || $User->IsProjectManager()) {
            echo "
    <a href='/c/tools/site_admin/user_roles.php'>Manage Roles for a User</a>
    <a href='/c/tools/p1release.php'>Project P1 Release</a>
    <a href='/c/tools/pper.php'>Post Processor Report</a>
    <a href='/c/tools/site_admin/sitenews.php'>Site News Central</a>\n";
        }
        echo "
    </pre>\n";
//        if($User->IsSiteManager()
//            || $User->IsProjectFacilitator()
//            || $User->IsProjectManager()
//            || $User->IsProjectManager()) {
//            echo "
//        <form id='frmadmin' name='frmadmin' action='' method='POST'>
//        <div class='lfloat'>
//             <p>Project Page for project ID:
//            <input type='text' name='link_projectid' id='link_projectid'>
//            <input type='submit' name='cmdprojectid' id='cmdprojectid' value='Submit'></p>
//		</div>
//        </form>\n";
//        }
        echo "
    </div>
    <hr class='left clear hidden' />\n";
}

function show_backlogs() {
    echo "
          <iframe src='backlog.php' style='width: 400px; height: 300px;
            overflow: hidden; border; none;>
          </iframe>\n";
}


// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function timer_string() {
    global $Context;
    return (string) $Context->TimerGet();
//    global $starttime;
//    $mtime = explode(" ", microtime());
//    $endtime = $mtime[1] + $mtime[0];
//    $totaltime = ($endtime - $starttime);
//    return left($totaltime, 5);
}

//function timer_array() {
//    global $time_comment;
//    return $time_comment;
//}

//function timer_milestone($remark) {
//    global $time_comment;
//    $time_comment[] = "$remark : " . timer_string();
//}

function html_footer() {
//    global $User;
    global $code_version;

    $strtime = timer_string();

    echo "<div id='divfooter' class='w100 em80 center white noserif redback'>"
        . _("Copyright Distributed Proofreaders Canada")
        . _(" (Page Build Time: {$strtime} ")
        . _(" Version $code_version")
        . "</div> <!-- divfooter -->\n";
//    if($User->Username() == 'dkretz') {
//        echo implode("<br>\n", timer_array());
//    }

    echo "\n</body>\n";
    echo "</html>\n";
}
