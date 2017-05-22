<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../../c/pinc/";
require $relPath . "dpinit.php";

$title = "Regex Testing";

echo "<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<title>$title</title>
</head>
<body>\n";


require $relPath . "../wc/scannos.php";
require $relPath . "../wc/guifix.php";

$pids = $dpdb->SqlValues("SELECT projectid FROM projects
						WHERE phase = 'PP'");

//$tots = array();
//for($i = 0; $i < $n; $i++) {
//	$tots[$i] = 0;
//}
$t = array();
//$i = 0;
foreach($pids as $pid) {
//	if($i++ > 10)
//		exit;
	dump($pid);
	$project = new DpProject($pid) ;
	assert($project->Exists());
	$text = $project->ActiveText();
//	echo "<pre>" . $text . "</pre>";
//	exit;
	$rx = '\n\n+';
	$fl = "ui";
	$ary = RegexSplit($rx, $fl, $text);
//	$n = RegexCount($rx, $fl, $text);
	$n = count($ary);
	say("paragraphs $n");

	$i = 0;
	foreach($ary as $para) {
		$i++;
		$n = RegexCount('"', "u", $para);
		say("$i  $n");
	}

//	$i = 0;
//	foreach($guifix as $key => $value) {
//		$i++;
//		$rx = "$key";
//		$n = RegexCount($rx, "u", $text);
//		if($n > 0) {
//			say("$i |$rx|===|$value|  $n");
//			exit;
//		}
//	}
	echo "<pre>$text</pre>";
	exit;
	// echo "\n$pid  {$project->NameOfWork()}\n";
//	for($i = 0; $i < $n; $i++) {
//		$m            = preg_match_all( $regex[ $i ], $text, $matches, PREG_SET_ORDER );
//		$tots[ $pid ] = array( "pid" => $pid, "descr" => $project->NameOfWork(), $regex[ $i ], $m );
//	}
}





