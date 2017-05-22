<?php
global $relPath;

/*
 * Note that this table comprises a <form> which submits to "edit_pages.php",
 * which in turn diverts back to project.php
 */
function echo_page_table( $project ) {
    /** @var DpProject $project */
    global $code_url;
    global $dpdb;

    $projectid = $project->ProjectId();
    $projphase = $project->Phase();


    // This project may have skipped some rounds, and/or it may have rounds
    // yet to do, so there may be some round-columns with no data in them.
    // Figure out which ones to display.
    //

    if($project->UserMayManage()) {
        echo "
        <form id='pagesform' name='pagesform' method='post'
                action='$code_url/tools/project_manager/edit_pages.php'>
            <input type='hidden' name='projectid' value='$projectid'>\n";
    }


    // Top header row

    $tbl = new DpTable("tblpages", "dptable bordered em90 margined");

    // Bottom header row

//    if ($project->UserMayManage()) {
//        $tbl->AddCaption("^", 1, "center b-left b-bottom b-top");
//        $tbl->AddColumn("^X", "pagename", "echeck", "b-left b-bottom");
//    }
    $tbl->AddCaption("^", 4, "center b-left b-bottom b-top");
    $tbl->AddColumn("^Page", "pagename", "epage");
	$tbl->AddColumn("^Page<br />state", "state", "estate", "b-right");
    $tbl->AddColumn("^Image", "imagefile", "eimage");
    $tbl->AddColumn(">OCR<br>Text", "prep", "etext", "b-right");


    $colclass = false;
	if($projphase != "PREP") {
		foreach(array("p1", "p2", "p3", "f1", "f2") as $phase) {
			$colclass = ! $colclass;
			$tbl->AddCaption($phase, 4, "ephasecaption", "center b-all");
			$tbl->AddColumn("^Diff", $phase, "ediff", "b-left");
			$tbl->AddColumn("^Date", $phase, "edate", "em80");
			$tbl->AddColumn("<User", $phase, "euser");
			$tbl->AddColumn(">Text", $phase, "etext", "b-right");
			if(lower($phase) == lower($projphase)) {
				break;
			}
		}
	}

	$ncol = 0;
    if($project->IsInRounds()) {
	    $ncol++;
        $tbl->AddColumn("^Edit", "pagename", "eedit");
    }

    if ($project->UserMayManage()) {
        $tbl->AddColumn("^Clear", "pagename", "eclear");
        $ncol ++;

        if( ($projphase == 'PREP' || $project->IsInRounds())) {
            $ncol++;
            $tbl->AddColumn("^Delete", "pagename", "edelete");
        }
    }
	if($ncol > 0) {
		$tbl->AddCaption("Manage", $ncol, "center b-all");
	}


    $sql = table_sql();
    $args = array(&$projectid);
    echo html_comment($sql);
    $rows = $dpdb->SqlRowsPS( $sql, $args );

	if(count($rows) < 1) {
		echo "<h3>No pages in this project yet.</h3>\n";
		return;
	}

	foreach($rows as &$row) {
//		dump($row);
		$projectid = $row['projectid'];
		$pagename = $row['pagename'];
		$prepversion = $row['prepversion'];
		$p1version = $row['p1version'];
		$p2version = $row['p2version'];
		$p3version = $row['p3version'];
		$f1version = $row['f1version'];
		$f2version = $row['f2version'];
		$preptext = PageVersionText($projectid, $pagename, $prepversion);
		$p1text = PageVersionText($projectid, $pagename, $p1version);
		$p2text = PageVersionText($projectid, $pagename, $p2version);
		$p3text = PageVersionText($projectid, $pagename, $p3version);
		$f1text = PageVersionText($projectid, $pagename, $f1version);
		$f2text = PageVersionText($projectid, $pagename, $f2version);
		$row['p1diff'] = (rtrim($preptext) != rtrim($preptext));
		$row['p2diff'] = (rtrim($p1text) != rtrim($p2text));
		$row['p3diff'] = (rtrim($p2text) != rtrim($p3text));
		$row['f1diff'] = (rtrim($p3text) != rtrim($f1text));
		$row['f2diff'] = (rtrim($f1text) != rtrim($f2text));
	}
    $tbl->SetRows($rows);
    $tbl->EchoTable();
    if($project->UserMayManage()) {
        echo "</form>  <!-- pagesform -->\n";
    }
}

