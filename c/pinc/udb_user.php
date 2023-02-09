<?php

$db_server = ini_get("mysqli.default_host");
$db_user = ini_get("mysqli.default_user");
$db_password = ini_get("mysqli.default_pw");
if (strpos($_SERVER['SERVER_NAME'], "sandbox") !== false)
    $db_name     = 'sandbox_DPCanada';
else
    $db_name     = 'dbDPCanada';

$site_managers = array("ionav", "alexwhite", "DPCanada");

?>
