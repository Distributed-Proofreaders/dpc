<?php
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath = "./../../pinc/";
require_once $relPath."dpinit.php";
require_once $relPath."DpProject.class.php";
require_once $relPath."DpTable.class.php";
require_once $relPath."theme.inc";
require_once $relPath."links.php";

$projectid          = ArgProjectId();
if(! $projectid) {
    die("No projectid specified");
}
$is_my_pages        = IsArg("my_pages");

/** @var $User DpThisUser */
$User->IsLoggedIn()
    or redirect_to_home();

$project = new DpProject($projectid);

$title = $project->Title();
$page_title = _('Page details: ').$title;

theme($page_title, "header");

echo "
    <div class='w25 left'>
    ".link_to_project($projectid, "Return to project page")."<br>
    ".($is_my_pages
        ? link_to_page_detail($projectid, "Show all pages")
        : link_to_page_detail_mine($projectid, "Show your pages"))."
    </div>
    <h1 class='center'>$title</h1>

    <form name='pagesform' id='pagesform' action='' method='POST'>
    <input type='hidden' id='projectid' name='projectid'
                                        value='{$projectid}' />
    <input type='hidden' id='pagename' name='pagename' value='' />\n";

echo_pagetable($project, $is_my_pages);
echo "
    </form>
  </table>\n";

html_footer();
exit;

function echo_pagetable($project, $is_my_pages) {

    /** @var $project DpProject */
    $tblrows = page_table_rows($project, $is_my_pages);
    if(! $tblrows) {
        return;
    }

    $tbl = new DpTable();
    $tbl->SetId("tblpages");
    $roundid = $project->RoundId();
    $tblclass = ($roundid == "F1" || $roundid == "F2"
                    ? "em80"
                    : "em90");
    $tbl->SetClass($tblclass);

    $tbl->AddCaption(null, 3); 

    $tbl->AddColumn("^"._("Page"), null, "imagelink", "skinny");
    $tbl->AddColumn("^"._("Text"), null, "mastertextlink", "skinny");
    $tbl->AddColumn("^"._("Status"), null, "pagestate", "w4em");

    if($roundid != "OCR") {
        $i = 0;
        foreach(array("P1", "P2", "P3", "F1", "F2") as $roundid) {
            $i++;
            $tbl->AddCaption("^".$roundid, 4);
            $tbl->AddColumn("^"._("Diff"), "$roundid", "difflink");
            $tbl->AddColumn("^"._("Date"), "time_{$i}", "roundtime");
            // if($project->UserMayManage()) {
            $tbl->AddColumn("^"._("User"), "$roundid", "usrlnk", "nopad");
            // }
            $tbl->AddColumn(">"._("Text"), "r{$i}", "textlink");
            if($roundid == $project->RoundId())
                break;
        }
    }

    $tbl->AddCaption("^".(""), 2);
    if($project->UserMayManage()) {
        $tbl->AddColumn("^"._("Fix"), null, "fixlink");
    }
    $tbl->AddColumn("^"._("Edit"), "roundid", "usreditlink");

    $tbl->SetRows($tblrows);
    $tbl->EchoTableNumbered();
}

function page_table_rows($project, $is_my_pages) {
    global $dpdb;
    global $User;

    /** @var $project DpProject */

    $projectid  = $project->ProjectId();
    $roundid    = $project->RoundId();
    $myname     = $User->Username();
    
    $sql = "
        SELECT  
            '$projectid'                AS projectid,
            '$roundid'                  AS roundid,
            pp.fileid                   AS pagename,
            pp.image                    AS imagefile,
            pp.state                    AS pageroundstate,
            p.state                     AS projectroundstate,
            SUBSTRING(p.state, 1, 2)    AS roundid,
            CHAR_LENGTH(pp.master_text) AS textlength_master,\n";

    for($i = 1; $i <= 5; $i++) {
        $textfield = TextFieldForRoundIndex($i); 
        $prevtextfield = TextFieldForRoundIndex($i-1);
        $sql .= "
            pp.Round{$i}_user AS user_{$i},
            pp.Round{$i}_time AS time_{$i},
            CHAR_LENGTH(pp.Round{$i}_text) AS textlength_{$i},
            CASE WHEN BINARY TRIM(pp.$textfield) = BINARY TRIM(pp.$prevtextfield)
                THEN 0 ELSE 1 END AS is_diff_{$i},\n";
    }

    $sql .= "
            pp.b_user
        FROM $projectid AS pp,
        projects AS p
        WHERE p.projectid = '{$project->ProjectId()}'";

    if($is_my_pages) {
        $sql .= "
           AND ( ( pp.round1_user = '$myname' AND pp.state != 'P1.page_avail')
              OR ( pp.round2_user = '$myname' AND pp.state != 'P2.page_avail')
              OR ( pp.round3_user = '$myname' AND pp.state != 'P3.page_avail')
              OR ( pp.round4_user = '$myname' AND pp.state != 'F1.page_avail')
              OR ( pp.round5_user = '$myname' AND pp.state != 'F2.page_avail')
        )\n";
    }

    $sql .= " ORDER BY fileid\n";

    echo "\n<!--\n$sql\n-->\n";
    $rows = $dpdb->SqlRows($sql);
    return $rows;
}