function ephasecaption($pfx) {
	switch($pfx) {
		case "p1":
		case "p2":
		case "p3":
        case "f1":
        case "f2":
			return upper($pfx);
		default:
			return "";
	}
}
function echeck($pagename) {
    return "<input type='checkbox' name='chk[$pagename]' />";
}

function efix($pagename, $row) {
    $projectid = $row["projectid"];
    return link_to_view_image($projectid, $pagename, "Upload", true);
}

function epage($pagename, $row) {
    $image = $row["imagefile"];
    return "<div class='bold' title='$image'>$pagename</div>\n";
}

function eimage($imagefile, $row) {
    /** @var DpPage $page */
    $projectid = $row["projectid"];
	$pagename = $row["pagename"];
    $size = ProjectImageFileSize($projectid, $imagefile);
    return link_to_view_image($projectid, $pagename, $size, true);
}

function emaster($len, $row) {
    $projectid = $row['projectid'];
    $pagename = $row['pagename'];
    return link_to_page_text($projectid, $pagename, "PREP", $len, true);
}

function estate($state) {
	switch($state) {
		case "A":
			return "avail";
		case "O":
			return "chk out";
		case "C":
			return "done";
		case "B":
		default:
			return "bad";
	}
}

//function roundtextlenfield($roundid) {
//    return "{$roundid}_length";
//}

// diff is asking about diff with preceding phase
function ediff($phase, $row) {
	switch($phase) {
		case "PREP":
			return "";
		default:
			$phase2 = upper($phase);
			break;
	}
	if($row[$phase.'state'] == 'A') {
		return "";
	}
	$projectid = $row['projectid'];
	$pagename = $row['pagename'];
	return $row[$phase.'diff']
		? link_to_diff($projectid, $pagename, $phase2, "Diff", "1", true)
			: "";
}
function edate($phase, $row) {
	if($row[$phase.'state'] == 'A') {
		return "";
	}
	return $row[$phase.'time'];
}

function color_class($roundid, $phase, $state) {
    if($phase != $roundid) {
        return "pg_unavailable";
    }
    if($state == $roundid . ".page_out") {
        return "pg_out";
    }
    return "pg_completed";
}

function euser($phase, $row) {
    global $User;
//
	if($row[$phase.'state'] == 'A') {
		return "";
	}
	if($phase == "PREP") {
		return "";
	}
	$phase_user = $row[$phase . 'user'];
	$projphase = $row['project_phase'];
	$privacy = $row[$phase . "privacy"];
    $state = $row[$phase . "state"];
    $username = $User->Username();

	switch($projphase) {
		case "F1":
		case "F2":
		case "PP":
		case "PPV":
		case "POSTED":
			$class = "em80 ";
			break;
		default:
			$class = "em100 ";
			break;
	}

    if($privacy) {
        return "<span class='$class'>Anon</span>";
    }

    if(lower($phase_user) != lower($username)) {
        return "<span class='$class'>" . link_to_pm($phase_user, $phase_user, true) . "</span>";
    }

    $class = $class . color_class($phase, $phase, $state);
    return "<div class='$class'>$phase_user</div>";
}

function etext($phase, $row) {
	if($row[$phase.'state'] == 'A') {
		return "";
	}
	$vsn = $row[$phase.'version'];
	$version = number_format($vsn);
	if($version == "") {
		return "";
	}
    $projectid = $row['projectid'];
    $pagename = $row['pagename'];
	$text = PageVersionText($projectid, $pagename, $version);
	$lenstr = mb_strlen($text);
    return link_to_version_text($projectid, $pagename, $version, $lenstr, true);
}
function eclear($pagename) {
    return "<input type='submit' name='submit_clear[$pagename]' value='Clear' />\n";
}
function eedit($pagename, $row) {
    global $User;
    $projectid = $row['projectid'];
	$proofer = $row['current_proofer'];
    if(lower($proofer) == lower($User->Username())
            || lower($row['pm']) == lower($User->Username()) || $User->IsSiteManager()) {
        return link_to_proof_page($projectid, $pagename, "Edit", true);
    }
    return "";
}
function edelete($pagename) {
    return "<input type='submit' name='submit_delete[$pagename]' value='Delete' />\n";
}



// -----------------------------------------------------------------------------

