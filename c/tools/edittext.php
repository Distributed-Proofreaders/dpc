<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath.'dpinit.php');

$projectid = ArgProjectId();
$pagename  = ArgPageName();
$roundid   = Arg("roundid");
$pagetext  = Arg("pagetext");

$page      = new DpPage($projectid, $pagename);

if(! $page->UserMayManage()) {
    die("Permission problem.");
}

$text = $page->RoundText();

html_head();
?>
<body>
<form name='txtform' id='txtform'>
    <textarea>$text</textarea>
</form>
</body>
</html>
