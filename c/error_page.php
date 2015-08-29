<?php
/*

*/

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath = "./pinc/";
require_once $relPath."dpinit.php";

$error_message = Arg("error_message");

echo "
<!DOCTYPE HTML>
<HTML>
<head>
<title>DPC Error Page</title>
</head>
<body>
<h1>Something surprising happened</h1>
<p1>$error_message</p1>
</body>
</HTML>";
