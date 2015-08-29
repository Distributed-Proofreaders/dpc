<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
include_once $relPath . 'dpinit.php';

$projectid = 'projectID51297990e1024';
$project = new  DpProject($projectid);

$t = $project->ActiveText();
$t = maybe_convert($t);

$a = preg_split("//u", $t, 0, PREG_SPLIT_NO_EMPTY);
$chars = array();
foreach($a as $c) {
    switch($c) {
        case "\n":
        case "\r":
        case '\t':
        case ' ':
            continue;
        default:
            if(isset($chars[$c]))
                $chars[$c]++;
            else
                $chars[$c] = 1;
    }
}

asort($chars);

echo "<pre>\n";
foreach($chars as $key => $val) {
    $d = ord($key);
    echo "$key: $val\n";
}
exit;
