<?PHP
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
error_reporting(E_ALL);

$relPath = "../../c/pinc/";
require $relPath . "dpinit.php";

dump(get_current_user());
$dirpath = "/var/sftp/dpscans/textfilesfromjuly2015projects-tosort/*";
dump($dirpath);
$dirs = glob($dirpath);
//dump($dirs);
$n = 0;
foreach($dirs as $path) {
    $projectid = basename($path);
    $p = build_path($path, "/dc.xml");
    $is_file = file_exists($p) ;
    if($is_file) {
        $n++;
        $s = file_get_contents($p);
        $title = RegexMatch("<title>([^<]*)</title>", "ui", $s, 1);
        $p2 = $path . "/*";
        $nfiles = count(glob($p2));
        $is = $is_file ? "dc.xml yes" : "dc.xml no";
        say("$projectid ($nfiles files)  [$is]    $title");
    }
    else {
        $nfiles = 0;
    }
    tend_r_projectids($projectid, $path, $is_file, $nfiles);
}
say("<br><br>Number of projects: $n");

function tend_r_projectids($projectid, $path, $is_file, $nfiles) {
    global $dpdb;
//    $rows = $dpdb->SqlRows("SELECT * FROM r_projetids WHERE projectid = '$projectid'");
//    if(count($rows) == 0) {
        $sql = "
            REPLACE INTO r_projectids
                ( projectid, is_dir, path, nfiles )
            VALUES
                ( ?, ?, ?, ?)";
        $args = array(&$projectid, &$is_file, &$path, &$nfiles);
//    }
//    else {
//        $sql = "UPDATE r_projectids
//                SET is_dir = ?, path = ?
//                WHERE projectid = ?";
//        $args = array(&$is_file, &$path, &$projectid);
//    }
    $dpdb->SqlExecutePS($sql, $args);
}
