<?php
/*
 *      pagename=all
 *      include=separator
 *      phase=OCR
 */

$relPath = "./pinc/";
require_once "pinc/dpinit.php";

$projectid = ArgProjectid();
$project = new DpProject($projectid);
$path = $project->SmoothTextFilePath();
if($path == "")
    exit;

send_file($path);
