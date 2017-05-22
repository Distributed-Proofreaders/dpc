<?PHP
$relPath='../../pinc/';
include_once($relPath.'dpinit.php');

$pcheckout      = ArgArray("checkout");     // checkout to PP
$puncheckout    = ArgArray("uncheckout");   // return without upload
$pupload        = ArgArray("upload");       // complete with upload
$psetcomplete   = ArgArray("setcomplete");  // complete without upload
$upload_action  = Arg("upload_action");

$User->IsLoggedIn()
	or RedirectToLogin();

$error_message = "";

if(count($pcheckout) > 0) {
    foreach($pcheckout as $k => $v) {
        $p = new DpProject($k);
        $p->PPCheckout();
        break;
    }
}

if(count($puncheckout) > 0) {
    foreach($puncheckout as $k => $v) {
        $p = new DpProject($k);
        $p->PPUnCheckout();
        break;
    }
}

if(count($pupload) > 0) {
    $keys = array_keys($pupload);
    $projectid = $keys[0];
	$url = url_for_upload_pp($projectid);
    divert($url);
}

if(count($psetcomplete) > 0) {
    foreach($psetcomplete as $k => $v) {
        // projectid 'PP Complete'
        $p = new DpProject($k);
        /** @var DpProject $p */
        if($p->Phase() == "PP") {
            $msgs = $p->PPSetComplete();
        }
        else if($p->Phase() == "PPV") {
            $msgs = $p->PPVSetComplete();
        }
        else {
            $msgs = array(
                "Attempting to set PP or PPV complete while phase = {$p->Phase()}.");
            break;
        }
        if(count($msgs) > 0) {
            $msgs[] = "PP Completion failed";
            $error_message = implode("<br>", $msgs);
            die($error_message);
        }
    }
}


if ( $User->IsSiteManager() || $User->IsProjectFacilitator() ) {
    $username = Arg("username", $User->Username() );
}
else {
    $username = $User->Username();
}

$no_stats = 1;
theme( _("My Projects"), 'header');

/*
$smoothies = $dpdb->SqlRows("
    SELECT
        sr.username,
        sr.projectid,
        p.phase,
        p.nameofwork,
        p.authorsname,
        p.username,
        p.postproofer,
        p.smoothread_deadline,
        p.n_pages
    FROM
        smoothread sr
        LEFT JOIN projects p
            ON sr.projectid = p.projectid
    WHERE phase IN ('PREP', 'P1', 'P2', 'P3', 'F1', 'F2', PP')
        AND p.smoothread_deadline > UNIX_TIMESTAMP()
        AND username = '$username'");

if(count($smoothies) > 0) {
    $tblsmooth = new DpTable("tblsmooth", "dptable", "Projects you volunteered to Smooth Read");
    $tblsmooth->AddColumn("<Title", "nameofwork");
    $tblsmooth->AddColumn("<Author", "authorsname");
    $tblsmooth->AddColumn("^<PM", "username");
    $tblsmooth->AddColumn("^<PPer", "postproofer");
    $tblsmooth->SetRows($smoothies);
}
*/


if ( $username == $User->Username() ) {
    $heading_proof = _("My Projects");
    $open_title = _("I have pages checked out for proofing in the following projects");
    $open_heading = _("Projects I've helped format and/or proof");
    $heading_reserved =  _("Projects reserved for me to post-process");
}
else {
    $open_title = _("$username has pages checked out for proofing in the following projects");
    $open_heading = _("Projects $username has helped format and/or proof");
    $heading_reserved =  _("Projects reserved for $username to post-process");
}




// --------------------------------------------------------------

echo link_to_my_diffs("P1", "My diffs", true);

if($User->IsSiteManager() || $User->IsProjectFacilitator()) {
	echo "
	<form name='frmuser' id='frmuser'>
	<div class='left'>
		<label> Username:
		<input type='text' id='username' name='username' value='{$username}'>
		</label>
		<input type='submit' value='submit'>
	</div>
	</form>\n";
}

