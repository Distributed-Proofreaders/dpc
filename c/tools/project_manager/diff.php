<?php
/*
    buttons are: next, prev, proofer next, proofer prev
    which needs current projectid, pagename, roundid, proofer
    to make query SELECT pagename FROM pages
                  WHERE projectid ... AND pagename < or > pagename
                  (AND maybe roundn_user = joe)

	"Next proofer page" means the next page for the left-hand proofer.
	We want to show the diffs that follow.

	So the reference page version is on the left,
		which drives the next version on the right.
*/



$relPath="./../../pinc/";
require_once $relPath . "dpinit.php";
$diffPath = '../../../Lib/php-text-difference/src/Diff/";Diff.php' ;
require_once '../../../Lib/php-text-difference/src/Diff/Diff.php' ;
require_once '../../../Lib/php-text-difference/src/Diff/SequenceMatcher.php' ;
require_once '../../../Lib/php-text-difference/src/Diff/Renderer/AbstractRenderer.php' ;
require_once '../../../Lib/php-text-difference/src/Diff/Renderer/Html/ArrayRenderer.php' ;
require_once '../../../Lib/php-text-difference/src/Diff/Renderer/Html/SideBySide.php' ;

$User->IsLoggedIn()
	or RedirectToLogin();

$projectid              = ArgProjectId();
$pagename               = ArgPageName();
$phase                  = Arg("phase");
$mode                   = Arg("mode", "1");
$btn_nextpage           = IsArg("btn_nextpage");
$btn_prevpage           = IsArg("btn_prevpage");
$btn_proofer_next       = IsArg("btn_proofer_next");
$btn_proofer_prev       = IsArg("btn_proofer_prev");


if(! $projectid)
	die(_("Project id not provided."));
$project         = new DpProject($projectid);
if(! $pagename)
	die(_("Page Name not provided."));
if(! $phase)
	die("neither phase nor roundid provided");

$page = new DpPage($projectid, $pagename);
// get the version now in case wee need the proofer
$version = $page->PhaseVersion($phase);
if(! $version)
	die(_("No text version found for $projectid $pagename $phase"));

$proofer = $version ? $version->Username() : "";



// determine what page is wanted
if($btn_nextpage) {
//	$pgname = $project->PageNameAfter($pagename);
	$pg = $project->PageAfter($pagename, $phase);
	if($pg) {
		$page = $pg;
		$pagename = $page->PageName();
		$version = $page->PhaseVersion($phase);
		$proofer = $version->Username();
	}
}
else if($btn_prevpage) {
	$pg = $project->PageBefore($pagename, $phase);
	if($pg) {
		$page= $pg;
		$pagename = $page->PageName();
		$version = $page->PhaseVersion($phase);
		$proofer = $version->Username();
	}
}
else if($btn_proofer_next) {
	$pg = $project->ProoferPhasePageAfter($pagename, $phase, $proofer);
	if($pg) {
		$page = $pg;
		$pagename = $page->PageName();
		$version = $page->PhaseVersion($phase);
		$proofer = $version->Username();
	}
}
else if($btn_proofer_prev) {
	$pg = $project->ProoferPhasePageBefore($pagename, $phase, $proofer);
	if($pg) {
		$page = $pg;
		$pagename = $page->PageName();
		$version = $page->PhaseVersion($phase);
		$proofer = $version->Username();
	}
}

$problem = "";
$isdiff = true;

