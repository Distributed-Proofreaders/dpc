<?PHP

// -----------------------------------------------------------------------------

function PhaseGoalDate($phase, $phdate) {
    global $dpdb;
    return $dpdb->SqlOneValue("
        SELECT goal FROM phase_goals
        WHERE phase = '$phase'
            AND goal_date = '$phdate'");
}

function PhaseGoalToday($phase) {
    global $dpdb;
    return $dpdb->SqlOneValue("
        SELECT goal FROM phase_goals
        WHERE phase = '$phase'
            AND goal_date = CURRENT_DATE()");
}
function PhaseGoalYesterday($phase) {
    global $dpdb;
    return $dpdb->SqlOneValue("
        SELECT goal FROM phase_goals
        WHERE phase = '$phase'
            AND goal_date = DATE_ADD(CURRENT_DATE(), INTERVAL -1 DAY)");
}

function PhaseGoalMonth($phase) {
    global $dpdb;
    return $dpdb->SqlOneValue("
        SELECT SUM(goal) FROM phase_goals
        WHERE phase = '$phase'
            AND goal_date >= DATE(DATE_FORMAT(NOW() ,'%Y-%m-01'))
            AND goal_date < DATE_ADD(DATE(DATE_FORMAT(
                NOW(),
                '%Y-%m-01')), INTERVAL 1 MONTH)");
}

function PhaseCountToday($phase) {
    global $dpdb;
    return $dpdb->SqlOneValue("
            SELECT COUNT(1) FROM page_events_save
            WHERE round_id = '$phase'
                AND timestamp >= UNIX_TIMESTAMP(CURRENT_DATE())");
}

function PhaseCountYesterday($phase) {
    global $dpdb;
    return $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM page_events_save
        WHERE round_id = '$phase'
            AND timestamp >= UNIX_TIMESTAMP(DATE_SUB(CURRENT_DATE(), INTERVAL 1 day))
            AND timestamp < UNIX_TIMESTAMP(CURRENT_DATE())");
}

function PhaseCountMonth($phase) {
    global $dpdb;
    return $dpdb->SqlOneValue("
        SELECT SUM(pagecount)
        FROM
        (   SELECT SUM(urp.page_count) pagecount
            FROM user_round_pages urp
            WHERE round_id = '$phase'
                AND count_time >= UNIX_TIMESTAMP(DATE(DATE_FORMAT(NOW() ,'%Y-%m-01')))
            UNION ALL
            SELECT COUNT(1) pagecount
            FROM page_events_save
            WHERE round_id = '$phase'
                AND TIMESTAMP > UNIX_TIMESTAMP(CURRENT_DATE())
        ) a");
}

function ExtendPhaseGoals() {
    global $dpdb;

    $ago = $dpdb->SqlOneValue("
        SELECT DATEDIFF(MAX(goal_date), CURRENT_DATE()) FROM phase_goals
    ");
    dump($ago);

    for($i = $ago; $i <= 31; $i++) {
        $dpdb->SqlExecute("
            REPLACE INTO phase_goals
                (goal_date, PHASE, goal)
            SELECT DATE_ADD(CURRENT_DATE(), INTERVAL $i DAY), PHASE, goal
            FROM phase_goals
            WHERE goal_date = (
                SELECT MAX(goal_date) FROM phase_goals
            )");
    }
}

// vim: sw=4 ts=4 expandtab


