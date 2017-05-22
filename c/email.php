<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// include_once "./include/dpinit.php";

echo "<pre>\n";

echo date("r");

var_dump(mail("dakretz@gmail.com",
              "tue. " . date("r"),
              "stmp = powerstamp in php.ini, and restart httpd."),
		"From: admin@pgdpcanada.net");


echo "<hr>\n";


?>
