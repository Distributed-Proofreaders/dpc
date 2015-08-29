<?php
/**
 * Created by PhpStorm.
 * User: don
 * Date: 2/17/2015
 * Time: 11:46 AM
 */
ini_set("display_errors", true);
error_reporting(E_ALL);
// Give information on smooth reading
// including (most importantly) the list of projects currently available

$relPath = '../../pinc/';
include_once($relPath.'dpinit.php');

// look for files in /ppfiles
$filename = Arg("filename");
if($filename) {
	$path = build_path(DPC_PATH, "ppfiles");
	$path = build_path($path, $filename);
	if(! file_exists($path)) {
		die( "No file: $path" );
	}
	$text = file_get_contents($path);
}
if(! $text) {
	$projectid = Arg( "projectid" );
	if ( ! $projectid ) {
		die( "No source designated" );
	}
	$project = new DpProject( $projectid );
	if ( ! $project->Exists() ) {
		die( "Project $projectid does not exist." );
	}
	$text = $project->ActiveText();
}

//$text = $project->ActiveText();

$todo = Arg("todo");

switch($todo) {
	case "checktags":
		checktags( $text );
		break;
	case "checkfootnotes":
		checkfootnotes($text);
		break;
	case "checksidenotes":
		checksidenotes($text);
		break;
	default:
		dump("No todo.");
		break;
}
exit;


// anchors should appear before checkfootnotes
// anchors and proofnotes should appear in order
// footnotes should cluster
//

function checkfootnotes($text) {
	$footnotes = array();
	$anchors = array();

	$lines = text_lines($text);
	$linenum = 0;
	$akey = $fkey = 0;
	foreach($lines as $line) {
		if(isfootnote($line)) {
//			dump($line);
			$ary = RegexMatches("\[Footnote (\w+):\s*(.*)$", "iu", $line );
//			dump($ary);
			$key = $ary[1][0];
			$fn  = $ary[2][0];
			assert($key = $fkey + 1);
			$fkey = $key;
//			dump("fn " . $key);
			$footnotes[$key] = json_encode(array($key, $linenum, $fn));
		}
		if(isanchor($line)) {
			$keys = RegexMatches("\[(\d{1,4})\]", "uis", $line);
//			dump($keys);
			foreach($keys[1] as $key) {
//				dump($key);
//				$k = $key[1];
//				dump("key " . $key);
				assert($key = $akey + 1);
				$akey = $key;
				$anchors[$key] = json_encode( array( $key, $linenum ) );
			}
		}
		$linenum++;
	}

	dump("n anchors: " . count($anchors));
	dump("n footnotes: " . count($footnotes));

	assert(count($anchors) == count($footnotes));

	foreach($anchors as $anchor) {
//		dump($anchor);
		$a = json_decode($anchor);
		$key = $a[0];
		$kline = $a[1];
		$footnote = $footnotes[$key];
		$f = json_decode($footnote);
		$fline = $f[1];
		assert($fline >= $kline);
	}
//	dump($footnotes);
//	dump($anchors);
}

function checksidenotes($text) {
	$sidenotes = array();
	$lines = text_lines($text);
	$linenum = 0;
	foreach($lines as $line) {
		if ( issidenote( $line ) ) {
			$sn = RegexMatch("^\[Sidenote:\s*(.*?)\]\n", "uis", $line);
			$sidenotes[] = json_encode(array($sn, $linenum));
		}
		$linenum++;
	}
}

function checktags($text) {
	$lines = text_lines($text);

//	$_tags       = array();
	$_footnotes  = array();
//	$_footnote  = "";
//	$_anchors    = array();
	$_linenum   = -1;
//	$_sidenotes  = array();
//	$_sidenote  = "";
	$_notecount = 0;
	$_indent     = 0;

	echo "<pre>\n";

	foreach($lines as $line) {
		if($_linenum > 500) {
			break;
		}
		$line = trim($line);
		if($line == "") {
			HandleBlankLine();
			continue;
		}
		// handle block delimiters - always exactly 2 characters long
		if($line == "/*") {
			HandleStartNoWrap();
			$_indent += 4;
			continue;
		}
		if($line == "*/") {
			HandleEndNoWrap();
			$_indent -= 4;
			continue;
		}
		if($line == "/#" || $line == "/Q") {
			HandleStartBlockQuote();
			$_indent += 4;
			continue;
		}
		if($line == "#/" || left($line, 2) == "Q/") {
			HandleEndBlockQuote();
			$_indent -= 4;
			continue;
		}
		if($line == "/P") {
			HandleStartPoetry();
			$_indent += 4;
			continue;
		}
		if($line == "P/") {
			HandleEndPoetry();
			$_indent -= 4;
			continue;
		}
		if($line == "/U") {
			HandleStartList();
			$_indent += 4;
			continue;
		}
		if($line == "U/") {
			HandleEndList();
			$_indent -= 4;
			continue;
		}
		if($line == "/C") {
			HandleStartCenter();
			$_indent += 4;
			continue;
		}
		if($line == "C/") {
			HandleEndCenter( $line );
			$_indent -= 4;
			continue;
		}
		if($line == "/R") {
			HandleStartRight( $line );
			$_indent += 4;
			continue;
		}
		if($line == "R/") {
			HandleEndRight( $line );
			$_indent -= 4;
			continue;
		}
		if($line == "/X") {
			HandleStartPre( );
			$_indent += 4;
			continue;
		}
		if($line == "X/") {
			HandleEndPre();
			$_indent -= 4;
			continue;
		}
		if($line == "/T") {
			HandleStartTable( );
			$_indent += 4;
			continue;
		}
		if($line == "T/") {
			HandleEndTable( );
			$_indent -= 4;
			continue;
		}
		if($line == "/F") {
			HandleStartFootSection();
			$_indent += 4;
			continue;
		}
		if($line == "F/") {
			HandleEndFootSection();
			$_indent -= 4;
			continue;
		}
		// end of two-character block delimiter lines

		if(isfootnote($line)) {
			HandleStartFootnote( $line );
			dump($_footnotes);
			continue;
		}
		// both footnotes and sidenotes
		if(right($line, 1) == "]") {
			if($_notecount > 0) {
				HandleEndNote( $line );
				continue;
			}
		}
		HandleOther($line);
	}
	echo "</pre>\n";
}


