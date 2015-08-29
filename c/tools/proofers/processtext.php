<?PHP
ini_set("display_errors", 1);
error_reporting(E_ALL);
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'slim_header.inc');

$text_data = Arg("text_data");

extract($_REQUEST);
$projectid  = Arg("projectid");
$pagename   = Arg("pagename");
if($pagename == "") {
    $imagefile  = Arg("imagefile");
    $pagename   = imagefile_to_pagename($imagefile);
}
$page = new DpPage($projectid, $pagename);
$imagefile = $page->ImageFile();


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
define('B_RUN_COMMON_ERRORS_CHECK', 11);
define('B_ZOOM',                    35);


// set tbutton
  if (isset($button1) || isset($button1_x))
    {$tbutton = B_TEMPSAVE;}
  if (isset($button2) || isset($button2_x))
    {$tbutton = B_SAVE_AND_DO_ANOTHER;}
  if (isset($button3) || isset($button3_x))
    {$tbutton = B_QUIT;}
  if (isset($button4) || isset($button4_x)) 
    {$tbutton = B_SWITCH_LAYOUT;}
  if (isset($button5) || isset($button5_x))
    {$tbutton = B_SAVE_AND_QUIT;}
  if (isset($button6) || isset($button6_x))
    {$tbutton = B_REPORT_BAD_PAGE;}
  if (isset($button7) || isset($button7_x))
    {$tbutton = B_RETURN_PAGE_TO_ROUND;}
  if (isset($button8) || isset($button8_x))
    {$tbutton = B_REVERT_TO_ORIGINAL;}
  if (isset($button9) || isset($button9_x))
    {$tbutton = B_REVERT_TO_LAST_TEMPSAVE;}
  if (isset($button10) || isset($button10_x))
    {$tbutton = B_RUN_SPELL_CHECK;}
  if (isset($button11) || isset($button11_x))
    {$tbutton = B_RUN_COMMON_ERRORS_CHECK;}
  if (isset($button35) || isset($button35_x))
    {$tbutton = B_ZOOM;}

  if (isset($spcorrect))
    {$tbutton = 101;} // Make Spelling Corrections
  if (isset($spexit))
    {$tbutton = 102;} // Exit Spelling Corrections
  // if (isset($errcorrect))
    // {$tbutton = 111;} // Make Spelling Corrections
  // if (isset($errexit))
    // {$tbutton = 112;} // Exit Spelling Corrections

assert(isset($tbutton));
if(! isset($tbutton)) {
    var_dump($_REQUEST);
    die();
}

// if($pguser == 'dkretz') {
    // dump($imgzoom);
    // dump($userP);
// }


// $ischange = false;
// if enhanced

if($User->IsEnhancedLayout()) {
    if($User->FontFace() != $fntFace) {
        $User->SetFontFace($fntFace);
    }
    if($User->FontSize() != $fntSize) {
        $User->SetFontSize($fntSize);
    }
}
if($User->ImageZoom() != $imgzoom) {
    $User->SetZoom($imgzoom);
}


// If the user simply wants to leave the proofing interface,
// then it doesn't matter what state the project or page is in.
// So handle that case before we do any continuity/permission checks.
if ($tbutton == B_QUIT) {
    leave_proofing_interface($projectid, _("Stop Proofreading"), '' );
    exit;
}


// BUTTON CODE

switch( $tbutton ) {
    case B_TEMPSAVE:
        $page->SaveText($text_data);
        include('proof_frame.inc');
        break;

    case B_SWITCH_LAYOUT:
        $User->SwitchLayout();
        leave_proofing_interface($projectid,
            _("Switching Layout"), _("Switching Layout - Reopen Page to Continue.") );
        exit;
        break;

    case B_REVERT_TO_ORIGINAL:
        $page->RevertToOriginal();
        include('proof_frame.inc');
        break;

    case B_REVERT_TO_LAST_TEMPSAVE:
        $page->RevertToTemp();
        include('proof_frame.inc');
        break;

    case B_SAVE_AND_DO_ANOTHER:
        $roundid = $page->RoundId();
        $page->SaveAsDone($text_data);
        $proj = new DpProject($page->ProjectId());
        if($roundid != $proj->RoundId()) {
            leave_proofing_interface($projectid, url_for_project($projectid),
                        _("Project has completed $roundid."));
            exit;
        }
        if(! $proj->IsAvailableForActiveUser()) {
            leave_proofing_interface($projectid, url_for_project($projectid),
                        _("No more pages available for you."));
            exit;
        }
        $url = url_for_proof_frame_next($page->ProjectId());
        divert($url);
        exit;

    case B_SAVE_AND_QUIT:
        $page->SaveAsDone($text_data);
        leave_proofing_interface($projectid,  _("Save as 'Done'"), _("Page Saved.") );
        break;

    case B_RETURN_PAGE_TO_ROUND:
        $page->ReturnToRound();
        leave_proofing_interface($projectid, 
            _("Return to Round"), _("Page Returned to Round.") );
        break;

    case B_REPORT_BAD_PAGE:
        $page->MarkBad();
        leave_proofing_interface($projectid,  _("Mark Bad"), _("Page Bad.") );
        break;

    case B_RUN_SPELL_CHECK:
        if ( ! is_dir($aspell_temp_dir) )
            { mkdir($aspell_temp_dir); }
        include('spellcheck.inc');
        break;

    case B_ZOOM:
        include('proof_frame.inc');
        break;

    case 101:
        $correct_text = spellcheck_apply_corrections();
        include('proof_frame.inc');
        break;

    case 102:
        $correct_text = spellcheck_quit();
        $page->SaveText($text_data);
        include('proof_frame.inc');
        break;

    default:
        die( "unexpected tbutton value: '$tbutton'" );
}
exit;

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function leave_proofing_interface($projectid, $title, $content ) {
    slim_header( $title );
    $url = url_for_project($projectid);
    echo
    "<!DOCTYPE html>
    <html>
    <title>"._("Redirecting to project page.")."</title>
    <script typw='text/javascript'>
    setTimeout('top.location.href=\"$url\";', 1000);
    </script>
    <body>
    $content
    </body>
    </html";
    exit;
}

// vim: sw=4 ts=4 expandtab
