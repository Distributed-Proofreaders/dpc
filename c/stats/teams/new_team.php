<?PHP
$relPath = "./../../pinc/";
include_once($relPath . 'dpinit.php');

$teamname       = Arg("teamname");
$description    = Arg("description");
$submitcreate   = IsArg("submitcreate");
$submitquit     = IsArg("submitquit");

$errmsg = "";

if($submitquit) {
    divert(url_for_team_list());
}

if($Context::TeamExists($teamname)) {
    $errmsg = "Team $teamname already exists.";
}


if($teamname != "" && $errmsg == "") {
    $username = $User->Username();
    $sql = "
        INSERT INTO teams
            (teamname, team_info, createdby, created_time)
        VALUES
            (?, ?, ?, UNIX_TIMESTAMP())";
    $args = array(&$teamname, &$description, &$username);
//    dump($sql);
//    dump($args);
//    die();
    $dpdb->SqlExecutePS($sql, $args);
    divert(url_for_team_list());
    exit;
}

$no_stats = 1;
$teams = $User->Teams();
theme("Create Team", "header");

echo "
<form enctype='multipart/form-data' id='frmTeam' name='frmTeam' method='POST'>
<input name='tsid' value='0' type='hidden'>
<table class='dptable center form'>
<tr> <td colspan='2'> New Proofreading Team</td></tr>\n";
if($errmsg != "") {
    echo "<tr> <td colspan='2'>$errmsg</td></tr>\n";
}
echo "<tr><td>Name:&nbsp;</td>
    <td><input name='teamname' size='50' type='text' value='$teamname'></td></tr>
</td></tr>
<tr><td>Description&nbsp;</td>
    <td><textarea id='description' name='description' cols='40' rows='6'></textarea></td></tr>\n";

echo "
    <tr><td colspan='2'>
        <input type='submit' name='submitcreate' id='submitcreate' value='Create Team'>
        <input type='submit' name='submitquit' id='submitquit' value='Quit'>
</td></tr></table>
</form>
";
theme('', 'footer');