$rows = open_page_counts($username);
if(count($rows) > 0) {
    echo "
    <div class='padded bordered margined w75'>
    <h3 class='center'>" . _("My Open Proofing Pages") . "</h3>
    <p class='center'>$open_title</p>\n";

    $tbl = new DpTable();
    $tbl->SetClass("dptable sortable w90");
    $tbl->AddColumn("<Title", "nameofwork", "etitle");
    $tbl->AddColumn("^Round", "phase", "ephase");
    $tbl->AddColumn("^Pages", "pagecount");
    $tbl->SetRows($rows);
    $tbl->EchoTable();

    echo "</div>\n";
}


echo_my_pp_projects($username);

echo_open_proofing_projects($username, $open_heading);

theme( '', 'footer' );
exit;

function eephase($phase, $row) {
	switch($phase) {
		case "P1":
		case "P2":
		case "P3":
		case "F1":
		case "F2":
			return "$phase " . ($row["is_hold"] > 0 ? "On Hold" : "");
		default:
			return $phase;
	}
}

function open_page_counts($username) {
    global $dpdb;
    $sql = "
            SELECT p.nameofwork,
                   pv.phase,
                   pv.projectid,
                   COUNT(1) pagecount
            FROM  page_last_versions pv
            JOIN  projects p
            ON pv.projectid = p.projectid
            WHERE pv.username = '$username'
                AND pv.state = 'O'
            GROUP BY pv.projectid
            ORDER BY pv.phase, p.nameofwork";


    $rows = $dpdb->SqlRows($sql);

	return $rows;
}

function etitle($nameofwork, $row) {
    $title = $nameofwork;
    return link_to_project($row['projectid'], $title, true);
}


function ephase($phase) {
    return $phase;
}

function ePM($pm) {
    return link_to_pm($pm, $pm, true);
}

function eLastTime($ts) {
    return $ts;
}

function echo_open_proofing_projects($username, $heading) {
    global $dpdb;
    $tbl = new DpTable();

    $tbl->SetClass("dptable sortable w90");

    $tbl->SetId("tbl_my_projects");
    $tbl->AddColumn("<Title", "nameofwork", "eTitle");
    $tbl->AddColumn("<Current state", "phase", "eephase", "sortkey=roundseq");
    $tbl->AddColumn("<Worked in", "round_id");
    $tbl->AddColumn("<Last activity", "max_time", "eLastTime", "sortkey=strtime");


    $sql = "
        SELECT  pv.projectid,
                GROUP_CONCAT(DISTINCT pv.phase
                    ORDER BY ph.sequence
                    SEPARATOR ', ') round_id,
                pph.sequence roundseq,
                DATE_FORMAT(MAX(FROM_UNIXTIME(pv.version_time)), '%b&nbsp;%d,&nbsp;%Y') AS max_time,
                MAX(FROM_UNIXTIME(pv.version_time)) AS strtime,
                p.nameofwork,
                p.username,
                p.phase,
                p.state,
                MIN(h.id) is_hold
        FROM page_versions pv
        JOIN projects p ON pv.projectid = p.projectid
        JOIN phases ph ON pv.phase = ph.phase
        JOIN phases pph ON p.phase = pph.phase
        LEFT JOIN project_holds h ON p.projectid = h.projectid AND p.phase = h.phase
        WHERE pv.username='$username'
            AND pv.phase IN ('P1', 'P2', 'P3', 'F1', 'F2')
        GROUP BY pv.projectid
        ORDER BY strtime DESC
        ";

    $rows = $dpdb->SqlRows($sql);

    $tbl->SetRows($rows);


    echo "<div class='padded bordered margined w75'>\n";
    echo "<h3 class='center'>$heading</h3>\n";
    $tbl->EchoTable();
    echo "</div>\n";
}

