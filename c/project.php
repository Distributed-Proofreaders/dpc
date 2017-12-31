<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath='./pinc/';

include_once($relPath.'dpinit.php');
include_once($relPath.'rounds.php');
include_once($relPath.'RoundsInfo.php');
include_once 'pt.php'; // echo_page_table

$User->IsLoggedIn()
	or RedirectToLogin();

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

// Usually, the user arrives here by clicking on the title of a project
// in a list of projects.

$projectid              = Arg('projectid', Arg('id'));
$project                = new DpProject( $projectid );
$level                  = Arg('detail_level', $project->UserMayManage() ? '3' : '2');
$btn_manage_words       = IsArg("btn_manage_words");
$btn_manage_files       = IsArg("btn_manage_files");
$btn_manage_holds       = IsArg("btn_manage_holds");
$linktotopic            = Arg("linktotopic");

$submit_post_comments   = IsArg("submit_post_comments");
$postcomments           = Arg("postcomments");
$smoothcomments         = Arg("smoothcomments");

$post_notify            = IsArg("post_notify");
$smooth_notify          = IsArg("smooth_notify");

$submit_srcomments      = IsArg("submit_srcomments");
$srdays                 = ArgInt("srdays");
$srcomments             = Arg("srcomments");

$submit_export          = Arg("submit_export");
$submit_view            = Arg("submit_view");
$submit_view_both       = Arg("submit_view_both");
$exportphase            = Arg("exportphase");
$exact                  = Arg("exact", "0");
$exportinclude          = Arg("exportinclude");

if($smooth_notify) {
    $project->ToggleSmoothNotify();
}
if($post_notify) {
    $project->TogglePostNotify();
}
if($submit_srcomments) {
    $project->SetSmoothDeadlineDays($srdays);
    $project->SetSmoothComments(h($srcomments));
}
if($btn_manage_words) {
    divert(url_for_project_words($projectid));
    exit;
}

if($btn_manage_files) {
    divert(url_for_project_files($projectid));
    exit;
}
if($btn_manage_holds) {
    divert(url_for_project_holds($projectid));
    exit;
}

if($linktotopic) {
    if(! $project->ForumTopicId()) {
        $project->CreateForumThread();
    }
    $topicid = $project->ForumTopicId();
    divert($project->ForumTopicUrl());
    exit;
}

if($submit_view_both) {
    divert(url_for_view_text_and_images($projectid));
    exit;
}

if($submit_export || $submit_view) {
    switch($exportphase) {

        case "OCR":
            $exportphase = "PREP";
            break;

        case "PREP":
        case "P1":
        case "P2":
        case "P3":
        case "F1":
        case "F2":
            break;

        default:
            $exportphase = $project->Phase();
            break;
    }

    if($Context->PhaseSequence($exportphase) > $project->PhaseSequence()) {
        $exportphase = $project->Phase();
    }

    if($submit_export) {
        $text = $project->PhaseExportText($exportphase, $exportinclude, $exact);
        send_string("{$projectid}_{$exportphase}.txt", $text);
    }
    else {
        divert(url_for_view_text($projectid, $exportphase, $exportinclude, $exact));
    }
    exit;
}
// -----

$project->MaybeAdvanceRound();

// if user submitted comments for post processing, load them
if($submit_post_comments) {
    $project->SetPostComments($postcomments);
}

// -----------------------------------------------------------------------------

// In a tabbed browser, the page-title passed to theme() will appear in
// the tab, which tends to be small, as soon as you have a few of them.
// So, put the distinctive part of the page-title (i.e. the name of the
// project) first.

switch($project->Phase()) {
	case "F1":
	case "F2":
//    	$verb = "format";
        $noun = "formatting";
		break;

	default:
//		$verb = "proofread";
		$noun = "proofreading";
		break;
}

$title_for_theme = $project->NameOfWork() . _(' project page');

// touch modifieddate whenever PPer views this page
//if($project->UserIsPPer() && $project->Phase() == "PP") {
//    $project->SetModifiedDate();
//}

// confusing call to prepare top and bottom status boxes
//list($top_status, $bottom_status) = top_bottom_status($project);

// -------------------------------------------------------------------------------
//   Display
// -------------------------------------------------------------------------------
//
//if ($level == 1) {
//    theme($title_for_theme, "header");
//    echo "<div id='divproject' class='px800 lfloat clear'>
//            <h1 class='center'>{$project->Title()}</h1>\n";

//    detail_level_switch($project, $level);
//    project_info_table($project);
//    echo "</div>  <!-- divproject -->\n";
//    exit;
//}




// don't show the stats column
$no_stats = 1;
theme($title_for_theme, "header");


echo "<div id='divproject' class='lfloat clear'>\n";
//detail_level_switch($project, $level);
//echo "<div id='divtrace' class='rfloat margined j5'>"
//     .link_to_project_trace($projectid, "Project Trace")
//     ."</div>  <!-- divtrace -->\n";
echo "<h1 class='center clear'>
        {$project->Title()}<br>
        <span class='em80'>by {$project->Author()}</span></h1>\n";

echo_top_box($project);
//echo "
//    <div id='status_box_1' class='status_box'>
//    </div>   <!-- status_box_1 -->\n";

project_info_table($project);

/*
if($project->Phase() == 'PREP'
	&& $project->CPComments() != "") {
	display_cp_comments($project);
}


if($project->Phase() == 'PP' ) {
    solicit_smooth_reading($project);
	solicit_postcomments($project);
}

if( $project->Phase() == 'PPV') {
	solicit_postcomments($project);
}
*/


//if($level > 2) {

//	if ( $project->UserMayManage() ) {
//		show_management_box( $project , $level);
//	}

//    if($project->IsInRounds()) {
//        show_page_summary( $project );
//    }

    /*
    if($project->IsRoundsComplete()
            && ($User->MayWorkInRound("PP") || $User->MayWorkInRound("PPV"))) {
        offer_pp_downloads( $project);
    }
    */

//    offer_downloads($project) ;
//}

