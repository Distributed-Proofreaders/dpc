<?php

// This is the extended search form.
// Accessed via the Extended Search link on the main search form, search.php
// Search and Search2 should really be one file.
// This one allows searching on extra criteria
// - PPer and PPVer;
// - Phases PP and PPV;
// - Search title/author on fadedpage as well

error_reporting(E_ALL);
$relPath = "./pinc/";
require_once $relPath."dpinit.php";
require_once $relPath."Spreadsheet.inc";
require_once $relPath."gutenberg.ca.inc";

$Context = new DpContext();

$qtitle         = Arg("qtitle");
$qauthor        = Arg("qauthor");
$qpm            = Arg("qpm");
$qpp            = Arg("qpp");
$qppv           = Arg("qppv");
$qfadedpage     = Arg("qfadedpage");
$qclearance     = Arg("qclearance");
$qgutca         = Arg("qgutca");
$qdod           = Arg("qdod");
$qlang          = ArgArray("qlang");
$qgenre         = ArgArray("qgenre");
$qphase         = ArgArray("qphase");
$qstatus        = Arg("qstatus", "both");
$orderby        = Arg("orderby", "nameofwork");

$pagenum        = Arg("pagenum", "1");
$rowsperpage    = Arg("rowsperpage", "100");
$cmdPgUp        = IsArg("cmdPgUp");
$cmdPgDn        = IsArg("cmdPgDn");
$dosearch       = IsArg("dosearch");

$sql            = "";
$nprojects      = 0;

