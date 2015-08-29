<?PHP
$relPath="./../../pinc/";
include_once $relPath.'dpinit.php';
include_once $relPath.'Project.inc';
//include_once $relPath.'slim_header.inc';

$User->IsLoggedIn()
	or RedirectToLogin();

// (User clicked on "Start Proofreading" link or
// one of the links in "Done" or "Save Draft" trays.)

$projectid      = Arg("projectid")
    or die( "No project requested in proof.php." );

$pagename       = Arg("pagename");
//$ui             = Arg("ui");


$project = new DpProject($projectid);
$roundid = $project->RoundId();

if(! $User->MayWorkInRound($roundid))
    die("Not authorized for Round $roundid.");

divert("../../pennask/proofpage.php?projectid={$projectid}&pagename={$pagename}");
/*
//if($User->Username() == "dkretz") {
//	divert("../../pennask2/proofpage.php?projectid={$projectid}&pagename={$pagename}");
//	exit;
//}
//switch(lower($ui)) {
//	case "ahmic":
//		divert("../../ahmic/proofpage.php?projectid={$projectid}&pagename={$pagename}");
//		divert("../../pennask/proofpage.php?projectid={$projectid}&pagename={$pagename}&editor=ahmic");
//		exit;
//	case "pennask":
//		divert("../../pennask/proofpage.php?projectid={$projectid}&pagename={$pagename}");
//		exit;
//	case "whistler":
//		divert("../../tools/proofers/proofpage.php?projectid={$projectid}&pagename={$pagename}");
//		exit;
//	case "blackcomb":
//		divert("../../blackcomb/proofpage.php?projectid={$projectid}&pagename={$pagename}");
//		exit;
//
//	default:
//		break;
//}

//if($User->IsAhmicLayout()) {
//    divert("../../ahmic/proofpage.php?projectid={$projectid}&pagename={$pagename}");
//	divert("../../pennask/proofpage.php?projectid={$projectid}&pagename={$pagename}&editor=ahmic");
//    exit;
//}

//if( $User->IsPennaskLayout()) {
//    divert("../../pennask/proofpage.php?projectid={$projectid}&pagename={$pagename}");
//    exit;
}

//if($User->IsWhistlerLayout()) {
//    divert("./proofpage.php?projectid={$projectid}&pagename={$pagename}");
//    exit;
//}
//if($User->IsBlackcombLayout()) {
//    divert("../../blackcomb/proofpage.php?projectid={$projectid}&pagename={$pagename}");
//    exit;
//}
//if($pagename == "") {
//    $imagefile      = Arg("imagefile");
//    $pagename       = imagefile_to_pagename($imagefile);
//}
//
// Add name of round before nameofwork
//$namehdr = "[" . $roundid . "] " . $project->NameOfWork();
//$url = $code_url."/tools/proofers/proof_frame.php"
//                . "?projectid=$projectid";
//if($pagename != "") {
//    $pg = new DpPage($projectid, $pagename);
//    if(! $pg->Exists()) {
//        die("invalid page requested - $projectid, $pagename");
//    }
//    if(! $pg->MayBeSelectedByActiveUser() ) {
//        die("invalid page requested - $projectid, $pagename - not owned by you");
//    }
//    $url .= "&amp;pagename=$pagename";
//}

//load the master frameset

//echo "
//<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Frameset//EN' 'http://www.w3.org/TR/html4/frameset.dtd'>\n";
//
//slim_header($namehdr." - "._("Proofreading Interface"), false, false);
//
//echo "
//<script language='JavaScript' type='text/javascript' src='dp_scroll.js?1.14'></script>
//<script language='JavaScript' type='text/javascript' src='dp_proof.js?1.49'></script>
//<script type='/text/javascript'>
//	function divert(url) {
//		window.location = url;
//	}
//</script>
//</head>
//<frameset rows='*,90'>
//<frame name='proofframe' src='$url'>
//<frame name='menuframe' src='ctrl_frame.php?round_id={$roundid}'>
//</frameset>
//<noframes>
//    Your browser currently does not display frames.
//</noframes>
//</html>";
*/