//if($level > 3) {

//	if ( $project->Phase() == "PPV" && $User->MayPPV() ) {
//		solicit_pp_report_card( $project );
//	}

//    offer_text_downloads( $project );
//	offer_extra_files( $project );
//	show_history( $project );

	echo "</div> <!-- divproject -->\n";

/*
	if($project->PageCount() > 0) {
		show_page_table( $project );
	}
*/
//}
echo "</div>  <!-- divproject -->\n";

echo "<hr class='lfloat w50 clear'>\n";
//detail_level_switch($projectid, $level);
theme('', 'footer');
exit;

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/*
function detail_level_switch($project, $level = 3) {
    echo "
    <div class='lfloat margined clear'>\n";
    echo_top_box($project, $level);
    echo "</div>  <!-- detail_level_switch (not an id) -->\n";
//        Viewing page at detail level $level.&nbsp;&nbsp;Switch to: ");
//        for($i = 1; $i <= 4; $i++) {
//        if ( $i != $level ) {
//            echo link_to_project_level($projectid, $i, $i);
//        }
}
*/

/**
 * @param DpProject $project
 */
function echo_top_box($project) {
    echo "
    <div class='center margined clear bordered w50'>\n";

    $phase = $project->Phase();
    switch($phase) {
        case "P1":
        case "P2":
        case "P3":
            $proof_link = link_to_proof_next($project->ProjectId(), "start proofreading");
            break;

        case "F1":
        case "F2":
            $proof_link = link_to_proof_next($project->ProjectId(), "start formatting");
            break;

        default:
            $proof_link = "Not available";
            break;
    }

    echo "
    <div class='liner'>
        <span class='em200'>$phase</span> $proof_link
    </div>\n";
    echo "</div>\n";


}