if($dosearch || $cmdPgUp || $cmdPgDn) {
	$fmt = "<span id='%s'>%s</span>\n";
	$titleCaption   = sprintf($fmt, "lktitle",    _("Title"));
	$authorCaption  = sprintf($fmt, "lkauthor",   _("Author"));
	$langCaption    = sprintf($fmt, "lklang",     _("Lang"));
	$projidCaption  = sprintf($fmt, "lkprojid",   _("Project"));
	$genreCaption   = sprintf($fmt, "lkgenre",    _("Genre"));
	$pmCaption      = sprintf($fmt, "lkpm",       _("Proj Mgr"));
	$ppCaption      = sprintf($fmt, "lkpp",       _("Post Proofer"));
//	$diffCaption    = sprintf($fmt, "lkdiff",     _("Difficulty"));
	$phaseCaption   = sprintf($fmt, "lkphase",    _("Phase"));

	$tbl = new DpTable("tblsearch", "dptable sortable w95");
	//$tbl->SetQBE();
	$tbl->AddColumn("<".$titleCaption,          "title",            "title_link");
	$tbl->AddColumn("<".$authorCaption,         "author");
	$tbl->AddColumn("^".$langCaption,           null,               "all_langs");
	$tbl->AddColumn("^".$genreCaption,          "genre");
	$tbl->AddColumn("^"._("avail<br>total"),    null,               "page_counts", "nosort");
	$tbl->AddColumn("<".$pmCaption,             "project_manager",  "pmlink");
	$tbl->AddColumn("<".$ppCaption,             "pp",               "pmlink");
	$tbl->AddColumn("^PPV",                     "ppverifier",       "pmlink");
//	$tbl->AddColumn("^".$diffCaption,           "difficulty");
	$tbl->AddColumn("^".$phaseCaption,          "phase",    "ephase",   "sortkey=sequence");
	$tbl->AddColumn(_("^Edit"),                 null,               "edit_link", "nosort");
	$tbl->AddColumn("^".$projidCaption,         "projectid");

	$awhere = array("p.phase != 'DELETED'");
    $args = array();

	if($qtitle) {
        $qqtitle = "%$qtitle%";
        $args[] = &$qqtitle;
		$qsql = "(p.nameofwork LIKE ?)";
		$awhere []  = $qsql;
	}

	if($qauthor) {
		$qqauthor = "%$qauthor%";
        $args[] = &$qqauthor;
		$qsql = "(p.authorsname LIKE ?)";
		$awhere []  = $qsql;
	}


	if(count($qphase)> 0 && $qphase != array("")) {
		$a = array();
        $includeQueue = false;
        $includeP1 = false;
		foreach($qphase as &$q) {

            // Include the P1 queue or not?
            if ($q == "P1/Queue") {
                $includeQueue = true;
                continue;
            }
            if ($q == "P1") {
                $includeP1 = true;
                continue;
            }
            $a[] ="p.phase = ?";
            $args[] = &$q;
		}

        // The artificial P1/Queue phase is tricky, and means we need to
        // consider P1 and P1/Queue together, since P1 only and P1/Queue only
        // need to test the hold count
        if ($includeQueue) {
            if ($includeP1) {
                // P1 and P1/Queue
                $a[] = "p.phase = 'P1'";
            } else {
                // P1/Queue but *not* P1 only
                $a[] = "(p.phase = 'P1' AND (SELECT count(*) FROM project_holds ph WHERE ph.projectid = p.projectid AND ph.phase = 'P1' AND ph.hold_code = 'queue') > 0)";
            }
        } else {
            if ($includeP1)
                // P1 but *not* P1/Queue
                $a[] = "(p.phase = 'P1' AND (SELECT count(*) FROM project_holds ph WHERE ph.projectid = p.projectid AND ph.phase = 'P1' AND ph.hold_code = 'queue') < 1)";
        }

		if(count($a) > 1) {
			$awhere [] = "(".implode(" OR ", $a).")";
		}
		else if(count($a) == 1) {
			$awhere [] = $a[0];
		}
	}

	if(is_array($qpm) && count($qpm) > 0 && $qpm != array("")) {
		$a = array();
		foreach($qpm as &$q) {
			$a[] ="p.username = ?";
            $args[] = &$q;
		}

		if(count($a) > 1) {
			$awhere [] = "(".implode(" OR ", $a).")";
		}
		else if(count($a) == 1) {
			$awhere [] = $a[0];
		}
	}
	if(is_array($qpp) && count($qpp) > 0) {
		$a = array();
		foreach($qpp as &$q) {
			$a[] ="p.postproofer = ?";
            $args[] = &$q;
		}

		if(count($a) > 1) {
			$awhere [] = "(".implode(" OR ", $a).")";
		}
		else if(count($a) == 1) {
			$awhere [] = $a[0];
		}
	}
	if(is_array($qppv) && count($qppv) > 0) {
		$a = array();
		foreach($qppv as &$q) {
			$a[] ="p.ppverifier = ?";
            $args[] = &$q;
		}

		if(count($a) > 1) {
			$awhere [] = "(".implode(" OR ", $a).")";
		}
		else if(count($a) == 1) {
			$awhere [] = $a[0];
		}
	}

	if($qgenre) {
		$a = array();
		foreach($qgenre as &$q) {
			$a[] = "genre = ?";
            $args[] = &$q;
		}

		if(count($a) > 1) {
			$awhere [] = "(".implode(" OR ", $a).")";
		}
		else if(count($a) == 1) {
			$awhere [] = $a[0];
		}
	}

	if($qlang) {
		$a = array();
        $qq = array();
		foreach($qlang as &$q)
            $qq[] = "%$q%";

        foreach ($qq as &$q) {
            $args[] = &$q;
            $args[] = &$q;
			$a[] ="language LIKE ?"
			    . " OR seclanguage LIKE ?";
		}

		if(count($a) > 1) {
			$awhere [] = "(".implode(" OR ", $a).")";
		}
		else if(count($a) == 1) {
			$awhere [] = $a[0];
		}
	}

	//    if($qprojectid) {
	//        $qqid = mysql_real_escape_string($qprojectid);
	//        $ids = preg_split("/\s+/", $qqid);
	//        $qsql = "p.projectid IN ('"
	//            . implode("', '", $ids)
	//            . "')";
	//        $awhere [] = $qsql;
	//    }

	$where = implode("\nAND ", $awhere);
	$sql = project_search_view_sql($where, $orderby);

    //echo "ARGS: " . print_r($args, true);
    if (count($args) > 0)
        $rows = $dpdb->SqlRowsPS($sql, $args);
    else
        // An empty set of conditions causes an empty arglist,
        // which for some reason causes an error.
        $rows = $dpdb->SqlRows($sql);
	if($cmdPgUp) {
		$pagenum = max($pagenum - 1, 1);
	}
	if($cmdPgDn) {
		$pagenum = min($pagenum + 1, ceil(count($rows) / $rowsperpage));
	}
    if($dosearch) {
        $pagenum = 1;
    }

	$nprojects = count($rows);
}

