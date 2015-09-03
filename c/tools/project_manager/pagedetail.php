<?php
$relPath="./../../pinc/";
require_once $relPath."dpinit.php";

if($User->Username() == 'dkretz') {
	require_once $relPath."DpTable2.class.php";
}

$projectid          = Arg("projectid")
    or die("No Projectid");

$todo               = Arg("todo");
$show_image_size    = Arg("show_image_size", 0);
$select_by_user     = Arg("select_by_user");
$is_my_pages        = IsArg("select_by_user");
$chkfile            = ArgArray("chkfile");

/** @var $User DpThisUser */
$User->IsLoggedIn()
    or redirect_to_home();

$project = new DpProject($projectid);

$chkfiles = array_keys($chkfile);
if(count($chkfiles) > 0) {
    switch($todo) {
        default:
            break;
        case "deletePages":
            $project->DeletePages($chkfiles);
            break;
	    case "clearPages":
		    $project->ClearPages($chkfiles);
		    break;
    }
}



// ====================================================================================
// display
// ====================================================================================

$title = $project->Title();
$page_title = _('Page details: ').$title;

$args = array("css_data" => "
        .package {
            text-align: center;
        }
        .shrink-to-fit {
            padding: .5em 2.5em;
            display: inline-block;
        }
        .lfloat {
            float: left;
        }
        .rfloat {
            float: right;
        }\n");

theme($page_title, "header", $args);

echo "
    <div class='w25 left'>
    ".link_to_project($projectid, "Return to project page")."<br>
    ".($is_my_pages
        ? link_to_page_detail($projectid, "Show all pages")
        : link_to_page_detail_mine($projectid, "Show your pages"))."
    </div>
    <div class='package'>
    <h1>$title</h1>

    <form name='pagesform' id='pagesform' method='POST'  onclick='eFormClick(event)'>
    <input type='hidden' id='projectid' name='projectid' value='{$projectid}' />
    <input type='hidden' id='pagename' name='pagename' value='' />
    <input type='hidden' id='todo' name='todo' value='' />";

    echo "
    <div class='shrink-to-fit'>\n";
if($project->UserMayManage()) {
	echo "
        <input type='button' value='Clear selected'  onclick='eClearPages()'
            title='Forget work done in this round and set text to result of previous round' />
        <input type='button' value='Delete selected' onclick='eDeletePages()'
            title='Remove selected pages entirely from the project' />\n";
}
	echo "
        <input type='button' value='View logs' onclick='eViewLogs()'
            title='View event log for all pages in this project' />
    </div>\n";

echo_pagetable($project, $is_my_pages);
echo "
    </form>
</div> <!-- package -->
</table> <!-- hanging tag from theme -->\n";

html_footer();
exit;

function echo_pagetable($project, $is_my_pages) {
	/** @var $project DpProject */
	$tblrows = page_table_rows( $project, $is_my_pages );

	echo_js( $tblrows );

	$tbl = new DpTable( "tblpages dptable em90" );

    if($project->UserMayManage()) {
        $tbl->AddCaption(null, 4);  // leave one for the rownumber?
        $tbl->AddColumn(chk_caption(), null, "rowchkbox", "width: 4em");
    }
    else {
        $tbl->AddCaption(null, 3);
    }

    $tbl->AddColumn("^"._("Page"), null, "imagelink", "skinny");
    $tbl->AddColumn("^"._("OCR"), null, "mastertextlink");
    $tbl->AddColumn("^"._("Status"), "state", "eState", "w4em");

    if($project->Phase() != "PREP") {
        $i = 0;
        foreach(array("P1", "P2", "P3", "F1", "F2") as $roundid) {
            $i++;
            $tbl->AddCaption("^".$roundid, $project->UserMayManage() ? 4 : 3);
            $tbl->AddColumn("^"._("Diff"), $roundid, "ediff");
            $tbl->AddColumn("^"._("Date"), $roundid . "_version_time");
            if($project->UserMayManage()) {
                $tbl->AddColumn("<"._("User"), "$roundid", "usrlnk", "nopad");
            }
            $tbl->AddColumn(">"._("Text"), $roundid, "textlink");
            if($roundid == $project->RoundId())
                break;
        }
    }

    $tbl->SetRows($tblrows);
    $tbl->EchoTableNumbered();
}

