<?PHP
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'stages.inc');
include_once($relPath.'LPage.inc');
include_once($relPath.'DpPage.class.php');
include_once($relPath.'DpProject.class.php');
include_once('PPage.inc');

$projectid      = Arg("projectid");
// $proj_state     = Arg("proj_state");
$imagefile      = Arg("imagefile");

// capture specified page identification
$pagename       = Arg("pagename");
if($pagename == "") {
    $pagename = imagefile_to_pagename($imagefile);
}

if($pagename) {
    $page           = new DpPage($projectid, $pagename);
}
else {
    $project = new DpProject($projectid);
    $page    = $project->NextAvailablePage();
}

if(! $page ) {
    $body = "$err<br>\n" . sprintf(_("Return to the %sproject listing page%s."),
                "<a href='round.php?round_id={$round->id}' target='_top'>","</a>\n");
    $title = _("Unable to get an available page");
    echo "
        <html>
            <head>
                <title>$title</title>
            </head>
            <body>$body</body>
        </html>";
    exit;
}

include $User->IsEnhancedLayout() 
    ? 'proof_frame_enh.inc'
    : 'proof_frame_std.inc';

// vim: sw=4 ts=4 expandtab
?>
