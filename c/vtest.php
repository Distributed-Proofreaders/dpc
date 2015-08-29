<?PHP
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
require $relPath . "dpinit.php";

$projs = $dpdb->SqlRows("
	SELECT projectid, nameofwork, phase
	FROM projects
	");

foreach($projs as $proj) {
	$projectid = $proj['projectid'];
	$phase = $proj['phase'];
	$title = $proj['nameofwork'];

	echo "<br><br>$projectid  $phase  $title";

	$pgs = $dpdb->SqlRows("
		SELECT projectid, pagename, COUNT(1)
		FROM page_versions
		WHERE projectid = '$projectid'
		GROUP BY pagename
		ORDER BY pagename
		");
	$nmismatch = $nmatch = 0;
	foreach($pgs as $pg) {
		$pagename = $pg['pagename'];
		$versions = $dpdb->SqlRows("
			SELECT projectid, pagename, version, state
			FROM page_versions
			WHERE projectid = '$projectid'
				AND pagename = '$pagename'
			ORDER BY pagename, version
		");

		$vsndir   = ProjectPagePath( $projectid, $pagename );
		$ptn = build_path( $vsndir, $pagename . ",*" );
		$fversions = glob( $ptn );

		if ( count( $fversions ) != count( $versions ) ) {
//			echo "<br>$pagename nfiles: " . count( $fversions ) . "   nversions: " . count( $versions );
			$nmismatch++;
		}
		else {
			$nmatch++;
		}
	}
	say("$projectid:  nmatches: " . $nmatch . "   mismatches: " . $nmismatch);

/*
	foreach($versions as $vsn) {
		$pagename = $vsn['pagename'];
		$version  = $vsn['version'];
		$state    = $vsn['state'];
	}
	$path = PageVersionPath($projectid, $pagename, $version);
	if(! file_exists($path)) {
		dump("$projectid $pagename $version");
	}

	$a = array();
	$states = array();
	foreach($states as $state => $n) {
		$states[$state] = isset($states[$state]) ? $states[$state] + 1 : 1 ;
		$a[] = "$state:$n";
	}
	dump("$projectid $phase   " . implode($a, ", "));
*/
}


