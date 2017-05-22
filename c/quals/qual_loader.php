<?
/**
 * Created by PhpStorm.
 * User: don
 * Date: 7/7/2015
 * Time: 11:22 AM
 */
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once $relPath.'dpinit.php';

$qps = $dpdb->SqlObjects("
	SELECT id, projectid
	FROM qual_projects
	WHERE is_loaded = 0");


$n1 = $n2 = $n3 = 0;
foreach($qps as $qp) {
	$table = "X" . $qp->projectid;
	if ( ! $dpdb->IsTable( $table ) ) {
		$table = $qp->projectid;
	}
	$sql = "
		REPLACE INTO qual_pages (
              candidate,
              projectid,
              pagename,
              start_text,
              target_text,
              eval_text,
              state)
		SELECT NULL,
              p.id,
              pp.fileid,
              pp.round2_text,
              pp.round3_text,
              pp.round2_text,
              'T'
		FROM $table pp,
			qual_projects p
		WHERE p.projectid = '{$qp->projectid}'";
	$n   = $dpdb->SqlExecute( $sql );
	dump( "$qp->projectid  $n" );
}



