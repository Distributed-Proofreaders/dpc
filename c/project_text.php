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
	case "P1":
	case "P2":
	case "P3":
	case "F1":
	case "F2":
        $text = $project->RoundText( $phase );
		break;
	default:
		$text = $project->ActiveText();
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

