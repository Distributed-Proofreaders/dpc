<?PHP
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');

$team_id        = Arg("team_id");

if($team_id == "new") {
    $team = null;
    $teamname       = Arg('teamname');
    $team_info      = Arg('text_data');
}
else {
    $team           = new DpTeam($team_id) ;
}



$title = $team ? _("Edit ") . $team->Name() : _("Create Team");
theme($title, "header");

echo "<form id='frmteam' name='frmteam' method='POST'";

echo "<input type='hidden' name='team_id' value='$team_id'>\n";

$tbltitle = ($team && $team->CreatedBy() == $User->Username())
                ? _("Edit Team Information")
                :  _("Create New Team");

$tbl = new DpTable("tblteam", "center dptable");
$tbl->SetTitle($tbltitle);
$tbl->AddColumn("<", "left", "eleft");
$tbl->AddColumn("<", "right", "eright");

$rows = array(
    array("left" => "teamname"), array("right"   => "$teamname", "ename"),
    array("left" => "description"), array("right"   => "$team_info", "edesc"),
    array("ok", "cancel"));;

$tbl->SetRows($rows);
$tbl->EchoTable();

/*
echo "\n<tr><td>
        <table border='0' cellspacing='0' cellpadding='0' width='100%'>
            <tr><td>"._("Team Name").":&nbsp;</td>
          <td><input type='text' value='{$team->Name()}' name='teamname' size='50'>&nbsp;</td></tr>
         <tr><td>
        "._("Team Webpage").":&nbsp;</td>
          <td><input type='text' value='{$team->WebPage()}' name='teamwebpage' size='50'>&nbsp;</td></tr>
        <tr><td>"._("Team Avatar").":&nbsp;</td>
        <td><input type='file' name='teamavatar' size='50'>&nbsp;</td></tr>
        <tr><td> "._("Team Icon").":&nbsp;</td>
        <td><input type='file' name='teamicon' size='50'>&nbsp;</td></tr>
    </table></td></tr>
    <tr> <td>"._("Team Description")."</b>&nbsp;
    <br /><textarea name='text_data' cols='40' rows='6'>{$team->Info()}</textarea> <br /></td></tr>\n";

if ($team->OwnerId() == $User->Username()
    && $User->Team1() != 0
    && $User->Team2() != 0
    && $User->Team3() != 0 ) {
    echo "
            <tr bgcolor='".$theme['color_mainbody_bg']."'><td><center>"
        ._("You must join the team to create it,
                    which team space would you like to use?")."<br />
        <select name='tteams' title='"._("Team List")."'>
        <option value='{$User->Team1()}'>$User->Team1()</option>
        <option value='{$User->Team2()}'>$User->Team2()</option>
        <option value='{$User->Team3()}'>$User->Team3()</option>
        </select></center></td></tr>";
}
else {
    echo "<input type='hidden' name='teamall' value='1'>";
}
*/

theme("", "footer");
exit;