// chosen text on right, compare back
if($mode == "1") {
	$label = "($phase) $proofer";

	// is there a preceding version?
	if ( $version->VersionNumber() == 0 ) {
		$problem = "No preceding text version to compare with";
		$isdiff  = false;
	}

	if ( ! $problem ) {
		$version0 = $page->Version( ( $version->VersionNumber() - 1 ) );
		if ( $version0->State() != "C" ) {
			$problem = "Preceding version not completed yet (???)";
			$isdiff  = false;
		}
	}

	if ( ! $problem ) {
		$text0  = $version0->VersionText();
		$lines0 = text_lines( $text0 );
		$text1  = $version->VersionText();
		$lines1 = text_lines( $text1 );

		if ( $lines0 == $lines1 ) {
			$problem = _( "No differences" );
			$isdiff  = false;
		}
	}

	if ( $version0 ) {
		$proofer0 = $version0->Username();
		$phase0   = $version0->Phase();
		$label0   = "($phase0) $proofer0";
	} else {
		$label0 = $problem;
	}
}
else {
	$label = "($phase) $proofer";

	// is there a preceding version?
	if ( $version->VersionNumber() == $page->LastVersionNumber() ) {
		$problem = "No following text version to compare with";
		$isdiff  = false;
	}

	if ( ! $problem ) {
		$version2 = $page->Version( ( $version->VersionNumber() + 1 ) );
		if ( $version2->State() != "C" ) {
			$problem = "Following version not completed yet";
			$isdiff  = false;
		}
	}

	if ( ! $problem ) {
		$text2  = $version2->VersionText();
		$lines2 = text_lines( $text2 );
		$text1  = $version->VersionText();
		$lines1 = text_lines( $text1 );

		if ( $lines2 == $lines1 ) {
			$problem = _( "No differences" );
			$isdiff  = false;
		}
	}

	if ( $version2 ) {
		$proofer2 = $version2->Username();
		$phase2   = $version2->Phase();
		$label2   = "($phase2) $proofer2";
	} else {
		$label2 = $problem;
	}

}

$project_title   = $page->Title();




// now have the image, users, labels etc all set up
// -----------------------------------------------------------------

$title      = "Page {$pagename} Diff — {$project_title}";

$view_image = _("view image");
$imglink    = link_to_view_image($projectid, $pagename, $view_image, true);



echo "<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<title>$title</title>
<link type='text/css' rel='stylesheet' href='{$css_url}/dp.css'>
<link rel='stylesheet' href='/Lib/php-text-difference/example/styles.css' type='text/css' charset='utf-8'/>
<script type='text/javascript' src='/c/tools/project_manager/dpdiff.js'></script>
<style type='text/css'>
    .DifferencesSideBySide .ChangeInsert td.Right {
        background: #0c0;
    }
</style>
</head>

<body onload='init()'>
<div class='container left'>

<h1>{$page->Title()}</h1>
<h2 class='nobold'>Page {$page->PageName()}</h2>

<div id='diffbox' class='center w80'>
$imglink
    <form id='navform' name='navform' method='POST' accept-charset='UTF-8' class='right'>
	<input type='hidden' id='projectid' name='projectid' value='$projectid'>
	<input type='hidden' id='pagename' name='pagename' value='$pagename'>
    <input type='hidden' name='phase' id='phase' value='$phase'>\n";

echo "
	<div class='lfloat'>
        <input type='submit' id='btn_prevpage' name='btn_prevpage' value='"
     ._("Previous")."'>

        ". _(" Selected page: ") . "
        <select name='pagelist' id='pagelist' onChange='eListChange()'>\n";

foreach($project->PageRows() as $row) {
	$sel = ($row['pagename'] === $pagename
		? " selected='selected' "
		: "");
	$name = $row['pagename'];
	echo "<option value='$name' {$sel}>$name</option>\n";
}

echo "
        </select>
    <input type='submit' id='btn_nextpage' name='btn_nextpage' value='"._("Next")."'>
    </div>\n";


echo "
    </form>
    </div>\n";


if($mode == "1") {
	echo "
        <div class='center'>
            <h1>
                <span class='pct75 nobold'> $label0 </span>"
	     . _( "&nbsp;&nbsp; $problem &nbsp;&nbsp;" ) . "
                <span class='pct75 nobold'> $label </span>"
	     . "</h1>
        </div>\n";
}
else {
	echo "
        <div class='center'>
            <h1>
                <span class='pct75 nobold'> $label </span>"
	     . _( "&nbsp;&nbsp; $problem &nbsp;&nbsp;" ) . "
                <span class='pct75 nobold'> $label2 </span>"
	     . "</h1>
        </div>\n";

}

if($problem == "" && $isdiff == true) {
	if($mode == "1") {
		$diff     = new \Adaptive\Diff\Diff( $lines0, $lines1 );
		$renderer = new \Adaptive\Diff\Renderer\Html\SideBySide();
	}
	else {
		$diff     = new \Adaptive\Diff\Diff( $lines1, $lines2 );
		$renderer = new \Adaptive\Diff\Renderer\Html\SideBySide();
	}

	echo $diff->render( $renderer );
}

echo "
</div>
</body></html>\n";

// ---------------------------------------------------------


// theme_footer();
exit;


// vim: sw=4 ts=4 expandtab
