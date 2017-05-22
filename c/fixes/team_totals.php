<?
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../pinc/";
include_once $relPath . 'dpinit.php';

$sql = "
    SELECT id, teamname
    FROM user_teams";
// dump($sql);
$teams = $dpdb->SqlObjects($sql);
// dump($teams);

$id = 33;

dump($dpdb->SqlOneValue("
    SELECT username FROM users
        WHERE team_1 = $id
            OR team_2 = $id
            OR team_3 = $id"));

foreach($teams as $team) {
    $id = $team->id;
    $users = $dpdb->SqlValues("
        SELECT username FROM users
        WHERE team_1 = $id
            OR team_2 = $id
            OR team_3 = $id");
    dump("{$team->id} {$team->teamname} " . count($users));
}

