<?php

error_reporting(E_ALL);
$relPath = "../pinc/";

include_once $relPath.'dpinit.php';

// Note: pagename is either provided if known,
// or the user wants the "next available" page.

/** @var $User DpThisUser */
$User->IsLoggedIn()
    or RedirectToLogin();


$projectid      = ArgProjectId();
$pagename       = ArgPageName();

if($projectid == "")
    die("projectid not provided.");

$project        = new DpProject($projectid);
assert($project->Exists());

assert($project->MayBeProofedByActiveUser());
$project->MayBeProofedByActiveUser()
    or die("Security violation.");

if($pagename != "") {
    $page           = new DpPage($projectid, $pagename);
    $page->MayBeSelectedByActiveUser()
        or die("Page unavailable to you.");
}

// $urlproof       = url_for_proof_frame($projectid, $pagename);
$urlproof       = proof_frame_url($projectid, $pagename);

$urlctls        = "ctrlframe.php";

global $site_abbreviation, $ajax_url, $site_url;

$title          = "{$site_abbreviation}: "
                    ."[{$project->RoundId()}] "
                    ."{$project->Title()}";

echo 
"<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<script>
    var AJAX_URL = '{$ajax_url}';
    var SITE_URL = '{$site_url}';
</script>\n";

echo link_to_js("dp_edit.js");

echo "
<title>$title</title>
</head>

<frameset rows='*,78' onload='eBodyLoad()' onresize='top.eResize()'>
<frame id='proofframe' name='proofframe' src='{$urlproof}'>

<frame id='ctlsframe' name='ctlsframe' 
       noresize='noresize' src='{$urlctls}'>
</frameset>
<noframes>Your browser does not display frames!</noframes>
</html>";

exit;

function proof_frame_url($projectid, $pagename) {
    return "./proof_frame.php?projectid=$projectid&amp;pagename=$pagename";
}
?>
