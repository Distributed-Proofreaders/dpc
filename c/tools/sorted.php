<?PHP
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
error_reporting(E_ALL);

$relPath = "../../c/pinc/";
require $relPath . "dpinit.php";

dump(get_current_user());
$dirpath = "/var/sftp/dpscans/textfilesfromjuly2015projects-sorted/*";
dump($dirpath);
$dirs = glob($dirpath);
//dump($dirs);
$n = 0;
foreach($dirs as $dir) {
    $p = $dir . "/dc.xml";
    if(file_exists($p)) {
        $n++;
        $s = file_get_contents($p);
        $title = RegexMatch("<title>([^<]*)</title>", "ui", $s, 1);
        $p2 = $dir . "/*";
        $n2 = count(glob($p2));
        dump("$dir $n2   $title");
    }
}
