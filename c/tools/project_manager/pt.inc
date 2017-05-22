<?php
global $relPath;

function echo_page_table( $Project ) {
    /** @var DpProject $project */
	global $code_url;
    global $dpdb, $Context;

	$may_manage = $project->UserMayManage() ;

	$projectid = $Project->ProjectId();
	$phase = $Project->Phase();

	$show_delete = $may_manage 
                && ($phase == 'PREP' || $phase == 'P1' || $phase == 'P2'
                    || $phase == 'P3' || $phase == 'F1'|| $phase == 'F2');


	// This project may have skipped some rounds, and/or it may have rounds
	// yet to do, so there may be some round-columns with no data in them.
	// Figure out which ones to display.
	//

	if ( $may_manage ) {
		echo "
        <form name='pagesform' method='post'
        action='$code_url/tools/project_manager/edit_pages.php'> <input
        type='hidden' name='projectid' value='$projectid'>\n";
	}

	echo "<table id='page_table' class='bordered'>\n";


	// Top header row

    $tbl = new DpTable();
    $tbl->AddCaption(1, "");
    $tbl->AddCaption(1, "");
    $tbl->AddCaption(2, "Upload");
    $tbl->AddCaption(1, "");
    if($phase != 'PREP') {
        foreach($Context->Phases() as $ph) {
            $tbl->AddCaption(4, $ph);
            if($ph == $phase) {
                break;
            }
        }
    }


	// Bottom header row
   
    if ($may_manage) {
        $tbl->AddColumn("^X", "projectid", "echeck");
    }
    // $tbl->AddColumn("I", "rownum");
    $tbl->AddColumn("^Image", "image", "eimage");
    $tbl->AddColumn(">Text", "master_text", "emaster");
    $tbl->AddColumn(">Page State", "state");

    foreach($Context->Rounds() as $round) {
        $tbl->AddColumn("^Diff", $round, "ediff");
        $tbl->AddColumn("^Date", $round, "edate");
        $tbl->AddColumn("<User", $round, "euser");
        $tbl->AddColumn(">Text", $round, "etext");
        if($round == $phase) {
            break;
        }
    }

    if ($may_manage) {
        $tbl->AddColumn("^Clear", null, "eclear");
        $tbl->AddColumn("^Edit", null, "eedit");
        $tbl->AddColumn("^Bad<br />fix", null, "ebad");
    }

    if ($show_delete) {
        $tbl->AddColumn("^Delete", null, "edelete");
    }

    $sql = table_sql($projectid);
    echo html_comment($sql);
    $rows = $dpdb->SqlRows( table_sql($projectid));
    $tbl->SetRows($rows);
    $num_rows = count($rows);
    $tbl->EchoTableNumbered();

	$avail_pages = "";


echo "
<script type='text/javascript'>
var avail_pages = " . substr($avail_pages, 0, -1) . ";
var num_rows = $num_rows;
var pagesform = document.getElementById('pagesform');


function changeSelection(sel) {
    var i;
    switch(sel) {
        case 'all':
            for(i = 1; i <= num_rows;  i++) {
                pagesform.elements[i].checked = true;
            }
            break;

        case 'unproofed':
            changeSelection('clear');
            for(i = 1; i <= num_rows; i++) {
                pagesform.elements[avail_pages[i]].checked = true;
            }
            break;

        case 'invert':
            for(i = 1; i <= num_rows; i++) {
                pagesform.elements[i].checked = ! pagesform.elements[i].checked;
            }
            break;

        case 'clear':
            for(i = 1; i <= num_rows; i++) {
                pagesform.elements[i].checked = false;
            }
            break;

        default:
            break;
    }
}
</script>\n";

    echo "
        <br>
		<select name='select' 
            onChange='changeSelection(this.options[this.selectedIndex].value)'>
		<option value='0'>"._("Select...")."</option>
		<option value='all'>"._("Select all")."</option>
		<option value='unproofed'>"._("Select all unproofread")."</option>
		<option value='invert'>"._("Invert selection")."</option>
		<option value='clear'>"._("Clear selection")."</option>
		</select>"
		._("For each selected page:") . "
		<select name='operation'>
		  <option value='clear' >" . _("Clear effects of current round") . "</option>
		  <option value='delete'>" . _("Delete") . "</option>
		</select>
		<input type='submit' value='" . _("Go") . "'>
		</form>\n";

}

function echeck($projectid) {
    return "<input type='checkbox' name='chk[$projectid]' />";
}

function eimage($image) {
    global $Project;
    $size = $Project->ImageFileSize($image);
    return link_to_view_image($Project->ProjectId(), $image, $size);
}

function emaster($mastersize, $row) {
    $projectid = $row['projectid'];
    $pagename = $row['fileid'];
    return link_to_page_text($projectid, $pagename, $mastersize);
}

