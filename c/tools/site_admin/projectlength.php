<?PHP
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'user_is.inc');
include_once($relPath.'theme.inc');

function echo_result($result) {
  ?><table><tr><?
  if(! $result) {
    ?><th>result not valid</th><? 
  }
  else {
    $i = 0;
    while ($i < mysql_num_fields($result)) {
      $meta = mysql_fetch_field($result, $i);
      ?><th style="white-space:nowrap"><?=$meta->name?></th><?
      $i++;
    }
    ?></tr><?
   
    if(mysql_num_rows($result) == 0) {
      ?><tr><td colspan="<?=mysql_num_fields($result)?>">
      <strong><center>no result</center></strong>
      </td></tr><?
    } else
      while($row=mysql_fetch_assoc($result)) {
        ?><tr style="white-space:nowrap"><?
        foreach($row as $key=>$value) { ?><td><?=$value?></td><? }
        ?></tr><?
      }
  }
  ?></table><?
}

theme("Project Timing", "header");

$query = "SELECT p.projectid AS ˝ID˝,
                 p.nameofwork AS ˝Name˝,
                 p.authorsname AS ˝Author˝,
                 p.username AS ˝User˝,
                 p.state AS ˝State˝,
                 p.postednum AS ˝PostedPG˝,
                 FROM_UNIXTIME(project_events.timestamp) AS ˝Start˝,
                 FROM_UNIXTIME(p.modifieddate) AS ˝Last Modified˝, '
                 DATEDIFF(FROM_UNIXTIME(p.modifieddate),
                 FROM_UNIXTIME(project_events.timestamp)) AS ˝Mod Days˝,
                 FROM_UNIXTIME(p.t_last_edit) AS ˝Last Edit˝,
                 DATEDIFF(FROM_UNIXTIME(p.t_last_edit),
                          FROM_UNIXTIME(project_events.timestamp)) AS ˝Edit Days˝
            FROM projects p
            LEFT JOIN project_events  pe ON p.projectid = pe.projectid
            WHERE pe.event_type = 'creation'
            ORDER BY p.postednum, pe.timestamp";

$result = mysql_query($query);

echo_result($result);

theme("","footer");
?>
