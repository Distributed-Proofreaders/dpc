<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath='./pinc/';

include_once($relPath.'dpinit.php');
include_once($relPath.'stages.inc');
include_once($relPath.'rounds.php');
include_once($relPath.'RoundsInfo.php');
include_once($relPath.'comment_inclusions.inc'); // parse_project_comments()
include_once 'pt.inc'; // echo_page_table
include_once($relPath.'user_is.inc');
include_once($relPath.'smoothread.inc');           // functions for smoothreading
include_once $relPath . "export.php";

define("DATETIME_FORMAT", "%A, %B %e, %Y %X");
define("DATE_FORMAT", "%A, %B %e, %Y");
define("TIME_FORMAT", "%X");

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

// Usually, the user arrives here by clicking on the title of a project
// in a list of projects.
// But there are lots of other less-used pages that link here.

$projectid              = Arg('projectid', Arg('id'));
$project                = new DpProject( $projectid );
$detail_level           = Arg('detail_level', $project->UserMayManage() ? '3' : '2');
$btn_manage_files       = IsArg("btn_manage_files");
$btn_manage_holds       = IsArg("btn_manage_holds");
$submit_post_comments   = IsArg("submit_post_comments");
$postcomments           = Arg("postcomments");
$down_images            = IsArg("down_images");
$export                 = IsArg("export");
$export_roundid           = Arg("export_roundid", $project->Phase());
$exact                  = IsArg("exact");
$proofers               = IsArg("proofers");

if($btn_manage_files) {
    divert(url_for_project_files($projectid));
    exit;
}
if($btn_manage_holds) {
    divert(url_for_project_holds($projectid));
    exit;
}

if($export) {
    export_round($project, $export_roundid, $proofers, $exact);
}
// -----

$project->RecalcPageCounts();
$project->MaybeAdvanceRound();

// if user submitted comments for post processing, load them
if($submit_post_comments) {
    $project->SetPostComments($postcomments);
}

if($down_images) {
    $project->SendImageZipFile();
}

// -----------------------------------------------------------------------------

// In a tabbed browser, the page-title passed to theme() will appear in
// the tab, which tends to be small, as soon as you have a few of them.
// So, put the distinctive part of the page-title (i.e. the name of the
// project) first.
$title_for_theme = $project->NameOfWork() . _(' project page');

$title = _("Project Page for ") . $project->NameOfWork();

// touch modifieddate whenever PPer views this page
if($project->UserIsPPer() && $project->Phase() == "PP") {
    $project->SetModifiedDate();
}

if ($detail_level == 1) {
    theme($title_for_theme, "header");
    echo "<div id='tblproject' class='px800 lfloat'>
            <h1>$title</h1>\n";

    show_detail_level_switch($projectid, $detail_level);

    show_project_info_table($project, $project);

    show_detail_level_switch($projectid, $detail_level);
    echo "</div>\n";
    exit;
}

// don't show the stats column
$no_stats = 1;
theme($title_for_theme, "header");

echo "<div id='tblproject' class='px800 lfloat'>
    <h1 class='center'>$title</h1>\n";

show_detail_level_switch($projectid, $detail_level);

if($project->UserMayManage()) {
    echo "<div class='rfloat'>"
        .link_to_project_trace($projectid, "Project Trace") 
        ."</div>\n";
}

// confusing call to prepare top and bottom status boxes
list($top_status, $bottom_status) = decide_status($project);

show_status_box( $top_status );

show_project_info_table($project, $project);

if($project->UserMayManage()) {
    echo "<div class='clear'>
    ".link_to_edit_project($projectid, "Edit above project information")."
    </div>\n";
}

show_status_box( $bottom_status );

if ($project->UserMayManage()) {
    show_uploads_box($project);
}
solicit_postcomments($project);

solicit_smooth_reading($project);

if($project->Phase() == "PPV" && $User->MayPPV()) {
    solicit_pp_report_card($project);
}

if($detail_level <= 2) {
    show_detail_level_switch($projectid, $detail_level);
    theme('', 'footer');
    exit;
}


echo "<hr class='clear'>\n";
offer_images($projectid);

