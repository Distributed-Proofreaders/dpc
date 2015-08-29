<?php
$relPath = "./pinc/";
require_once "pinc/dpinit.php";

$projectid = ArgProjectid();
$project = new DpProject($projectid);
$text = h($project->PrePostText());

echo
"<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<title>$projectid Post Text</title>
</head>
<body>\n";

echo "<pre>$text</pre>";

echo "</body></html>";
