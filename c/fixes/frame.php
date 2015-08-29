<?
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
include_once $relPath . 'dpinit.php';

$projs = $dpdb->SqlObjects("
    SELECT projectid, phase, comment, nameofwork, authorsname
    FROM projects
    WHERE phase IN ('P1', 'P2', 'P3', 'F1', 'F2', 'PREP', 'PP', 'PPV')");

    $n = 0;
    foreach($projs as $proj) {
        $c1 = $proj->comment;
        $c2 = maybe_convert($proj->comment);
        if($c1 != $c2) {
            $n++;
            dump($c1);
        }
    }

