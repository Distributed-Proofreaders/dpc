<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../c/pinc/";

require $relPath . "dpinit.php";
require $relPath . "Diff.class.php";

$rounds = array("P3", "F2");
//$regex1 = "/<sc>[^<]*<\/sc>/ui";
$regex2 = "/((\n|\A)[^\n]*)<sc>[^<]{2,4}<\/sc>([^\n]*)/ui";

$ids = $dpdb->SqlValues("SELECT projectid FROM projects WHERE phase = 'PP'");
$n = 0;
foreach($ids as $id) {
	$proj = new DpProject($id);
	$text = $proj->RoundText("F2");
//	dump($id);
//	preg_match_all($regex1, $text, $matches1);
	preg_match_all($regex2, $text, $matches2);
//	dump(count($matches));
//	dump(++$n . "  $id  " . count($matches1[0]) . "  " . count($matches2[0]));
//	if(count($matches1[0]) > 1000) {
	if(count($matches2[0]) > 10) {
//		for($i = 0; $i < 100; $i++ ) {
		foreach($matches2[0] as $m) {
			dump(h($m));
		}
	}
}

