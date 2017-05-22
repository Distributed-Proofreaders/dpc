<?PHP
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');
//include_once('../memhers/member.inc');

$order          = Arg("order", "u_id");
$direction      = Arg("direction", "asc");
$mstart         = Arg("mstart", 0);
$uname          = Arg("uname");

if($uname == "") {
    $rows = $dpdb->SqlRows("
		SELECT u.u_id, u.username, u.date_created, u.u_privacy, bu.user_id
		FROM users u
        JOIN forum.bb_users bu ON u.username = bu.username
		ORDER BY u.{$order} $direction
		LIMIT $mstart, 20");
	$mRows = count($rows);
	$uname = "";
}
else {
	$rows = $dpdb->SqlRows("
		SELECT u.u_id, u.username, u.date_created, u.u_privacy, bu.user_id
		FROM users u
        JOIN forum.bb_users bu ON u.username = bu.username
		WHERE u.username LIKE '%".$_REQUEST['uname']."%'
		ORDER BY u.{$order} $direction
		LIMIT $mstart, 20");
	$mRows = count($rows);
	if ($mRows == 1) {
        $row = $rows[0];
        $u_id = $row['u_id'];
        metarefresh(0, "member_stats.php?id=$u_id", '', ''); 
        exit; 
    }
	$uname = "uname={$uname}&";
} 

$title = _("Member List");

theme($title, "header");
echo "
    <div class='center'>
      <table style='margin: 2em 2.5%; width: 95%;' class='b111'>
        <tr class='headerbar center'><td colspan='4'>" 
        ._("Distributed Proofreader Members") ."</td></tr>\n";

echo "
        <tr class='navbar'>\n";
	if ($order == "u_id" && $direction == "asc") {
        $newdirection = "desc"; 
    }
    else {
        $newdirection = "asc"; 
    }
    echo "<td class='center'><a href='mbr_list.php?".$uname."mstart=$mstart&order=u_id&direction=$newdirection'>"._("ID")."</a></td>";
	if ($order == "username" && $direction == "asc") {
        $newdirection = "desc"; 
    }
    else {
        $newdirection = "asc"; 
    }
		echo "
        <td class='left'>
            <a href='mbr_list.php?".$uname."mstart=$mstart&order=username&direction=$newdirection'>"._("Username")."</a>
        </td>\n";
	if ($order == "date_created" && $direction == "asc") {
        $newdirection = "desc"; 
    }
    else {
        $newdirection = "asc"; 
    }
		echo "<td class='center'><a href='mbr_list.php?".$uname."mstart=$mstart&order=date_created&direction=$newdirection'>"._("Date Joined DP")."</a></td>";
	echo "<td class='center'>"._("Options")."</td>";
echo "</tr>";

if (!empty($mRows)) {
	$i = 0;
    foreach($rows as $row) {
        echo "<tr>"; 

		if ( can_reveal_details_about($row['username'], $row['u_privacy']) ) {

			echo "<td class='center'>".$row['u_id']."</td>";
			echo "<td>".$row['username']."</td>";
			echo "<td>".date("m/d/Y", $row['date_created'])."</td>";
			echo "
            <td class='center'>
            <a href='member_stats.php?id="
                .$row['u_id']
                ."'>"
                ._("Statistics")
                ."</a>&nbsp;|&nbsp;<a href='$forums_url/privmsg.php?mode=post&u="
                .$row['user_id']."'>"._("PM")."</a></td>";

		} else {
			// Print Anonymous Info

			echo "<td class='center'>---</td>";
			echo "<td>Anonymous</td>";
			echo "<td class='center'>---</td>";
			echo "<td class='center'>None</td>";

		}


		echo "</tr>";
		$i++;
	}
}
else {
	echo "<tr><td colspan='6' class='center'>"._("No more members available.")."</td></tr>";
}

echo "<tr><td colspan='3' class='left'>";
if (!empty($mstart)) {
	echo "<a href='mbr_list.php?".$uname."order=$order&direction=$direction&mstart=".($mstart-20)."'>"._("Previous")."</a>";
}
echo "&nbsp;</td><td colspan='3' class='right'>&nbsp;";
if ($mRows == 20) {
	echo "<a href='mbr_list.php?".$uname."order=$order&direction=$direction&mstart=".($mstart+20)."'>"._("Next")."</a>";
}
echo "</td></tr>";
echo "<tr class='headerbar'>
<td colspan='6' class='center'>\n";
// 
// <a href='$code_url/accounts/addproofer.php'>
// <font color='".$theme['color_headerbar_font']."'>"._("Create a New Account")."
// </font>
// </a>
// 
echo "
</td></tr>";
echo "</table><p>";
theme("", "footer");
?>
