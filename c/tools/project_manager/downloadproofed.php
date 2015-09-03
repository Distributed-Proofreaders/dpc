<?PHP
$relPath="./../../pinc/";
require_once $relPath . 'dpinit.php';
require_once $relPath . "DpPage.class.php";

$projectid    = Arg("projectid");
$pagename     = Arg("pagename");
if($pagename == "") {
	$imagefile = Arg("imagefile");
	$pagename  = imagefile_to_pagename($imagefile);
}
$roundid      = Arg("roundid");
if($roundid == "") {
    $round_num      = Arg("round_num");
    $roundid        = RoundIdForIndex($round_num);
}

$page = new DpPage($projectid, $pagename);
$text = $page->RoundText($roundid);

echo "<!doctype html>
<head>
<meta charset='utf-8'>
<title>$projectid Page $pagename</title>
</head>
<body>
<pre>
$text
</pre>
</body>
</html>";