function chk_caption() {
    return "All <input type='checkbox' name='ckall' 
                onclick='CheckAll();'>";
}

function page_table_rows($project, $is_my_pages) {
    global $dpdb;
	global $User;
	$username = $User->Username();

    /** @var $project DpProject */

    $projectid = $project->ProjectId();
	$subwhere = $is_my_pages
			? "AND '$username' IN (
			        p1.username, p2.username, p3.username,
					p4.username, p5.username)"
			: "";

	$sql = "
SELECT
    pg.projectid,
    pg.pagename,
    pg.imagefile,

	DATE_FORMAT(FROM_UNIXTIME(p0.version_time), '%b-%e-%y %k:%i') P0_version_time,
    p0.crc32        P0_crc32,
    p0.textlen      P0_textlen,

    p1.version      P1_version,
    p1.username     P1_username,
    p1.phase        P1_phase,
    p1.state        P1_state,
	DATE_FORMAT(FROM_UNIXTIME(p1.version_time), '%b-%e-%y %k:%i') P1_version_time,
    p1.crc32        P1_crc32,
    p1.textlen      P1_textlen,

    p2.version      P2_version,
    p2.username     P2_username,
    p2.phase        P2_phase,
    p2.state        P2_state,
	DATE_FORMAT(FROM_UNIXTIME(p2.version_time), '%b-%e-%y %k:%i') P2_version_time,
    p2.crc32        P2_crc32,
    p2.textlen      P2_textlen,

    p3.version      P3_version,
    p3.username     P3_username,
    p3.phase        P3_phase,
    p3.state        P3_state,
	DATE_FORMAT(FROM_UNIXTIME(p3.version_time), '%b-%e-%y %k:%i') P3_version_time,
    p3.crc32        P3_crc32,
    p3.textlen      P3_textlen,

    p4.version      F1_version,
    p4.username     F1_username,
    p4.phase        F1_phase,
    p4.state        F1_state,
	DATE_FORMAT(FROM_UNIXTIME(p4.version_time), '%b-%e-%y %k:%i') F1_version_time,
    p4.crc32        F1_crc32,
    p4.textlen      F1_textlen,

    p5.version      F2_version,
    p5.username     F2_username,
    p5.phase        F2_phase,
    p5.state        F2_state,
	DATE_FORMAT(FROM_UNIXTIME(p5.version_time), '%b-%e-%y %k:%i') F2_version_time,
    p5.crc32        F2_crc32,
    p5.textlen      F2_textlen,

    plv.version     version,
    plv.username    username,
    plv.phase       phase,
    plv.state		state,

	p0.crc32 != p1.crc32 P1_diff,
	p1.crc32 != p2.crc32 P2_diff,
	p2.crc32 != p3.crc32 P3_diff,
	p3.crc32 != p4.crc32 F1_diff,
	p4.crc32 != p5.crc32 F2_diff

 FROM page_versions pv

 JOIN page_last_versions plv
     ON pv.projectid = plv.projectid
     AND pv.pagename = plv.pagename

 LEFT JOIN page_versions p0
     ON pv.projectid = p0.projectid
     AND pv.pagename = p0.pagename
     AND p0.phase = 'PREP'

 LEFT JOIN page_versions p1
     ON pv.projectid = p1.projectid
     AND pv.pagename = p1.pagename
     AND p1.phase = 'P1'

 LEFT JOIN page_versions p2
     ON pv.projectid = p2.projectid
     AND pv.pagename = p2.pagename
     AND p2.phase = 'P2'

 LEFT JOIN page_versions p3
     ON pv.projectid = p3.projectid
     AND pv.pagename = p3.pagename
     AND p3.phase = 'P3'

 LEFT JOIN page_versions p4
     ON pv.projectid = p4.projectid
     AND pv.pagename = p4.pagename
     AND p4.phase = 'F1'

 LEFT JOIN page_versions p5
     ON pv.projectid = p5.projectid
     AND pv.pagename = p5.pagename
     AND p5.phase = 'F2'

 JOIN pages pg
     ON pv.projectid = pg.projectid
     AND pv.pagename = pg.pagename

