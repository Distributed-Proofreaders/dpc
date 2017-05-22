<?php
/**
 * Created by PhpStorm.
 * User: don
 * Date: 5/13/2016
 * Time: 9:36 AM
 */
$relPath = "../pinc/";
include_once $relPath.'dpinit.php';
include_once $relPath.'dpctls.php';

$User->IsLoggedIn()
or RedirectToLogin();

$projectid          = ArgProjectid();
$projectid != ""
or die("missing or invalid project id");
$project            = new DpProject($projectid);

$project->Exists()
or die("Project $projectid doesn't exist.");
$project->UserMayManage()
or redirect_to_project($projectid);

$regexes = file_get_contents(__DIR__ . "/regex/dpc.regex");
$text = $project->ActiveText();
$aregexes = text_lines($regexes);
foreach($aregexes as $r) {
    if($r == "") {
        continue;
    }
    $a = preg_split("/\s*=>\s*/u", $r);
    $find = preg_replace("/'/", "", $a[0]);
    $flags = preg_replace("/['g]/", "", $a[3]) . "u";
    $n = RegexCount($find, $flags, $text);
    if($n > 0) {
        dump($find . " found " . $n);
    }
}
