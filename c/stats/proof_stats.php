<?PHP
$relPath='../pinc/';
include_once $relPath . 'dpinit.php';

$roundid = Arg("roundid");

$roundid
	or die("parameter 'roundid' is unset/empty");


$title = _("Top 100 Proofreaders for Round $roundid");
$subtitle = _("Users with the Highest Number of Pages Saved-as-Done in $roundid");

theme($title, 'header');

echo "
    <h2 class='center'>$title</h2>
    <h3 class='center'>$subtitle</h3>\n";

if(lower($roundid) == "all") {
    $where = "";
}
else {
    $where = "WHERE round_id = '$roundid'\n";
}
$rows = $dpdb->SqlRows("
      SELECT IF(u.u_privacy != 0, 'Anonymous', p.username) username,
             SUM(p.page_count) page_count
      FROM user_round_pages p
      JOIN users u ON u.username = p.username
      $where
      GROUP BY p.username
      ORDER BY page_count DESC, p.username
      LIMIT 100");

$tbl = new DpTable("tbltop100", "dptable center sortable w50");
$tbl->AddColumn("<Proofer", "username"); 
$tbl->AddColumn(">Pages", "page_count"); 
$tbl->SetRows($rows);
$tbl->EchoTableNumbered();


theme("", "footer");
?>
