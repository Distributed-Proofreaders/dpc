<?PHP

// batch_notify.php


ini_set("display_errors", 1);
set_include_path(get_include_path() . PATH_SEPARATOR . "/home/pgdpcanada/public_html/c/pinc"
    . PATH_SEPARATOR . "/home/pgdpcanada/public_html/forumdpc");
error_reporting(E_ALL);

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
if (strpos($_SERVER['DOCUMENT_ROOT'], "sandbox") !== false)
	$_SERVER['SERVER_NAME'] = "sandbox.pgdpcanada.net";
else
	$_SERVER['SERVER_NAME'] = "pgdpcanada.net";

$relPath = __DIR__ . "/../c/pinc/"; 
require __DIR__ . "/dpinit.php";

require __DIR__ . "/smooth.php";


$dt = $dpdb->SqlOneValue("SELECT CURRENT_TIMESTAMP()");

echo "--------------------------------------
$dt\n";

$n = SmoothNotify();

say( "Smooth Reading notifications sent: $n");

echo "--------------------------------------";

?>
