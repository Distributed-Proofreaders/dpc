<?PHP
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'stages.inc');

$projectid      = Arg("projectid");
$pagename       = Arg("pagename");
if($pagename == "") {
    $imagefile      = Arg("imagefile");
    $pagename       = imagefile_to_pagename($imagefile);
}


$project = new DpProject($projectid);

$roundid = $project->RoundId();

// specific page requested
if($pagename != "") {
    $page = new DpPage($projectid, $pagename);
    if($page->UserIsOwner()) {
        $page->ResumePage();
    }
}
else {      // get next available

    $page = $project->NextAvailablePage();
    if(! $page || ! $page->Exists()) {
        $project->MaybeAdvanceRound();
        $projecturl = url_for_project($projectid);
        echo "
<html>
<header>
    <title>'No pages available'</title>
    <script type='text/javascript'>
    window.top.location='$projecturl';
    </script>
</header>
<body>
</body>
</html>";
        exit;
    }
    $page->CheckOutPage();
    $pagename = $page->PageName();
}

if($User->IsEnhancedLayout()) { 
    include('proof_frame_enh.inc');
}
else {
    include('proof_frame_std.inc');
}

// vim: sw=4 ts=4 expandtab
?>
