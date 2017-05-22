<?php
error_reporting(E_ALL);
$relPath = "./pinc/";
require_once $relPath."dpinit.php";
require_once $relPath."lists.php";

$Context = new DpContext();

$qtitle         = Arg("qtitle");
$qauthor        = Arg("qauthor");
$qpm            = ArgArray("qpm");
$qpp            = ArgArray("qpp");
$qlang          = ArgArray("qlang");
$qgenre         = ArgArray("qgenre");
$qprojectid     = Arg("qprojectid");
$qroundid       = ArgArray("qroundid");
$qstatus        = Arg("qstatus", "both");
//$sort           = Arg("sort");
$desc           = ArgBoolean("desc");
$submit_search  = IsArg("submit_search");

$qwhere         = array();
$qargs          = array();

if($submit_search) {
    define("MAX_ROWS_PER_PAGE", 200);

    $pagerows       = Arg("pagerows", MAX_ROWS_PER_PAGE);
    $rownum         = Arg("rownum", 1);

    $fmt = "<span id='%s' class='lnk'>%s</span>\n";

    $tbl = new DpTable("tblsearch", "dptable w98");
    $tbl->AddColumn("<Title",  "title",    "title_link");
    $tbl->AddColumn("<Author", "author");
    $tbl->AddColumn("^Languagge",   null,       "all_langs");
    $tbl->AddColumn("^Genre",  "genre");
    $tbl->AddColumn("^Pages", null, "page_counts");
    $tbl->AddColumn("<Poject Mgr",     "project_manager", "pmlink");
    $tbl->AddColumn("<PPer",     "postproofer", "pplink");
    $tbl->AddColumn("PPVer",   "ppverifier", "pmlink");
    $tbl->AddColumn("^Diff",   "difficulty");
    $tbl->AddColumn("^Round",  "phase");
    $tbl->AddColumn("^^Edit",     null, "edit_link");
    $tbl->AddColumn("^Project id", "projectid");

    if($qtitle) {
        $title = "%".$qtitle."%";
        add_where_parm("p.nameofwork LIKE ?", $title);
    }

    if($qauthor) {
        $author = "%".$qauthor."%";
        add_where_parm("p.authorsname LIKE ?", $qauthor);
    }

    if(count($qroundid) > 0) {
        add_where_parm_array("p.phase IN (?)", $qroundid);
    }

    if(count($qpm) > 0 ) {
        add_where_parm_array("p.username IN (?)", $qpm);
    }

    if(count($qgenre) > 0) {
        add_where_parm_array("p.genre IN (?)", $qgenre);
    }

    if(count($qlang) > 0 ) {
        echo "got lang";
        add_where_parm_array("p.language IN (?)", $qlang);
    }

    if(count($qroundid) > 0 ) {
        add_where_parm_array("p.phase IN (?)", $qroundid);
    }

    if($qprojectid) {
        $qid = preg_split("/\s+/", $qprojectid);
        add_where_parm_array("p.projectid IN (?)", $qid);
    }

    switch($qstatus) {
            default:
                break;

        case "unavail":
            add_where("MIN(ph.id) IS NOT NULL");
            break;

        case "avail":
            add_where("MIN(ph.id) IS NULL");
            break;
    }

    // -----------------------------
    // build sql string and arg array
    // -----------------------------

    $where = implode("\nAND ", $qwhere);
    $sql = project_search_view_sql($where);
    dump($sql);
    dump($qargs);
    dump(count($qargs));

    if(count($qargs) > 0) {
        $rows = $dpdb->SqlRowsPS($sql, $qargs);
    }
    else {
        $rows = $dpdb->SqlRows($sql);
    }

    $nprojects = count($rows);

    echo html_comment($sql);
}


function add_where_parm($sql, $val) {
    global $qargs, $qwhere;
    $qwhere[] = $sql;
    $qargs[] = &$val[0];
//    $qargs = array_merge($qargs, $val);
}

function add_where_parm_array($sql, $val) {
//    echo "got awpa $sql";
//    dump($val);
    global $qargs, $qwhere;
    $n = count($val);
//    dump($n);
    $s = "?";
    for($i = 0; $i < $n - 1; $i++) {
        $s .= ", ?";
    }
    $qwhere[] = preg_replace("/\?/", $s, $sql);
//    echo "count \$val " . count($val);
    foreach($val as $v) {
        $qargs[] = &$v;
    }
//    dump($qargs);
//    $qargs = array_merge($qargs, $val);
}

function add_where($clause) {
    global $qwhere;
    $qwhere[] = $clause;
}

// ------------------------------------------------------------
// display elements
// ------------------------------------------------------------

// get array of langcode => langname
$optlanguages = $Context->ActiveLanguages();

