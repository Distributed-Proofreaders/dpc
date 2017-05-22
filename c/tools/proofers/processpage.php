<?php
$relPath="../../pinc/";
require_once $relPath."dpinit.php";

$User->IsLoggedIn()
    or redirect_to_home();

$projectid          = ArgProjectId();
if(! $projectid)
    die("Need projectid");

$tatext             = Arg('tatext');
$redirect_action    = Arg("redirect_action");
$savenext           = Arg("savenext")     || Arg("savenext_x");
$savequit           = Arg("savequit")     || Arg("savequit_x");
$returnpage         = Arg("returnpage")   || Arg("returnpage_x");
$quit               = Arg("quit")         || Arg("quit_x");
$todo               = Arg("todo");

$pagename   = ArgPageName();
if(! $pagename)
    die("Need pagename");
$page       = new DpPage($projectid, $pagename);

if($todo == "quit") {
    $page->saveOpenText($tatext);
    redirect_to_project($projectid);
    exit;
}

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
    $project = new DpProject($projectid);
    if($project->IsRoundCompleted() != 0) {
        redirect_to_project($projectid, "Round Complete");
        exit;
    }
    if($project->IsAvailableForActiveUser()) {
        redirect_to_proof_next($projectid);
        exit;
    }
    else {
        redirect_no_page_available($projectid);
        exit;
    }
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
    // send it back where it came from
    redirect_to_project($projectid);
//    divert(FromUrl() . "?projectid=$projectid&pagename=$pagename");
}

// vim: sw=4 ts=4 expandtab
