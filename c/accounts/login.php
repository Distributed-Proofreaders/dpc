<?PHP
// doesn't try to start a session --
// assumes name/password exists, or is required

$relPath = "./../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'links.php');

// -----------------------------------------------------------------------------

$userNM         = Arg("userNM");
$userPW         = Arg("userPW");
$destination    = Arg("destination", url_for_activity_hub());


if($User->IsLoggedIn() && $userNM != "" && $userPW != "") {
    $User->Logout();
}

//if($userNM && $userPW) {
//    $User = new DpThisUser($userNM, $userPW);
//    if(! $User->IsLoggedIn()) {
//        $destination = $forum_login_url;
//    }
//}

divert($destination);
