<?PHP
//   - Clean up Files: Remove duplicates and check in missing pages after 4 hours
//   - Promote Rounds: If a project is ready to be promoted, send it to next round
//   - Complete Project: If a project has completed all rounds, send it to post-processing
//   - Release Projects: If there are not enough projects available, release projects waiting to be released

$relPath = "../../pinc/";
require_once $relPath . "dpinit.php";
// require "RoundDescriptor.inc";
require_once $relPath . "stages.inc";
require_once $relPath . "DpProject.class.php";

error_reporting(E_ALL);

echo( "<pre>\n");

$rounds_in_order = array("P1", "P2", "P3", "F1", "F2"); 

foreach($rounds_in_order as $round_id) {
    echo "<h2>Check for completed projects in $round_id.</h2>\n";
    
    $project_ids = $dpdb->SqlValues("
        SELECT projectid FROM projects
        WHERE state = '{$round_id}.proj_avail'");

    foreach($project_ids as $project_id) {
        $project = new DpProject($project_id);
        if($project->IsBad()) {
            echo "<h3>Project $project_id is currently Bad.</h3>\n";
            continue;
        }
        if(! $project->IsAvailable()) {
            echo "<h3>Project $project_id is not Available.";
            continue;
        }

        if($project->NetAvailableCount() > 0) {
            echo "<h3>Project $project_id still has available pages.";
            continue;
        }

        if($project->IsRoundCompleted()) {
            echo "<h3>Project $project_id has completed all pages.</h3>";
            echo "<p>Advance it from Available to Completed.</p>";
            // move project to next round (status waiting)
            // and make pages available for next round
            // Also logs event
            $project->AdvanceRound();
            continue;
        }
        echo "<h3>Project $project_id has some puzzling indeterminate status.</h3>";
    }
}

// vim: sw=4 ts=4 expandtab
?>