function echo_level($lvl, $checked) {
    return $lvl . " <input type='radio' name='rdolevel' "
        . ($checked ? "checked='checked'" : "") . ">";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/** @var DpProject $project
 * @return array
 */
/*
function top_bottom_status($project) {
	global $noun;
    global $User;
    global $dpdb;

	$phase = $project->Phase();
	if($phase == "PP" && $project->UserIsPPer()) {
		$msg = _( "(PP) - Project is yours to Post Process." );
	}
	else if(! $phase == "PPV" && $project->UserIsPPVer()) {
		$msg = _( "($phase} yours to Post Process." );
	}
	else if(! $project->IsAvailable()) {
		$msg = _("$phase - not available.");
	}
	else if(! $project->UserMayProof()) {
		$msg = _("$phase - not available for you to proof.");
	}
	else if(! $project->IsAvailableForActiveUser()) {
		$msg = _("$phase - project is available, but there are no pages for you now.");
	}
	else {
		$msg = "";
	}

	if($msg !== "") {
		return array( $msg, $msg );
	}

	$projectid = $project->ProjectId();
	$username = $User->Username();
	$user_save_time  = $dpdb->SqlOneValue("SELECT IFNULL(MAX(version_time), 0)
									 FROM page_versions
									 WHERE projectid = '$projectid'
									 	AND phase = '$phase'
									 	AND username = '$username'");
    // If there's any proofreading to be done, this is the link to use.
	$label = _("{$project->Phase()} - start $noun");
    $proofreading_link = link_to_proof_next($project->ProjectId(), $label);

    // When was the project info last modified?
//    $last_edit_info = _("Project information last modified:")
//        . " " . $project->LastEditTimeStr()
//        . ($user_save_time == 0 ? "" : "<br>Proofed by you: " . std_date($user_save_time));

    // Other possible components of status:
    $please_scroll_down = _("Please scroll down and read the Project Comments
    for any special instructions <b>before</b> $noun!");

    $the_link_appears_below = _("The 'Start $noun' link appears below
    the Project Comments");

    $info_have_changed =
        "<p class='nomargin red bold'>"
        . _("Project information has changed!")
        . "</p>";

    // ---

    $bottom_status = "$proofreading_link";

//	$mod_time  = $timerow['modifieddate'];
    if (! $user_save_time) {
        $top_status = "$please_scroll_down
                      <br> $the_link_appears_below";
    }
    else if($user_save_time < $project->LastEditTime()) {
        // The user has saved a page for this project.

            // The current page-save was before the info was revised.
            // The user probably hasn't seen the revised project info.
            $top_status = "$info_have_changed <br/> $please_scroll_down
                <br>
		    $the_link_appears_below";
	}
	else {
            // The current page-save was after the info was revised.
            // We'll assume that the user has read the new info.
            $top_status = "$please_scroll_down
                <br> $proofreading_link";
    }

    return array( $top_status, $bottom_status );
}
*/

// -----------------------------------------------

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/**
 * @param DpProject $project
 */
function project_info_table($project) {
//    global $User;

    /** @var DpProject $project */

//    $projectid      = $project->ProjectId();
//    $postcomments   = $project->PostComments();
//    $postcomments   = str_replace("\n", "<br />", h($postcomments));



    // -------------------------------------------------------------------------
    // The state of the project

//    $available_for_SR = ( $project->SmoothreadDeadline() > time() );

//    $phase = $project->Phase();
//    $right = RoundIdDescription($phase);

//    echo_top_box($project, $level);
    echo "
    <div id='div_project_info_table' class='project_table margined padded clear'>
    <table id='project_info_table' class='w60 lfloat project_info'>\n";

//    if($project->Phase() == 'POSTED') {
//        echo_row_left_right( _( "Posted etext number" ),
//            link_to_fadedpage_catalog( $project->PostedNumber(), $project->PostedNumber() ) );
//    }

    // -----------------------

//	$status = ($project->ForumTopicIsEmpty()
//				? _("Start a discussion about this project")
//				: _("Discuss this project"));
//    $url = "?projectid={$projectid}&amp;linktotopic=1";
//    echo_row_left_right( _("Forum"), "<a href='$url'>$status</a>" );
//
    // -------------------------------------------------------------------------

//	$status = _("Images, pages edited, & differences");
//	$link = link_to_page_detail($projectid, $status);
//
//	$status2 = _("Just my pages");
//	$link2 = link_to_page_detail_mine($projectid, $status2);
//
//	echo_row_left_right( _("Page Detail"), "$link &gt;&gt;$link2 &lt;&lt;");

//    $username = $User->Username();

    /*
    if($project->Phase() != 'POSTED') {
        if (!$project->IsPublishUserNotify()) {
            $postcaption = _("Click to be notified when this project is posted to FadedPage.");
            $postsubmit = "<input type='submit' name='post_notify' id='post_notify' value='Notify'>";
        } else {
            $postcaption = _("<p>You are registered to be notified when this project posts to FadedPage.</p>\n");
            $postsubmit = "<input type='submit' name='post_notify' id='post_notify' value='Cancel'>\n";
        }

        $smoothintro = $project->IsSmoothUserNotify()
            ? "You will be notified when this project is available for smooth reading."
            : "Request to be notified when this project is available for smooth reading.";
        $smoothcaption = $project->IsSmoothUserNotify()
            ? _("Cancel")
            : _("Notify");

        // ------------------------------------------------------------

        echo_row_left_right(_("Notifications:"),
            "<form name='frmnotify' id='frmnotify' method='POST' class='em90 padded'>
                <span class='lfloat'>$postcaption</span>  <span class='rfloat margined'>$postsubmit</span>
                <hr class='clear w100'>
                <span class='lfloat clear'>$smoothintro</span>
                <span class='rfloat margined'>
                    <input type='submit' name='smooth_notify' id='smooth_notify' value='$smoothcaption'>
                </span>
                </form>\n");
    }
    */


//	if($project->UserCheckedOutPageCount() > 0 || $project->UserSavedPageCount() > 0 ) {
//		echo "<tr><td colspan='2'>\n";
//		echo_your_recent_pages($project);
//		echo "</td></tr>\n";
//	}
    $comments = trim(preg_replace("/&/u", "&amp;", $project->Comments()));
    if($comments == "") {
        $comments = "There are no project-specific instructions. Please follow the Guidelines.";
    }
    echo "<tr><td>$comments</td></tr>\n";
//    echo_row_one_cell( str_replace("&", "&amp;", $comments) );

    if ( $project->UserMayManage() ) {
        echo "<tr><td>\n";
        show_management_box( $project);
        echo "</td></tr>\n";
    }

    echo "<tr><td>\n";
    offer_views_and_downloads( $project->ProjectId() );
    echo "</td></tr>\n";

    if($project->Phase() == 'PREP'
        && $project->CPComments() != "") {
        echo "<tr><td>\n";
        display_cp_comments($project);
        echo "</td></tr>\n";
    }


    if($project->Phase() == 'PP' ) {
        echo "<tr><td>\n";
        solicit_smooth_reading($project);
        echo "</td></tr>\n";
        echo "<tr><td>\n";
        solicit_postcomments($project);
        echo "</td></tr>\n";
    }

    if( $project->Phase() == 'PPV') {
        echo "<tr><td>\n";
        solicit_postcomments($project);
        echo "</td></tr>\n";
    }

    echo "<tr><td>\n";
    offer_text_downloads( $project );
    offer_extra_files( $project );
    show_history( $project );
    echo "</td></tr>\n";

	echo "</table>  <!-- project_info_table -->\n";

    echo "
    <div id='div_proj_sidebar' class='project_table'>\n";
        echo
        sidebar_links($project)
        . sidebar_pages($project)
        . sidebar_meta($project)
        . sidebar_events($project)
        . sidebar_notify($project)
            . "\n";
    echo "
    </div>  <!-- div_proj_sidebar -->\n";

    echo "
	</div>  <!-- div_project_info_table -->\n";

}

/** @param DpProject $project */
function sidebar_meta($project) {
    echo "
    <div class='sidebar'>
    <h4>Project Meta</h4>
    <table class='table_sidebar'>
    <tr> <td>Type</td> <td>{$project->ProjectType()}</td> </tr>
    <tr> <td>Genre</td> <td>{$project->Genre()}</td> </tr>
    <tr> <td>Language</td> <td>{$project->Language()}</td> </tr>
    <tr> <td>Difficulty</td> <td>{$project->Difficulty()}</td> </tr>
    <tr> <td>Project manager</td> <td>{$project->PM()}</td> </tr>

    <tr> <td>Project ID</td> <td>{$project->ProjectId()}</td> </tr>
    <tr> <td>Clearance</td> <td>{$project->Clearance()}</td> </tr>
    <tr> <td>Source</td> <td>{$project->ImageSource()}</td> </tr>
    <tr> <td>Source URL</td> <td>{$project->ImageLink()}</td> </tr>
    <tr> <td>Image Preparer</td> <td>{$project->ImagePreparer()}</td> </tr>

    <tr> <td>Post processor</td> <td>{$project->PPer()}</td> </tr>
    <tr> <td>PP verifier</td> <td>{$project->PPVer()}</td> </tr>
    <tr> <td>Extra credits</td> <td>{$project->ExtraCredits()}</td> </tr>
    <tr> <td>Posted number</td> <td>{$project->PostedNumber()}</td> </tr>

    </table>
    </div>\n";
}


//$status = ($project->ForumTopicIsEmpty()
//    ? _("Start a discussion about this project")
//    : _("Discuss this project"));
//$url = "?projectid={$projectid}&amp;linktotopic=1";
//echo_row_left_right( _("Forum"), "<a href='$url'>$status</a>" );
/** @param DpProject $project */
function sidebar_links($project) {
    $projectid = $project->ProjectId();
    $forum_prompt = $project->ForumTopicIsEmpty()
            ? _("Start a discussion")
            : _("Discuss this project");
    $forum_url = "?projectid={$projectid}&amp;linktotopic=1";
    $forum_link = link_to_url($forum_url, $forum_prompt);
    $trace_prompt = link_to_project_trace($projectid, "Project Trace");
    $edit_prompt = link_to_edit_project($projectid, "Edit project");
    $vurl = "http://validator.w3.org/check?uri="
        . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]
        . "&amp;charset=%28detect+automatically%29&amp;doctype=Inline&amp;group=0";
    $vlink = link_to_url($vurl, "Validate this project page", true);

    $status = _("Project pages");
    $link = link_to_page_detail($projectid, $status, true);

    $status2 = _("Just my pages");
    $link2 = link_to_page_detail_mine($projectid, $status2, true);
    $hclass = $project->UserMayManage() ? "" : "class='hide'";

    echo "
    <div class='sidebar'>
    <h4>Project Links</h4>
    <table class='table_sidebar'>
    <tr> <td>Forum</td> <td>$forum_link</td> </tr>
    <tr><td>Page diffs and images</td> <td class='center'>$link<br>&gt;&gt;$link2&lt;&lt;</td></tr>
    <tr $hclass> <td>Trace</td> <td>$trace_prompt</td></tr>
    <tr $hclass><td>Edit project</td> <td>$edit_prompt</td></tr>
    <tr $hclass><td>Validate page</td> <td>$vlink</td></tr>
    </table>\n";
    echo "
    </div>\n";
}