$title = _("Project Search");

$args = array("js_file" => $js_url."/search.js");
$no_stats = 1;
theme(_("Search projects"), "header", $args);

echo "
    <script type='text/javascript'>
        function eclear2() {
            $('qgenre[]').selectedIndex = -1;
            $('qlang[]').selectedIndex = -1;
            $('qpm[]').selectedIndex = -1;
            $('qpp[]').selectedIndex = -1;
            $('qppv[]').selectedIndex = -1;
            $('qphase[]').selectedIndex = -1;
            $('qtitle').value = '';
            $('qauthor').value = '';
            $('qfadedpage').checked = false;
            $('qgutca').checked = false;
            $('qdod').checked = false;
            $('qclearance').checked = false;
        }
    </script>
";

// get array of langcode => langname
$optlanguages = $Context->ActiveLanguages();

$language_picker = "
    <select class='selsearch' name='qlang[]' id='qlang[]'
                            size='12' multiple='multiple'>
        <option value=''> </option>\n";
foreach($optlanguages as $optlang) {
	$name = $optlang["name"];
	$code = $optlang["code"];
	$language_picker .= "
        <option value='{$code}'"
	                    .(in_array($code, $qlang) ? " selected='selected'" : "")
	                    .">{$name}</option>";
}
$language_picker .= "
    </select>\n";


echo "<h2 class='center m50em'>$title</h2>\n";

echo html_comment($sql);

if ($qgutca == "on")
    $gutca = "checked";
else
    $gutca = "";

if ($qdod == "on")
    $dod = "checked";
else
    $dod = "";

if ($qfadedpage == "on")
    $fp = "checked";
else
    $fp = "";
if ($qclearance == "on")
    $clearance = "checked";
else
    $clearance = "";
echo "
<form id='searchform' name='searchform' method='POST'>
<div id='divsearch' class='center' onClick='eSetSort(event)'>
    <input type='hidden' name='rowsperpage' value='$rowsperpage'>
    <input type='hidden' name='pagenum' value='$pagenum'>
    <input type='hidden' name='orderby' id='orderby' value='nameofwork'>
    <div id='searchtable' class='left w95'>
	    <div id='divsubmit' class='lfloat w35'>
			<div class='right'>
				<input type='submit' id='dosearch' name='dosearch' value='Submit'/>
				<input type='button' id='doclear' name='doclear' value='Clear' onclick='eclear2()'/>
			</div>
			<div>
				<div class='w20 left lfloat'>Title</div>
				<input id='qtitle' name='qtitle' type='text' class='lfloat w75' value='$qtitle'>
			</div>
			<div>
				<div class='w20 left lfloat'>Author</div>
				<input id='qauthor' name='qauthor' type='text' class='lfloat w75' value='$qauthor'>
			</div>
			<div>
				<div class='w20 left lfloat'></div>
                <div class='lfloat w75'>
                <input id='qfadedpage' name='qfadedpage' type='checkbox' $fp>
                Search title and author on Fadedpage.com.
                </div>
			</div>
			<div>
				<div class='w20 left lfloat'></div>
                <div class='lfloat w75'>
                <input id='qclearance' name='qclearance' type='checkbox' $clearance>
                Search title and author in the Clearance Spreadsheet.
                </div>
			</div>
			<div>
				<div class='w20 left lfloat'></div>
                <div class='lfloat w75'>
                <input id='qgutca' name='qgutca' type='checkbox' $gutca>
                Search title and author on gutenberg.ca.
                </div>
			</div>
			<div>
				<div class='w20 left lfloat'></div>
                <div class='lfloat w75'>
                <input id='qdod' name='qdod' type='checkbox' $dod>
                Search author only in the Date of Death (DoD) Spreadsheet.
                </div>
			</div>
			<!--
			<div>
				Status:<br/>
				<input type='radio' name='qstatus'  value='avail' "
					 .($qstatus == "avail" ?  " checked='checked' " : "") .">
					Available<br/>
				<input type='radio' name='qstatus' value='unavail' "
					 .($qstatus == "unavail" ?  " checked='checked' " : "") .">
					Unavailable<br/>
				<input type='radio' name='qstatus' value='both' "
					 .($qstatus == "both" ?  " checked='checked' " : "") .">
					Both
			</div>
			-->
	    </div> <!-- divsubmit -->
        <div class='w10 lfloat'>
			<div>Language</div>
			<div>
				$language_picker
			</div>
		</div>
        <div class='w10 lfloat'>
			<div>Genre</div>
			<div>
        		<select class='selsearch' id='qgenre[]' name='qgenre[]'
        		    multiple='multiple' size='12' >\n";