function table_sql() {
    return "
       SELECT
        pg.projectid,
        pg.pagename,
        pg.imagefile,
        p.phase project_phase,
        p.username pm,
        plv.version,
        FROM_UNIXTIME(plv.version_time) versiontime,
        plv.state current_state,
        plv.username current_proofer,

        pvprep.username prepuser,
        pvp1.username p1user,
        pvp2.username p2user,
        pvp3.username p3user,
        pvf1.username f1user,
        pvf2.username f2user,

        pvprep.version_time preptime,
        pvp1.version_time p1time,
        pvp2.version_time p2time,
        pvp3.version_time p3time,
        pvf1.version_time f1time,
        pvf2.version_time f2time,

        up1.u_privacy p1privacy,
        up2.u_privacy p2privacy,
        up3.u_privacy p3privacy,
        uf1.u_privacy f1privacy,
        uf2.u_privacy f2privacy,

        pvprep.state prepstate,
        pvp1.state p1state,
        pvp2.state p2state,
        pvp3.state p3state,
        pvf1.state f1state,
        pvf2.state f2state,

        pvprep.version prepversion,
        pvp1.version p1version,
        pvp2.version p2version,
        pvp3.version p3version,
        pvf1.version f1version,
        pvf2.version f2version,

        pvprep.crc32 prepcrc,
        pvp1.crc32 p1crc,
        pvp2.crc32 p2crc,
        pvp3.crc32 p3crc,
        pvf1.crc32 f1crc,
        pvf2.crc32 f2crc,

        pvprep.textlen preptextlen,
        pvp1.textlen p1textlen,
        pvp2.textlen p2textlen,
        pvp3.textlen p3textlen,
        pvf1.textlen f1textlen,
        pvf2.textlen f2textlen,

        urpp1.page_count p1page_count,
        urpp2.page_count p2page_count,
        urpp3.page_count p3page_count,
        urpf1.page_count f1page_count,
        urpf2.page_count f2page_count

    FROM pages pg

    JOIN projects p
        ON pg.projectid = p.projectid

    JOIN page_last_versions plv
        ON pg.projectid = plv.projectid
        AND pg.pagename = plv.pagename

    LEFT JOIN page_versions pvprep
        ON pg.projectid = pvprep.projectid
        AND pg.pagename = pvprep.pagename
        AND pvprep.phase = 'PREP'
        AND pvprep.task = 'LOAD'

    LEFT JOIN page_versions pvp1
        ON pg.projectid = pvp1.projectid
        AND pg.pagename = pvp1.pagename
        AND pvp1.phase = 'P1'
        AND pvp1.task = 'PROOF'

    LEFT JOIN page_versions pvp2
        ON pg.projectid = pvp2.projectid
        AND pg.pagename = pvp2.pagename
        AND pvp2.phase = 'P2'
        AND pvp2.task = 'PROOF'

    LEFT JOIN page_versions pvp3
        ON pg.projectid = pvp3.projectid
        AND pg.pagename = pvp3.pagename
        AND pvp3.phase = 'P3'
        AND pvp3.task = 'PROOF'

    LEFT JOIN page_versions pvf1
        ON pg.projectid = pvf1.projectid
        AND pg.pagename = pvf1.pagename
        AND pvf1.phase = 'F1'
        AND pvf1.task = 'FORMAT'

    LEFT JOIN page_versions pvf2
        ON pg.projectid = pvf2.projectid
        AND pg.pagename = pvf2.pagename
        AND pvf2.phase = 'F2'
        AND pvf2.task = 'FORMAT'

    LEFT JOIN users up1
        ON pvp1.username = up1.username

    LEFT JOIN users up2
        ON pvp2.username = up2.username

    LEFT JOIN users up3
        ON pvp3.username = up3.username

    LEFT JOIN users uf1
        ON pvf1.username = uf1.username

    LEFT JOIN users uf2
        ON pvf2.username = uf2.username

    LEFT JOIN total_user_round_pages urpp1
        ON pvp1.username = urpp1.username
        AND pvp1.phase = urpp1.phase

    LEFT JOIN total_user_round_pages urpp2
        ON pvp2.username = urpp2.username
        AND pvp2.phase = urpp2.phase

    LEFT JOIN total_user_round_pages urpp3
        ON pvp3.username = urpp3.username
        AND pvp3.phase = urpp3.phase

    LEFT JOIN total_user_round_pages urpf1
        ON pvf1.username = urpf1.username
        AND pvf1.phase = urpf1.phase

    LEFT JOIN total_user_round_pages urpf2
        ON pvf2.username = urpf2.username
        AND pvp2.phase = urpf2.phase

    WHERE pg.projectid = ?
    GROUP BY pg.projectid, pg.pagename
    ORDER BY pg.pagename";
}