/** @param DpProject $project */
function sidebar_pages($project) {
    $projectid = $project->ProjectId();
    $checked_out_rows = checked_out_pages($project);
    $saved_rows = saved_pages($project);
    echo "
    <div class='sidebar'>
    <h4>Your available pages</h4>
    <ul class='clean'>\n";
    if(count($checked_out_rows) == 0) {
        echo _("<li><ul class='clean'><li>None</li></ul></li>\n");
    }
    else {
        echo "<li id='checked_out'>
        <h4>Pages currently checked out</h4>
              <ul class='clean'>\n";
        foreach($checked_out_rows as $row) {
            echo "<li>"
//            . link_to_proof_page($projectid, $row['pagename'], "{$row['version_time']} {$row['imagefile']}")
            . link_to_proof_page($projectid, $row['pagename'], "{$row['imagefile']} ({$row['version_time']})")
            . "</li>\n";
        }
        echo "</ul></li>\n";
    }

    if(count($saved_rows) == 0) {
        echo _("<li><ul class='clean'><li>None</li></ul></li>\n");
    }
    else {
        echo "<li id='your_saved'><h4>Pages saved in current Round</h4>
        <ul class='clean'>\n";
        foreach($saved_rows as $row) {
            echo "<li>"
//                . link_to_proof_page($projectid, $row['pagename'], "{$row['version_time']} {$row['imagefile']}")
                . link_to_proof_page($projectid, $row['pagename'], "{$row['imagefile']} ({$row['version_time']})")
                . "</li>\n";
        }
        echo "</ul></li>\n";
    }
    echo "
    </ul>
    </div>\n";
}
/** @param DpProject $project */
function sidebar_events($project) {
//    echo_row_left_right( _("Project info changed"), $project->LastEditTimeStr());
//    echo_row_left_right( _("Round changed"), $project->PhaseDate());
//    echo_row_left_right( _("Last page saved"), $project->CurrentVersionTime());
//    echo_row_left_right( _("Last Forum Post"), $project->LastForumPostDate() );

    echo "
    <div class='sidebar'>
    <h4>Project Events</h4>
    <table class='table_sidebar'>
    <tr> <td>Comments changed</td> <td>{$project->LastEditTimeStr()}</td> </tr>
    <tr> <td>Round changed</td> <td>{$project->PhaseDate()}</td> </tr>
    <tr> <td>Page last saved</td> <td>{$project->CurrentVersionTime()}</td> </tr>
    <tr> <td>Last forum post</td> <td>{$project->LastForumPostDate()}</td> </tr>
    </table>
    </div>\n";
}

/** @param DpProject $project */
function sidebar_notify($project) {
    if($project->Phase() == "POSTED")
        return;

    if (!$project->IsPublishUserNotify()) {
        $postcaption = _("Request to be notified when this project is posted to FadedPage.");
        $postsubmit = "<input type='submit' name='post_notify' id='post_notify' value='Notify'>";
    } else {
        $postcaption = _("<p>You are registered to be notified when this project posts to FadedPage.</p>\n");
        $postsubmit = "<input type='submit' name='post_notify' id='post_notify' value='Cancel'>\n";
    }
    $smoothintro = $project->IsSmoothUserNotify()
        ? "You will be notified when this project is available for smooth reading."
        : "Request to be notified when this project is available for smooth reading.";
    $smoothcaption = $project->IsSmoothUserNotify()
        ? _("Cancel")
        : _("Notify");
    $smoothsubmit = "<input type='submit' name='smooth_notify' id='smooth_notify' value='$smoothcaption'>";

        // ------------------------------------------------------------

    echo "
        <div class='sidebar'>
        <h4>Notifications</h4>
            <form name='frmnotify' id='frmnotify' method='POST'>
            <table class='table_sidebar'>
                <tr> <td>$postcaption</td> <td>$postsubmit </td>
                </tr>
                <tr> <td>$smoothintro</td> <td> $smoothsubmit</td>
                </tr>
            </table>
            </form>
    </div>\n";

    echo "<div class='sidebar'>
    <h4>Page Summary</h4>
        <table id='tblpagesummary' class='noborder center'>
            <tr><td class='left'>Available</td><td class='right_cell'>{$project->AvailableCount()}</td><td>&nbsp;</td></tr>
            <tr><td class='left'>Checked Out</td><td class='right_cell'>{$project->CheckedOutCount()}</td>
             <td>(Reclaimable: {$project->ReclaimableCount()})</td></tr>
            <tr><td class='left'>Completed</td><td class='right_cell'>{$project->CompletedCount()}</td><td>&nbsp;</td></tr>
            <tr><td class='left'>Bad Pages</td><td class='right_cell'>{$project->BadCount()}</td><td>&nbsp;</td></tr>
            <tr><td class='left' colspan='3'><hr></td></tr> <!-- 6 -->
            <tr><td class='left'>Total Pages</td><td class='right_cell'>{$project->PageCount()}</td><td>&nbsp;</td></tr>
        </table>
    </div>\n";

//    <h4>Project Notifications</h4>
//    <table class='table_sidebar'>
//    <tr> <td> </td> <td> </td> </tr>
//    <tr> <td> </td> <td> </td> </tr>
//    <tr> <td> </td> <td> </td> </tr>
//    <tr> <td> </td> <td> </td> </tr>
//    </table>
}
// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function echo_row_left_right( $left, $right) {
    echo "
    <tr><td class='left w25 bgEEEEEE'><b>$left</b></td>
    <td>$right</td></tr> <!-- 1 -->\n";
}

