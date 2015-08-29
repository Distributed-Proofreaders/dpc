<?PHP
$relPath = "./../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'theme.inc');

$title = _("Sign In");

theme($title, "header");
echo _("ID and Password are case sensitive.<BR>Make sure your caps lock is not on.");
theme("", "footer");
?>