WHERE pv.projectid = '$projectid'
	$subwhere
 GROUP BY pv.projectid, pv.pagename
	";

    $rows = $dpdb->SqlRows($sql);
    return $rows;
}

function rowchkbox($pagerow) {
    $name = 'chkfile['.$pagerow['pagename'].']';
    return "<input type='checkbox' name='$name'>";
}

function imagelink($pagerow) {
    $projectid  = $pagerow['projectid'];
    $pagename   = $pagerow['pagename'];

    return $pagerow['imagefile']
        ? link_to_view_image($projectid, $pagename, $pagename)
        : "<span class='danger'>$pagename</span>";
}

function mastertextlink($pagerow) {
//    global $pm_url;
    $projectid = $pagerow['projectid'];
    $pagename = $pagerow['pagename'];
	$pagelen  = $pagerow['P0_textlen'];
    return link_to_page_text($projectid, $pagename, "PREP", $pagelen, true);
}

function textlink($roundid, $pagerow) {
	$prompt = $pagerow[$roundid . "_textlen"];
//    global $User;
//    $idx = substr($idx, 1);
//    $roundid = RoundIdForIndex($idx);
    $projectid = $pagerow['projectid'];
    $pagename = $pagerow['pagename'];
	return link_to_page_text($projectid, $pagename, "PREP", $prompt, true);
//    $key = "textlength_{$idx}";
//    if(! isset($pagerow[$key]))
//        return "";
//	 c/textsrv.php
//    $url = url_for_page_text($projectid, $pagename, $roundid);
//    $userval = $pagerow["user_{$rindex}"];
//    $myclass = ($User->NameIs($userval)
//            ? " class='red'"
//            : " ");
//    return "<a $myclass target='_blank' href='$url'>".$pagerow[$key]."</a>";
}

function eState($state) {
	return pagestate($state);
}
function pagestate($state) {

    switch($state) {
        case "A":
            return "<div class='pg_available'>Avail</div>\n";

        case "B":
            return "<div class='danger'>Bad</div>\n";

        case "O":
            return "<div class='pg_out'>Chk Out</div>\n";

        case "C":
            return "<div class='pg_completed'>Done</div>\n";

        default:
            return $state;
    }
}

//function roundtime($val) {
//    return $val == 0
//        ? ""
//        : strftime( "%x %H:%M", intval($val) );
//}

function clearlink($pagerow) {
    $pgstate = $pagerow['pageroundstate'];  // e.g. P2a.page_avail
    if(right($pgstate, 10) == "page_avail") {
        return "";
    }
    return "<span id='{$pagerow['pagename']}'
                    class='likealink'>"._("Clear")."</a>";
}

function fixlink($pagerow) {
    global $pm_url;
    $fix = _("Fix");
    $url = "$pm_url/handle_bad_page.php"
            ."?projectid={$pagerow['projectid']}"
            . "&chkfile={$pagerow['imagefile']}";
    return "<a target='_blank' href='{$url}'>$fix</a>";
}

function ediff($phase, $pagerow) {
	$isdiff = $pagerow[$phase . "_diff"];
	if(! $isdiff) {
		return "";
	}
	$projectid = $pagerow['projectid'];
	$pagename = $pagerow['pagename'];
	return link_to_diff($projectid, $pagename, $phase, "Diff", "1", true);
}

function pageroundid($pagerow) {
    return preg_replace("/\..*$/", "", $pagerow['pageroundstate']);
}

