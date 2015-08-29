<?PHP
$relPath="./../../pinc/";
include_once $relPath . "dpinit.php";
include_once($relPath.'user_is.inc');
include_once($relPath.'theme.inc');
include_once($relPath.'DpTable.class.php');

theme("Copyright Approval", "header");

if (!user_is_a_sitemanager()) {
    echo _('You are not authorized to invoke this script.');
    exit;
}

//----------------------------------------------------------------------------------
if (Arg('update')) {
//update project approval status
     if ($_GET['metadata'] =='approved') {
         $statuschange = 'project_new_app';
     }
     else {
         $statuschange = 'project_new_unapp';     
     }

     $dpdb->SqlExecute("
             UPDATE projects 
             SET state = '$statuschange' 
             WHERE projectid = '$update'");
}

echo "<table border=1>
<tr>
    <td align='center' colspan='4'><b>Books Waiting for Copyright Approval</b></td>
    <tr></tr>
    <td align='center' colspan='4'>
        The following books need to be approved/disapproved for copyright clearance.
    </td>
    <tr></tr>
    <td align='center' colspan='1'><b>Title</b></td>
    <td align='center' colspan='1'><b>Author</b></td>
    <td align='center' colspan='1'><b>Clearance Line</b></td>
    <td align='center' colspan='1'><b>Approved/Disapproved</b></td>
</tr>\n";

    $projs = $dpdb->SqlObjects("
      SELECT projectid, nameofwork, authorsname, clearance, state 
      FROM projects 
      WHERE state = 'project_new_waiting_app'");

    $tbl = new DpTable();
    $tbl->SetRows($projs);
    $tbl->EchoTable();
    exit;

    foreach($projs as $proj) {
        $projectid = $proj->projectid;
        $state = $proj->state;
        $name = $proj->nameofwork;
        $author = $proj->authorsname;
        $clearance = $proj->clearance;

	    if ($rownum % 2 ) {
			$row_color = $theme['color_mainbody_bg'];
		}
        else {
			$row_color = $theme['color_navbar_bg'];
		}
		
        echo "<tr bgcolor='$row_color'>";
        echo "<td align='right'><a href = '$code_url/project.php?id=$projectid'>$name</a></td>\n";
        echo "<td align='right'>$author</td>\n";
        echo "<td><input type='text' size='67' name='clearance' value='$clearance'></td>";
        echo "<td>
              <form action = \"proj_approvals.php"
                    ."?projectid=$projectid\">
              <input type ='hidden' name ='update' value='$projectid'>";

        echo "Approved
            <input type='radio' name='metadata' value='approved'>
            Disapproved
            <input type='radio' name='metadata' value='disapproved'>
            <input type=submit value=\"update\">
            </td></form>";

      $rownum++;
      echo "</tr>";
	}

//echo "</table>";
echo "<br>";
		echo "<tr></tr>\n";
		echo "<tr></tr>\n";
		echo "<tr></tr>\n";
		echo "<tr></tr>\n";
		echo "<tr></tr>\n";
		echo "<tr></tr>\n";
echo "</table>";
echo "</center>";
echo "<br>";
theme("","footer");
?>

