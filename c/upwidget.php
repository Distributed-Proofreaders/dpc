<?php
/*
    Intended to be hosted by another form. It creates an iframe with
    this as the source. The other form passes projectid and pagename
    in the url. When/if the user chooses a file, the file and info
    are submitted here, and this form replaces the page image with
    the selected file. Then via js (by embedding a call in the page
    init function for the completed transfer) the hosting page is
    advised that the replacement has taken place so it can refresh
    the image.
*/
$relPath = "./pinc/";
require_once $relPath."dpinit.php";
require_once $relPath."DpProject.class.php";

/** @var $User DpThisUser */
/** @var $page DpPage */
$User->IsLoggedIn() or die();

$projectid = ArgProjectId();
$projectid != ""
    or die("no projectid");
$project = new DpProject($projectid);
$pagename  = ArgPageName();
if($pagename != "") {
    $page = new DpProtoPage($projectid, $pagename);
}
$todo = Arg("todo", "replaceimage");

$files = @$_FILES["upfile"];
if(is_array($files)) {
    $frompath = $files["tmp_name"];
    $filename = $files["name"];
    $sfx = right($filename, 4);
    switch($sfx) {
        case ".png":
        case ".jpg":
            // $topath = build_path($page->ProjectPath(), $filename);
            // $tofilename = basename($filename).$sfx;
            $page = new DpProtoPage($projectid, $pagename);
            $page->SetExternalImageFile($frompath);
            $onload = "top.eReplacedImageFile();";
            // $upmessage = 
                    // "top.ReplacedImage('$projectid', '$pagename')";
            // else {
                // $topath = build_path(
                    // $project->TransientPath(), $filename);
                // move_uploaded_file($frompath, $topath);
                // $upmessage = "top.UploadedFile($filename);";
            // }
            break;

        case ".zip":
            // $upmessage = "top.IncomingZipfile('$filename');";
            $topath = build_path(
                        $project->TransientPath(), $filename);
            move_uploaded_file($frompath, $topath);
            $onload = "";
            break;

        default:
            exit;
    }
}
else {
    $onload = "";
}

echo "<!DOCTYPE HTML>
<html style='width:100%; height:100%; overflow:hidden;'>
<head>
<title>upwidget</title>
<script type='text/javascript'>
function init()
{
    // console.debug('init');
    {$onload}
}
function eQuitUpload(e) {
    top.document.getElementById('uploadframe').style.display = 'none';
}
</script>

</head>
<body onload='init()' style='display: inline'>
<form action='' method='post' name='upform'
enctype='multipart/form-data' onsubmit='top.eUp()' 
style='display: inline'>
<input type='hidden' name='projectid' value='$projectid'>
<input type='hidden' name='pagename' value='$pagename'>
<input type='hidden' name='todo' value='$todo'>

<input type='file' onchange='top.eUpFile()' name='upfile' style='display: inline'>
<input type='submit' value='Replace' name='upbutton' style='display: inline'>
<span id='uploading' style='display: none; color: red;'>Uploading...</span>
<input type='button' value='Quit' name='quitbutton' onclick='eQuitUpload(event)'>
</form>
</body></html>";
?>
