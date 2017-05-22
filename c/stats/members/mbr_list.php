<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath = "./../../pinc/";
require_once($relPath.'dpinit.php');

$username       = Arg("username");

if($username != "") {
    if(! $Context->UserExists($username))
        die("Searching for invalid username");
	$usr = new DpUser($username);
//    $rows = $dpdb->SqlRows("
//        SELECT username, date_created, u_privacy
//        FROM users
//        WHERE username LIKE '%{$username}%'");
	if( $usr->Exists()) {
//    if(count($rows) == 1) {
//        $row = $rows[0];
//        $rowuser = $row['username'];
        divert( "$code_url/stats/members/member_stats.php?username=$username");
		exit;
    }
} 
//else {
//    $rows = $dpdb->SqlRows("
$rows = $dpdb->SqlRows("
        SELECT
			u.username,
			DATE(FROM_UNIXTIME(u.date_created)) createdate,
			u.u_privacy privacy
		FROM users u");
//}

$title = _("Member List");
theme($title, "header");

$tbl = new DpTable("tblmembers", "dptable sortable w75");
$tbl->SetTitle(_("DPC Members"));
$tbl->AddColumn("<Username", "username", "eUser");
$tbl->AddColumn("^Date Joined", "createdate");
$tbl->AddColumn("<Statistics", "username", "eStats");
$tbl->SetRows($rows);

$tbl->EchoTable();
theme("", "footer");
exit;

function eUser($username, $row) {
	return $row["privacy"] > 0 ? _("Anonymous") : link_to_pm($username);
}

function eStats($username, $row) {
	return $row["privacy"] > 0 ? "" : link_to_member_stats($username);
}

/*
echo "
    <div class='center'>
      <table style='margin: 2em 2.5%; width: 95%;' class='b111 sortable'>
        <tr class='headerbar center'>
        <td colspan='4'>" 
        ._("Distributed Proofreader Members") ."
        </td></tr>

        <tr class='navbar'>
        <td class='left'>
            <a href='mbr_list.php?sort=username'>"
            ._("Username")."</a>
        </td>

        <td class='center'>
          <a href='mbr_list.php?sort=date_created'>"
            ._("Date Joined DP")
        ."</a></td>

        <td class='center'>"._("Options")."</td>
        </tr>\n";
*/
//if(count($rows) == 0) {
//    echo "<tr class='mainbody'>
//          <td colspan='6' class='center'>"._("No more members available.")."
//          </td></tr>\n";
//}
//else {
//    $i = 0;
//    foreach($rows as $row) {
//        if (($i % 2) == 0) {
//            echo "<tr class='mainbody'>";
//        }
//        else {
//            echo "<tr class='navbar'>";
//        }
//
//        if ( can_reveal_details_about($username, $row['u_privacy']) ) {
//
//    echo "<td>".$row['username']."</td>
//          <td>".date("m/d/Y", $row['date_created'])."</td>
//          <td class='center'>
//            <a href='$code_url/stats/members/member_stats.php"
//                . "?username=$username'>"
//                . _("Statistics")
//                ."/"
//                . link_to_pm($username, "PM")
//                . "</td>\n";
//
//        } else {
//             Print Anonymous Info
//
//    echo "<td>Anonymous</td>
//          <td class='center'>---</td>
//          <td class='center'>None</td>\n";
//        }
//
//
//        echo "</tr>\n";
//        $i++;
//    }
//}
//
//echo "<tr class='mainbody'><td colspan='3' class='left'>\n";
//
//echo "&nbsp;</td>
//    <td colspan='3' class='right'>&nbsp;";
//
//echo "</td></tr>
//      <tr class='headerbar'>
//        <td colspan='6' class='center'>\n";
// 
// <a href='$code_url/accounts/addproofer.php'>
// <font color='".$theme['color_headerbar_font']."'>"._("Create a New Account")."
// </font>
// </a>
// 
//echo "
//</td></tr>";
//echo "</table><p>";
