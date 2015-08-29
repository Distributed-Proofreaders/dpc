<?PHP

// www.pgdpcanada.net/c/tools/cleanup.php?projectid='p50730001
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');

$projectid = Arg("projectid");
$errmsg    = Arg("error");
$msgs      = ArgArray("msgs");

if($projectid == "") {
	restart_this( "No projectid" );
}

$project = new DpProject($projectid);
if(! $project->Exists()) {
	restart_this("Project $projectid does not exist.");
}

$project->UserMayManage()
    or die("Not your project");

$proj_link = link_to_project($projectid, "Back to project page");


// -­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­-­

html_start();

// feedback from advance
if(count($msgs)) {
}

echo "
<form target='' name='frmflow' method='post'>
<div class='w75'>
    Project ID: 
    <input type='text' name='projectid' value='$projectid' size='30'>
    <input tabindex='1' type='submit'>
    <p>This page is only available to Adminstrators, and for Project Managers, only
    for their own projects. For the time being, PMs can view all projects - but be
    careful.</p>
</div>
<hr>

<div>
<pre>
    Project ID: {$project->ProjectId()}  $proj_link
    Title:      {$project->NameOfWork()}
    Author:     {$project->AuthorsName()}
    PM:         {$project->ProjectManager()}
    Phase:      {$project->Phase()}
    Status:     {$project->VersionState()}
    PPer:       {$project->PPer()}
    PPVer:      {$project->PPVer()}
    Posted #:   {$project->PostedNumber()}
</pre>
</div>\n";

$re_utf8bom = "/\xef\xbb\xbf/";
$re_spacey = '\s"\s';
$re_hyphen = "-[\r\n]";
$re_curley = "”‟““’‛‘‘";

$a_bom = array();
$a_spacey = array();
$a_hyphen = array();
$a_curley = array();
$a_convert = array();
$a_utf8   = array();

foreach($project->PageObjects() as $pg) {
    if(CountReplaceRegex($re_utf8bom, "u", "", $pg->master_text)) {
        $a_bom[] = $pg->imagefilename;
    }

    if($n = RegexCount($re_spacey, "u", $pg->master_text)) {
        $a_spacey[] = "{$pg->imagefilename} ($n)";
    }
    if($n = RegexCount($re_hyphen, "u", $pg->master_text)) {
        $a_hyphen[] = "{$pg->imagefilename} ($n)";
    }
    if($n = RegexCount($re_curley, "u", $pg->master_text)) {
        $a_curley[] = "{$pg->imagefilename} ($n)";
    }
    if(maybe_convert($pg->master_text) != $pg->master_text) {
        $a_html[] = $pg->imagefilename;
    }
}

echo "
<pre>

BOM removed: " . count($a_bom) . "
spacey-quotes: " . count($a_spacey) . "
line-end hyphens: " . count($a_hyphen) . "
curley quotes: " . count($a_curley) . "
utf8: " . count($a_utf8) . "
</pre>
</pre>\n";

html_end();


function restart_this($errmsg) {
	global $msgs;
	global $projectid;

	$msgs[] = $errmsg;
		html_start();
		echo "
    $errmsg
    <form name='frmflow' method='POST'>
    <div>
    Project ID: <input type='text' name='projectid' value='$projectid' size='30'><input tabindex='1' type='submit'>
    </div>\n";
		html_end();
		exit;
}
