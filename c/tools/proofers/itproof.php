<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

global $site_abbreviation, $ajax_url, $site_url;

$relPath = "../../pinc/";

include_once $relPath.'site_vars.php';
include_once $relPath.'dpinit.php';
//include_once($relPath.'dp_main.inc');

// Note: pagename is either provided if known,
// or the user wants the "next available" page.

/** @var $User DpThisUser */
$User->IsLoggedIn()
    or RedirectToLogin();

$projectid      = ArgProjectId();
$pagename       = ArgPageName();

if(! $projectid)
    die("parameter 'projectid' is invalid");

$project        = new DpProject($projectid);

$project->MayBeProofedByActiveUser()
    or die("Security violation.");

if($pagename != "") {
    $page = new DpPage($projectid, $pagename);
    $page->MayBeSelectedByActiveUser()
        or die("Page unavailable to you.");
}

// $urlproof       = url_for_proof_frame($projectid, $pagename);
$urlproof = "$proof_url/itproof_frame.php"
            ."?projectid={$projectid}"
            . ($pagename ? "&pagename={$pagename}" : "");

$urlctls = "itctrlframe.php";

$title = "{$site_abbreviation}: "
            ."[{$project->RoundId()}] "
            ."{$project->Title()}";

$jslink = link_to_js("itdp_edit.js");

echo 
"<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<script>
    var AJAX_URL = '{$ajax_url}';
    var SITE_URL = '{$site_url}';
</script>
$jslink
<title>$title</title>
</head>

<frameset rows='*,7%' onload='eBodyLoad()' onresize='top.eResize()'>
<frame id='proofframe' name='proofframe' src='{$urlproof}'>

<frame id='ctlsframe' name='ctlsframe' src='{$urlctls}'>
</frameset>
<noframes>Your browser does not display frames!</noframes>
</html>";

?>
