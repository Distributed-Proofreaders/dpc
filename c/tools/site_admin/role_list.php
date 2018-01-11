<?PHP

ini_set('display_errors', 1);
error_reporting(E_ALL);


$relPath = "../../pinc/";
include_once($relPath.'dpinit.php');

$User->IsLoggedIn()
    or RedirectToLogin();

$User->IsProjectFacilitator() || $User->IsSiteManager() || $User->IsProjectManager()
    or die("Unauthorized");

$sort = Arg("sort", "role");

switch($sort) {
    default:
    case "role":
        $orderby = "r.role_code, u.username";
        break;
    case "user":
        $orderby = "u.username, r.role_code";
        break;
}

$tbl = new DpTable("tblroles", "left dptable sortable");
$tbl->SetRows($dpdb->SqlRows("
    SELECT r.role_code, r.description, u.username
    FROM users u
    JOIN user_roles ur ON u.username = ur.username
    JOIN roles r ON ur.role_code = r.role_code
    WHERE r.role_code NOT IN ('P1', 'P2', 'P3', 'F1', 'F2')
    ORDER BY $orderby"));

echo html_head("Role List");
echo "<h1 class='center'>Role List</h1>\n";
$tbl->EchoTable();
echo html_end();

// vim: ts=4 sw=4 expandtab
