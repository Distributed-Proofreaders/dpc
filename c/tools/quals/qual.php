<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="../../pinc/";
include_once( $relPath . 'dpinit.php' );

// -----------------------------------------------------------------------------

$no_stats = 1;
theme("Qual: Qual Project Preparation", "header");
?>

<h2 class='red center'>Warning: Under Construction</h2>

<h4 class='center'>What happens in this stage:</h4>

<p>These projects have been constructed for use in P3 qualification testing.
When they are Available, proofers will be able to choose them for proofing.</p>

<p>Qual projects show up on one of three lists below.</p>

<ul>
<li>All Qual projects for all phases.</li>
<li>Project clones currently available for proofing, assigned to specific proofers.</li>
<li>Project clones completed and waiting for review (or being reviewed.)</li>
</ul>

<h2>Qual Projects</h2>

<?php
echo_qual_projects();
?>

<h2>Projects currently being proofed</h2>

<?php
echo_qual_active();
?>

<h2>Projects completed and ready to be reviewed</h2>

<?php
echo_qual_complete();


theme("", "footer");
exit;

function echo_qual_projects() {
    global $dpdb;

    $rows = $dpdb->SqlRows("
		SELECT
			qp.projectid,
			qp.nameofwork,
			qp.authorsname,
			qp.phase,
			qp.state,
            IFNULL(SUM(qpn.id > 0), 0) n_pages
		FROM qual_projects AS qp
		LEFT JOIN qual_page_versions qpn
			ON qp.projectid = qpn.qual_projectid
			AND qpn.username = 'DEFAULT'
	    GROUP BY qp.projectid
        ORDER BY qp.phase, qp.state, qp.nameofwork");


    $tbl = new DpTable("tblqual", "dptable lfloat clear");
	$tbl->AddColumn("^", "phase");
    $tbl->AddColumn("<Title", "nameofwork", "etitle");
    $tbl->AddColumn("^Pages", "n_pages", "enpages");
    $tbl->SetRows($rows);

    echo "
    <div class='w100'>
    <div class='lfloat w30'>
    <form id='frmprop' target='' method='POST' name='frmprop'>\n";
    $tbl->EchoTable();
    echo "</form>
    </div>
    <pre id='pretext'>Text</pre>
    <div class='lfloat w30'>
    </div>
    <img id='pgimage' class='lfloat w100' src=''/>
    <div class='lfloat w30'>
    </div>
    </div>\n";
}
function echo_qual_active() {
	global $dpdb;
	$sql = "SELECT qc.phase,
				qc.qual_projectid,
				qc.username,
				qc.createdate
			FROM qual_clones qc
			JOIN qual_projects qp
				ON qc.qual_projectid = qp.projectid
			WHERE qc.state = 'A'
			ORDER by qc.phase, qc.createdate";

		$rows = $dpdb->SqlRows($sql);
		$tbl = new DpTable();
		$tbl->SetRows($rows);
		$tbl->EchoTable();


}
function echo_qual_complete() {
	global $dpdb;
	$sql = "SELECT qc.phase,
				qc.qual_projectid,
				qc.username,
				qc.createdate
			FROM qual_clones qc
			JOIN qual_projects qp
				ON qc.qual_projectid = qp.projectid
			WHERE qc.state = 'C'
			ORDER by qc.phase, qc.createdate";
	$rows = $dpdb->SqlRows($sql);
	$tbl = new DpTable();
	$tbl->SetRows($rows);

	echo_head("Qual Projects");
	$tbl->EchoTable();
}


function url_for_qual_project($projectid) {
	global $code_url;
	return $code_url . "/tools/qualpages.php?projectid={$projectid}";
}

function link_to_qual_project($projectid, $caption) {
	return link_to_url(url_for_qual_project($projectid), $caption) ;
}

function etitle($title, $row) {
    // $title = maybe_convert($title);
    $projectid = $row['projectid'];
    return link_to_qual_project($projectid, $title);
}

function epm($pm) {
    return $pm == ""
        ? "<span class='red'>--</span>\n"
        : link_to_pm($pm);
}

function eclearance($clearance) {
    return $clearance == ""
        ? "<span class='red'>--</span>\n"
        : $clearance;
}

function enpages($npages) {
    return $npages > 0
        ? $npages
        : "<span class='red'>0</span>\n";

}

// vim: sw=4 ts=4 expandtab