function imagelink($pagerow) {
    $projectid  = $pagerow['projectid'];
    $pagename   = $pagerow['pagename'];

    return $pagerow['pagename']
        ? link_to_view_image($projectid, $pagename, $pagename, true)
        : "<span class='danger'>$pagename</span>";
}

function mastertextlink($pagerow) {
    global $pm_url;
    $url = unamp("$pm_url/downloadproofed.php"
        ."?projectid={$pagerow['projectid']}"
        ."&pagename={$pagerow['pagename']}"
        ."&roundid=OCR");
    $ret = "<a target='_blank' href='{$url}"
        ."'>{$pagerow['textlength_master']}</a>";
    return $ret;
}

function textlink($idx, $pagerow) {
    global $pm_url, $User;
    $idx = substr($idx, 1);
    $roundid = RoundIdForIndex($idx);
    $key = "textlength_{$idx}";
    if(! isset($pagerow[$key]))
        return "";
    $url = unamp("$pm_url/downloadproofed.php"
            . "?projectid={$pagerow['projectid']}"
            . "&pagename={$pagerow['pagename']}"
            . "&roundid={$roundid}");
    $rindex = RoundIndexForId($roundid);
    $userval = $pagerow["user_{$rindex}"];
    $myclass = ($userval == $User->Username()
            ? " class='red'"
            : " ");
    return "<a $myclass target='_blank' href='$url"
            . "'>".$pagerow[$key]."</a>";
}

function pgstat($pagerow) {
    $rs = $pagerow['pageroundstate'];
    return preg_replace("/^.+?_/", "", $rs);
}

function pagestate($pagerow) {
    $stat = pgstat($pagerow);

    switch($stat) {
        case "avail":
            return "<div class='pg_available'>$stat</div>\n";

        case "bad":
            return "<div class='danger'>$stat</div>\n";

        case "out":
        case "temp":
            return "<div class='pg_out'>out</div>\n";

        case "saved":
            return "<div class='pg_completed'>$stat</div>\n";

        default:
            return $stat;
    }
}

function roundtime($val) {
    return $val == 0
        ? ""
        : strftime( "%x %H:%M", intval($val) );
}


function fixlink($pagerow) {
    return link_to_fix($pagerow['projectid'], $pagerow['pagename']);
}

function usreditlink($roundid, $row) {
    global $project;
    global $User;

    if($roundid != $project->RoundId())
        return "";
    $rindex = RoundIndexForId($roundid);
    $userval = $row["user_{$rindex}"];
    if($User->Username() != $userval)
        return "";
    $projectid = $project->ProjectId();
    // $pagename = $row['pagename'];
    $pagename = $row['pagename'];
    return link_to_proof_page($projectid, $pagename, "edit");
}

function difflink($rindex, $pagerow) {
    global $pm_url;
    $id = RoundIndexForId($rindex);
    $key = "textlength_{$id}";
    if(! isset($pagerow[$key]) || $pagerow[$key] == 0)
        return "";
    $key = "is_diff_{$id}";
    if( $pagerow[$key] == 0)
        return "no diff";

    $url = unamp("$pm_url/diff.php"
            ."?projectid={$pagerow['projectid']}"
            . "&pagename={$pagerow['pagename']}"
            . "&roundid={$rindex}");
    return "<a target='_blank' href='{$url}'>diff</a>";
}

function pageroundid($pagerow) {
    return preg_replace("/\..*$/", "", $pagerow['pageroundstate']);
}

function usrlnk($roundid, $page) {
    global $User;
    $rindex = RoundIndexForId($roundid);
    $key = "user_{$rindex}";
    $proofername = $page[$key];
    // index for current round
    $pindex = RoundIndexForId(pageroundid($page));

    if($proofername == "")
        return "";

    if($User->Username() != $proofername) {
        $ln = link_to_pm($proofername);
        return "<div class='notmypage'>$ln</div>";
    }

    // current round?
    if($rindex != $pindex) {
        return "<div class='mypagedone'>$proofername</div>";
    }

    // based on page state ...
    switch(pagestate($page)) {
        case "out":
        case "temp":
            return "<div class='mypageopen'>$proofername</div>"; 
        case "saved":
            return "<div class='mypagesaved'>$proofername</div>";
        default:
            return "<div class='red'>$proofername</div>";
    }
}


// vim: sw=4 ts=4 expandtab
