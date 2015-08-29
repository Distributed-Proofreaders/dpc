<?php
$relPath="../../pinc/";
require_once $relPath."dpinit.php";

/** @var $User DpThisUser */
$User->IsLoggedIn()
    or redirect_to_home();

$projectid          = ArgProjectId();
if(! $projectid)
    die("Need projectid");

$tatext             = Arg('tatext');
$redirect_action    = Arg("redirect_action");
// $fontsize           = Arg("fntSize");
// $fontface           = Arg("fntFace");
// $zoom               = Arg("zoom");
// $revert             = Arg("revert");
$savenext           = Arg("savenext")     || Arg("savenext_x");
$savequit           = Arg("savequit")     || Arg("savequit_x");
$returnpage         = Arg("returnpage")   || Arg("returnpage_x");
$quit               = Arg("quit")         || Arg("quit_x");
// $switchlayout       = Arg("switchlayout") || Arg("switchlayout_x");
// $wordcheck          = Arg("wordcheck")    || Arg("wordcheck_x");
// $acceptwords        = Arg("acceptwords");
// $badreason          = Arg("badreason");
// if($acceptwords != "") {
    // $acceptwords = preg_split("/\t/", $acceptwords);
// }
// $langcode           = ArgLangCode("eng");
$todo               = Arg("todo");

// need to do this before possibly swapping orientation
// $User->SetFontAndZoom($fontface, $fontsize, $zoom);

if($todo == "quit") {
    redirect_to_project($projectid);
    exit;
}

$pagename           = ArgPageName();
if(! $pagename)
    die("Need pagename");
$page       = new DpPage($projectid, $pagename);

if($todo == "badpage" && $page->MayBeMarkedBadByActiveUser()) {
    $page->MarkBad($badreason);
    redirect_to_project($projectid);
}

if($todo == "fixpage" && $page->UserMayManage()) {
    $page->ClearBad();
    redirect_to_project($projectid);
}

if($todo == "savenext") {
//     if($acceptwords != "") {
        // $page->AcceptWordsArray($langcode, $acceptwords);
    // }
    $page->saveAsDone($tatext);
    redirect_to_proof_next($projectid);
    exit;
}

else if($todo == "savequit") {
    // if($acceptwords != "") {
        // $page->AcceptWordsArray($langcode, $acceptwords);
    // }
    $page->saveAsDone($tatext);
    redirect_to_project($projectid);
    exit;
}

else if($todo == "returnpage") {
    // if(count($acceptwords) > 0) {
        // $page->AcceptWordsArray($acceptwords);
    // }
    $page->returnToRound();
    redirect_to_project($projectid);
    exit;
}

else {
    var_dump($_POST);
    die("No recognizable request in processtext.php");
}

// vim: sw=4 ts=4 expandtab
