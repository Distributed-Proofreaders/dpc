<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
include_once $relPath . 'dpinit.php';

$User->IsSiteManager()
    or die("Not permitted.");

$goal = Arg("goal");

if($goal > 0) {
    foreach($Context->Phases() as $phase) {
    $dpdb->SqlExecute("
        REPLACE INTO phase_goals
            (phase, goal_date, goal)
        VALUES
            ('$phase', CURRENT_DATE(), $goal)");
    $dpdb->SqlExecute("
        UPDATE phase_goals
        SET goal = '$goal'
        WHERE goal_date > CURRENT_DATE()");
    }
}

$rows = $dpdb->SqlRows("SELECT MAX(pg.goal) goal
                        FROM phase_goals pg
                        WHERE pg.goal_date = CURRENT_DATE()");

$tbl = new DpTable("tblgoals", "dptable lfloat");
$tbl->addColumn("^Goal", "goal");
$tbl->addColumn("^New Goal", "phase", "eNewGoal");
$tbl->SetRows($rows);

echo $basic_header;
echo "<h1>Set Rounds Goal</h1>
<ol>
<li>Here is the goal setting for today.</li>
<li>You can only change the for today, but changes will affect the future as well.</li>
<li>Any new value you enter will change the goal for today and all future days.</li>
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
    return "<input name='goal' size='3' type='text' />\n";
}


