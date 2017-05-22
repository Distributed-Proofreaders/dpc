<?php
/**
 * Created by PhpStorm.
 * User: don
 * Date: 7/3/2015
 * Time: 6:09 PM
 */

$relPath = "../pinc/";
require $relPath . "dpinit.php";

$sql = "SELECT projectid FROM projects
		WHERE phase IN ('F1', 'F2', 'PP')";

$projs = $dpdb->SqlValues($sql);

$i = 0;
foreach($projs as $projectid) {
	if(++$i > 20) {
		break;
	}
	$project = new DpProject( $projectid );
	$path    = build_path( $project->ProjectPath(), "diffs" );
	dump( $path );
	if ( ! is_dir( $path ) ) {
		mkdir( $path );
	}
	assert( is_dir( $path ) );
	$text1    = $project->RoundText( "P2" );
	$text2    = $project->RoundText( "P3" );
	$path2    = build_path( $path, "P2.txt" );
	$path3    = build_path( $path, "P3.txt" );
	$pathdiff = build_path( $path, "diffout.txt" );
	dump( $pathdiff );

	file_put_contents( $path2, $text1 );
	file_put_contents( $path3, $text2 );
	$cmd = "diff -b -B $path2 $path3 > $pathdiff";
	say( $cmd );
	exec( $cmd );
	assert( is_file( $pathdiff ) );
	dump( file_get_contents( $pathdiff ) );
}

exit;