function ediff($diff, $row) {
    $projectid = $row['projectid'];
    $pagename = $row['fileid'];
    return $diff ? link_to_diff($projectid, $pagename, $roundid);
}
function edate($roundtime) {
    return $roundtime;
}
function roundtimefield($roundid) {
    switch($roundid) {
        case "P1":
            return "round1_time";
        case "P2":
            return "round2_time";
        case "P3":
            return "round3_time";
        case "F1":
            return "round4_time";
        case "F2":
            return "round5_time";
        default:
            return "";
    }
}
function roundtextfield($roundid) {
    switch($roundid) {
        case "P1":
            return "round1_text";
        case "P2":
            return "round2_text";
        case "P3":
            return "round3_text";
        case "F1":
            return "round4_text";
        case "F2":
            return "round5_text";
        default:
            return "";
    }
}
function rounduserfield($roundid) {
    switch($roundid) {
        case "P1":
            return "round1_user";
        case "P2":
            return "round2_user";
        case "P3":
            return "round3_user";
        case "F1":
            return "round4_user";
        case "F2":
            return "round5_user";
        default:
            return "";
    }
}

function euser($roundid, $row) {
    global $Project;
    global $User;
    $username = $User->Username();
    $rounduser = $row[rounduserfield($roundid)];

    if( $row["user1"] == $username || $row["user2"] == $username || $row["user3"] == $username || $row["user4"] == $username || $row["user5"] == $username
            || $Project->UserMayManage()) {
        return link_to_pm($rounduser);
    }
    else {
        return "";
    }
}
function etext($roundid, $row) {
    $projectid = $row['projectid'];
    $pagename = $row['fileid'];
    return link_to_page_text($projectid, $pagename, $roundid, $row['length_text_$roundid']);
}
function eclear($pagename) {
    $projectid = $row['projectid'];
    $pagename = $row['fileid'];
    return link_to_edit_pages($projectid, $pagename,
}
function eedit($pagename) {
    $projectid = $row['projectid'];
    $pagename = $row['fileid'];
    return link_to_proof_page($projectid, $pagename, "Edit");
}
function efix($pagename) {
    $projectid = $row['projectid'];
    $pagename = $row['fileid'];
    return link_to_fix($projectid, $pagename, "Fix");
}
function edelete($pagename) {
    $projectid = $row['projectid'];
    $pagename = $row['fileid'];
    return link_to_delete_page($projectid, $pagename, "Fix");
}



// -----------------------------------------------------------------------------

function table_sql($projectid) {
    return "
    SELECT  
        '$projectid' projectid,
        fileid AS pagename,
        image,
        state,
        LENGTH(master_text) OCR_length,
        LENGTH(round1_text) P1_length,
        LENGTH(round2_text) P2_length,
        LENGTH(round3_text) P3_length,
        LENGTH(round4_text) F1_length,
        LENGTH(round5_text) F2_length,

        round1_time P1_time,
        round2_time P2_time,
        round3_time P3_time,
        round4_time F1_time,
        round5_time F2_time,

        round1_user P1_user,
        round2_user P2_user,
        round3_user P3_user,
        round4_user F1_user,
        round5_user F2_user,

        urp1.page_count P1_page_count,
        urp2.page_count P2_page_count,
        urp3.page_count P3_page_count,
        urp4.page_count F1_page_count,
        urp5.page_count F2_page_count,

        BINARY master_text = round1_text P1_diff,
        BINARY round1_text = round2_text P2_diff,
        BINARY round2_text = round3_text P3_diff,
        BINARY round3_text = round4_text F1_diff,
        BINARY round4_text = round5_text F2_diff

        FROM $projectid p

        LEFT JOIN users u1 ON p.round1_user = u1.username
        LEFT JOIN users u2 ON p.round2_user = u2.username
        LEFT JOIN users u3 ON p.round3_user = u3.username
        LEFT JOIN users u4 ON p.round4_user = u4.username
        LEFT JOIN users u5 ON p.round5_user = u5.username

        LEFT JOIN total_user_round_pages urp1
        ON urp1.round_id = 'P1'
            AND urp1.username = round1_user

        LEFT JOIN total_user_round_pages urp2
        ON urp2.round_id = 'P2'
            AND urp2.username = round2_user

        LEFT JOIN total_user_round_pages urp3
        ON urp3.round_id = 'P3'
            AND urp3.username = round3_user

        LEFT JOIN total_user_round_pages urp4
        ON urp4.round_id = 'F1'
            AND urp4.username = round4_user

        LEFT JOIN total_user_round_pages urp5
        ON urp5.round_id = 'F2'
            AND urp5.username = round5_user

       ORDER BY image ASC
       ";
}

?>