$optgenres = $Context->ActiveGenreArray();
echo array_to_options($optgenres, true, $qgenre);

echo "
                </select>
            </div>
        </div>

        <div class='w10 lfloat'>
            <div>PM</div>
            <div>
                <select class='selsearch' id='qpm[]' name='qpm[]'
					multiple='multiple' size='12'>\n";

$optpms = $Context->ActivePMArray();
echo array_to_options($optpms, true, $qpm);

echo "
                </select>
            </div>
        </div>
        <div class='w10 lfloat'>
            <div>PP</div>
            <div>
                <select class='selsearch' id='qpp[]' name='qpp[]'
					multiple='multiple' size='12'>\n";

$optpps = $Context->ActivePPArray();
echo array_to_options($optpps, true, $qpp);

echo "
                </select>
            </div>
        </div>

        <div class='w10 lfloat'>
            <div>PPV</div>
            <div>
                <select class='selsearch' id='qppv[]' name='qppv[]'
					multiple='multiple' size='12'>\n";

$optppvs = $Context->ActivePPVArray();
echo array_to_options($optppvs, true, $qppv);

echo "
                </select>
            </div>
        </div>

        <div class='w10 lfloat'>
			<div>Phase</div>
			<div>
				<select class='selsearch' id='qphase[]' name='qphase[]'
					   multiple='multiple' size='12'>\n";

$optphases = PhasesInOrder();
array_splice($optphases, 1, 0, array("P1/Queue"));
echo array_to_options($optphases, false, $qphase);

echo "
				</select>
			</div>
        </div>
    </div>   <!-- searchtable -->

    <div class='w70 left search-instructions'>
        " ._("For titles and authors, matching uses
          wildcarding; 'ford' matches 'Oxford' and 'Stanford'.<br>
           Genre, language, etc. allow multiple choices
          by pressing the control-key.")."
	</div>
</div>
<!--
  $sql
-->
</form>
\n";


if ( $nprojects == 0 ) {
	if($dosearch) {
		echo _( "<p class='bold'>No projects locally on DPC matched the search criteria.</p>" );
	}

} else {

    echo _("<p class='hpadded'>$nprojects projects locally on DPC matched the search criteria.</p>");

    $tbl->SetRowCount(count($rows));
    //$tbl->SetPaging($pagenum, $rowsperpage);
    $tbl->SetRows($rows);

    echo "<div class='center' onclick='eSetSort(event)'>\n";
    $tbl->EchoTable();
    echo "</div>";
}

