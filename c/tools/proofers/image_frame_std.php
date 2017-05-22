<?
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'http_headers.inc');
require_once $relPath . "DpPage.class.php";

$projectid  = Arg("projectid");
$pagename   = Arg("pagename");
if($pagename == "") {
    $imagefile = Arg("imagefile");
    $pagename = imagefile_to_pagename($imagefile);
}

$page = new DpPage($projectid, $pagename);
$imgurl = $page->ImageUrl();
$zoom = $User->ImageZoom();
$zoom = max(25, $zoom);
$zoom = min(400, $zoom);
$imgwidth = $zoom;

include_once($relPath.'slim_header.inc');
slim_header("Image Frame", true, false);
echo "
<!DOCTYPE HTML>
<html>
<head>
<title>Image Frame</title>
</head>
<body bgcolor='#CDC0B0'>
    <div id='imagedisplay' style='width: {$imgwidth}%' class='center'>
            <img id='scanimage' style='width: 100%' alt='' src='$imgurl' >
    </div>
</body>
</html>";
