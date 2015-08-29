<?PHP
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');

$tid            = Arg("tid", Arg("tsid"));
$team           = new DpTeam($tid);

if ($User->Username() != $team->OwnerName()) {
    divert("tdetail.php?tid=$tid");
    exit;
}

$teamname       = Arg('teamname');
$team_info      = Arg('text_data');
$webpage        = Arg('teamwebpage');
$avatarfile     = FileArg('teamavatar');
$iconfile       = FileArg('teamicon');

if ($team) {
	$edit = _("Edit") . $team->Name();
	theme($edit, "header");
	showTeamEditForm($team);
	theme("", "footer");
    exit;
}

showTeamEditForm(null);
//showTeamProfile($team);
theme("", "footer");
exit;

function showTeamEditForm($team) {
    /** @var DpTeam $team */
    global $theme;
    global $User;

    $avatarfile = FileArg("avatar");
    $iconfile   = FileArg("icon");

    echo "<form enctype='multipart/form-data' id='mkTeam' name='mkTeam' method='POST'";
    if($team) {
        echo " action='tedit.php'>\n";
        echo "<input type='hidden' name='tsid' value='{$team->Id()}'>";
    }
    else {
        echo " action='new_team.php'>\n";
    }
    if ($avatarfile != "") {
        echo "<input type='hidden' name='avatar' value='$avatarfile'>";
    }
    if ($iconfile != "") {
        echo "<input type='hidden' name='icon' value='$iconfile'>";
    }
    echo "\n<table id='team_edit_table>";
    echo "<tr><th>\n";
    if ($team->OwnerId() == $User->Username()) {
        echo _("Edit Team Information")."</b></font></td></tr>";
    }
    else {
        echo _("New Proofreading Team")."</b></font></td></tr>";
    }
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

    if ($team->OwnerId() == $User->Username()) {
        echo "<tr><td>
        <input type='submit' name='mkMake' value='" ._("Make Team")."'>&nbsp;&nbsp;&nbsp;
        <input type='submit' name='Quit' value='" ._("Quit")."'> </td></tr>
        </table>
        </form>\n";
    } else {
        echo "<tr><td>
        <input type='submit' name='edMake' value='" ._("Save Changes")."'>&nbsp;&nbsp;&nbsp;
        <input type='submit' name='edQuit' value='" ._("Quit")."'>
        </td></tr>
        </table>
        </form>\n";
    }
}
