<?PHP
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
error_reporting(E_ALL);

$relPath = "../pinc/";
require $relPath . "dpinit.php";

$dirpath = "/var/sftp/dpscans/PPqueue/*";
$adirs = glob($dirpath);

foreach($adirs as $srcdir) {
    $projectid = basename($srcdir);
    $dcpath = $srcdir . "/dc.xml";
    if(file_exists($dcpath)) {
        $text = file_get_contents($dcpath);
//        dump($text);
        recover($projectid, $text);
        $frompaths = build_path($srcdir, "*.zip");
        $ps = glob($frompaths);
//        dump($ps);
        foreach($ps as $p) {
            $topath = build_path(ProjectPath($projectid), basename($p));
            dump("copy $p to $topath");
            copy($p, $topath);
        }
    }
    else {
        dump($dcpath);
    }
}
exit;

function recover($projectid, $text) {
    global $dpdb;
    $title = RegexMatch("<title>(.*)</title>", "ui", $text, 1);
    $author = RegexMatch("<creator>(.*)</creator>", "ui", $text, 1);
    $contributer = RegexMatch("<contributer>(.*)</contributer>", "ui", $text, 1);
    $language = RegexMatch("<language>(.*)</language>", "ui", $text, 1);
    $source = RegexMatch("<source>(.*)</source>", "ui", $text, 1);
//    $credits = RegexMatch("<source>(.*)</source>", "ui", $text, 1);
//    dump($projectid);
//    dump($title);
//    dump($author);
//    dump($contributer);
//    dump($language);
//    dump($source);
    $sql = "REPLACE INTO projects
            SET projectid = ?,
                nameofwork = ?,
                authorsname = ?,
                comments = ?,
                language = ?,
                clearance = ?,
                extra_credits = ?,
                phase = 'PP'";
    $args = array(&$projectid, &$title, &$author, &$text, &$language, &$source, &$contributer);
    dump($dpdb->SqlExecutePS($sql, $args));
//    dump($sql);
//    dump($args);
}