//function echo_caption_row($content) {
//    echo "
//    <tr><td colspan='2' class='center bgEEEEEE'><b>$content</b></td></tr>\n";
//}

//function echo_row_one_cell( $content ) {
//    echo "<tr><td colspan='2' style='min-height: 1em;'> $content </td></tr> <!-- 3 -->\n";
//}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/** @param DpProject $project
 * @return array
 */
function checked_out_pages($project) {
    global $User, $dpdb;
    $projectid = $project->ProjectId();
    $username = $User->Username();
    $sql = "
        SELECT  pv.projectid,
        		pv.pagename,
        		DATE_FORMAT(FROM_UNIXTIME(pv.version_time), '%m-%e-%y %H:%i') version_time,
        		pp.imagefile
        FROM page_last_versions pv
        JOIN projects p ON pv.projectid = p.projectid
		LEFT JOIN pages pp ON pv.projectid = pp.projectid
			AND pv.pagename = pp.pagename
        WHERE pv.projectid = ?
        	AND pv.username = ?
            AND pv.state = 'O'
        ORDER BY pv.version_time DESC";
    $args = array(&$projectid, &$username);
    return $dpdb->SqlRowsPS($sql, $args);
}

/** @param DpProject $project
 * @return array
 */
function saved_pages($project) {
    global $User, $dpdb;
    $projectid = $project->ProjectId();
    $username = $User->Username();
    $sql = "
        SELECT  pv.projectid,
        		pv.pagename,
        		DATE_FORMAT(FROM_UNIXTIME(pv.version_time), '%m-%e-%y %H:%i') version_time,
        		pp.imagefile
        FROM page_last_versions pv
        JOIN projects p ON pv.projectid = p.projectid
		JOIN pages pp ON pv.projectid = pp.projectid
			AND pv.pagename = pp.pagename
        WHERE pv.projectid = ?
        	AND pv.username = ?
            AND pv.state = 'C'
        ORDER BY pv.version_time DESC";
    $args = array(&$projectid, &$username);
    return $dpdb->SqlRowsPS($sql, $args);
}


