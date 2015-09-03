<?PHP

ini_set('display_errors', 1);
error_reporting(E_ALL);


$relPath = "pinc/";
include_once $relPath . 'dpinit.php';

$projectid = ArgProjectid();
$phase = Arg("phase");

$project = new DpProject($projectid);

switch($phase) {
	case "dk":
		$text = $project->PrePostText();
		break;
	case "utf8":
		$text = h($project->ActiveText());
		break;
	case "":
		$text = $project->ActiveText();
		break;
	default:
		$text = $project->RoundText( $phase );
		break;
}

echo "<style type='text/css'>
i {
	color: red;
}
</style>\n";

echo "<pre>
$text
</pre>";

