<?php 
include_once("gettext_setup.inc");

function slim_header($title = "", $doctype = true, $head = true) {
if($doctype) {
    echo "<!DOCTYPE HTML>\n";
}
echo "<html>
<head>
    <title>$title;</title>
    <meta charset='UTF-8'/>
    <meta http-equiv='Pragma' content='no-cache' />
    <meta http-equiv='Expires' content='-1' />\n";

if($head)
    echo "
</head>
<body onLoad='self.focus()'>\n";
}
