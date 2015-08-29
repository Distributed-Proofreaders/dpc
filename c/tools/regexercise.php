<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

$title = "Regex Testing";

echo "<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<title>$title</title>
</head>
<body>\n";

$regex = array();
$m     = array();

$regex[] = '/\btne/u'; // "tne word")
$regex[] = "/\stne\w/iu"; // "tne prefix")
$regex[] = "/\s\-\n\w/iu"; // "EOL hanging dash")
$regex[] = "/(\S\- | \-\S)/u"; // "hanging dash")
$n = count($regex);

$relPath = "../../c/pinc/";
require $relPath . "dpinit.php";

$pids = $dpdb->SqlValues("SELECT projectid FROM projects
						WHERE phase IN ('P1', 'P2', 'P3','F1', 'F2')");

echo "<pre>\n";
//$tots = array();
//for($i = 0; $i < $n; $i++) {
//	$tots[$i] = 0;
//}
$t = array();
foreach($pids as $pid) {
	$project = new DpProject($pid) ;
	assert($project->Exists());
	$text = $project->OCRText();
	// echo "\n$pid  {$project->NameOfWork()}\n";
	for($i = 0; $i < $n; $i++) {
		$m            = preg_match_all( $regex[ $i ], $text, $matches, PREG_SET_ORDER );
		$tots[ $pid ] = array( "pid" => $pid, "descr" => $project->NameOfWork(), $regex[ $i ], $m );
	}
}

foreach($pids as $pid) {
	for ( $i = 0; $i < $n; $i ++ ) {
		$t[ $i ] += $tots[ $pid ][ $i ];
	}
}

$nproj = count($pids);

uasort($tots, "t_sort");

uasort($tots, "t_sortr");

$i =  0;
foreach($tots as $t) {
	echo "{$t['pid']} {$t['descr']} {$t[$regex1]} {$t[$regex1]} {$t[$regex3]} {$t[$regex4]}\n";
	if(++$i > 30) {
		break;
	}
}
echo "
</pre>
</body>
</html>";
exit;

function t_sort($a, $b) {
	return $a["tdbl"] - $b["tdbl"];
}
function t_sortr($a, $b) {
	return $b["tdbl"] - $a["tdbl"];
}







