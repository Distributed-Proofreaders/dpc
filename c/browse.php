<?PHP

$relPath = "./pinc/";
require_once $relPath . "dpinit.php";

$projectid = ArgProjectId()
	or die("No project id provided");

$project = new DpProject($projectid);
$project->Exists()
	or die("No such project - $projectid");

$pages = $project->PageRows();
$pgsrc = url_for_page_image($projectid, $pages[0]["image"]);

echo "
<!DOCTYPE HTML>
<html>
  <head>
	  <title>{$project->Title()}</title>
  </head>
  <body>
    <form name='frmbrowse' id='frmbrowse' action='' method='POST'>
    <input type='hidden' name='fileid' id='fileid' value='".$pages[0]["fileid"]."'>
    <div id='ctlbar' class='w100'>
	    <input type='button' value='back' id='btnback' name='btnback'>
    </div>
    <iframe src='$pgsrc' width='30%' height='100%' class='lfloat' />
    <div id='divtext' class='w70 lfloat'>
    {$pages[0]['master_text']}
	</form>
  </body>
</html>";
