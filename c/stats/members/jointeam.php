<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');

$otid = Arg("otid", "0");
$tid  = Arg("tid");
$tid or die("No tid provided.");

$username = $User->Username();
$uid = $User->Uid();

if ($User->Team1() != $tid && $User->Team2() != $tid && $User->Team3() != $tid) {
    if ($User->Team1() == 0 || $otid == 1) {
        $dpdb->SqlExecute("
			UPDATE users SET team_1 = $tid 
            WHERE username = '$username'");

        if ($otid != 0) {
            $dpdb->SqlExecute("
            UPDATE user_teams SET active_members = active_members-1 
            WHERE id = {$User->Team1()}"); 
        }
        $redirect_team = 1;
    }
    else if ($User->Team2() == 0 || $otid == 2) {
        $dpdb->SqlExecute("
			UPDATE users SET team_2 = $tid 
            WHERE username = '$username'");

        if ($otid != 0) {
            $dpdb->SqlExecute("
            UPDATE user_teams SET active_members = active_members-1 
            WHERE id = {$User->Team1()}"); 
        }
        $redirect_team = 1;

        $dpdb->SqlExecute("
            UPDATE user_teams
            SET latestUser = $uid, 
                member_count = member_count+1, 
                active_members = active_members+1 
            WHERE id = $tid");

        if ($otid != 0) {
            $dpdb->SqlExecute("
                UPDATE user_teams 
                SET active_members = active_members-1 
                WHERE id = {$User->Team2()}"); 
        }
        $redirect_team = 1;
    }
    else if ($User->Team3() == 0 || $otid == 3) {
        $dpdb->SqlExecute("
			UPDATE users SET team_3 = $tid 
            WHERE username = '$username'");

        $dpdb->SqlExecute("
            UPDATE user_teams 
            SET latestUser = $uid,
                member_count = member_count+1,
                active_members = active_members+1 
            WHERE id = $tid");

        if ($otid != 0) {
            $dpdb->SqlExecute("
                UPDATE user_teams 
                SET active_members = active_members-1 
                WHERE id = {$User->Team3()}");
        }
        $redirect_team = 1;
    }
    else {
			include_once($relPath.'theme.inc');
			$title = _("Three Team Maximum!");
			theme($title, "header");
			echo "<br><center>";
			echo "<table border='1' bordercolor='#111111' cellpadding='3' width='95%'>";
			echo "<tr bgcolor='".$theme['color_headerbar_bg']."'>
                <td colspan='3'>
                <b><center>
                <font face='".$theme['font_headerbar']."' 
                    color='".$theme['color_headerbar_font']."'>"
                ._("Three Team Maximum")."</font></center></b></td></tr>";
			echo "<tr bgcolor='".$theme['color_mainbody_bg']."'>
                <td colspan='3'><center>
                <font face='".$theme['font_mainbody']."' 
                    color='".$theme['color_mainbody_font']."' size='2'>"
                ._("You have already joined three teams.<br>
                Which team would you like to replace?")."</font></center></td></tr>";
			echo "<tr bgcolor='".$theme['color_navbar_bg']."'>";

			$tname1 = $dpdb->SqlOneValue("
                SELECT teamname FROM user_teams 
                WHERE id='{$User->Team1()}'");
			$tname2 = $dpdb->SqlOneValue("
                SELECT teamname FROM user_teams 
                WHERE id='{$User->Team2()}'");
			$tname3 = $dpdb->SqlOneValue("
                SELECT teamname FROM user_teams 
                WHERE id='{$User->Team3()}'");

			echo "<td width='33%'><center><b>
                <a href='jointeam.php?tid=$tid&otid=1'>$tname1</a></b></center></td>\n";

			echo "<td width='33%'><center><b>
                <a href='jointeam.php?tid=$tid&otid=2'>$tname2</a></b></center></td>\n";

			echo "<td width='34%'><center><b>
                <a href='jointeam.php?tid=$tid&otid=3'>$tname3</a></b></center></td>\n";

			echo "</tr><tr bgcolor='".$theme['color_headerbar_bg']."'>
                <td colspan='3'><center><b>
                <a href='../teams/tdetail.php?tid=$tid'>
                <font face='".$theme['font_headerbar']."' 
                    color='".$theme['color_headerbar_font']."' size='2'>"
                ._("Do Not Join Team")
                ."</font></a></b></center></td></tr></table></center>\n";
			theme("", "footer");
		}
	} else {
		$title = _("Unable to Join the Team");
		$desc = _("You are already a member of this team....");

		divert("../teams/tdetail.php?tid=$tid");
		$redirect_team = 0;
	}

if ($redirect_team == 1) {
    $User->FetchPreferences();
	$title = _("Join the Team");
	$desc = _("Joining the team....");
	divert("../teams/tdetail.php?tid=$tid");
}
?>
