<?PHP
$relPath="../pinc/";
include_once($relPath.'dpinit.php');

$phase = Arg("phase", "PP");
if ( $phase == "PP" ) {
	$activity = _('Post Processing');
}
else if ( $phase == "PPV" ) {
	$activity = _('Post Processing Verification');
}
else {
    die("invalid phase");
}

$title = _("Books Checked Out for ") . $activity;
theme($title,'header');

echo "<h2>$title</h2>\n";

// ------------------

// Header row

$where = ($phase == "PP")
    ? "WHERE p.phase = 'PP' AND LENGTH(p.postproofer) > 0"
    : "WHERE p.phase = 'PPV' AND LENGTH(p.ppverifier) > 0";


$rows = $dpdb->SqlRows("
	SELECT p.projectid,
           p.nameofwork,
           p.username,
           IFNULL(p.postproofer, '') postproofer,
           IFNULL(p.ppverifier, '') ppverifier,
		   DATE(FROM_UNIXTIME(p.modifieddate)) moddate,
		   DATE(FROM_UNIXTIME(upp.t_last_activity)) upp_last_time,
		   DATE(FROM_UNIXTIME(uppv.t_last_activity)) uppv_last_time
	FROM projects p
    LEFT JOIN users upp ON p.postproofer = upp.username
    LEFT JOIN users uppv ON p.ppverifier = uppv.username
	$where
	ORDER BY p.postproofer");

$tbl = new DpTable();
$tbl->AddColumn("<Title", "nameofwork", "etitle");
$tbl->AddColumn("<PM", "username", "epm");
$tbl->AddColumn("<PPer", "postproofer", "epm");
$tbl->AddColumn("<Last on site", "upp_last_time");
$tbl->AddColumn("<PPVer", "ppverifier", "epm");
$tbl->AddColumn("<Last on site", "uppv_last_time");
$tbl->AddColumn("<Project modified", "moddate");
$tbl->SetRows($rows);
$tbl->EchoTable();

echo "</table>";
theme("","footer");
exit;

function etitle($title, $row) {
    return link_to_project($row["projectid"], $title);
}

function epm($user) {
    return link_to_pm($user);
}
?>

