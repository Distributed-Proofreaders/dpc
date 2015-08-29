<?PHP
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');

$teamid = Arg("tid");
if($User->Team1() == $teamid) {
    $User->ClearTeam(1);
}
if($User->Team2() == $teamid) {
    $User->ClearTeam(1);
}
if($User->Team3() == $teamid) {
    $User->ClearTeam(1);
}
divert("../teams/tdetail.php?tid=$teamid");

