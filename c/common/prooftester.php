<?php
/**
 * Created by PhpStorm.
 * User: don
 * Date: 7/3/2015
 * Time: 6:09 PM
 */

require "scanno_regex.php";
require "DpDiff.class.php";
$relPath = "../pinc/";
require $relPath . "dpinit.php";
$projectid = ArgProjectId("projectID51870bf940cce");
$project = new DpProject($projectid);
$path = build_path($project->ProjectPath(), "diffs");
dump($path);
if(! is_dir($path)) {
	mkdir($path);
}
assert(is_dir($path));
$text1  = $project->RoundText("P2");
$text2  = $project->RoundText("P3");
$path2 = build_path($path, "P2.txt");
$path3 = build_path($path, "P3.txt");
$pathdiff = build_path($path, "diffout.txt");
dump($pathdiff);

file_put_contents($path2, $text1);
file_put_contents($path3, $text2);
$cmd = "diff $path2 $path3 > $pathdiff";
say($cmd);
exec($cmd);
assert(is_file($pathdiff));
dump(file_get_contents($pathdiff));

exit;



$words = $project->ActiveTextWords();

$k = array_keys($replacements);

$i = 0;
foreach($k as $w) {
	$i++;
	$n = preg_match_all("/\\b{$w}\\b/u", $text, $matches);
	if($n > 0) {
		dump(" $i count of $w - $n");
	}
}

dump("check count $i");

$i = 0;
foreach($other_regex as $key => $value) {
	$i++;
	$rx = "/{$key}/u";
	dump($rx);
	$n = preg_match_all("/($key)/u", $text, $matches);
	if($n > 0) {
		dump(" $i count of $key - $n");
	}
}

//$bw = array_intersect($words, $k);
//dump($bw);

//$words[] = ". hlack";
//$bw = array_intersect(array_keys($badbegins), $words);
//dump($bw);
