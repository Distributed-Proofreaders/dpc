<?PHP
$relPath="./../../pinc/";
include_once $relPath.'dpinit.php';

if(! $User->IsLoggedIn()) {
    die("Please log in.");
}

$projectid  = Arg('projectid');
$setclear   = Arg('setclear');
$username   = $User->Username();

if($setclear == "set" && ! $dpdb->SqlExists("
            SELECT 1 FROM notify 
            WHERE projectid = '$projectid' AND username = '$username'")) {
    $dpdb->SqlExecute("
            INSERT INTO notify (projectid, username)
            VALUES ('$projectid', '$username')");
}
else if($setclear == "clear") {
    $dpdb->SqlExecute("
            DELETE FROM notify 
            WHERE projectid = '$projectid' AND username = '$username'");
}

$projectstate = $dpdb->SqlOneValue("
            SELECT state FROM projects
            WHERE projectid = '$projectid'");

divert( "$code_url/project.php"
                    ."?id=$projectid"
                    ."&amp;expected_state=$proofstate" );
?>
