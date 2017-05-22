<?php
/**
 * Created by PhpStorm.
 * User: don
 * Date: 2/6/2015
 * Time: 12:03 AM
 */

/*
 *   1. text with embedded page #s
 */

$relPath = "./pinc/";
require $relPath . "dpinit.php";

// default is Arundel
$projectid = Arg("projectid", "projectID51870bf940cce");

if($projectid === "") {
	die("No project id");
}

$project = new DpProject($projectid);

if( ! $project->Exists()) {
	die("Invalid project id");
}

$text = "";

$path = build_path($project->ProjectPath(), "arundel.txt");
file_put_contents($path, $text);

$targ = array();
$rpl  = array();


$ptn[] = "/\/\*\s*\n/uis";
$rpl[] = "[literal]\n";
$ptn[] = "/\*\/\s*\n/uis";
$rpl[] = "[/literal]\n";

$text = preg_replace($ptn, $rpl, $text);


$path = build_path($project->ProjectPath(), "arundel1.txt");
file_put_contents($path, $text);
