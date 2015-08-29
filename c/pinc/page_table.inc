<?PHP
function echo_page_table($project, $pgsel = null) {
    /** @var DpProject $project */
    global $dpdb;
    assert($pgsel == null);
    $projectid = $project->ProjectId();
    $phaseindex = $project->PhaseIndex();
    $phase = $project->Phase();
    $rows = $dpdb->SqlRows("
        SELECT  '$projectid' projectid,
                $phaseindex phaseindex,
                '$phase' phase,
                fileid,
                image,
                master_text,
                round1_text,
                round2_text,
                round3_text,
                round4_text,
                round5_text,
                round1_user,
                round2_user,
                round3_user,
                round4_user,
                round5_user,
                FROM_UNIXTIME(round1_time) round1_time,
                FROM_UNIXTIME(round2_time) round2_time,
                FROM_UNIXTIME(round3_time) round3_time,
                FROM_UNIXTIME(round4_time) round4_time,
                FROM_UNIXTIME(round5_time) round5_time,
                LENGTH(master_text) OCR_text_length,
                LENGTH(round1_text) round1_text_length,
                LENGTH(round2_text) round2_text_length,
                LENGTH(round3_text) round3_text_length,
                LENGTH(round4_text) round4_text_length,
                LENGTH(round5_text) round5_text_length,
                CASE WHEN BINARY master_text = BINARY round1_text
                    THEN 0 ELSE 1 END AS is_diff_1,
                CASE WHEN BINARY round1_text = BINARY round2_text
                    THEN 0 ELSE 1 END AS is_diff_2,
                CASE WHEN BINARY round2_text = BINARY round3_text
                    THEN 0 ELSE 1 END AS is_diff_3,
                CASE WHEN BINARY round3_text = BINARY round4_text
                    THEN 0 ELSE 1 END AS is_diff_4,
                CASE WHEN BINARY round4_text = BINARY round5_text
                    THEN 0 ELSE 1 END AS is_diff_5,
                state
        FROM $projectid
        ORDER BY fileid");
    $tbl = new DpTable();

    if($project->UserMayManage()) {
        $tbl->AddCaption(null, 4);  // leave one for the rownumber?
        $tbl->AddColumn(chk_caption(), null, "rowchkbox", "width: 4em");
    }
    else {
        $tbl->AddCaption(null, 2); 
    }

    $tbl->AddCaption(null, 4);  // leave one for the rownumber?
    $tbl->AddColumn("^Image", "fileid", "eimage");
    $tbl->AddColumn(">Text", "textlength_m", "mtext");
    $tbl->AddColumn("^State", "state", "estate", "w4em");

    if($phaseindex > 0) {
        $tbl->AddCaption("^".$phaseindex, 4);

        $tbl->AddColumn("^Diff?", "is_diff_1", "ediff");
        $tbl->AddColumn("^Date", "round1_time", "etime");
        $tbl->AddColumn("^User", "round1_user", "euser");
        $tbl->AddColumn("^Text", "round1", "etext");
    }
    if($phaseindex > 1) {
        $tbl->AddCaption("^".$phaseindex, 4);

        $tbl->AddColumn("^Diff", "is_diff_2", "ediff");
        $tbl->AddColumn("^Date", "round2_time", "etime");
        $tbl->AddColumn("^User", "round2_user", "euser");
        $tbl->AddColumn("^Text", "round2", "etext");
    }
    if($phaseindex > 2) {
        $tbl->AddCaption("^".$phaseindex, 4);

        $tbl->AddColumn("^Diff", "is_diff_3", "ediff");
        $tbl->AddColumn("^Date", "round3_time", "etime");
        $tbl->AddColumn("^User", "round3_user", "euser");
        $tbl->AddColumn("^Text", "round3", "etext");
    }
    if($phaseindex > 3) {
        $tbl->AddCaption("^".$phaseindex, 4);

        $tbl->AddColumn("^Diff", "is_diff_4", "ediff");
        $tbl->AddColumn("^Date", "round4_time", "etime");
        $tbl->AddColumn("^User", "round4_user", "euser");
        $tbl->AddColumn("^Text", "round4", "etext");
    }
    if($phaseindex > 4) {
        $tbl->AddCaption("^".$phaseindex, 3);

        $tbl->AddColumn("^Date", "round5_time", "etime");
        $tbl->AddColumn("^User", "round5_user", "euser");
        $tbl->AddColumn("^Text", "round4", "etext");
    }
    $tbl->AddColumn("^Clear", "fileid", "eclear");
    $tbl->AddColumn("^Edit", "fileid", "eedit");
    $tbl->AddColumn("^Bad<br>Fix", "fileid", "efix");
    $tbl->AddColumn("^Delete", "fileid", "edelete");

    $tbl->SetRows($rows);
    $tbl->EchoTableNumbered();
}

function chk_caption() {
    return "^All<br><input type='checkbox' name='ckall'
                onclick='CheckAll();'>";
}
function rowchkbox($row) {
    $name = 'imagefile['.$row['image'].']';
    return "<input type='checkbox' name='$name'>";
}
function eimage($pagename, $row) {
    $projectid  = $row['projectid'];
    $imagefile  = $row['image'];

    return $pagename != ""
        ? link_to_view_image($projectid, $pagename, $pagename, true)
        : "<span class='danger'>$imagefile</span>";
}

function enumber($num, $row) {
    $projectid  = $row['projectid'];
    $pagename  = $row['fileid'];
    return link_to_page_text($projectid, $pagename, number_format($num), true);
}

function mtext($size, $row) {
    $projectid = $row["projectid"];
    $pagename  = $row["fileid"];
    $phase = "OCR";
    return link_to_page_text($projectid, $pagename, $phase, $size, true);
}

function etext($pfx, $row) {
    global $User;
    $projectid = $row["projectid"];
    $pagename  = $row["fileid"];
    $phase     = $row["phase"];
    $userval   = $row["{$pfx}_user"];
    $size      = $row["{$pfx}_text_length"];
    if($userval == $User->Username()) {
        return "<div class='redbox'>"
                .link_to_page_text($projectid, $pagename, $phase, $size, true)
                ."</div>\n";
    }
    return link_to_page_text($projectid, $pagename, $phase, $size, true);
}
function estate($state) {
    return preg_replace("/^.*_/", "", $state);
}
function ediff($pagename, $row) {
    $projectid = $row["projectid"];
    $phase = $row["phase"];
    return link_to_diff($projectid, $pagename, $phase, "diff", true);
}
function etime($time) {
    return $time == 0 ? "" : $time;
}
function euser($proofer) {
    global $User;
    $username = $User->Username();
    if($proofer == "") {
        return "";
    }
    return $proofer == $username
         ? "<div class='mypagedone'>$proofer</div>"
         : link_to_pm($proofer, $proofer, true);
}

function eclear($pagename) {
    return "<input type'submit' name='clear[$pagename]' value='Clear'>\n";
}
function eedit($pagename, $row) {
    $projectid = $row["projectid"];
    return link_to_proof_page($projectid, $pagename, "Edit", true); 
}
function efix($pagename, $row) {
    $projectid = $row["projectid"];
    return link_to_fix($projectid, $pagename, true); 
}
function edelete($pagename) {
    return "<input type'submit' name='delete[{$pagename}]' value='Delete'>\n";
}

