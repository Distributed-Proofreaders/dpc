<?PHP
//clear cookie if one is already set
$relPath = './../pinc/';
include_once($relPath.'dpinit.php');

if($User->IsLoggedIn()) {
    $User->Logout();
}

divert("../default.php");
?>