//function FilterForAnchors($line) {
//	$ary = RegexMatches("\[(\d{1,4})\]", "uis", $line);
//	return $ary;
//}


function issidenote($line) {
	return RegexMatch("\[Sidenote:", "ius", $line) != null;
}

function isfootnote($line) {
	return RegexMatch("\[Footnote \w+:", "ius", $line) != null;
}

function isanchor($line) {
	$ary = RegexMatches("\[(\d{1,4})\]", "uis", $line);
	return (count($ary) > 0);
}


function pushtag($tag) {
	global $_tags;
//	echo "\n$tag >> ";
	$_tags[] = $tag;
}

function poptag($tag = "") {
	global $_tags;
//	echo "\n$tag << ";
	if(count($_tags) <= 0) {
		echo "\nbut the stack is empty.";
		assert( false );
		return null;
	}
	$ptag = array_pop($_tags);
	if($tag != "") {
		if ( $tag != $ptag ) {
			dump( "requested tag '$tag' mismatch - popped '$ptag'" );
		}
	}
	return $tag;
}

function HandleBlankLine() {
//	emit("");
}

function HandleStartNoWrap() {
	pushtag("nowrap");
}
function HandleStartBlockQuote() {
	pushtag("quote");
}
function HandleStartPoetry() {
	pushtag("poetry");
}
function HandleStartList() {
	pushtag("list");
}
function HandleStartRight() {
	pushtag("right");
}
function HandleStartCenter() {
	pushtag("center");
}
function HandleStartPre() {
	pushtag("pre");
}
function HandleStartTable() {
	pushtag("table");
}
function HandleStartFootSection() {
	pushtag("footsec");
}
function HandleStartFootnote($text) {
	global $_notecount;
	$ary = RegexMatch("\[Footnote (\w+):", "ius", $text, 1);
	$notenum = $ary[0];
	pushtag("foot $notenum");
	$_notecount++;
}
function HandleStartSidenote($text) {
	global $_notecount;
	$note = RegexMatch("\[Sidenote:\s*(.*)\]\n", "usi", $text, 1);
	pushtag("side " . linenum() . " " .$note);
	$_notecount++;
}
function HandleEndNoWrap() {
	poptag();
}
function HandleEndBlockQuote() {
	poptag();
}

function HandleEndPoetry() {
	poptag();
}
function HandleEndList() {
	poptag();
}
function HandleEndRight() {
	poptag();
}
function HandleEndCenter() {
	poptag();
}
function HandleEndPre() {
	poptag("pre");
}
function HandleEndTable() {
	poptag("table");
}
function HandleEndFootSection() {
	poptag("footsection");
}
function HandleEndNote($text) {
	global $_footnote, $_sidenote;
	global $_notecount;

	$tag = poptag();
	dump($tag);
	if(left($tag, 4) != "foot" && left($tag, 4) != "side") {
		echo "\nEnd note but tag is '$tag'";
	}
	$text = trim(left($text, mb_strlen($text) - 1));
	if(left($tag, 4) == "foot") {
		$_footnote .= ("\n" . $text);
		$_footnotes[] = $_footnote;
		$_footnote = "";
	}
	else if(left($tag, 4) == "side") {
		$_sidenote .= ("\n" . $text);
		$_sidenotes[] = $_sidenote;
		$_sidenote = "";
	}
	$_notecount--;
}

function HandleOther($text) {
	global $_footnote, $_sidenote;
	$t = toptag();
	if($t && ($t == "foot")) {
		$_footnote .= ( "\n" . $text );
	}
	else if(toptag() == "side") {
		$_sidenote .= ( "\n" . $text );
	}
	else {
//		emit($text);
	}
}

function tagcount() {
	global $_tags;

	return count( $_tags );
}
function istags() {
	return (tagcount() > 0);
}
function toptag() {
	global $_tags;

	if(! istags()) {
		return null;
	}
	$i = count($_tags) - 1;
	if(! isset($_tags[$i])){
		dump( "No index: $i" );

		return null;
	}
	return $_tags[count($_tags) - 1];
}

function linenum() {
	global $_linenum;
	return $_linenum;
}

function emit($str) {
	global $_indent, $_linenum;
	echo "\n"
		. str_pad(++$_linenum, 3,  " ", STR_PAD_LEFT)
	    . "  "
		. str_repeat(" ", $_indent)
		. $str;
}
