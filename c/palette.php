<?PHP

ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "./pinc/";
include_once $relPath . 'dpinit.php';

?>
<h1>Bugs and Pages</h1>

<h2>Bugs</h2>
<h4>User Prefs is broken and disabled. #9</h4>
<h4>From Page Detail, Diff link is broken. #7</h4>


<h2>Pages</h2>

<table>
<?php

echo "<table>\n";

// 1
e ("default.php", "Home page", true);
// 2
e ("activity_hub.php", "Activity hub");
// 3
e ("tools/proofers/my_projects.php", "My projects");
// 4
e ("project.php?id=projectID52060d0a2d5d7", "Project - default level 2");
// 5
e ("project.php?id=projectID52060d0a2d5d7&detail_level=4", "Project - level 4");
// 6
e ("tools/project_manager/page_detail.php?project=projectID52060d0a2d5d7&show_image_size=0",
        "Project => Images, pages proofread, and diffs");
// 7
e ("tools/project_manager/diff.php?project=projectID52060d0a2d5d7&image=009.png&L_round_num=0&R_round_num=1", "A Diff");
// 8
e ("tools/project_manager/projectmgr.php"
        ."?show=search"
        ."&title="
        ."&author=faulkner"
        ."&language[]="
        ."&projectid="
        ."&project_manager="
        ."&checkedoutby="
        ."&rows_per_page=100", "Project Search");
// 9
e ("userprefs.php", "Preferences");
// 10
e ("tools/project_manager/projectmgr.php", "Project manager");
// 11
e ("tools/proofers/round.php?round_id=P1", "Round 1");
// 12
e ("stats/stats_central.php", "Stats central");

e ("tools/project_manager/pagelist.php?projectid=projectID52060d0a2d5d7",
    "New pagelist. This is for the link from the project page that anyone can see
    that has the option for 'my pages only'. Not for the PM, not for manipulating pages.
    Just for display and linking out (e.g. PMs to other people).");

echo "</table>\n";

// e ("tools/project_manager/edit_pages.php", "Edit pages");
// e ("tools/site_admin/proj_approvals.php", "Project approvals");

function e($url, $prompt, $init = false) {
    global $site_url;
    static $_n;
    $_n = $init ? 1 : $_n+1;
    $url = "/c/" . $url;
    echo "<tr><td>$_n</td><td>" . link_to_url($url, $prompt) . "</td></tr>\n";
}
