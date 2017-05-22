<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
include_once $relPath . 'dpinit.php';

$User->IsAdmin()
	or RedirectToLogin();

$goals = ArgArray("goal");

foreach($goals as $phase => $goal) {
    if($goal > 0) {
        $dpdb->SqlExecute("
            REPLACE INTO phase_goals
                (phase, goal_date, goal)
            VALUES
                ('$phase', CURRENT_DATE(), $goal)");

        $dpdb->SqlExecute("
            UPDATE phase_goals
            SET goal = '$goal'
            WHERE phase = '$phase'
                AND goal_date >= CURRENT_DATE()");
    }
}

$rows = $dpdb->SqlRows("SELECT pg.phase, pg.goal
                        FROM phase_goals pg
                        JOIN phases p ON pg.phase = p.phase
                        WHERE pg.goal_date = CURRENT_DATE()
                        ORDER BY p.sequence");

$tbl = new DpTable("tblgoals", "dptable lfloat");
$tbl->addColumn("<Phase", "phase");
$tbl->addColumn("^Goal", "goal");
$tbl->addColumn("^New Goal", "phase", "eNewGoal");
$tbl->SetRows($rows);

echo $basic_header;
echo "<h1>Admin</h1>
<ol>
<li>Here are the goal settings for today.</li>
<li>You can only change goals for today, but changes will affect the future as well.</li>
<li>Any new values you enter for a phase will change the goal for today and all future days.</li>
<li>Any values you leave blank will remain as they are.</li>
</ol>
<form name='goalsform' method='POST'>\n";

$tbl->EchoTable();

echo "  <br class='clear' />
        <input type='submit' value='Submit' />
    </form>
</body>
</html>";
exit;


function eNewGoal($phase) {
    return "<input name='goal[$phase]' size='3' type='text' />\n";
}


