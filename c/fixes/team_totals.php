<?
/*
    Long-term: Need a table of Team Totals by Round
    Date    Team ID    Round        Count Total

    Date    holder_id  tally_name   Prev. Row + Daily Count

*/

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

// loop until today
$date1 = "2013-10-05";
for($i = 1; ; $i++) {
    $date1 = "2013-10-05";
    $rows = $dpdb->SqlRows("
        SELECT * FROM team_round_pages_total
        WHERE dateval = DATE('{$date1}')");
    foreach($rows as $row) {
        $dpdb->SqlExecute("
    $date2 = $dpdb->SqlOneValue("
        SELECT DATE_ADD(DATE('2013-10-05'), INTERVAL $i DAY)");

    $dpdb->SqlExecute("
            INSERT INTO team_round_pages_total
                ( dateval, team_id, round_id, page_count)
            SELECT DATE('$dte'), {$team->id}, '
    if($dpdb->SqlOneValue("
        SELECT DATE('$dte') = CURRENT_DATE()");
}
            
foreach($teams as $team) {
    $id = $team->id;
    $users = $dpdb->SqlValues("
        SELECT username FROM users
        WHERE team_1 = $id
            OR team_2 = $id
            OR team_3 = $id");

    // There are no Team values in past_tallies - only users.
    // So assume the values (one per team per round) are for the
    // last date recorded for users in past_tallies.
    // That date is Oct 5 2013 (2013-10-05 00:00:00).
    // So start resumming on 10/05, one day at a time.

    $dpdb->SqlExecute("
        REPLACE INTO current_tallies (tally_name, holder_type, holder_id, tally_value)
        SELECT tally_name, 'T', {$team->id}, tally_value + page_count
        FROM team_round_pages trp
        WHERF team_id = {$team->id}
            AND round_id = tally_name
            AND holder_type = 'T'
            AND holder_id = 

    $maxdate = $dpdb->SqlOneValue("
        SELECT MAX(FROM_UNIXTIME(timestamp)) FROM past_tallies
        WHERE 
    dump("{$team->id} {$team->teamname} " . count($users));
}

