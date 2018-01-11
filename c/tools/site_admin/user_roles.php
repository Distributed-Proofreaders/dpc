<?php
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath = "./../../pinc/";
require_once $relPath."dpinit.php";
require_once $relPath."DpTable.class.php";
require_once $relPath."theme.inc";

$User->MayModifyAccess()
    or die("Permission denied.");

$username   = Arg("username");
$role       = Arg("role");
$grants     = ArgArray("grant");
$revokes    = ArgArray("revoke");
$grants     = array_keys($grants);
$revokes    = array_keys($revokes);
$qrysubmit  = IsArg("qrysubmit");

if($username) {
	$Context->UserExists($username)
		or die("No user '$username'");
}

if(count($grants) > 0) {
    handle_grants($username, $grants);
}
if(count($revokes) > 0) {
    handle_revokes($username, $revokes);
}

if($username != '') {
    $sql = "
	SELECT  u.username,
            r.role_code,
            ur.id urid,
            r.description

    FROM users u

    CROSS JOIN roles r

    LEFT JOIN user_roles ur
        ON u.username = ur.username
        AND r.role_code = ur.role_code

    WHERE u.username = '$username'
    ";
    $rows = $dpdb->SqlRows($sql);

    $tbluser = new DpTable("tbluser");
    $tbluser->AddColumn("<Role", "role_code");
    $tbluser->AddColumn("<Description", "description");
    $tbluser->AddColumn("<Status", "urid", "estatus");
    $tbluser->AddColumn("^Grant", null, "egrant");
    $tbluser->AddColumn("^Revoke", null, "erevoke");
    $tbluser->SetRows($rows);


    $sql = "
		SELECT  urp.phase,
                DATE(FROM_UNIXTIME(MIN(urp.count_time))) first_date,
                DATEDIFF(CURRENT_DATE, DATE(FROM_UNIXTIME(MIN(urp.count_time)))) days_in_round,
                SUM(urp.page_count) page_count
        FROM user_round_pages urp
        JOIN rounds r ON urp.phase = r.roundid
        WHERE urp.username = 'dkretz'
        GROUP BY urp.phase
        ORDER BY r.round_index
        ";

    $round_stats = $dpdb->SqlRows($sql);

    $tblstats = new DpTable("tblstats", "dptable bordered padded");
    $tblstats->AddColumn("<Round", "phase");
    $tblstats->AddColumn("^Since", "first_date");
    $tblstats->AddColumn("^Days", "days_in_round");
    $tblstats->AddColumn(">Pages", "page_count");
    $tblstats->SetRows($round_stats);
}
else if($role != "") {
	$sql = "SELECT  r.role_code,
                    r.description,
                    ur.id urid,
                    u.username
		    FROM roles r
		    LEFT JOIN user_roles ur ON r.role_code = ur.role_code
			LEFT JOIN users u on ur.username = u.username
			WHERE r.role_code = '$role'";

	$rows = $dpdb->SqlRows($sql);

	$tblrole = new DpTable("tblrole");
	$tblrole->AddColumn("^Username", "username", "euserquery");
    $tblrole->AddColumn("<Role", "role_code");
	$tblrole->AddColumn("^Revoke", null, "erevoke");
	$tblrole->SetRows($rows);
}

$no_stats = 1;
theme("DPC User Roles", "header");

if ($username)
    echo "<h1>User Roles for $username</h1>";
else if ($role)
    echo "<h1>All Users with the Role $role</h1>";
else
    echo "<h1>User Roles</h1>";
echo"
<form name='frmroles' id='frmroles' method='POST'>
<div id='divinput' class='controlbox w30 lfloat'>
<p>Submit a Username to manipulate their roles.
Alternatively, submit just a role to get a list of all users
with that role.  Do not give both username and role.
</p>
	<div class='w50 clear lfloat'> Username </div>
	<div class='w50 lfloat'>
		<input type='text' name='username' id='username' value='$username'>
	</div>
    <br>
    <div class='w50 clear lfloat'> Role </div>
	<div class='w50 lfloat'>
		<input type='text' name='role' id='role' value='$role'>
	</div>
	<div class='w100 center'>
		<input type='submit' name='qrysubmit' value='Submit'>
	</div>
</div> <!-- divinput -->

<div id='divtable' class='w50 rfloat'>\n";

if($username) {
    $tbluser->EchoTable();

    /*
    echo "
    <hr>
    <h2>Quizwork</h2>\n";
    echo $tblquiz;
    */

    echo "
    <hr>
    <h2>Round Work</h2>\n";
    $tblstats->EchoTable();
}
else if($role) {
    echo '
        <p>Sorry, revoke does not work in the current context,
        follow through the username, and you can revoke there.</p>
    ';
	$tblrole->EchoTable();
}

echo "</div></form>\n";

theme("", "footer");
exit;

function ecode($code) {
    return $code;
}
function estatus($urid) {
    return $urid == "" ? "" : "Yes";
}
function egrant($row) {
    return $row['urid'] ? "" : grant_button($row['role_code']);
}
function erevoke($row) {
    return $row['urid'] ? revoke_button($row['role_code']) : "";
}
function euserquery($username) {
	return link_to_user_roles($username);
}

function grant_button($code) {
    return "<input type='submit' value='Grant' name='grant[$code]' id='g$code'/>";
}

function revoke_button($code) {
    return "<input type='submit' value='Revoke' name='revoke[$code]' id='r$code'/>";
}

function handle_grants($username, $roles) {
    $usr = new DpUser($username);
//    assert($usr->Exists());
    foreach($roles as $role) {
        $usr->GrantRole($role);
   }
}

function handle_revokes($username, $roles) {
    $usr = new DpUser($username);
//    assert($usr->Exists());
    foreach($roles as $role) {
        $usr->RevokeRole($role);
    }
}


// vim: sw=4 ts=4 expandtab
