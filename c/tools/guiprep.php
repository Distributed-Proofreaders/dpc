<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../pinc/";
include_once $relPath . 'dpinit.php';

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
$pgs = $project->PageObjects();

foreach($pgs as $pg) {
	dump($pg);
}

$project->PageCount() > 0
	or die("No pages");

//$project->Phase() == "PREP"
//	or die("Project not in PREP phase.");


$text = $project->OCRText();

$rows = $project->PageOCRRows();

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

if(false) {
	clean( "spacey-quote", '\s"\s', "u", ' " ', $text );
	clean( "Replace tabs", "\t", "uis", " ", $text );
	clean( "Squeeze multiple spaces", "  +", "uis", " ", $text );
	clean( "End-of-line spaces", "\s+$", "ui", "\r", $text );
	clean( "Remove spaces around hyphens", "( +-|- +)", "uis", "-", $text );
	clean( "Multiple underlines to hyphens", "__+", "ui", "--", $text );
	clean( "Space before punctuation", " ([\.,!?;])", "ui", "$1", $text );
	clean( "Space inside left brackets etc.", "([\(\[{]) ", "ui", "$1", $text );
	clean( "Space inside right brackets etc.", " ([\)\]}])", "ui", "$1", $text );
	clean( "Multiple blank lines", "\n\n\n+", "ui", "\r\r", $text );

	clean( "tli or tii at start of word to th", "\b(tli|tii)(\w+)", "ui", "th$1", $text );
	//clean("\\v or \\\\",                               "(\\)(\\)|(\\)v)",   "ui",  "w",    $text);
	//clean("'j' at end of word",                     "(\w+)j\b",      "ui",  "$1j",    $text);
	clean( "slash (/) at end of word to comma-apostrophe", "(\w+)/\b", "ui", "$1,'", $text );
	clean( "wli at start of word to th", "\bwli(\w+)", "ui", "wh$1", $text );
	clean( "rn at start of word to m", "\brn(\w+)", "ui", "m$1", $text );
	clean( "hl at start of word to bl", "\bhl(\w+)", "ui", "bl$1", $text );
	clean( "hr at start of word to br", "\bhr(\w+)", "ui", "br$1", $text );
	clean( "rnp in word to mp", "(\w)rnp(\w)", "ui", "$1mp$2", $text );
	clean( "vv or v/ at start of word to w", "\b(v/|vv)(\w+)", "ui", "w$2", $text );
	clean( "!! at start of word to H", "\b!!(\w+)", "ui", "H$1", $text );
	clean( "! within word to ell", "(\w+)!(\w+)", "ui", "$1l$2", $text );
	clean( "'-eleven to 'll", "'11", "ui", "'ll", $text );
	clean( "'rnm in word not after 'e' to mm", "([^e])rnm(\w+)", "ui", "$1mm$2", $text );
	clean( "cb in word to ch", "(\w)cb(\w)", "ui", "$1ch$2", $text );
	clean( "gbt in word to ght", "(\w)gbt(\w)", "ui", "$1ght$2", $text );
	clean( "[ai]hle to [ai]ble ", "([ai])hle", "ui", "$1ble", $text );
	clean( "pbt in word to pht", "(\w)pbt(\w)", "ui", "$1pht$2", $text );
	clean( "'to he' to 'to be'", "\bto he\b", "ui", "to be", $text );
	clean( "solitary ell to one", "\bl\b", "u", "1", $text );
	clean( "solitary zero to oh", "\b0\b", "ui", "O", $text );

	clean( "iooo standalone to 1000", "\biooo\b", "u", "1000", $text );
	clean( "(\d)ooo standalone to d000", "\b(\d)ooo\b", "u", "$1000", $text );
	clean( "iooi standalone to 1001", "\biooi\b", "u", "100i", $text );
	clean( "ioi standalone to 101", "\bioi\b", "u", "101", $text );
	clean( "ioo standalone to 100", "\bioo\b", "u", "100", $text );
	clean( "(\d)oo standalone to d00", "\b(\d)oo\b", "u", "$100", $text );
	clean( "io standalone to 10", "\bio\b", "u", "10", $text );
	clean( "(\d)o standalone to d0", "\b(\d)o\b", "u", "$10", $text );
	clean( "standalone ist to 1st", "\bist\b", "u", "1st", $text );
	clean( "digit ist to n1st", "\b(\d)ist\b", "u", "$11st", $text );
	clean( "standalone nth to 11th", "\bnth\b", "u", "11th", $text );
	clean( "standalone ioth to 10th", "\bioth\b", "u", "10th", $text );
	clean( "n\d to 11d", "n(\d)", "u", "11$1", $text );

	clean( "left-paren-space-quote", '\( "', "u", '("', $text );
	clean( "quote-space-right-paren", ' "\)', "u", ')"', $text );
	clean( "quote-space-right-paren", ' "\)', "u", ')"', $text );

	inspect_degrees();
	inspect_one_in_alphas();
	inspect_zero_in_alphas();
	inspect_ell_in_digits();
	inspect_oh_in_digits();
}

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
	private $_version;
	private $_text;

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

function inspect_degrees() {}
function inspect_one_in_alphas() {}
function inspect_zero_in_alphas() {}
function inspect_ell_in_digits() {}
function inspect_oh_in_digits() {}
