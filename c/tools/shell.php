<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'site_news.inc');

$release = ArgArray("release");

if(count($release) > 0) {
    foreach($release as $key => $value) {
        $project = new DpProject($key);
        $project->AdvanceFromPrep();
    }
}

// -----------------------------------------------------------------------------

theme("PREP: Project Preparation", "header");

echo "

<h2 class='red center'>Warning: Under Construction</h2>

<h4 class='center'>What happens in this stage:</h4>
";

theme("", "footer");