function echo_my_pp_projects($username) {
    global $dpdb;

    $rows = $dpdb->SqlRows("
        SELECT
            projectid,
            nameofwork,
            authorsname,
            language,
            seclanguage,
            l1.name langname,
            l2.name seclangname,
            genre,
            n_pages,
            username AS pm,
            IFNULL(DATEDIFF(FROM_UNIXTIME(smoothread_deadline), CURRENT_DATE()), -1) AS smooth_days,
            DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(phase_change_date)) AS days_avail
        FROM projects p
        LEFT JOIN languages l1 ON p.language = l1.code
        LEFT JOIN languages l2 ON p.seclanguage = l2.code
        WHERE phase = 'PP'
            AND postproofer = '$username'
        ORDER BY days_avail");

    if(count($rows) == 0) {
        return;
    }

    echo "
    <div class='w75 padded bordered margined'>
        <h3 class='center'>Post-Processing (PPing) Projects</h3>
        <ul class='clean left pct80'>
            <li>Іn order to complete this task,</li>
            <li><ol>
                <li> upload a zip file with the completed project, and</li>
                <li> click the 'PP Complete' button,</li>
                <li> wait until the smooth-reading period is over, if there is one open.</li>
            </ol></li>
            <li>The 'Uploaded file' column shows the status of that file, with a button to click if
            you want to Upload. (It still works after uploading if you want to resend.)</li>
            <li>The project will advance to PPV when smooth-reading periods are completed, and you click
            the 'PP Completed' button.</li>
        </ul>

    <form name='myform' method='POST'>\n";

    $tbl = new DpTable();
    $tbl->SetClass("w90 dptable sortable");
    $tbl->AddColumn("<Title", "nameofwork", "etitle");
    $tbl->AddColumn("<Author", "authorsname");
    $tbl->AddColumn("<Language", "langname", "elangname");
    $tbl->AddColumn("<Genre", "genre");
    $tbl->AddColumn("^Pages", "n_pages");
    $tbl->AddColumn("<Proj mgr", "pm", "euser");
    $tbl->AddColumn("^Days", "days_avail", "edays");
    $tbl->AddColumn("^Smooth<br>days", "smooth_days", "esmooth");
    $tbl->AddColumn("^Uploaded<br>file", "projectid", "eupload");
    $tbl->AddColumn("^Manage", "projectid", "emanage");
    $tbl->SetRows($rows);

    $tbl->EchoTable();
    echo "</form>
    <p><a class='lfloat' href='/c/tools/pper.php'>See all your PP projects.</a></p>
    </div>\n";
}

function emanage($projectid, $row) {
    $color =  is_pp_upload_file($projectid) ? "lightGreen" : "inherit" ;
    $disabled = is_pp_upload_file($projectid) && $row['smooth_days'] < 0 ? "" : " disabled";
    $caption = is_pp_upload_file($projectid)
        ? ( $row["smooth_days"] < 0
                ? "PP Complete"
                : "Smoothing" )
        : "No Workfile";
    return "
        <input name='uncheckout[$projectid]' type='submit' value='Return to Avail'>
        <br/>
        <input name='setcomplete[$projectid]' type='submit'
            style='background-color: $color;' value='$caption' $disabled>\n";
}

function is_pp_upload_file($projectid) {
    return file_exists(ProjectPPUploadPath($projectid));
}

function esmooth($num) {
    return $num < 0 ? "" : edays($num);
}
function edays($num) {
    return number_format($num);
}
function euser($username) {
    return link_to_pm($username, $username, true);
}

function elangname($langname, $row) {
	return $langname
	       . ($row['seclangname'] == "" ? "" : "/" . $row['seclangname']);
}

function eupload($projectid) {
    $caption = is_pp_upload_file($projectid) ? "Replace" : "Upload";
    // $color =  is_pp_upload_file($projectid) ? "inherit" : "lightGreen" ;
    $color =  "lightGreen" ;
    return "<input type='submit' name='upload[$projectid]' 
        style='background-color: $color;' value='$caption'>\n";
}

