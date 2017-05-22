<?PHP
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');
include_once('../teams/team.inc');
include_once('../members/member.inc');

global $User;
$roundid = Arg('round_id', Arg('roundid'));
$usrname = Arg ('username');
if($usrname == "") {
    $usrname = $User->Username();
}
$usr = new DpUser($usrname);

theme("DPC: Profile for $usrname", "header");


echo _("<h1 class='center'>Details for user {$usr->PrivateUsername()}</h1>\n");

if ( $usr->Privacy() > 0 ) {
	echo _("<p class='center'>This user has requested their statistics be private.</p>\n");
}
else {
	EchoMemberInformation( $usr, $roundid );
}

theme("", "footer");
?>