if ($qfadedpage == "on" && ($qtitle != "" || $qauthor != "")) {
    $post = array();
    if($qtitle)
        $post['title'] = $qtitle;
    if($qauthor)
        $post['authorlike'] = $qauthor;
    //$post['debug'] = 'true';
    echo html_comment(print_r($post, TRUE));

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "https://www.fadedpage.com/csearc2.php");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    $result = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($result, $assoc = TRUE);
    echo html_comment(print_r($result, TRUE));
    $rows = $result['rows'];

    $tbl = new DpTable("tblsearch", "dptable sortable w95");
    //$tbl->SetQBE();
    $tbl->AddColumn("<FP ID", "pid", "fpBookLink");
    $tbl->AddColumn("<".$titleCaption, "title");
    $tbl->AddColumn("<".$authorCaption, "authors", "eauthors");
    $tbl->AddColumn("<Published", "first_publication");
    $tbl->AddColumn("<Pages", "pages");
    $tbl->AddColumn("^".$langCaption, "lang");
    $tbl->SetRowCount(count($rows));

	$nbooks = count($rows);
    $actual = $result['nrows'];
    if ($nbooks == 0)
        echo _("<p class='bold'>No books on FadedPage.com matched the search criteria.</p>");
    else {

        echo _("<p class='hpadded'>$actual books on FadedPage.com matched the search criteria.");
        if ($actual != $nbooks)
            echo " Only $nbooks have been returned, refine your search.";
        echo "</p>";

        $tbl->SetRowCount($nbooks);
        $tbl->SetRows($rows);

        echo "<div class='center' onclick='eSetSort(event)'>\n";
        $tbl->EchoTable();
        echo "</div>";
        echo "
            <style type='text/css'>
                .frame_div {
                    width:100%;
                    height:400px;
                    display:none;
                }
                .bookdetails {
                    width:100%;
                    height:99%;
                    border: 0;
                }
            </style>
            <div id='frame_div' class='frame_div'>
                <iframe class='bookdetails' name='bookdetails'></iframe>
            </div>
            ";
    }
}

if ($qclearance == "on" && ($qtitle != "" || $qauthor != "")) {

    $rows = loadClearanceSpreadsheet($qtitle, $qauthor);

    $tbl = new DpTable("tblsearch", "dptable sortable w95");
    $tbl->AddColumn("<FP ID", 'id');
    $tbl->AddColumn("<".$titleCaption, 'title');
    $tbl->AddColumn("<".$authorCaption, 'author');
    $tbl->AddColumn("<Published", 'published');
    $tbl->AddColumn("<Clearance", 'clearance');
    $tbl->AddColumn("<PM", 'pm');
    $tbl->SetRowCount(count($rows));

	$nbooks = count($rows);
    if ($nbooks == 0)
        echo _("<p class='bold'>No books in the Clearance Spreadsheet matched the search criteria.</p>");
    else {

        echo _("<p class='hpadded'>$nbooks books in the Clearance Spreadsheet matched the search criteria.");
        echo "</p>";

        $tbl->SetRowCount($nbooks);
        $tbl->SetRows($rows);

        echo "<div class='center' onclick='eSetSort(event)'>\n";
        $tbl->EchoTable();
        echo "</div>";
    }
}
if ($qgutca == "on" && ($qtitle != "" || $qauthor != "")) {

    $rows = searchGutenbergCA($qtitle, $qauthor);

    $tbl = new DpTable("tblsearch", "dptable sortable w95");
    $tbl->AddColumn("<directory", 'directory', 'egutdir');
    $tbl->AddColumn("<".$titleCaption, 'title');
    $tbl->AddColumn("<".$authorCaption, 'author');
    $tbl->AddColumn("<description", 'description', 'egutdesc');
    $tbl->SetRowCount(count($rows));

	$nbooks = count($rows);
    if ($nbooks == 0)
        echo _("<p class='bold'>No books on gutenberg.ca matched the search criteria.</p>");
    else {

        echo _("<p class='hpadded'>$nbooks books on gutenberg.ca matched the search criteria.");
        echo "</p>";

        $tbl->SetRowCount($nbooks);
        $tbl->SetRows($rows);

        echo "<div class='center' onclick='eSetSort(event)'>\n";
        $tbl->EchoTable();
        echo "</div>";
    }
}

