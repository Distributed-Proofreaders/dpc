<?php
$relPath="../pinc/";
require_once $relPath."dpinit.php";

if(! $User->IsLoggedIn()) {
    redirect_to_home();
    exit;
}

$projectid          = ArgProjectId();
if(! $projectid)
    die("Need projectid");
$pagename           = ArgPageName();
if(! $pagename)
    die("Need pagename");

$tatext             = Arg('tatext');
$redirect_action    = Arg("redirect_action");
$savenext           = Arg("savenext");
$savequit           = Arg("savequit");
$returnpage         = Arg("returnpage");
$quit               = Arg("quit");
$seltodo            = Arg("seltodo");
$acceptwords        = ArgArray("acceptwords");

$page       = new DpPage($projectid, $pagename);

if($acceptwords != "") {
    $page->AcceptWordsArray($langcode, $acceptwords);
}
if($seltodo == "opt_draft_quit") {
    $page->saveText($tatext);
    redirect_to_project($projectid);
    exit;
}

if($seltodo == "opt_draft_continue") {
    $page->saveText($tatext);
    redirect_to_proof_page($projectid, $pagename);
    exit;
}

if($seltodo == "opt_mark_bad" && $page->MayBeMarkedBadByActiveUser()) {
    $page->MarkBad($badreason);
    redirect_to_project($projectid);
    exit;
}

if($seltodo == "opt_submit_continue") {
    $page->saveAsDone($tatext);
    $project = new DpProject($projectid);
    if($project->UncompletedCount() == 0) {
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

else if($seltodo == "opt_submit_quit") {
    $page->saveAsDone($tatext);
    redirect_to_project($projectid);
    exit;
}

else if($seltodo == "opt_return_quit") {
    $page->returnToRound();
    redirect_to_project($projectid);
    exit;
}

else {
    // send it back where it came from
    redirect_to_project($projectid);
    exit;
}

// vim: sw=4 ts=4 expandtab
