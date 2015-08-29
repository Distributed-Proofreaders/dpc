<?php
/*
    buttons are: next, prev, proofer next, proofer prev
    which needs current projectid, pagename, roundid, proofer
    to make query SELECT pagename FROM pages
                  WHERE projectid ... AND pagename < or > pagename
                  (AND maybe roundn_user = joe)
*/

$relPath="./../../pinc/";
require_once $relPath . "dpinit.php";
$diffPath = '../../../Lib/php-text-difference/src/Diff/";Diff.php' ;
require_once '../../../Lib/php-text-difference/src/Diff/Diff.php' ;
require_once '../../../Lib/php-text-difference/src/Diff/SequenceMatcher.php' ;
require_once '../../../Lib/php-text-difference/src/Diff/Renderer/AbstractRenderer.php' ;
require_once '../../../Lib/php-text-difference/src/Diff/Renderer/Html/ArrayRenderer.php' ;
require_once '../../../Lib/php-text-difference/src/Diff/Renderer/Html/SideBySide.php' ;

/** @var $User DpThisUser */
$User->IsLoggedIn()
	or RedirectToLogin();

if(! IsArg("phase") && IsArg("version") &&  IsArg("roundid")) {
	die("neither phase or version provided");
}

$projectid       = ArgProjectId();
if(! $projectid)
	die(_("Project id not provided."));
//$imagefile       = Arg("imagefile");
$pagename        = ArgPageName();
if(! $pagename)
	die(_("Page Name not provided."));

if(isArg("phase")) {
	$phase = Arg( "phase", Arg( "roundid" ) );
}
else if(isArg("version")) {
	$version_num     = Arg("version");
}
else {
	die( _( "phase or version not provided." ) );
}

$project         = new DpProject($projectid);

$btnnext         = IsArg("btnnext");
$btnprev         = IsArg("btnprev");
$btnprfnext      = IsArg("btnprfnext");
$btnprfprev      = IsArg("btnprfprev");


// determine what page is wanted
if($btnnext) {
	$pgname = $project->PageNameAfter($pagename);
	if($pgname) {
		$pagename = $pgname;
	}
}
else if($btnprev) {
	$pgname = $project->PageNameBefore($pagename);
	if($pgname) {
		$pagename = $pgname;
	}
}
else if($btnprfnext) {
	$pgname = $project->ProoferRoundPageNameAfter($pagename, $phase);
	if($pgname) {
		$pagename = $pgname;
	}
}
else if($btnprfprev) {
	$pgname = $project->ProoferRoundPageNameBefore($pagename, $phase);
	if($pgname) {
		$pagename = $pgname;
	}
}

// if(empty($pagename)) {
// $pagename = $imagefile;
// }

// get the page
$page        = new DpPage($projectid, $pagename);

if(isset($version_num)) {
	$version = $page->Version($version_num);
}
else if($phase) {
	$version = $page->PhaseVersion( $phase );
}

$version_num     = $version->VersionNumber();
$proofername     = $version->Username();
$text            = $version->VersionText();
$lines           = text_lines($text);
$label           = $version->Username();

$prev_version    = $page->PenultimateVersion();
$prevproofername = $prev_version->Username();
$prevtext        = norm($prev_version->VersionText());
$prevlines       = text_lines($prevtext);
$prevlabel       = $prev_version->Username();

$project_title   = $page->Title();



// now have the image, users, labels etc all set up
// -----------------------------------------------------------------

$title      = "Page {$pagename} Diff — {$page->Title()}";
$projlink   = link_to_project($projectid, "Go to project page");

//$diffEngine = new DifferenceEngineWrapper();

$view_image = _("view image");
$imglink = link_to_view_image($projectid, $pagename, $view_image, true);

$diff = new \Adaptive\Diff\Diff($prevtext, $text);
$renderer = new \Adaptive\Diff\Renderer\Html\SideBySide();


echo "<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<title>$title</title>
<link type='text/css' rel='stylesheet' href='{$css_url}/dp.css'>
<link rel='stylesheet' href='/Lib/php-text-difference/example/styles.css' type='text/css' charset='utf-8'/>
</head>

<body>
<div class='container left'>

<h1>{$page->Title()}</h1>
<h2 class='nobold'>Page {$page->PageName()}</h2>

<div id='diffbox' class='center w80'>
$imglink
    <form id='navform' name='navform' method='POST'
            accept-charset='UTF-8' class='right' action='http://www.pgdpcanada.net/c/tools/project_manager/diff.php'>\n";

echo "
        <div class='lfloat'>
        <input type='submit' name='btnprev' value='"
     ._("Previous")."'>

        ". _(" Selected page: ") . "
        <select id='pagelist' onChange='eListChange()'>\n";

foreach($project->PageRows() as $row) {
	$sel = ($row['pagename'] === $pagename
		? " selected='selected' "
		: "");
	$name = $row['pagename'];
	echo "<option value='$name' {$sel}>$name</option>\n";
}

echo "
        </select>
    <input type='submit' name='btnnext' value='"._("Next")."'>
    </div>\n";

/*
if($phase == "P2" || $phase == "P3"
   || $phase == "P1" || $phase == "F2" ) {
	echo "
        <div class='rfloat'>
        <input type='submit' name='btnprfprev' 
                            value='"._("$prevroundid $prevproofername Prev")."'>
        <input type='submit' name='btnprfnext' 
                                value='"._("$prevroundid $prevproofername Next")."'>
        </div>\n";
}
*/

echo "
        <input type='hidden' 
            id='projectid' name='projectid' value='$projectid'>
        <input type='hidden' 
            id='pagename' name='pagename' value='$pagename'>
        <input type='hidden' id='roundid' name='roundid' 
            value='$phase'>
            $imglink
    </form>
    <p>{$projlink}</p>
    </div>\n";

//$diff = new \Adaptive\Diff\Diff($prevlines, $lines);
//$renderer = new \Adaptive\Diff\Renderer\Html\SideBySide();
//echo $diff->render($renderer);

if($prevtext == $text) {
	echo "
        <div class='center'>
            <h1>
                <span class='pct75 nobold'> ($prevproofername)</span>"
	     ._("&nbsp;&nbsp;No differences.&nbsp;&nbsp;")."
                <span class='pct75 nobold'>$phase ($proofername)</span>"
	     ."</h1>
        </div>\n";
}
//else {
	$diff = new \Adaptive\Diff\Diff($prevlines, $lines);
	$renderer = new \Adaptive\Diff\Renderer\Html\SideBySide();
	echo $diff->render($renderer);
//}

echo "
</div>
</body></html>\n";

// ---------------------------------------------------------


// theme_footer();
exit;


// vim: sw=4 ts=4 expandtab
