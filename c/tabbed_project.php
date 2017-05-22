<?php
/**
 * Created by PhpStorm.
 * User: don
 * Date: 12/4/2015
 * Time: 2:59 PM
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/pinc/theme.php";

class DpcTab
{
    private $_name;
    private $_caption;

    public function EchoTab() {
        echo "<div class='w100'>\n";
        echo "<div class='dpc_tab' id='{$this->_name}'>{$this->_caption}</div>\n";
        echo "</div>\n";
    }
}

$tabs = array("project" => "Project", "manage" => "Manage", "pp" => "Post Process", "smooth" => "Smooth");


$relPath='./pinc/';

include_once($relPath.'dpinit.php');
include_once($relPath.'rounds.php');
include_once($relPath.'RoundsInfo.php');
include_once 'pt.php'; // echo_page_table

//$User->IsLoggedIn()
//    or RedirectToLogin();

$_theme = new DpTheme();

$nproj = number_format($Context->PostedCount());
//theme("Project", "header");
/*
echo "<html>
<head>
<meta charset='utf-8'>
<link rel='shortcut icon' href='http://www.pgdpcanada.net/c/favicon.ico'>
<script type='text/javascript' src='/c/js/dp.js'></script>
<title>DP Canada Template</title>
<link type='text/css' rel='Stylesheet' href='/c/css/dp.css'>
</head>
<body >
<div class='w100'>
  <div id='logobox'>
    <div id='logoleft'>
        <a href='http://www.pgdpcanada.net/c/default.php'>
            <img width='336' height='68' alt='Distributed Proofreaders' src='http://www.pgdpcanada.net/c/graphics/dpclogo.png'>
        </a>
    </div>
    <div id='logoright' class='w50 lfloat middle'>
        <span class=' w100 center'> $nproj titles preserved for the world!</span>
    </div>
  </div>
</div>\n";
*/

$_theme->EchoHeader();
$_theme->EchoNavbar();
//echo_navbox();
//echo_navbox2();

echo "<div id='dpbody'>
</div>\n";

echo "
</body></html>";
//$dt = new DpcTab();

/*
echo "<ul class='clear lfloat'>
        <li>Project</li>
        <li>Proj Mgr</li>
        <li>Post Proc</li>
        <li>Smooth Rdg</li>
        <li>View/Export Pages</li>
        <li>Page Detail</li>
      </ul>\n";
*/

//$dt->EchoTab();

//theme('', 'footer');
exit;

/*
function echo_navbox() {
    $register = _("Register");
    $signin  = _("Sign In");
    $regurl  = url_for_registration();

    echo "
<div id='navbox'>
    <div id='navleft' class='nav lfloat'>
      <a class='nav' href='http://www.pgdpcanada.net/c/default.php'>DPC</a>
    · <a class='nav' href='http://www.pgdpcanada.net/c/activity_hub.php'>Activity Hub</a>
    · <a class='nav' href='http://www.pgdpcanada.net/c/search.php'>Project Search</a>
    · <a class='nav' href='http://www.pgdpcanada.net/c/tools/proofers/my_projects.php'>My Projects</a>
    · <a class='nav' href='http://www.pgdpcanada.net/c/userprefs.php'>My Preferences</a>
    · <a class='nav' href='http://www.pgdpcanada.net/forumdpc/ucp.php?i=pm&amp;folder=inbox'>My Inbox</a>
    · <a class='nav' href='http://www.pgdpcanada.net/forumdpc'>Forums</a>
    · <a class='nav' href='http://www.pgdpcanada.net/wiki/index.php'>Wiki</a>
    · <a class='nav' href='http://www.pgdpcanada.net/c/tools/logout.php'>Log out (dkretz)</a>
    </div>
    <div id='navright' class='nav rfloat'>
    <form class='nomargin' id='frmlogin' name='frmlogin' method='post'>
      ID <input class='nav' type='text' name='userNM' size='10' tabindex='1' maxlength='50'> "
      . _("Password:") . " <input class='nav' type='password' name='userPW' size='10' tabindex='2' maxlength='50'>
      <input class='nav' type='submit' name='submit_login' id='submit_login' value='$signin'>
      <a class='nav' href='$regurl'>$register</a>
    </form>
    </div>
</div>\n";
}
*/

function echo_quick_links() {
    global $User;

    if (! $User->IsLoggedIn())
        return;

    $items = array();

    if ($User->IsProjectManager() || $User->IsSiteManager()) {
        $items[] = link_to_project_manager("PM");
    }

    foreach(array("P1", "P2", "P3", "F1", "F2") as $phs) {
        if($User->MayWorkInRound($phs)) {
            $items[] = link_to_round($phs);
        }
    }
    if($User->MayWorkInRound("PP")) {
        $items[] = link_to_pp();
    }
    if($User->MayWorkInRound("PPV")) {
        $items[] = link_to_ppv();
    }
    $items[] = link_to_smooth_reading("SR");

    $divider        = "\n · ";
    echo implode($divider, $items);
}
