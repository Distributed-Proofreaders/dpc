<?PHP
/*

    Upload types:   
    PP work completed        _second                    advance to PPV             _pp_username
    PP work not completed    _first_in_prog_            no change                  _pp_username
    smooth available         _smooth_avail              set deadline               _smooth
    smooth work              _smooth_done_username      no change                  _smooth_username

      upload_action           filename
    post_1          _second
    return_1        _first_in_prog_{$username}";
    return_2        _second_in_prog_username 
    smooth_avail    _smooth_avail
    smooth_done     _smooth_done_username

*/


ini_set("display_errors", 1);
error_reporting(E_ALL);

$relPath="../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'theme.inc');
include_once($relPath.'helpers.php');

$projectid      = Arg('projectid', Arg('project'));
$upload_action  = Arg('upload_action');
//$weeks          = Arg('weeks');
$postcomments   = Arg('postcomments');
$submit_upload  = IsArg('submit_upload');  

$project        = new DpProject($projectid);
$nameofwork     = $project->Title();
$username       = $User->Username();

if(isset($_FILES) && isset($_FILES["dpupload"])) {
    $isuploadfile       = true ;
    $upfiles            = $_FILES["dpupload"];
    $uploadfilename     = $upfiles["name"];
    $uploadtmpfilename  = $upfiles["tmp_name"];
    $uploadfilesize     = $upfiles["size"];
}
else {
    $isuploadfile = false;
}

// if files have been uploaded, process them
// mangle the postcomments

// make reasonably sure script does not timeout on large file uploads
// $path_to_file = "$projects_dir/$projectid";

// do some checks. File must exist (except if we are returning to PP 
// or PPV available.
// if we have a file, we need its name to end in .zip, and we need
// it to have non zero size.  and there must be only one file.

if ($isuploadfile) {       // we have a file now. do some more checks.

	if(right($uploadfilename, 4) != ".zip") {
		echo _("Invalid Filename (not .zip)");
		divert($back_url);
	}
	if($uploadfilesize == 0) {
		echo _("File $uploadfilename is empty");
		divert($back_url);
	}

	switch($upload_action) {
        case "pp_temp":
        case "pp_complete":
            $tofilepath = $project->PPUploadPath();
            $back_url   = url_for_my_projects();
			$log_comment = "Uploaded file for $upload_action";
            break;

        case "ppv_complete":
            $tofilepath = $project->PPVUploadPath();
            $back_url       = "$code_url/tools/ppv.php";
	        $log_comment = "Uploaded file for $upload_action";
            break;

        case "ppv_temp":
            $tofilepath = $project->PPVUploadPath();
            $back_url = "$code_url/tools/ppv.php";
	        $log_comment = "Uploaded file for $upload_action";
            break;

        case "smooth_avail":
            $tofilepath = $project->SmoothDownloadPath();
            $back_url = "$code_url/project.php"
                ."?id=$projectid";
	        $log_comment = "Uploaded zipped files for smooth reading";
            break;

        case "smooth_done":
            $tofilepath = $project->SmoothUploadPath($username);
            $back_url = "$code_url/project.php"
                ."?id=$projectid";
	        $log_comment = "Uploaded smoothed files";
            break;

        default:
            die("Invalid value for upload_action: $upload_action");
    }

    rename($uploadtmpfilename, $tofilepath);
    chmod($tofilepath, 0777);
    
    // we've put the file in the right place.
    // now let's deal with the postcomments.
    // we construct the bit that's going to be added on to the existing postcomments.
    // if we're returning to available, and the user hasn't loaded a file, and not
    // entered any comments, we don't bother.
    // Otherwise, we add a divider, time stamp, user name, and the name of the file
	$tofilename = basename($tofilepath);
    $postcomments = "\n----------\n".date("Y-m-d H:i")
        .  ($isuploadfile ? "Uploaded $tofilename" : "")
        . "\n$log_comment
        $postcomments\n";

//    switch($upload_action) {
//        case "smooth_avail":
//            if($weeks == "replace") {
//                $project->LogProjectEvent( PJ_EVT_SMOOTH, 'text replaced' );
//                $sql = "
//                    UPDATE projects
//                    SET postcomments = CONCAT(postcomments, ?)
//                    WHERE projectid = '$projectid'";
//            }
//            else {

	$sql = "
			UPDATE projects
			SET  postcomments = CONCAT(IFNULL(postcomments, ''), ?)
			WHERE projectid = '$projectid'";
//            }
	$args = array(&$postcomments);
	$dpdb->SqlExecutePS($sql, $args);

//            if ( $weeks == "replace" ) {
//                $project->LogProjectEvent( 'smooth-reading', 'text replaced' );
//            }
//            else {
	$project->LogProjectEvent( 'smooth-reading', 'text available' );
//            }
//            break;

//        case "smooth_done":
//            $project->LogSmoothDone();
//            break;

//        case "ppv_temp":
//            $msg = _("Project saved.");
//            $project->LogProjectEvent( 'pp-verifying', 'interim file uploaded' );
//            break;
//
//        case "pp_temp":
//        case "pp_complete":
//            $msg = _("Project saved.");
//            $project->LogProjectEvent( 'post-proofing', 'PP file uploaded' );
//            break;

//        case "ppv_complete":
//            $msg = _("File uploaded. Thank you!");
//            $project->LogProjectEvent( 'pp-varifying', 'completed file uploaded' );
//            break;
//    }

    divert($back_url, $msg, 2);
    exit;
}

// Present the upload page.
$backto = "";
switch($upload_action) {
    case 'pp_complete':
    case 'pp_temp':
        $title = _("Upload Post-Processed Project<br/>(complete or not)");
        $backto = "<div class='lfloat'>" 
                    . link_to_my_projects("Back to My Projects") 
                    . "</div>\n";
        break;

    case 'ppv_complete':
        $title = _("Upload Completed Verified Project (to be posted)");
        break;

    case 'ppv_temp':
        $title = _("Upload incompletely Verified Project (for others to work on)");
        break;

    case 'smooth_avail':
        $title = _("Upload zipped Project files for Smooth Reading");
        break;

    case 'smooth_done':
        $title = _("Upload project you have Smooth Read");
        break;

    default:
        echo "Don't know how to handle upload_action='$upload_action'<br>\n";
        die();
}


theme($title, "header");


echo "
<div class='w800 lfloat center'>
{$backto}
  <h1 class='center'>$title</h1>
  <h2 class='center'>$nameofwork</h2>
    <form action='' method='post' enctype='multipart/form-data'>
      <input type='hidden' name='project' value='$projectid' />
      <input type='hidden' name='upload_action' value='$upload_action' />
      <input type='hidden' name='MAX_FILE_SIZE' value='300000000' />
    <div class='w50'>
      <input type='file' name='dpupload' class='center' />
    </div>
      <div class='center w50'>
      <input name='submit_upload' id='submit_upload' type='submit' value='Upload' />
      </div>\n";

    $caption = ($upload_action == "smooth_avail")
                ? "Instructions for Smooth Readers"
                : "Comments";
    echo "
        <div>
            <h4>" . _($caption) . "</h4>
            <textarea class='b111'  name='postcomments' cols='50' rows='16'></textarea>
        </div>
        " . _("<p>Please make sure the file you upload is zipped (with a .zip
        extension.)</p>
        
        <p>After you click Upload, the browser will be slow getting to the next
        page, while it is uploading the file.</p>") . "
      </form>
    </div>";

theme("", "footer");
exit;


// vim: sw=4 ts=4 expandtab



