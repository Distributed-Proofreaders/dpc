<?PHP
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
require $relPath . "dpinit.php";

$projectid = "p150825002";
/*
$pagename  = "006";
//$phase = "P1";
$page = new DpPage($projectid, $pagename);
$v1 = $page->Version(0);
$v2 = $page->Version(1);


dump($v1->VersionText());
dump($v2->VersionText());
dump($v1->VersionText() == $v2->VersionText());
dump(crc32($v1->VersionText()));
dump($v1->CRC32());
dump(crc32($v2->VersionText()));
dump($v2->CRC32());
exit;
*/

$project = new DpProject($projectid);
$pgnames = $project->PageNames();
foreach($pgnames as $pgname) {
	say("$pgname  ");
	$page = new DpPage($projectid, $pgname);
	$vsns = $page->Versions();
	/** @var DpVersion $vsn */
	foreach($vsns as $vsn) {
		$crc1 = $vsn->CRC32();
		$crc2 = crc32($vsn->VersionText());
		if($crc1 != $crc2) {
			dump($vsn->VersionNumber() . "  $crc1  $crc2");
			$vsn->ResetCRC();
		}
//		dump($vsn->Path());
//		dump($vsn->VersionText());
//		dump(file_get_contents($vsn->Path()));
	}
}

$text = $page->PhaseVersion("PREP")->VersionText();
$text2 = $page->PhaseVersion("P1")->VersionText();
dump(crc32($text));
dump(crc32($text2));

dump($text);
dump($text2);
exit;







$pgs = $dpdb->SqlRows("
	SELECT pagename, version FROM page_last_versions
	WHERE projectid = '$projectid'
	");

$str = "";
foreach($pgs as $pg) {
	$pagename = $pg['pagename'];
	$version  = $pg['version'];
	$str .= PageVersionText( $projectid, $pagename, $version );
}

dump("text length: " . mb_strlen($str));

$Context->ZipSendString($projectid . "_PP", $str);
exit;


/*
$sql = "SELECT projectid, pagename, version, crc32, textlen
		FROM page_versions";

$rows = $dpdb->SqlRows($sql);
foreach($rows as $row) {
	dump($row);
	$projectid = $row['projectid'];
	$pagename = $row['pagename'];
	$version  = $row['version'];
	$crc32    = $row['crc32'];
	$textlen  = $row['textlen'];
	$text     = PageVersionText($projectid, $pagename, $version);
	$tcrc32   = (string) crc32($text);
	$ttextlen = mb_strlen($text);

	$sql =            "UPDATE page_versions SET crc32 = ?, textlen = ?
						WHERE projectid = ? AND pagename = ? AND version = ?";
	$args = array(&$tcrc32, &$ttextlen, &$projectid, &$pagename, &$version);
	$n = $dpdb->SqlExecutePS($sql, $args);
	say("$projectid $pagename $version $crc32 $textlen $tcrc32  " . ($crc32 == $tcrc32 ? "same" : "not same") );
}
exit;
*/


//include "../c/pt.inc";


//$projectid = 'p150813001';
//$pagename = "001";

$project = new DpProject($projectid);


echo_page_table($project);
exit;
//$version = $project->AddPageVersionRow( "001", "P2", "PROOF", "A");
//$project->ClonePageVersionText( "001" );
//$project->AddPageVersionRows("P2", "PROOF", "A");
$names = $project->PageNames();
foreach($names as $pagename) {
	$ret = $project->CloneLastVersionText($pagename);
	dump($ret);
}
exit;

dump($project->IsAvailable());
dump($project->IsAvailableForActiveUser());
dump($project->next_available_page_for_user());
dump($project->next_retrievable_page_for_user());
exit;
//$pagename = "312";

//$page = new DpPage($projectid, $pagename);
//dump($page->Text());
//dump(text_lines($page->Text()));
//dump($project->ActiveText());
//dump($project->EnchantedWords("en"));
//exit;


//dump($page->FlagWordsArray("en"));
//dump(PageVersionPath($projectid, $pagename, 1));
//dump($project->ActivePageArray());



$project = new DpProject($projectid);
//dump($project->ActiveText());
//$ew = $project->EnchantedWords("en", $project->ActiveText(), $project->ProjectId());;
//dump($ew->WordsArray());
exit;

dump($projectid);
dump($project->CompletedCount());
dump($project->AvailableCount());
dump($project->CheckedOutCount());
$project->RecalcPageCounts();
dump($project->CompletedCount());
dump($project->AvailableCount());
dump($project->CheckedOutCount());
exit;
//$project->SetQueueHold("P1");
//$project->SetQCHold("P1");
/*
$projectid = "projectID51870bf940cce";
$project = new DpProject($projectid);
dump(count($project->BadWordCountNotZero("en")));

include "wc/scannos.php";
$s = array_keys($scannos);
//dump(array_slice($s,0,5));
ksort($s);
// array(array(word, count))
//$wds = $project->FlagWordsByCountAlpha("en");
//dump(array_slice($wds, 0, 10));

// word => count
//$a = $project->RoundText("OCR");
// which keys of scannos are in keys of counts
//dump($a);

$pagetitle = "massive word check";

echo "<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<title>$pagetitle</title>
</head>

<body onload='init()'>
<div class='container left'>\n";

$pids = $dpdb->SqlValues("
	SELECT p.projectid FROM projects  p
	JOIN phases ON p.phase = phases.phase
	WHERE p.phase IN ('P2', 'P3', 'F1', 'F2')
		AND p.language = 'en'
	ORDER BY phases.sequence");
say("Count " . count($pids) . " projects");
$i = 0;


foreach($pids as $pid) {
	$i++;
	$proj = new DpProject($pid);
	$phase = $proj->Phase();
	$title = $proj->NameOfWork();
	$words = text_to_words($proj->ActiveText());
	ksort($words);
//	dump(array_slice($words,0,5));
//	dump(count(array_intersect($words, array("the"=>"the", "THE" => "THE"))));
	$a =  array_unique(array_intersect($words, $s));
//	dump(count($a));
//	die();
//	dump(memory_get_peak_usage());
//	$a =  array_intersect($words, $s);
//	dump(memory_get_peak_usage());
	if(count($a) > 0) {
		dump("$i $pid $phase $title - " . count($a) . " of " . count($words));
		dump($a);
	}
//	$n1 = RegexCount('\\08D', "uis", $proj->RoundText("OCR"));
//	$n2 = RegexCount('\\08D', "uis", $proj->RoundText("P1"));
//	$n3 = RegexCount('\\08D', "uis", $proj->RoundText("P2"));
//	$n4 = RegexCount('\\08D', "uis", $proj->RoundText("P3"));

//	if($n1 > 0 || $n2 > 0 || $n3 > 0 ) {
//		say("$i $pid $n1 $n2 $n3");
//	}
//	if($i >= 50) {
//		break;
//	}
}




say("</div></body></html>");

*/