if ($qdod == "on" && $qauthor != "") {
    $rows = loadDoDSpreadsheet($qauthor);

    $tbl = new DpTable("tbldod", "dptable sortable w95");
    $tbl->AddColumn("<Name", 'author');
    $tbl->AddColumn("<Type", 'type');
    $tbl->AddColumn("Birth", 'birth');
    $tbl->AddColumn("Death", 'death');
    $tbl->AddColumn("<source", 'source', 'esource');
    $tbl->SetRowCount(count($rows));

    $ndead = count($rows);
    if ($ndead == 0)
        echo "<p class='bold'>No authors found in the DoD matching the author";
    else {
        echo _("<p class='hpadded'>$ndead authors in the DoD matched the search criteria.");
        echo "</p>";

        $tbl->SetRowCount($ndead);
        $tbl->SetRows($rows);

        echo "<div class='center' onclick='eSetSort(event)'>\n";
        $tbl->EchoTable();
        echo "</div>";
    }
}

echo "
<br />\n";
theme("", "footer");
exit;

function egutdesc($dir, $row) {
    return "<span style='font-size:smaller;'>$dir</span>";
}

function esource($s) {
    $url = link_to_new_url($s, $s);
    return "<span style='font-size:smaller;'>$url</span>";
}

function egutdir($dir, $row) {
    return "<a href='http://gutenberg.ca/ebooks/$dir/'>$dir</a>";
}

function is_available($row) {
	return preg_match("/_avail/", $row['phase'])
		? "avail"
		: "";
}

function all_langs($row) {
	return $row['langname']
	       . ( empty($row['seclangname'])
		? ""
		: "/". $row['seclangname']);
}

function eauthors($authors, $row) {
    $names = "";
    foreach ($authors as $author) {
        $name = $author['realname'];
        if (isset($author['pseudoname'])) {
            $pn = $author['pseudoname'];
            $name .= " as $pn";
        }
        if ($names != "")
            $names .= " & ";
        $names .= $name;
    }
    return $names;
}

function fpBookLink($pid, $row) {
    return "<a onclick=\"document.getElementById('frame_div').style.display='block'\" class='click_iframe' href='https://fadedpage.com/showbook.php?pid=$pid' target='bookdetails'>$pid</a>";
}

function ephase($phase, $row) {
    $postednum = $row['postednum'];
    $name = $phase . (($phase == 'P1' and $row['queued'] > 0) ? "/Queue" : "");
    if ($postednum != '' && $postednum != '0')
        $name .= '/' . fpBookLink($postednum, $row);
    return $name;
}

function array_to_options($optarray, $is_blank = true, $selected = null)
{
    if (!$is_blank)
        $ret = "";
    else {
        // Unset in the database is actually non-null but empty,
        // we use a blank string for this case.
        if (is_array($selected) && in_array("", $selected))
            $ret = "<option value='' selected='selected'> </option>\n";
        else
            $ret = "<option value=''> </option>\n";
    }
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

function project_search_view_sql($where, $orderby = "nameofwork") {
	$where = trim($where);
	if($where != "") {
		$where = "WHERE \n".$where;
	}
	$orderby = "ORDER BY {$orderby}";
	return  "SELECT
                p.projectid,
                p.nameofwork AS title,
                p.authorsname AS author,
                p.language,
                p.seclanguage,
                l1.name langname,
                l2.name seclangname,
                p.genre,
                p.phase,
                p.username as pm,
                p.postproofer as pp,
                p.ppverifier,
                p.n_pages as pagecount,
                p.n_available_pages as pages_available,
                p.n_pages AS pages_total,
                p.username AS project_manager,
                ph.sequence,
                (SELECT count(*) FROM project_holds ph WHERE ph.projectid = p.projectid AND ph.phase = 'P1' AND ph.hold_code = 'queue') queued,
                p.postednum
            FROM projects p
            LEFT JOIN languages l1 ON p.language = l1.code
            LEFT JOIN languages l2 ON p.seclanguage = l2.code
            JOIN phases ph ON p.phase = ph.phase
            $where
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
}

function pmlink($pm) {
	return link_to_pm($pm, $pm);
}

function edit_link($row) {
	global $User;

	return ( $User->IsSiteManager()
	         || $row['pm'] == $User->Username()
	       || $row['pp'] == $User->Username())
		? link_to_edit_project($row['projectid'], "Edit", true)
		: "";
}

// vim: sw=4 ts=4 expandtab
