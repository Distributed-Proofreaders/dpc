<?PHP
$relPath = "./../../pinc/";
include_once $relPath . 'dpinit.php';
include_once $relPath . 'slim_header.inc';
require_once $relPath . "DpPage.class.php";
require_once $relPath . "links.php";

/*
$_POST:
    $projectid, $proj_state,
    $imagefile, $page_state, $text_data,
    $button1, $button2, $button3, $button4, ...
    $button1_x, $button2_x, $button3_x, $button4_x, ...
*/

// extract($_REQUEST);

$projectid = Arg("projectid");
$imagefile = Arg("imagefile");
$text_data = Arg("text_data");
$fntFace         = Arg("fntFace");
$fntSize         = Arg("fntSize");
$zmSize          = Arg("zmSize");
$isSaveTemp      = IsArg("button_1"  || IsArg("button_1_x"));
$isSaveNext      = IsArg("button_2"  || IsArg("button_2_x"));
$isQuit          = IsArg("button_3"  || IsArg("button_3_x"));
$isSwitchLayout  = IsArg("button_4"  || IsArg("button_4_x"));
$isSaveDone      = IsArg("button_5"  || IsArg("button_5_x"));
$isBadPage       = IsArg("button_6"  || IsArg("button_6_x"));
$isReturnToRound = IsArg("button_7"  || IsArg("button_7_x"));
$isClearTemp     = IsArg("button_8"  || IsArg("button_8_x"));
$isReloadTemp    = IsArg("button_9"  || IsArg("button_9_x"));
$isSpellCheck    = IsArg("button_10" || IsArg("button_10_x"));
$isSpCorrect     = IsArg("spcorrect");
$isSpExit        = IsArg("spexit");














$pagename  = preg_replace("/(.*)\..*/", "$1", $imagefile);

assert($projectid);
assert($pagename);

$page = new DpPage($projectid, $pagename);

// $text_data = isset($text_data) ? $text_data : '';

define('B_TEMPSAVE',                1);
define('B_SAVE_AND_DO_ANOTHER',     2);
define('B_QUIT',                    3);
define('B_SWITCH_LAYOUT',           4);
define('B_SAVE_AND_QUIT',           5);
define('B_REPORT_BAD_PAGE',         6);
define('B_RETURN_PAGE_TO_ROUND',    7);
define('B_REVERT_TO_ORIGINAL',      8);
define('B_REVERT_TO_LAST_TEMPSAVE', 9);
define('B_RUN_SPELL_CHECK',         10);

if($User->IsEnhancedLayout()) {
    if (isset($fntFace) && $User->FontFace() != $fntFace) {
        $User->SetFontFace($fntFace);
    }
    if (isset($fntSize) && $User->FontSize() != $fntSize) {
        $User->SetFontSize($fntFace);
    }
    if (isset($zmSize) && $User->ImageZoom() != $zmSize) {
        $User->SetZoom($zmSize);
    }
}

if($isSaveTemp) {
    $page->SaveText($text_data);
    include('proof_frame.inc');
    exit;
}

if($isSaveDone) {
    $page->saveAsDone($text_data);
    divert(url_for_project($projectid));
    exit;
}

if($isQuit) {
    divert(url_for_project($projectid));
    exit;
}

if($isSaveTemp) {
    $User->SwitchLayout();
    include('proof_frame.inc');
    exit;
}

if($isSaveDone) {
    $page->saveAsDone($text_data);
    divert(url_for_project($projectid));
    exit;
}

if($isBad) {
    include('report_bad_page.php');
    exit;
}

if($isReturnToRound) {
    $page->ReturnToRound();
    divert(url_for_project($projectid));
    exit;
}

if($isSaveTemp) {
    $page->SaveText($text_data);
    include('proof_frame.inc');
    exit;
}

if($isReloadTemp) {
    // just reload form
    include('proof_frame.inc');
    exit;
}

if($isSpellCheck) {
    include('spellcheck.inc');
    exit;
}

if( isset($spcorrect) ) {
    $correct_text = spellcheck_apply_corrections();
    $page->SaveText($correct_text);
    include('proof_frame.inc');
    exit;
}

if( isset($spexit) ) {
    $correct_text = spellcheck_quit();
    $page->SaveText($correct_text);
    include('proof_frame.inc');
    exit;
}

assert(false);
exit;

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

// vim: sw=4 ts=4 expandtab
