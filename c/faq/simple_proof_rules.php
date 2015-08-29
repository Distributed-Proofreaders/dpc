<?
$relPath='../pinc/';
include_once($relPath.'dpinit.php');
include_once($relPath.'theme.inc');
$no_stats=1;
theme("Beginners' Simple Proofreading Rules",'header');
echo "<br><br>";
include_once($relPath.'simple_proof_text.inc');
echo "<br><br>";
theme('','footer');
?>