offer_post_downloads($project, $export_roundid);

if($project->UserMayManage() || $project->UserIsPM()) {
    do_extra_files($project);
}

echo "</div> <!-- tblproject -->\n";

echo "<hr class='lfloat w50 clear'>\n";
// Stuff that's (usually) only of interest to
// PMs/PFs/SAs and curious others.
show_history($project);

show_page_summary($project);

if($detail_level <= 3) {
    show_detail_level_switch($projectid, $detail_level);
    theme('', 'footer');
    exit;
}

show_page_table($project);

show_detail_level_switch($projectid, $detail_level);
theme('', 'footer');
exit;

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_detail_level_switch($projectid, $level = 3) {
    echo _("
    <div class='lfloat clear margined'>
        Viewing page at detail level $level.&nbsp;&nbsp;Switch to: ");
    foreach( array(1, 2, 3, 4) as $v ) {
        if ( $v != $level ) {
            $url = "project.php"
                        ."?projectid={$projectid}"
                        ."&amp;detail_level=$v";
            echo "<a href='$url'>$v</a>\n";
        }
    }
    echo "</div>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function decide_status($project) {
    /** @var DpProject $project */
    global $User;
    global $dpdb;

    // $username = $User->Username();

    if(! $project->IsAvailableState()) {
        $msg = _("Project Status: {$project->Phase()}<br/>(not available for proofreading)");
        return array( $msg, $msg );
    }

    // If there's any proofreading to be done, this is the link to use.
    $label = _("Start Proofreading");
    $proofreading_link = link_to_proof_next($project->ProjectId(), $label);

    // When was the project info last modified?
    $info_timestamp = $project->LastCommentChangeTime();
    $info_time_str = strftime(DATETIME_FORMAT, $info_timestamp);
    $info_last_modified_status = _("Project information last modified:") 
        . " " . $info_time_str;

    // Other possible components of status:
    $please_scroll_down = _("Please scroll down and read the Project Comments
    for any special instructions <b>before</b> proofreading!");
    $the_link_appears_below = _("The 'Start Proofreading' link appears below
    the Project Comments");
    $info_have_changed =
        "<p class='nomargin red bold'>"
        . _("Project information has changed!")
        . "</p>";

    // ---

    $bottom_status = "$info_last_modified_status<br>$proofreading_link";

    // Has the user saved a page of this project since the project info was
    // last changed? If not, it's unlikely they've seen the revised info.
    $projectid = $project->ProjectId();
    $username = $User->Username();
    $usercol = UserFieldForRoundid($project->RoundId());
    $timecol = TimeFieldForRoundid($project->RoundId());
    $rtime = $dpdb->SqlOneValue("
        SELECT MAX($timecol) FROM $projectid
        WHERE state LIKE '%save%' 
            AND $usercol = '$username'");
    if (! $rtime) {
        // The user has not saved any pages for this project.
        $top_status = "$please_scroll_down <br> $info_last_modified_status
                      <br> $the_link_appears_below";
    }
    else {
        // The user has saved a page for this project.

        if ($rtime < $info_timestamp) {
            // The latest page-save was before the info was revised.
            // The user probably hasn't seen the revised project info.
            $top_status = "$info_have_changed <br/> $please_scroll_down
                <br>
                $info_last_modified_status <br/> $the_link_appears_below";
        }
        else {
            // The latest page-save was after the info was revised.
            // We'll assume that the user has read the new info.
            $top_status = "$please_scroll_down <br> $info_last_modified_status
                <br> $proofreading_link";
        }
    }

    return array( $top_status, $bottom_status );
}

// -----------------------------------------------

function show_status_box( $status ) {
    if ( ! $status ) 
        return;

    echo "
    <div class='div_project_info w90'>
    <h4>$status</h4>
    </div>
    <br/>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_project_info_table($project) {
    global $code_url;
    global $dpdb, $User;
    global $detail_level;

    /** @var DpProject $project */

    $projectid = $project->ProjectId();
    $round = get_Round_for_project_state($project->State());

    // -------------------------------------------------------------------------
    // The state of the project

    $available_for_SR = ( $project->SmoothreadDeadline() > time() );

    if($round) {
        $right = RoundIdName($round->id) . " (" . RoundIdDescription($round->id) . ")";
    }
    else {
        $right = $project->Phase();
    }

    echo "<table id='project_info_table'>\n";
    echo_row_1_4( _("Project Status"), $right );
    echo_row_1_4( _("Title"),           $project->NameOfWork() );
    echo_row_1_4( _("Author"),          $project->AuthorsName() );
    echo_row_1_4( _("Language"),        $project->LanguageCode() );
    echo_row_1_4( _("Genre"),           $project->Genre() );
    echo_row_1_4( _("Difficulty"),      $project->Difficulty() );
    echo_row_1_4( _("Project ID"), $project->ProjectId() );
    if ( $project->UserIsPPVer() || $project->UserMayManage() ) {
        echo_row_1_4( _("Clearance line"), h($project->Clearance()));
    }
    if($project->ImageSource() != "") {
        echo_row_1_4( _("Image Source"), h($project->ImageSource()));
    }
    echo_row_1_4( _("Project Manager"), $project->PM());
    echo_row_1_4( _("Post Processor"), $project->PPer());
    echo_row_1_4( _("PP Verifier"), $project->PPVer() );
    echo_row_1_4( _("Credits so far"), h($project->Credits()));
    echo_row_1_4( _("Project info changed"), std_datetime($project->LastCommentChangeTime()));
    echo_row_1_4( _("Round/phase changed"), std_datetime($project->ModifiedDateInt()));
    echo_row_1_4( _("Last page proofread "), std_datetime($project->LatestProofTime()));

    $topic_id = $project->ForumTopicId();
    if ($topic_id) {
        $last_post_date = $dpdb->SqlOneValue("
            SELECT MAX(post_time) FROM forum.bb_posts 
            WHERE topic_id = $topic_id"); 
        $last_post_date = std_datetime($last_post_date);
        echo_row_1_4( _("Last Forum Post"), $last_post_date );
    }

    echo_row_1_4( _("Posted etext number"), $project->PostedNumber());

    // -----------------------

    $status = ($topic_id == "") ? _("Start a discussion about this project")
                                : _("Discuss this project");
    $url = "$code_url/tools/proofers/project_topic.php?projectid=$projectid";
    echo_row_1_4( _("Forum"), "<a href='$url'>$status</a>" );

    // -------------------------------------------------------------------------
    // Page detail (link to diffs etc.)

    if($project->PageCount() > 0) {
        // $url = url_for_page_detail($projectid);
        $status = _("Images, Pages Proofread, & Differences");
        $link = link_to_page_detail($projectid, $status);

        // $url2 = "$url&amp;my_pages=1";
        $status2 = _("Just my pages");
        $link2 = link_to_page_detail_mine($projectid, $status2);

        echo_row_1_4( _("Page Detail"), "$link &gt;&gt;$link2 &lt;&lt;");
        // "<a href='$url'>$status</a> &gt;&gt;<a href='$url2'>$status2</a>&lt;&lt;");
    }

    // -------------------------------------------------------------------------
    // Personal data with respect to this project
    // (This is the only section that uses $pguser and $userP.)

    $username = $User->Username();
    
    $isnotify = $dpdb->SqlExists( "
             SELECT id FROM notify
             WHERE projectid = '$projectid'
                 AND username = '$username'");
    if (! $isnotify) {
        $status = _("Click here to register for automatic email notification of
        when this has been posted."); 
        $url = "$code_url/tools/proofers/posted_notice.php" 
                                            ."?projectid=$projectid"
                                            ."&amp;setclear=set";
    }
    else {
        $status = _("<p>You ($username) are registered to be notified by email
        when this project has been posted.</p>
        <p>Click here to cancel your registration.</p>");
        $url = "$code_url/tools/proofers/posted_notice.php" 
                                            ."?projectid=$projectid"
                                            ."&amp;setclear=clear";
    }

    // ------------------------------------------------------------

    echo_row_1_4( _("Book Completion:"), "<a href='$url'>$status</a>" );

    // ------------------------------------------------------------

    if ($round && $detail_level > 1) {
        your_recent_pages($project, 0);
        your_recent_pages($project, 1);
    }

    // -------------------------------------------------------------------------
    // Comments

    $postcomments = maybe_convert($project->PostComments());
    $postcomments = str_replace("\n", "<br />", h($postcomments));
    if ($postcomments != '') {
        if ( $available_for_SR ) {
            echo_row_two_high( _("Instructions for Smooth Reading"), '' );
            echo_row_one_cell( $postcomments );
        }
        // Postcomments should be shown to those directly involved with the project (the
        // first three conditions). But when the project is available for PPV, the 
        // PPVer should be able to read the PPer's comments without checking out the project.
        else if ( $project->UserIsPPer() || $project->UserIsPPVer()
                || $project->UserMayManage()
                || ( $project->Phase() == "PPV" && $User->MayWorkInRound("PPV") ) ) {
            echo_row_two_high( _("Post Processor's Comments"), '' );
            echo_row_one_cell( $postcomments );
        }
    }

    $comments = maybe_convert($project->Comments());
    $comments = parse_project_comments($comments);
    if ( $comments == '' ) {
        $comments = '&nbsp;';
    }
    echo_row_one_cell( $comments );
    echo "</table>";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function echo_row_1_4( $left, $right) {
    echo "
    <tr><td class='center bgCCCCCC'><b>$left</b></td>
    <td colspan='4'>$right</td></tr> <!-- 1 -->\n";
}

function echo_row_two_high( $top, $bottom, $bgcolor = '#CCCCCC' ) {
    echo " <tr> <td colspan='5' class='center' style='background-color: $bgcolor'>
    <p class='em110 bold nomargin'>$top</p>\n";
    if ($bottom) {
        echo "<p class='nomargin'>$bottom</p>\n";
    }
    echo "</td></tr> <!-- 2 -->\n";
}

function echo_row_one_cell( $content ) {
    echo "<tr><td colspan=5> $content </td></tr> <!-- 3 -->\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function your_recent_pages( $project, $wlist ) {
    global $User;
    global $dpdb;

    /** @var DpProject $project */
    $username = $User->Username();

    $projectid = $project->ProjectId();

    $roundid = $project->RoundId();
//    assert($round);

    // saved or in progress?
    if ($wlist == 0) {
        $top = _("DONE");
        $bottom = _("&nbsp;<b>My Recently Completed</b> - pages I've finished
        proofreading, that are available for correction");
        $state_condition = "state LIKE '%saved'";
        $bg_color = '#D3FFCE';
    }
    else {
        $top = _("IN PROGRESS");
        $bottom = _("&nbsp;<b>My Recently Proofread</b> - pages I haven't yet
        completed");
        $state_condition = "state LIKE '%checked_out'";
        $bg_color = '#FFEEBB';
    }

    echo_row_two_high( $top, $bottom, $bg_color );

    $timecol = $usercol = "";
    switch($roundid) {
        case 'P1':
            $timecol = "round1_time";
            $usercol = "round1_user";
            break;
        case 'P2':
            $timecol = "round2_time";
            $usercol = "round2_user";
            break;
        case 'P3':
            $timecol = "round3_time";
            $usercol = "round3_user";
            break;
        case 'F1':
            $timecol = "round4_time";
            $usercol = "round4_user";
            break;
        case 'F2':
            $timecol = "round5_time";
            $usercol = "round5_user";
            break;
    }


    $sql = "
        SELECT image,
               state,
               fileid AS pagename,
               $timecol prooftime
        FROM $projectid
        WHERE $usercol = '$username' 
            AND $state_condition
        ORDER BY $timecol DESC
        LIMIT 5";
    $rows = $dpdb->SqlRows($sql);

    $rownum = 0;
    foreach($rows as $row) {
        if($rownum == 5) {
            break;
        }
        $imagefile = $row['image'];
        $pagename  = $row['pagename'];
        $timestamp = $row['prooftime'];

        $eURL = url_for_proof_page( $projectid, $pagename );

        if (($rownum % 5) == 0) {
            echo "<tr>\n";
            // echo "</tr> <!-- 5 -->\n<tr>";
        }
        echo "<td class='center'>";
        echo "<a href='$eURL'>";
        echo strftime(_("%b %d"), $timestamp).": ".$imagefile."</a></td>\n";
        $rownum++;
    }

    while (($rownum % 5) != 0) {
        echo "<td class='center'>&nbsp;</td>"; $rownum++;
    }
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/*
 *
 */
function show_uploads_box($project) {
    /** @var DpProject $project */
    $mng_holds = _("Manage Holds");
    $projectid = $project->ProjectId();
    $nholds = $project->HoldCount();
    $sholds = ($nholds == 0
                    ? "are no Holds"
                    : ($nholds == 1
                        ? "is one Hold"
                        : "are $nholds Holds"));

    echo "
    <div class='clear' id='divupload'>
    <h4>Project Management</h4>
    <form method='POST' action=''>
    <input type='hidden' name='projectid' value='$projectid'>
    "._("Add/Upload page text and image files.")."
    <input type='submit' name='btn_manage_files' id='btn_manage_files'
            value='Manage Files'>
    <p>There $sholds currently in effect.
    <input type='submit' name='btn_manage_holds' id='btn_manage_holds' value='$mng_holds'>
    </p>
    </form>
    </div>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_history($project) {
    global $dpdb;

    /** @var DpProject $project */
    $projectid = $project->ProjectId();


    $events = $dpdb->SqlRows("
        SELECT FROM_UNIXTIME(timestamp) timestamp, 
            TRIM(event_type) event_type,
            TRIM(details1) details1,
            TRIM(details2) details2
        FROM project_events
        WHERE projectid = '$projectid'
        ORDER BY timestamp");

    // $events2 = fill_gaps_in_events( $project,  $events );

    // echo "<table border='1'>\n";
    foreach ( $events as $event ) {
        // $ts = strftime('%Y-%m-%d %H:%M:%S', $event->timestamp);
        // echo "
        // <tr><td>$ts</td>
        // <td align-='center'>{$event->who}</td>
        // <td>{$event->event_type}</td>\n";

        if ( $event["event_type"] == 'transition' 
                || $event["event_type"] == 'transition(s)') {
            $from_state = $event["details1"];
            $event["from_state"] =  project_states_text($from_state);

            $to_state = $event["details2"];
            $event["to_state"] =  project_states_text($to_state) ;


        }
    }
    $tbl = new DpTable("tblevents", "w75");
    $tbl->SetClass("lfloat");
    $tbl->AddColumn("^When", "timestamp");
    $tbl->AddColumn("^What", "event_type");
    $tbl->AddColumn("", "details1");
    $tbl->AddColumn("", "details2");
    $tbl->SetRows($events);

    echo "<div class='clear'>
     <h4>Project History</h4>\n";
    $tbl->EchoTable();
    echo "</div>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function offer_images($projectid) {
    global $code_url;

    $url = "$code_url/tools/proofers/images_index.php?projectid=$projectid";
    $link = link_to_url($url, "View Images Online");

    echo "<form name='frmimages' action='' method='POST'>
        "._('<h4>Images</h4>')."
        <p>$link</p>
        <input type='submit' name='down_images' value='Download Images Zip File'>
        </form>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function do_extra_files($project) {
    global $dpdb;
    /** @var DpProject $project */

    echo _('<h4>Extra Files in Project Directory</h4>') . "\n";

    echo link_to_url($project->ProjectUrl(), "Browse project files");


    $path = build_path($project->ProjectPath(), "*");

    $filenames = glob($path);


    if ( count($filenames) == 0 ) {
        echo "<p>(no files in project directory)</p>\n";
        return;
    }

    $images = $dpdb->SqlValues("
        SELECT image FROM {$project->Projectid()}");

    $notfiles = array();
    foreach($images as $img) {
        $notfiles[] = basename($img);
    }
    // dump($notfiles);

    echo "<ul>\n";
    foreach ($filenames as $filename) {
        $filename = basename($filename);
        if ( !in_array( $filename, $notfiles ) ) {
            $url = build_path($project->ProjectUrl(), $filename);
            echo "<li>" . link_to_url($url, $filename) . "</li>\n";
        }
    }
    echo "</ul>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function offer_post_downloads($project, $export_roundid) {
    global $User, $Context;
    /** @var DpProject $project */

    $username = $User->Username();
    $projectid = $project->ProjectId();

    if ( user_can_work_in_stage($username, 'PP') ) {
        echo "
        <h4 class='clear'>". _("Post Downloads") . "</h4>
        <ul>\n";

        echo_download_zip($project, _("Download Zipped Images"), 'images' );

        if ($project->Phase() == "PP") {
            echo_download_zip($project, _("Download Zipped Text"), '' );

            echo "<li>";
            echo_uploaded_zips($project, '_first_in_prog_', _('partially post-processed'));
            echo "</li>";
        }
        else if ($project->Phase() == "PPV") {
            echo_download_zip($project, _("Download Zipped Text"), '_second' );
            echo "<li>";
            echo_uploaded_zips($project, '_second_in_prog_', _('partially verified'));
            echo "</li>";
        }
    }
    else {
        echo "
        <h4 class='clear'>". _("Concatenated Text Files")."</h4>
        <ul>\n";
    }
    echo "
    <li>
    <form method='post' action=''>
    <input type='hidden' name='projectid' value='$projectid'>\n";

    // $highest_round_id = get_Round_for_project_state($project->State());
    echo "
    Download concatenated text from
    <input type='radio' name='round_id' value='OCR'>OCR&nbsp;\n";
    foreach ( $Context->Rounds() as $roundid ) {
        echo "<input type='radio'  name='export_roundid' value='$roundid'>$roundid&nbsp;\n";
        if ( $roundid == $export_roundid )  {
            break;
        }
    }
    echo "
    <input type='radio' name='export_roundid' value='newest' CHECKED>Newest&nbsp;
    <br>\n";
    if ( $project->UserMaySeeNames() ) {
        echo "
        <input type='checkbox' name='proofers' id='proofers' />
        Include proofer names? <br/>\n";
    }
    echo "
    <input type='checkbox' id='exact' name='exact'>
    For each page, include the round text, including blank text for unproofed pages.
    Otherwise the newest proofed text is included.  If every page has been proofed
    in the round, the concatenated texts are identical.<br>\n";

    // proofer names allowed for people who can see proofer names
    // on the page details
    /** @var DpProject project */

    echo "
    <input type='submit' name='export' value='Download'>
    </form>
    </li>
    </ul>\n";
}

// -----------------------------------------------------------------------------
function echo_uploaded_zips($project, $filetype, $upload_type) {
    /** @var DpProject $project */

    $pdir = $project->ProjectPath();

    $done_files = glob("$pdir/*".$filetype."*.zip");
  if ($done_files) {
      echo sprintf( _("Download %s file uploaded by:"), $upload_type);
      echo "<ul>";
      foreach ($done_files as $filename) {
          $showname = basename($filename,".zip");
          $showname = substr($showname, strpos($showname,$filetype) + strlen($filetype));
          echo_download_zip($project, $showname, $filetype.$showname );
        }
      echo "</ul>";
    }
  else {
      echo sprintf( _("No %s results have been uploaded."), $upload_type);
    }

}
// -----------------------------------------------------------------------------

function echo_download_zip( $project, $link_text, $filetype ) {
    global $code_url;
    /** @var DpProject $project */

    $projectid = $project->ProjectId();
    $pdir = $project->ProjectPath();

    if ( $filetype == 'images' ) {
        // Generate images zip on the fly,
        // so it's not taking up space on the disk.

        $url = "$code_url/tools/download_images.php"
                    ."?projectid=$projectid"
                    ."&amp;dummy={$projectid}images.zip";
        $filesize_b = 0;
        foreach( glob("$pdir/*.{png,jpg}", GLOB_BRACE) as $image_path ) {
            $filesize_b += filesize($image_path);
        }
        $filesize_kb = round( $filesize_b / 1024 );
    }
    else {
        $p = "$projectid$filetype.zip";
        $url = build_path($project->ProjectUrl(), $p);
        $filesize_kb = @round( filesize( "$pdir/$p") / 1024 );
    }

    echo "<li><a href='$url'>$link_text</a> ($filesize_kb kb) </li>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function solicit_pp_report_card($project) {
    /** @var DpProject $project */
    global $code_url;
    $url = "$code_url/tools/post_proofers/ppv_report.php?projectid={$project->ProjectId()}";
    echo "<p>" . link_to_url($url, "Submit a PPV Report Card for this project") . "</p>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function solicit_postcomments($project) {
    global $forums_url;

    /** @var DpProject $project */

    $projectid = maybe_convert($project->ProjectId());

    if ($project->UserIsPPer()) {
        $postcomments = maybe_convert($project->PostComments());
        echo "<h4>" . _("Post-Processor's Comments") . "</h4>";

        // Give the PP-er a chance to update the project record
        // (limit of 90 days is mentioned below).
        echo '<p>' . sprintf(_("You can use this text area to enter comments on how you're
                     doing with the post-processing, both to keep track for yourself
                     and so that we will know that there's still work in progress.
                     You will not receive an e-mail reminder about this project for at
                     least another %1\$d days.") .
                     _("You can use this feature to keep track of your progress,
                     missing pages, etc. (if you are waiting on missing images or page
                     scans, please add the details to the <a href='%2\$s'>Missing Page
                     Wiki</a>)."),
                     90, "$forums_url/viewtopic.php?t=7584") . ' ' .
                     _("Note that your old comments will be replaced by those
                     you enter here.") . "</p>
        <form name='pp_update' method='post' action=''>
        <textarea name='postcomments' cols='60' rows='6'>$postcomments</textarea>
        <input type='hidden' name='projectid' value='$projectid' />
        <br />
        <input type='submit' name='submit_post_comments'
            value='" . _('Update comment and project status') . "'/>
      </form>\n";
    }
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function solicit_smooth_reading($project) {
    global $code_url, $User;

    $username = $User->Username();

    /** @var DpProject $project */
    if ( $project->Phase() != "PP" )
        return;

    $projectid = $project->ProjectId();

    echo "
    <h4>", _('Smooth Reading'), "</h4>
    <ul>";

    if ( ! $project->SmoothreadDeadline() ) {
        echo _('<li>This project has not been made available for smooth reading.</li>');

        if ($project->UserIsPPer()) {
            $link_1 = link_to_upload_text_to_smooth($projectid, "smooth_avail", 1, "one week");
            $link_2 = link_to_upload_text_to_smooth($projectid, "smooth_avail", 2, "two weeks");
            $link_4 = link_to_upload_text_to_smooth($projectid, "smooth_avail", 4, "four weeks");
//            $link_1 = link_to_smooth_upload($projectid, "1", "one week");
//            $link_2 = link_to_smooth_upload($projectid, "2", "two weeks");
//            $link_4 = link_to_smooth_upload($projectid, "4", "four weeks");
            echo "
            <li>\n";
                echo _("<p>As the project's PPer, you can set the period of time 
                it is available.</p>") ."
                <ul>
                <li>$link_1</li>
                <li>$link_2</li>
                <li>$link_4</li>
                </ul>
            </li>\n";
        }
    }
    else {
        // Project has been made available for SR

        if ( $project->IsAvailableForSmoothReading()) {
            echo _("<li>This project has been made available for smooth reading
                until <b>{$project->SmoothReadDate()}</b>.</li>\n");

            if (! $project->UserIsPPer()) {
                echo "
                <li>" . link_to_smooth_download($projectid,
                                "Download zipped text for smoothreading") ." </li>
                <li>" . link_to_smoothed_upload($projectid, "Upload a smooth-read text") ." </li>\n";

                // The upload does not cause the project to change state --
                // it's still checked out to PPer.

                if (!sr_user_is_committed($projectid, $username)) {
                    echo _('<li>You may indicate your commitment
                        to smoothread this project to the PP by pressing:');
                    sr_echo_commitment_form($projectid);
                    echo "</li>\n";
                }
                else {
                    echo _(
                        '<li>You have committed to smoothread this project.
                        If you want to withdraw your commitment, please press:');
                    sr_echo_withdrawal_form($projectid);
                    echo "</li>";
                }
            }
            else {
                echo "
                <li>
                <a href='$code_url/tools/upload_text.php"
                            ."?projectid=$projectid"
                            ."&amp;stage=smooth_avail"
                            ."&amp;weeks=replace'>
                "._("Replace the current file that's available for smooth-reading.")."
                </a>
                </li>\n";
            }
        }
        else {
            echo "
            <li>
            ". _('The deadline for smooth-reading this project has passed.')."
            </li>\n";

            if ($project->UserIsPPer()) {
                $link_1 = link_to_upload_text_to_smooth($projectid, "smooth_avail", 1, "one week");
                $link_2 = link_to_upload_text_to_smooth($projectid, "smooth_avail", 2, "two weeks");
                $link_4 = link_to_upload_text_to_smooth($projectid, "smooth_avail", 4, "four weeks");
                echo "
            <li>\n";
                echo _("<p>As the project's PPer, you can make it available
                for a further period.</p>") ."
                <ul>
                <li>$link_1</li>
                <li>$link_2</li>
                <li>$link_4</li>
                </ul>
            </li>\n";
            }
        }

        if ($project->UserIsPPer()) {

            $sr_list = sr_get_committed_users($projectid);

            if (count($sr_list) == 0) {
                echo _('<p>Nobody has committed to smoothread this project.</p>');
            }
            else {
                echo _("
                <pre>The following users have committed to smoothread this project:\n");
                foreach ($sr_list as $sr_user) {
                    echo "<li>" . link_to_pm($sr_user) . "</li>\n";
                }
                echo "</pre>\n";
            }

            echo_uploaded_zips($project, '_smooth_done_', _('smoothread'));
        }
    }

    echo "</ul>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX


function PrepActions($project) {
    global $User;
    /** @var DpProject $project */
    $holds = $project->ActiveHolds();
    foreach($holds as $hold) {
        $code = $hold['hold_code'];
        if($User->MayReleaseHold($code)) {
            return array("prompt" => "Release {$hold['hold_code']} hold", 
                         "action" => "release.$code");
        }
    }
    return array();
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_page_summary($project) {
    /** @var DpProject $project */
    if ( !$project->IsProjectTable() )
        return;


    // page counts by state.
    $total_num_pages = $project->PageCount();
    $counts = $project->PageStateCounts();

    echo "
    <div class='lfloat clear'>
        <h3>"._("Page Summary")."</h3>
        <table id='tblpagesummary' class='noborder lfloat'>\n";
foreach(array("available", "checkedout", "completed", "bad") as $status) {
    $num_pages = $counts[$status];
    if ( $num_pages != 0 ) {
        echo "
            <tr><td class='right'>$num_pages</td>
                <td>$status</td>
            </tr> <!-- 4 -->\n";
    }
}
echo "
            <tr><td colspan='2'><hr></td></tr> <!-- 6 -->
            <tr><td class='right'>$total_num_pages</td>
                <td class='center'>" . _("pages total")."</td>
            </tr> <!-- 7 -->
        </table>
        </div>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function show_page_table($project) {

    /** @var DpProject $project */
    if ( ! $project->IsProjectTable() )
        return;

    echo "
    <div id='page_table_key' class='clear'>
        <h4 class='lfloat'>". _('Pages proofread by you and...') . "</h4>
        <div style=' margin-bottom: .4em' class='pg_out bordered padded lfloat clear'>"
        . _("IN PROGRESS (awaiting completion this round)") . "</div>
        <div style=' margin-bottom: .4em' class='pg_completed bordered padded lfloat'>"
        . _("DONE (still available for editing this round)") ."</div>
        <div style=' margin-bottom: .4em' class='pg_unavailable bordered padded lfloat'>"
        . _("DONE in a previous round (no longer available for editing)") ."</div>
    </div>

    <div class='lfloat clear'>\n";

    // second arg. indicates to show size of image files.
    echo_page_table($project);
    echo "
    </div>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

// vim: sw=4 ts=4 expandtab

