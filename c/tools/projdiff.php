<?php
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="../pinc/";
require_once $relPath . "dpinit.php";
require_once "./project_manager/DifferenceEngineWrapper.php";

/** @var $User DpThisUser */
$User->IsLoggedIn()
    or RedirectToLogin();

$projectid       = ArgProjectId();
$roundid         = Arg("roundid");

if(! $projectid)
    die(_("Project id not provided."));
if(! $roundid)
    die(_("Round id not provided."));

$project         = new DpProject($projectid);
$nextroundid     = RoundIdAfter($roundid);


$project_title   = $project->Title();

$label           = $roundid;

$title      = "Project Diff — $project_title ($roundid - $nextroundid)";
$projlink   = link_to_project($projectid, "Go to project page");

$diffEngine = new DifferenceEngineWrapper();

echo "<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<title>$title</title>
<link type='text/css' rel='stylesheet' href='{$css_url}/dp.css'>
<script type='text/javascript' src='dpdiff.js'></script>
</head>

<body onload='init()'>
<div class='container left'>

<h1 class='center'>$title</h1>

<div id='diffbox' class='center w80'>
    <form id='navform' name='navform' method='POST' 
            accept-charset='UTF-8' class='right' >

    </form>
    <p>{$projlink}</p>
    </div>\n";

    $a = $project->RoundText($roundid);
    $b = $project->RoundText($nextroundid);
    $diffEngine->showDiff($a, $b, $roundid, $nextroundid);
echo "
</div>
</body></html>\n";

// ---------------------------------------------------------


// theme_footer();
exit;


// vim: sw=4 ts=4 expandtab
?>
