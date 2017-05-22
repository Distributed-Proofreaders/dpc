<?PHP
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'theme.inc');
// include_once($relPath.'metarefresh.inc');
include_once('../teams/team.inc');

$order      = Arg("order", "id");
$direction  = Arg("direction", "ASC");
$tstart     = ArgInt("tstart", 0);
$tname      = Arg("tname");


if (! empty($tname)) {
    $rows = $dpdb->SqlRows("
        SELECT * FROM user_teams
        WHERE teamname LIKE '%{$tname}%'
        ORDER BY $order $direction
        LIMIT $tstart, 20");
	$tRows = count($rows);
	if (count($rows) == 1) {
        $row = $rows[0];
        $id = $row['id'];
        divert("tdetail.php?tid={$id}");
        exit; 
    }
	$tname = "tname=$tname" . "&";
}
else {
    $rows = $dpdb->SqlRows("
        SELECT * FROM user_teams
        ORDER BY $order $direction
        LIMIT $tstart, 20");
}

$name = _("Team List");

theme($name, "header");
echo "<center><br>
<style type='text/css'>
.sidetable {
    border: 1px solid #111111;
    padding: 2px 4px;
    width: 95%;
}

.sidetable tr.a {
    background-color: #e7efde;
}

.sidetable td {
    color: black;
}

.sidetable img.icon {
    width: 25px;
    height: 25px;
}
</style>\n";

//Display of user teams
echo "
<table class='sidetable'>
<tr class='a'>
    <td colspan='a center'>" . _("User Teams") . " </td>
</tr>\n
<tr>
	<td>"._("Icon")."</td>\n";

	$newdirection = ($order == "id" && $direction == "asc") ? "desc" : "asc";
echo "
    <td>><a href='tlist.php"
                    ."?tname=$tname"
                    ."&amp;tstart=$tstart"
                    ."&amp;order=id"
                    ."&ampdirection=$newdirection'>"._("ID")."</a></td>\n";

    $newdirection = ( $order == "teamname" && $direction == "asc") ? "desc" : "asc";

echo "
    <td><a href='tlist.php"
                    ."?$tname"
                    ."&amp;tstart=$tstart"
                    ."&amp;order=teamname"
                    ."&amp;direction=$newdirection'>"._("Team Name")."</a></td>\n";

    $newdirection = ($order == "member_count" && $direction == "desc") ? "asc" : "desc";

echo "
    <td>><a href='tlist.php"
                    ."?$tname"
                    ."&amp;tstart=$tstart"
                    ."&amp;order=member_count"
                    ."&amp;direction=$newdirection'>"._("Total Members")."</a></td>\n";

	echo "
    <td>"._("Options")."</td>
</tr>\n";

if(count($rows) > 0) {
	$i = 0;
    $trclass = (! $i % 2) ? "mainbody" : "navbar";
    echo "
    <tr class='$trclass'>
        <td>
            <a href='tdetail.php?tid=".$row['id']."'>
                <img class='icon' src='$team_icons_url/".$row['icon']."' alt=''></a></td>
		<td align='center'><b>".$row['id']."</b></td>
		<td>".$row['teamname']."</td>
		<td>".$row['member_count']."</td>
		<td><a href='tdetail.php?tid=".$row['id']."'>"._("View")."</a>&nbsp;\n";

    if(! $User->IsTeamMemberOf($row['id']))  {
        echo "
        <a href='../members/jointeam.php"."?tid=".$row['id']."'>"._("Join")."</a></td>\n";
    }
    else {
        echo "
    <a href='../members/quitteam.php?tid=".$row['id']."'>"._("Quit")."</a></td>";
    }
    echo "</tr>\n";
    $i++;
}
else {
	echo "
    <tr class='trclass'>
        <td colspan='6 center'>"._("No more teams available.")."</td>
    </tr>\n";
}

echo "
    <tr class='trclass'>
        <td colspan='3 left'>\n";

if (! $tstart) {
    $tend = $tstart + 20;
    $tback = $tstart - 20;
	echo "
        <a href='tlist.php"
                ."?$tname"
                ."&amp;order=$order"
                ."&direction=$direction"
                ."&tstart=$tback'>"._("Previous")."</a>\n";
}
echo "&nbsp;
</td>
<td class='right' colspan='3'>&nbsp;";
if ($tRows == 20) {
    $tnext = $tstart + 20;
	echo "
    <a href='tlist.php"
                ."?$tname"
                ."&amp;order=$order"
                ."&amp;direction=$direction"
                ."&amp;tstart=$tnext'>"._("Next")."</a>\n";
}
echo "
    </td>
</tr>
<tr class='headerbar'>
    <td colspan='6' class='center'>
        <a class='headerbar' href='new_team.php'> "._("Create a New Team")."</a>
    </td>
</tr>
</table>
<p>";
theme("", "footer");