$language_picker = "
    <select name='qlang[]' class='selsearch' multiple='multiple'>
        <option value=''> </option>\n";
        foreach($optlanguages as $lang) {
            $language_picker .= "
                <option value='{$lang}'"
                .(in_array($lang, $qlang) ? " selected='selected'" : "")
                .">{$lang}</option>";
        }
        $language_picker .= "
    </select>\n";
        
// ------------------------------------------------------------
// generate html
// ------------------------------------------------------------

$title = _("Search projects");

$args = array("js_file" => $js_url."/search.js",
                "js_text" => "
     function eSetSort(e) {
        initTable(document.getElementById('tbl2'));
    }");
$args = array("js_file" => $js_url."/search.js");
$no_stats = 1;
theme(_("Search projects"), "header", $args);

echo "<h2 class='center m50em'>$title</h2>\n";

echo "
<div id='divsearch' class='center'>
<form id='searchform' name='searchform' method='POST'
                            onClick='eSetSort(event)' action=''>
<input type='hidden' name='sort' id='sort' value='Title'>
<input type='hidden' name='desc' id='desc' value='0'>
<div>
<table id='searchtable' class='left dptable logobar gray_border padded noborder'>
<tbody>
<tr>
    <th class='center'>&nbsp;</th>
    <th class='center'>&nbsp;</th>
    <th class='center'>Language</th>
    <th class='center'>Genre</th>
    <th class='center'>PM</th>
    <th class='center'>Round</th>    
</tr>
<tr>
    <td rowspan='3' width='10%'> 
        <input  type='submit' name='submit_search' value='Query'> 
    </td>

    <td> <div class='lfloat'>Title </div>
         <input type='text' class='rfloat' name='qtitle' size='40' value='$qtitle'>
    </td>
    
    <td rowspan='4' class='center'> $language_picker </td>
    <td rowspan='4' class='center'> 
        <select name='qgenre[]' class='selsearch' multiple='multiple'>\n";
        $optgenres = $Context->ActiveGenreArray();
        echo array_to_options($optgenres, true, $qgenre) . "
        </select>
    </td>
    <td rowspan='4' class='center'>
        <select name='qpm[]' class='selsearch' multiple='multiple'>\n";
        $optpms = $Context->ActivePMArray();
        echo array_to_options($optpms, true, $qpm) . "
        </select>
    </td>
    <td rowspan='4' class='center'>
        <select name='qroundid[]' class='selsearch em70' multiple='multiple'>\n";
        $optroundids = PhasesInOrder();
        echo array_to_options($optroundids, true, $qroundid) ."
        </select>
    </td>
</tr>

<tr> <td>
        <div class='lfloat'>Author</div>
        <input type='text' class='rfloat' name='qauthor' size='40' value='$qauthor'> 
</td> </tr>

<tr> <td>
        <div class='lfloat'>Project ID(s)</div>
        <input type='text' class='rfloat' name='qprojectid' size='40' value='$qprojectid'> 
    </td>
</tr>
<tr>
    <td class='middle' colspan='2'>
    <div class='rdosearch'>
        Status:
    </div>
    <div class='rdosearch'>
        <input type='radio' name='qstatus'  value='avail' "
            .($qstatus == "avail" ?  " checked='checked' " : "") .">
        Available
    </div>
    <div class='rdosearch'>
        <input type='radio' name='qstatus' value='unavail' "
        .($qstatus == "unavail" ?  " checked='checked' " : "") .">
        Unavailable
    </div>
    <div class='rdosearch'>
        <input type='radio' name='qstatus' value='both' "
        .($qstatus == "both" ?  " checked='checked' " : "") .">
        Both
    </div>
    </td>
</tr>
</tbody>
</table>
</div>

<div class='w70 left search-instructions'>
    <ul class='m25em'>
    " ._("<li>For titles and authors, matching uses
        wildcarding; 'ford' matches 'Oxford' and 'Stanford'.</li>

        <li>Search for multiple Project ids (with wildcarding) 
        by entering a list separated by spaces.</li>
        
        <li> Task, language, etc. allow multiple choices 
        by pressing the control-key.</li>")."
    </ul>
</div>\n";

if($submit_search) {
    if ( $nprojects == 0 ) {
        echo _("<b>No projects matched the search criteria.</b>");
        theme("", "footer");
        exit;
    }

    $tbl->SetRows($rows);

    echo "<div class='center'>\n";
    results_navigator($rownum, $pagerows, $nprojects);
    $tbl->EchoTable();
    results_navigator($rownum, $pagerows, $nprojects);
    echo "</div>\n";
}

echo "
</form>
</div>
<br />\n";
theme("", "footer");
exit;


function results_navigator($pagenum, $rows_per_page, $rowcount) {
    if ( $pagenum > 1 ) {
        $prevpage = $pagenum - 1;
        $url = unamp(ThisPageUrl() . "?pagenum={$prevpage}");
        echo "<a href='$url'>"._("Previous")."</a> |\n";
    }

    $minrow = $rows_per_page * ($pagenum-1) + 1;
    $maxrow = min($rows_per_page * $pagenum, $rowcount);

    $position = sprintf(
        _("Projects %1\$d to %2\$d of %3\$d\n"),
                                $minrow, $maxrow, $rowcount);
    echo "
    <span class='lfloat'>
        $position
    </span>\n";

    if ( $maxrow < $rowcount) {
        $nextpage = $pagenum + 1;
        $url = unamp(ThisPageUrl() . "?pagenum={$nextpage}");
        echo " | <a href='$url'>"._("Next")."</a>";
    }
}


function is_available($row) {
    return preg_match("/_avail/", $row['state'])
        ? "avail" 
        : "";
}

function all_langs($row) {
    return $row['language'];
}

function roundid($row) {
    return preg_match("/^(.+?)\..*_(.*)$/", $row['state'], $matches) > 0
        ? $matches[1]."&nbsp;".$matches[2]
        : "";
}

function status($row) {
    return preg_match("/_(.*)$/", $row['state'], $matches) > 0
        ? $matches[1]
        : "";
}


function array_to_options($optarray, $is_blank = true, 
                            $selected = null) {
    $ret = $is_blank
        ? "<option value=''> </option>\n"
        : "";
    // foreach($optarray as $opt => $val) {
    foreach($optarray as $val) {
        // assert($opt);
        $ret .= ("<option value='$val'"
                . (is_array($selected)
                    ? (in_array($val, $selected)
                        ? " selected='selected'"
                        : "")
                    : (is_string($selected)
                        && $selected == $val
                        ? " selected='selected'"
                        : "")
                    ) .">$val</option>\n");
    }
    return $ret;
}

function project_search_view_sql($where, $orderby = "") {
    $where = trim($where);
    if(! empty($where))
    {
        $where = "WHERE \n".$where;
    }
    return  "SELECT
                p.projectid,
                p.nameofwork AS title,
                p.authorsname AS author,
                p.language,
                p.difficulty,
                p.genre,
                p.state,
                p.phase,
                p.username as pm,
                p.postproofer,
                p.ppverifier,
                p.n_pages as pagecount,
                p.n_available_pages as pages_available,
                p.n_pages AS pages_total,
                p.username AS project_manager,
                CASE WHEN p.phase = 'PREP' THEN 0
                     WHEN p.phase LIKE 'P1' THEN 1
                     WHEN p.phase LIKE 'P2'  THEN 2
                     WHEN p.phase LIKE 'P2'  THEN 3
                     WHEN p.phase LIKE 'F1' THEN 4
                     WHEN p.phase LIKE 'F2' THEN 5
                     ELSE 6
                END AS phase_index,
                CASE WHEN p.state LIKE 'P1%' THEN 1
                     WHEN p.state LIKE 'P2%' THEN 2
                     WHEN p.state LIKE 'P3%'  THEN 3
                     WHEN p.state LIKE 'F1%' THEN 4
                     WHEN p.state LIKE 'F2%' THEN 5
                     ELSE 6
                END AS round_index
            FROM projects AS p
            $where
            GROUP BY p.projectid
            $orderby";
}

function title_link($title, $row) {
    global $code_url;
    $url = "$code_url/project.php"
        	."?id={$row['projectid']}'";
    return "<a href='{$url}>{$title}</a>";
}

function page_counts($row) {
    $pgpg = sprintf("%d/%d", $row['pages_available'], $row['pages_total']);
    return link_to_page_detail($row['projectid'], $pgpg);
    // $url = "$pm_url/page_detail.php"
            // ."?projectid={$row['projectid']}";
            
    // $fmt = "<a href='{$url}'>%d/%d</a>";
    // $fmt = "<span style='color: pink'>$fmt</span>";
    // return sprintf($fmt, 
         // $row['pages_available'],
         // $row['pages_saved'], 
         // $row['pages_total']);
         // $row['pages_out'], 
         // $row['pages_temp'],
         // $row['pages_bad']);
}

function pmlink($pm) {
    return link_to_pm($pm, $pm);
}

/*
function coblink($cob) {
    return empty($cob)
        ? ""
        : link_to_private_message($cob, $cob);
}

function words_link($row) {
    $projectid = $row['projectid'];
    return link_to_project_words($projectid, "Words", true);
}
*/


/*
function words_link($row) {
    global $User;

    return ( $User->IsSiteManager()
             || $row['pm'] == $User->Username())
        ? link_to_project_words($row['projectid'], "Words", true)
        : "";
}
*/


function edit_link($row) {
    global $User;

    return ( $User->IsSiteManager()
             || $row['pm'] == $User->Username())
        ? link_to_edit_project($row['projectid'], "Edit", true)
        : "";
}