function echo_your_recent_pages( $project ) {
    global $User;
    global $dpdb;

    /** @var DpProject $project */
    $username = $User->Username();
    $projectid = $project->ProjectId();


	// -----------------------------------------------------------
	//    Checked Out (top)
	// -----------------------------------------------------------

//	$sql = "
//        SELECT  pv.projectid,
//        		pv.pagename,
//        		DATE_FORMAT(FROM_UNIXTIME(pv.version_time), '%b-%e-%y %H:%i') version_time,
//        		pp.imagefile
//        FROM page_last_versions pv
//        JOIN projects p
//        ON pv.projectid = p.projectid
//		LEFT JOIN pages pp
//		ON pv.projectid = pp.projectid
//			AND pv.pagename = pp.pagename
//        WHERE pv.projectid = '$projectid'
//        	AND pv.username = '$username'
//            AND pv.state = 'O'
//        ORDER BY pv.version_time DESC";

//	$checked_out_objs = $dpdb->SqlObjects($sql);

//    echo html_comment($sql);
    $checked_out_rows = checked_out_pages($project);

	// ---------
	$bg_color = '#FFEEBB';
	echo "
   <table id='tblpages' class='w100'>
	 <tr><td colspan='5' class='center' style='background-color: $bg_color'>
        <p class='em110 nomargin'><b>Pages Checked Out</b> and not yet completed</p>
	</td></tr>\n";

	echo "<tr>";
	// ------------

	for($i = 0; $i < 5; $i++) {
		if(count($checked_out_rows) > $i) {
			$row = $checked_out_rows[$i];

			echo "<td class='center w20'>"
			     . link_to_proof_page($projectid, $row['pagename'], "{$row['version_time']} {$row['imagefile']}")
			     ."</td>\n";
//			     ." <a href='$eURL'>{$prooftime}: {$obj->image}</a></td>\n";
		}
		else {
			echo "<td>&nbsp;</td>\n";
		}
	}
	echo "</tr>\n";

	// -----------------------------------------------------------
	//    Submitted (bottom)
	// -----------------------------------------------------------

        $sql = "
        SELECT  pv.projectid,
        		pv.pagename,
        		DATE_FORMAT(FROM_UNIXTIME(pv.version_time), '%b-%e-%y %H:%i') version_time,
        		pp.imagefile

        FROM page_last_versions pv

        JOIN projects p
        ON pv.projectid = p.projectid
        	AND pv.phase = p.phase

		JOIN pages pp
		ON pv.projectid = pp.projectid
			AND pv.pagename = pp.pagename

        WHERE pv.projectid = '$projectid'
        	AND pv.username = '$username'
            AND pv.state = 'C'
        ORDER BY pv.version_time DESC
        LIMIT 5";
	echo html_comment($sql);
	$rows = $dpdb->SqlRows($sql);

	// ---------------------
	$bg_color = '#D3FFCE';
	echo _("
	  <tr><td colspan='5' class='center' style='background-color: $bg_color'>
	     <p class='em110 nomargin'><b>Pages Submitted</b>
	     but still available to edit or correct</p>
	  </td></tr>\n");
	// --------------------


	echo "<tr>\n";
	for($i = 0; $i < 5; $i++) {
		if(count($rows) > $i) {
			$row = $rows[$i];
			$imagefile = $row['imagefile'];
			$pagename  = $row['pagename'];
			$version_time = $row['version_time'];

			$eURL = url_for_proof_page( $projectid, $pagename );

			echo "<td class='w20 center'>";
			echo "<a href='$eURL'>";
			echo "$version_time $imagefile</a></td>\n";
		}
		else {
			echo "<td class='w20'></td>\n";
		}
	}
	echo "</tr>
	</table>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/*
 *
 */
function show_management_box($project) {
    /** @var DpProject $project */
    $mng_holds = _("Manage Holds");
    $mng_words = _("Manage Words");
    $projectid = $project->ProjectId();
    $nholds = $project->HoldCount();
    $sholds = ($nholds == 0
                    ? "are no Holds"
                    : ($nholds == 1
                        ? "is one Hold"
                        : "are $nholds Holds"));

    echo "
    <div id='divupload' class='projbox overflow'>
        <h3 class='center'>Project Management</h3>
        <form method='POST' class='overflow'>
            <input type='hidden' name='projectid' value='$projectid'>
            <ul class='clean center'>
            <li><input type='submit' name='btn_manage_files' id='btn_manage_files' value='Manage Files'>
            "._("Add/Upload page text and image files.")."</li>
            <li><input type='submit' name='btn_manage_holds' id='btn_manage_holds' value='$mng_holds'>
            "._("There $sholds currently in effect.")."</li>
            <li><input type='submit' name='btn_manage_words' id='btn_manage_words' value='$mng_words'>
            "._("WordCheck adminstration.")."</li>
            </ul>
        </form>
    </div>   <!-- divupload -->\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_history($project) {
    global $dpdb;

    /** @var DpProject $project */
    $projectid = $project->ProjectId();


    $events = $dpdb->SqlRows("
        SELECT DATE_FORMAT(FROM_UNIXTIME(event_time), '%b-%e-%y %H:%i') timestamp,
            TRIM(event_type) event_type,
            TRIM(details1) details1,
            TRIM(details2) details2
        FROM project_events
        WHERE projectid = '$projectid'
        ORDER BY event_time");

    $tbl = new DpTable("tblevents", "w75 center padded noborder");
    $tbl->AddColumn("<When", "timestamp");
    $tbl->AddColumn("<What", "event_type");
    $tbl->AddColumn("<", "details1");
    $tbl->AddColumn("<", "details2");
    $tbl->SetRows($events);

    echo "<div id='divhistory' class='projbox'>
     <h3>Project History</h3>\n";
    $tbl->EchoTable();
    echo "</div>   <!-- divhistory -->\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/*
function show_page_summary($project) {
	/** @var DpProject $project */


/*
	echo "
    <div id='div_page_summary' class='projbox'>
        <h3>"._("Page Summary")."</h3>\n";

	if($project->PageCount() == 0) {
		echo "<p>No pages in this project yet.</p>\n";
	}
	else if($project->IsRoundPhase()) {

		echo "
			<table id='tblpagesummary' class='noborder center'>
			<tr><td class='padded'>Available</td><td class='right padded'>{$project->AvailableCount()}</td><td>&nbsp;</td></tr>
			<tr><td class='padded'>Checked Out</td><td class='right padded'>{$project->CheckedOutCount()}</td>
			 <td class='padded'>(Reclaimable: {$project->ReclaimableCount()})</td></tr>
			<tr><td class='padded'>Completed</td><td class='right padded'>{$project->CompletedCount()}</td><td>&nbsp;</td></tr>
			<tr><td class='padded'>Bad Pages</td><td class='right padded'>{$project->BadCount()}</td><td>&nbsp;</td></tr>
			<tr><td colspan='3'><hr></td></tr> <!-- 6 -->
			<tr><td class='padded'>Total Pages</td><td class='right padded'>{$project->PageCount()}</td><td>&nbsp;</td></tr>
			</table>\n";
	}

	else {
		echo "
			<table id='tblpagesummary' class='noborder lfloat'>
			<tr><td class='padded'>Total Pages</td><td class='right padded'>{$project->PageCount()}</td></tr>
			</table>\n";

	}
	echo "
	</div>   <!-- div_page_summary -->\n";
}
*/

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/*
function show_page_table($project) {


	echo "
    <div id='page_table_key' class='clear'>
        <p>". _('Pages edited by you and...') . "</p>
        <div style=' margin-bottom: .4em' class='pg_out bordered padded lfloat clear'>"
	     . _("Checked Out (awaiting completion this round)") . "</div>
        <div style=' margin-bottom: .4em' class='pg_completed bordered padded lfloat'>"
	     . _("Completed (still available for editing this round)") ."</div>
        <div style=' margin-bottom: .4em' class='pg_unavailable bordered padded lfloat'>"
	     . _("Completed in a previous round (no longer available for editing)") ."</div>
    </div>   <!-- div_table_key -->

    <div id='div_page_table' class='lfloat clear'>\n";

	// second arg. indicates to show size of image files.
	echo_page_table($project);
	echo "
    </div>    <!-- div_page_table' -->\n";
}
*/

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function offer_views_and_downloads($projectid) {
//	global $User;

//    $imglink        = link_to_view_page_images($projectid, "View page images online", true);
//    $bothlink       = link_to_view_text_and_images($projectid, "View images and latest text online");
//    $image_zip_link = link_to_zipped_images($projectid, "Download zipped images");
//    $extra_zip_link = link_to_zipped_extras($projectid, "Download zipped extra files");
//    $textlink       = link_to_view_project_text($projectid, "latest", "View latest text online", true);
//    $ocrlink = link_to_project_text($projectid, "all", "View current text online", true);
//    $p3link = link_to_project_text($projectid, "P3", "View current text online", true);
//	$textlink = link_to_url($texturl, "View current Text Online", true);
//	if($project->PhaseSequence() >= $Context->PhaseSequence("P3")) {
//		$texturlP3 = "$code_url/project_text.php?projectid=$projectid&amp;phase=P3";
//		$textlinkP3 = link_to_url($texturlP3, "View P3 text online", true);
//	}
//    if($project->PhaseSequence() > $Context->PhaseSequence("F2")) {
//        $pp_text_link   = link_to_pp_text($projectid, "Download PP text");
//    }

    echo "<div class='projbox center'>
        "._('<h3>Project Integrated Text/Image View</h3>')."
        <p>View the most recent page text, with full access to all page images. Scroll and search the text.
        Compare any part of the text with the matching image.</p>
        " . link_to_view_text_and_images($projectid, "View images and latest text online") ."
        </div>\n";
    /*
    echo "
    <div id='div_images_text' class='projbox'>
		<form name='frmimages' method='POST'>
        "._('<h3>Views and Downloads</h3>')."
        <ul class='clean'>
        <li>$imglink</li>
        <li>$bothlink</li>
        <li>$image_zip_link</li>
        <li>$extra_zip_link</li>
	    </ul>
        </form>
        </div>  <!-- div_images_text -->\n";
    */
//	if(isset($textlinkP3)) {
//		echo "<li>$textlinkP3</li>\n";
//	}
//    if(isset($pp_text_link)) {
//        echo "<li>$pp_text_link</li>\n";
//
//    }

}


// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function offer_extra_files($project) {
    /** @var DpProject $project */

    $projectid = $project->ProjectId();
    $extrapaths = $project->ExtraFilePaths();
    $extra_zip_link = link_to_zipped_extras($projectid, _("Download zipped extra files"));
    echo "
    <div id='div_extra_files' class='projbox'>
    "._("<h3>Project Files</h3>")."
    <p>$extra_zip_link</p>\n";

    echo "
    <ul class='clean'>\n";

    foreach($extrapaths as $extrapath) {
        $filename = basename($extrapath);
        $url = build_path($project->ProjectUrl(), $filename);
        echo "<li>" . link_to_url($url, $filename) . "</li>\n";
    }

    echo "</ul>
	<p>" . link_to_url($project->ProjectUrl(), "Browse the project directory")
      ."</p>
    </div>  <!-- div_extra_files -->
	\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/*
function offer_pp_downloads($project) {
//    global $User, $code_url, $Context;

    $projectid = $project->ProjectId();
    $image_zip_link = link_to_zipped_images($projectid);
    $text_link  = link_to_pp_text($projectid);

    echo "
	<div id='div_pp_downloads' class='bordered margined padded'>
        <h3 class='clear'>". _("Post Processor/Verifier Downloads") . "</h3>
        <ul class='clean'>
            <li>$image_zip_link</li>
            <li>$text_link</li>
        </ul>
    </div>   <!-- div_pp_downloads -->\n";
}
*/
//    $prompt_text =  _("Download Zipped Images");
//    $url = "$code_url/tools/download_images.php"
//            ."?projectid=$projectid"
//            ."&amp;dummy={$projectid}images.zip";
//    echo "<li><a href='$url'>$prompt_text</a></li>\n";
//    if ($project->Phase() == "PP") {
//        echo_download_pp_zip($project, _("Download Concatenated Text") );
//
//    echo "<li>";
//            echo_uploaded_zips($project, '_first_in_prog_', _('partially post-processed'));
//    echo "</li>";
//        }

//    else if ($project->Phase() == "PPV") {
//        echo_download_ppv_zip($project, _("Download Zipped PP Text") );
//        echo "<li>";
//        echo_uploaded_zips($project, '_second_in_prog_', _('partially verified'));
//        echo "</li>";
//    }
//    echo "</ul>\n";
/** @param DpProject $project */
function offer_text_downloads($project)
{
    global $Context, $level;
    $projectid = $project->ProjectId();
    echo "
    <div class='projbox'>
        <h3>Project Downloads</h3>
        <form name='frmdownload' method='post'>
          <input type='hidden' name='projectid' value='$projectid'>
          <input type='hidden' name='detail_level' value='$level'>
         " . link_to_zipped_images($projectid, "Download zipped images") ."<br>

            <p>Download concatenated project text from <input type='radio' name='exportphase' value='OCR'>OCR: \n";

    foreach ($Context->Rounds() as $round) {
        $roundid = $round->RoundId();
        echo "
              <input type='radio'  name='exportphase' value='{$roundid}'>{$roundid}&nbsp;\n";
        if ($roundid == $project->Phase()) {
            break;
        }
    }
    echo "<input type='radio' name='exportphase' value='newest' checked>Newest&nbsp;
            </p>\n";

    if ($project->UserMaySeeNames()) {
        echo "
            <p><input type='radio' name='exportinclude' value='nothing'>Unbroken text.<br>
            <input type='radio' name='exportinclude' value='separator' checked = 'checked'> Page separators.<br>
            <input type='radio' name='exportinclude' value='names'> Proofer names in separators.<br>
            <input type='radio' name='exportinclude' value='pagetag'> No separator but include &lt;page&gt; tags.</p>\n";
    }
    echo "
            <p><input type='checkbox' id='exact' name='exact'> Include only pages which have completed the Round.<br/>
            (Otherwise, for each page not yet completed, the current completed  version is included.)</p>
            <p></p>
            \n";

    $prompt1 = _("Download text");
    $prompt2 = _("View text");
    echo "
            <p class='center'><input type='submit' id='submit_export' name='submit_export' value='$prompt1'>
            <input type='submit' id='submit_view' name='submit_view' value='$prompt2'></p>\n";
    echo "
        </form>
    </div>\n";
}

/** @param DpProject $project */
function display_cp_comments($project) {
	echo "
	<div id='div_cp_comments' class='projbox'>
	" . _("<h3>Content Provider Comments</h3>
	</hr>
	<pre>" . $project->CPComments() . "</pre>
	</div>  <!-- div_cp_comments -->\n");
}

function solicit_postcomments($project) {
    global $forums_url, $User;

    /** @var DpProject $project */

    if(! $project->UserIsPPer() && ! $project->UserIsPPVer() && ! $User->IsSiteManager()) {
        return;
    }

    $projectid = $project->ProjectId();
	$postcomments = $project->PostComments();

	echo "
	<div id='div_pp_comments' class='projbox'>
	<h3>" . _("Post-Processor's Comments") . "</h3>";

	// Give the PP-er a chance to update the project record
	// (limit of 90 days is mentioned below).
	echo "<p>" . sprintf(_("You can use this text area to enter comments on how you're
				 doing with the post-processing, both to keep track for yourself
				 and so that we will know that there's still work checked out.") .
				 _("You can use this feature to keep track of your progress,
				 missing pages, etc. (if you are waiting on missing images or page
				 scans, please add the details to the <a href='%2\$s'>Missing Page
				 Wiki</a>)."),
				 90, "$forums_url/viewtopic.php?t=7584") . ' ' .
				 _("Note that your old comments will be <b>replaced</b> by those
				 you enter here; <b>not</b> appended with a timestamp.") . "</p>
        <form name='pp_update' method='post'>
        <textarea name='postcomments' cols='100' rows='10'>$postcomments</textarea>
        <input type='hidden' name='projectid' value='$projectid'>
        <br />
        <input type='submit' name='submit_post_comments'
            value='" . _('Update comment and project status') . "'>
      </form>
      </div>  <!-- div_pp_comments -->\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

//function sr_echo_withdrawal_form($projectid) {
//	$button_text = _("Withdraw SR commitment");
//
//	echo "
//    <form name='sr' method='POST'>
//        <input type='hidden' name='projectid' value='$projectid'>
//        <input type='submit' name='sr_withdraw' value='$button_text'>
//    </form>\n";
//}

/** @param DpProject $project */
function solicit_smooth_reading($project) {

//    $project->MaybeUnzipSmoothZipFile();
    $projectid = $project->ProjectId();

    echo "
    <div id='divsmooth' class='projbox'>
    <h3>". _('Smooth Reading'). "</h3>\n";


    echo "
    <form name='srform' id='srform' method='POST' enctype='multipart/form-data'>";
    if( $project->IsAvailableForSmoothReading() ) {
        echo _("<p>This project is available for smooth reading
				until {$project->SmoothreadDateString()}.</p>\n");
        if( count($project->SmoothDownloadFileNames()) > 0) {
            echo "<ul class='clean'>
                <li>Download from the following smooth reading files.</li>
                <li>
                    <ul class='clean'>\n";
            foreach($project->SmoothDownloadFileNames() as $name) {
                echo "<li>" . link_to_smooth_download_file($project, $name, $name, true) . "</li>\n";
            }
            echo "</ul>
                </li>
              </ul>\n";
        }
        else {
            echo "<p>There are no files to download.</p>\n";
        }
        echo "<p>" . link_to_smoothed_upload($projectid,
                "Upload a text you have smooth-read") ." </p>\n";
    }

    if ( $project->UserMayManage()) {
        echo "
        <div class='bordered margined padded'>\n";
        $instructions = $project->SmoothComments();
        $days = $project->SmoothDaysLeft();
        if($days < 0) {
            $days = "";
        };

         echo _("<h4 class='center'>Manage Smooth Reading</h4>") ."
        <p class='clear'>
            Set the smooth-reading deadline to how many days from today?
            <input type='text' name='srdays' id='srdays' value='$days' size='3'>
        </p>
        <label for='srcomments'>Instructions for smooth-readers</label>
        <textarea name='srcomments' id='srcomments' rows='10' class='w60'>$instructions</textarea>
        <input type='submit' name='submit_srcomments' value='Submit' class='clear'>\n";

        echo "
        <p>" . link_to_upload_text_to_smooth($projectid, "Upload a file for smooth-reading (new or replacement)") ."</p>\n";

	    if($nuploaded = count($project->SmoothUploadedFiles()) > 0) {
		    echo "<p>Smooth readers have uploaded the following files:</p>\n";
		    foreach($project->SmoothUploadedFiles() as $upfile) {
			    echo "<p>" . link_to_uploaded_smooth_file($project, $upfile) . "</p>\n";
		    }
	    }
	    else {
		    echo _("<p>No one has uploaded yet.</p>\n");
	    }
        echo "</div>\n";
    }

    echo "
    </form>  <!-- srform -->
    </div> <!-- divsmooth -->\n";
}

/**
 * @param DpProject $project
 * @param string $filename
 *
 * @return string
 * @internal param DpProject $project
 */
function link_to_uploaded_smooth_file($project, $filename) {
	$url = build_path($project->ProjectUrl(), $filename);
	$text = RegexMatch("_smooth_done_(.*)\.zip", "ui", $filename, 1);
    if (empty($text))
        $text = RegexMatch("_smooth_done_(.*)", "ui", $filename, 1);
    else
        $text = "$text (zip file)";
	return "<a href='$url'>$text</a>\n";
}

/**
 * @param DpProject $project
 * @param string $name
 * @param string $prompt
 * @param bool $is_new_tab
 * @return string
 */
function link_to_smooth_download_file($project, $name, $prompt = "", $is_new_tab = false) {
    if($prompt == "")
        $prompt = $name;
    return link_to_url(url_for_smooth_download_file($project, $name), $prompt, $is_new_tab );
}

/**
 * @param DpProject $project
 * @param string $name
 * @return string
 */
function url_for_smooth_download_file($project, $name) {
    return build_path($project->SmoothDirectoryUrl(), $name);
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX


//function PrepActions($project) {
//    global $User;
//    $holds = $project->ActiveHolds();
//    foreach($holds as $hold) {
//        $code = $hold['hold_code'];
//        if($User->MayReleaseHold($code)) {
//            return array("prompt" => "Release {$hold['hold_code']} hold",
//                         "action" => "release.$code");
//        }
//    }
//    return array();
//}

//function export_project($project) {
//	$text = $project->PhaseExportText($project->Phase());
//	send_string($project->ProjectId()."_PP.txt", $text);
//}

// vim: sw=4 ts=4 expandtab

