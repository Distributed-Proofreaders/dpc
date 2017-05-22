<?PHP

ini_set("display_errors", 1);
error_reporting(E_ALL);

$relPath="../pinc/";
include_once($relPath.'dpinit.php');

$projectid      = Arg('projectid', Arg('project'));
$smoothcomments = Arg('smoothcomments');
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
    $tofilepath = $project->SmoothDownloadPath("zip");
    $suffix = extension($uploadfilename);
    switch($suffix) {
        case "zip":
            break;
        case "pdf":
        case "epub":
        case "mobi":
        case "txt":

    }

    rename($uploadtmpfilename, $tofilepath);
    chmod($tofilepath, 0777);
    $project->UnzipSmoothZipFile();

    $tofilename = basename($tofilepath);
    $str = "\n----------\n".date("Y-m-d H:i")
        .  "Uploaded $tofilename for smooth reading.";

    $project->PrependPostComments($str);
    $project->SetSmoothComments($smoothcomments);
    divert(url_for_project($projectid));;
}

// Present the upload page.
$backto = link_to_project($projectid);
$title = _("Upload files for Smooth Reading");

theme($title, "header");

$caption = "Instructions for Smooth Readers";

echo "
<div class='w800 lfloat center'>
$backto
  <h1 class='center'>$title</h1>
  <h2 class='center'>$nameofwork</h2>
    <form action='' method='post' enctype='multipart/form-data'>
      <input type='hidden' name='project' value='$projectid' />
      <input type='hidden' name='MAX_FILE_SIZE' value='300000000' />
      <div>
        <label for='dpupload' class='dp-upload'>
          <p>Expected file types: .zip, .txt, .epub, .mobi, .pdf</p>
        </label>
        <input name='dpupload' id='dpupload' type='file' accept='.zip,.txt,.epub,.mobi,.pdf' />
        " . _("<p>After you click Upload, the browser may be slow getting to the next
               page, while it is uploading the file.</p>")

    . "
        <hr>
        <h4>Comments for Smooth Readers</h4>
        <div>
            <textarea class='b111'  name='smoothcomments' cols='50' rows='16'></textarea>
        </div>
      </div>
      <input name='submit_upload' id='submit_upload' type='submit' value='Submit' />
    </form>
</div>";

theme("", "footer");
exit;


// vim: sw=4 ts=4 expandtab

