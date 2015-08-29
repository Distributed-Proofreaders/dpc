<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);
$relPath='../pinc/';
include_once($relPath.'dpinit.php');

$title = _("Post-Processing Verification Statistics");
theme($title,'header');

echo "
    <h2 class='center'>$title</h2>
    <h3 class='center'>" . _("Post-Processing Verifiers") . "</h3>
    <h4 class='center'>" . _("(Number of Projects Posted to FadedPage)") . "</h4>\n";

$sql = "SELECT ppverifier,
				u.u_privacy,
				COUNT(1) AS project_count
        FROM projects p
        JOIN users u ON p.ppverifier = u.username
        WHERE  p.phase = 'POSTED'
        GROUP BY p.ppverifier
        ORDER BY COUNT(1) DESC";

$tbl = new DpTable();
$tbl->SetRows($dpdb->SqlRows($sql));
$tbl->AddColumn("<PP Verifier", "ppverifier", "eUsername");
$tbl->AddColumn(">Projects", "project_count");
$tbl->EchoTableNumbered();

theme("","footer");
exit();

function eUsername($name, $row) {
	global $User;
	return ($row['u_privacy'] > 0)
		? ($User->IsAdmin() ? "{$name}*" : "anonymous")
		: $name;
}
