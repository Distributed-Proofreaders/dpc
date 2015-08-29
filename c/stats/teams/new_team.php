<?PHP
$relPath = "./../../pinc/";
include_once($relPath . 'dpinit.php');

$teamname       = Arg("teamname");
$textinfo       = Arg("textinfo");
$teamwebpage    = Arg("teamwebpage");

//if($teamname) {
//    if($dpdb->SqlExists("SELECT 1 FROM user_teams WHERE teamname = '$teamname'")) {
//        $msgs[] = "Duplicate Team Name.";
//        $teamname = "";
//    }
//}

//$a_avatarfile = FileArg('avatarfile');
//$a_iconfile   = FileArg('iconfile');

//if(! $teamname) {
//    divert(ThisFullUrl());
//}
//
//$avatar = ($a_avatarfile ? "'" . $a_avatarfile["name"] . "'" : "NULL");
//$icon   = ($a_iconfile   ? "'" . $a_iconfile["name"] . "'" : "NULL");
$dpdb->SqlExecute("
    INSERT INTO user_teams
        (teamname, team_info, webpage, createdby, owner, created)
    VALUES
        ('$teamname', '$textinfo', '$teamwebpage', '{$User->Username()}',
            $User->Uid(), UNIX_TIMESTAMP())");

$tid = $dpdb->InsertId();

if($User->Team1() == "") {
    $dpdb->SqlExecute("UPDATE users SET team_1 = $tid WHERE username = {$User->Username()}");
}
else if($User->Team2() == "") {
    $dpdb->SqlExecute("UPDATE users SET team_2 = $tid WHERE username = {$User->Username()}");
}
else if($User->Team3() == "") {
    $dpdb->SqlExecute("UPDATE users SET team_3 = $tid WHERE username = {$User->Username()}");
}
else {
    $msgs[] = _("You already belong to three teams. You will need to quit one before you can
    join your new team.");
}

