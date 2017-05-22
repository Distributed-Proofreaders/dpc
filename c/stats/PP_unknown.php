<?
$relPath="../pinc/";
include_once($relPath.'dpinit.php');

// Not translating these strings since the PP mysteries are old English-only projects

theme("Post-Processing Mysteries", "header");

if ($order == 'default') {
    $order ='nameofwork';
}


$ok_orders = array("nameofwork",
                   "authorsname",
                   "username",
                   "projectid",
                   "modifieddate");

if (! in_array ($order, $ok_orders)) {
	$order = 'nameofwork';
}

echo "<br><br><h2>Post-Processing Mysteries</h2><br>\n";

echo "We don't know for sure who PPd these books; if you do know, or if you
did, please send email e-mail: <a
href='mailto:$general_help_email_addr'>$general_help_email_addr</a> 
	quoting the other information in the row, including the project ID. Thanks!<br><br>";

	
//get projects that have been PPd but we don't know by whom
$rows = $dpdb->SqlRows("2
	        SELECT  nameofwork, 
                    authorsname,
                    username, 
                    projectid,
                    FROM_UNIXTIME(modifieddate) as LMDate
			FROM projects 
            WHERE phase in ('PP', 'PPV', 'POSTED')
			AND postproofer = 'No Known PPer' 
			ORDER BY 'nameofwork'");

$tbl = new DpTable();
$tbl->AddColumn("<Title", "nameofwork");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("^Project<br/>Manager", "username");
$tbl->AddColumn("<Project ID", "projectid");
$tbl->AddColumn("<Date Modified", "LMDate");
$tbl->SetRows($rows);
$tbl->EchoTableNumbered();

theme("", "footer");
?>