function usrlnk($roundid, $page) {
    global $User;
	$phase = $page['phase'];
//    $rindex = RoundIndexForId($roundid);
//    if($rindex <= 0) {
//        return "";
//    }
	$key = $roundid . "_username";
    $proofername = $page[$key];
    // index for current round

    if($proofername == "")
        return "";

    if($User->NameIs($proofername)) {
        return link_to_pm($proofername);
        // return "<div class='notmypage'>$proofername</div>";
    }

    // current round?
    if($roundid != $phase) {
        return "<div class='mypagedone'>$proofername</div>";
    }

    // based on page state ...
    switch($roundid) {
        case "O":
            return "<div class='mypageopen'>$proofername</div>";
        case "C":
            return "<div class='mypagesaved'>$proofername</div>";
        default:
            return "<div class='mystery'>$proofername</div>";
    }
}

function echo_js($rows) {
//    $num_rows = count($rows);
    
    ?>
<script type='text/javascript'>

    var _cks;

    function $(id) {
        return document.getElementById(id);
    }

    var avail_pages = [
    <?php 
    foreach($rows as $row) {
        if(pagestate($row) == 'avail') {
            echo "'{$row['pagename']}', ";
            // echo "avail_pages.push('{$row['pagename']}')\n";
        }
    }
    ?>];
    var i;

    function checked_count() {
        var n = 0;
        var pgchecks = $('tblpages').getElementsByTagName('input');
        for(var i = 0; i < pgchecks.length; i++) {
            if(pgchecks[i].checked) {
                n++;
            }
        }
        return n;
    }

    function eDeletePages() {
        var n = checked_count();
        if(n == 0) {
            return false;
        }
        if(! window.confirm('Confirm you want to delete page ' 
                                    + n.toString() + ' pages')) {
            return false;
        }
        $('todo').value = 'deletePages';
        $('pagesform').submit();
        return true;
    }

    function eClearPages() {
        var n = checked_count();
        if(n == 0) {
            return false;
        }
        if(! window.confirm('Confirm you want to clear '
                                + n.toString() + ' pages')) {
            return false;
        }
        $('todo').value = 'clearPages';
        $('pagesform').submit();
        return true;
    }

    function eViewLogs() {
        $('pagesform').target = '_blank';
        $('pagesform').action = 'pagelog.php';
        $('pagesform').submit();
        return true;
    }

    function eDeletePage(name) {
        if(! window.confirm('Confirm you want to delete page '  + name)) {
            return false;
        }
        $('pagename').value = name;
        $('todo').value = 'deletePage';
        $('pagesform').submit();
        return true;
    }

    function eClearPage(name) {
        if(! window.confirm('Confirm you want to clear page '  + name)) {
            return false;
        }
        $('pagename').value = name;
        $('todo').value = 'clearPage';
        $('pagesform').submit();
        return true;
    }

    function ePageLog(name) {
        $('pagename').value = name;
        $('todo').value = 'clearPage';
        $('pagesform').submit();
    }

    function eFormClick(e) {
        if(! e) e = window.event;
        var t = e.target;
        if(t.innerHTML == 'Delete') {
            return eDeletePage(t.id);
        }
        else if(t.innerHTML == 'Clear') {
            return eClearPage(t.id);
        }
        else if(t.innerHTML == 'Page log') {
            return ePageLog(t.id);
        }
        return true;
    }

    function CheckAll() {
        if(document.pagesform.ckall.checked) {
            eSelectAll();
        }
        else {
            eClearAll();
        }
    }

    function SetCheckboxes(val) {
        val = val ? 'checked' : '';
        var pgchecks = $('tblpages').getElementsByTagName('input');
        for(var i = 0; i < pgchecks.length; i++) {
            pgchecks[i].checked = val;
        }
    }

    function eSelectAll() {
        SetCheckboxes(true);
    }

    function eClearAll() {
        SetCheckboxes(false);
    }

</script>
<?php
}

// vim: sw=4 ts=4 expandtab
?>


