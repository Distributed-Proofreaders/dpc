<?PHP
/*
 *
 * File: upload_smooth.php
 * Prompt to upload a zip file with fiels to be smoothed;
 * Also accept and process said file.

*/


ini_set("display_errors", 1);
error_reporting(E_ALL);

$relPath="../pinc/";
include_once($relPath.'dpinit.php');

$projectid      = ArgProjectId();
$project        = new DpProject($projectid);
$nameofwork     = $project->Title();
$username       = $User->Username();

// The only upload
if(isset($_FILES) && isset($_FILES["dpupload"])) {
    $isuploadfile       = true ;
    $upfiles            = $_FILES["dpupload"];
    $uploadfilename     = $upfiles["name"];
    $uploadtmpfilename  = $upfiles["tmp_name"];
    $uploadfilesize     = $upfiles["size"];

    $back_url = url_for_project($projectid);

    if(extension($uploadfilename) != "zip") {
        die("Uploaded file not a zip file.");
    }

    $tofilepath = $project->SmoothZipFilePath();
    rename($uploadtmpfilename, $tofilepath);
    chmod($tofilepath, 0777);

    $project->MaybeUnzipSmoothZipFile();

    $log_comment = "\n----------\n".date("Y-m-d H:i")
        . "Uploaded zip file for smooth reading ({$project->SmoothZipFilePath()})"
        . "\n$log_comment\n";
    $project->AddPostComment($log_comment);

    divert(url_for_project($projectid));
    exit;
}

$title = _("Upload zipped Project files for Smooth Reading");

theme($title, "header");

echo "
<div class='w800 center'>
  <h1 class='center'>$title</h1>
  <h2 class='center'>$nameofwork</h2>
    <form method='post' enctype='multipart/form-data'>
      <input type='hidden' name='project' value='$projectid' />
      <input type='hidden' name='MAX_FILE_SIZE' value='300000000' />
    <div class='w75'>
    <input name='dpupload' id='dpupload' type='file' accept='zip' />
    <input name='submit_upload' id='submit_upload' type='submit' value='Submit'/>
    </div>
        " . _("
        <p>(After you click Upload, the browser may be slow getting to the next
        page, while it is uploading the file.)</p>") . "
";

theme("", "footer");
exit;


// vim: sw=4 ts=4 expandtab



