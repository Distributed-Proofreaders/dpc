<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../pinc/";
include_once $relPath . 'dpinit.php';
include "../wc/scannos.php";
include "../wc/guifix.php";

$projectid = ArgProjectid();

if($projectid == "") {
	$sql = "
			SELECT
				projectid,
				nameofwork AS title,
				authorsname AS author,
				username AS pm,
				n_pages AS pages
			FROM projects
			WHERE phase = 'PREP'
				AND n_pages > 0
			ORDER BY username";
	$tbl = new DpTable();
	$tbl->SetRows( $dpdb->SqlRows( $sql ) );
	$tbl->EchoTable();
	exit;
}

$project = new DpProject($projectid);
$dpath = $project->ProjectPath();

$pgs = $project->PageObjects();

$project->PageCount() > 0
	or die("No pages");

$text = $project->OCRText();
file_put_contents(build_path($dpath, "ocrtext.txt"), $text);

$ptn = array();
$rpl = array();
foreach($guifix as $ptn => $rpl) {
	say("|$ptn|$rpl");
//	list($ptn[], $rpl[]) = $g;
//	$t = preg_replace($ptn, $rpl, $text, -1, $n);
//	if($n > 0) {
//		dump("$n $ptn");
//	}
//	list($ptn[], $rpl[]) = $g;
}
exit;
$text2 = preg_replace($ptn, $rpl, $text, -1, $n);
file_put_contents(build_path($dpath, "difftext.txt"), $text2);
dump($n);
dump($dpath);
exit;

foreach($rows as $row) {
	$pages[] = new PrePage($project, $row);
}
$totlines = 0;
/** @var PrePage $pg */
foreach($pages as $pg) {
	$totlines += $pg->NumLines();
}

html_start();

say("Pages: " . count($pages));
say("Lines: $totlines");


say("The End");
html_end();
exit;

//function text_paragraphs($text) {
//	return RegexSplit( "\n\n", "uis", $text );
//}


class PrePage {
	/** @var  DpProject $this->_project */
	/** @var  DpVersion $this->_version */
	private $_row;
	private $_project;

	function __construct(&$project, $row) {
		$this->_row = $row;
	}
	public function Text() {
		return $this->_row['text'];
	}
	public function Lines() {
		return text_lines($this->Text());
	}

	public function NumLines() {
		return count($this->Lines());
	}

	public function Words() {
		/** @var DpProject $proj */
		$proj = $this->_project;
		return $proj->ActiveTextWords();
	}

	public function Dehyphenate() {
		$n = $this->NumLines();
		$lines = $this->Lines();
		for($i = 0; $i < $n-1; $i++) {
			$line = $lines[$i];
			if(RegexCount("\w-$", "ui", $line) > 0) {
				$w1 = LastRegexMatch("\w+-", "ui", $line);
				$w1 = ReplaceRegex("(.*)-$", "$1", "ui", $w1);
				if($w1) {
					$l2 = $lines[ $i + 1 ];
					$w2 = FirstRegexMatch( "\w+", "ui", $l2 );
					if ( $w2 ) {
						/** @var DpProject $proj */
						$proj      = $this->_project;
						$testword  = $w1 . $w2;
						$testword2 = "$w1-$2w";
						$n1        = RegexCount( "\b{$testword}\b", "ui", $proj->ActiveText() );
						$n2        = RegexCount( "\b{$testword2}\b", "ui", $proj->ActiveText() );
						if ( $n1 == 0 && $n2 > 0 ) {
							die("would not hyphenate $testword2");
						} else if ( $n1 > 0 && $n2 == 0 ) {
							die("would hyphenate $testword");
						} else {
							die("could not resolve $line");
						}
					}
				}
			}
		}
	}
}

function clean($caption, $ptn, $flags, $repl, &$text) {
//	$n = CountReplaceRegex($ptn, $repl, $flags, $text);
	assert($repl != "");
	$n = RegexCount($ptn, $flags, $text);
	say("$caption  $n");
}
