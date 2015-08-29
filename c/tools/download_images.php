<?php

// Download a generated-on-demand zip of the
// image files in a given project directory.

$relPath='../pinc/';
include_once($relPath.'dpinit.php');

$projectid = ArgProjectid();

// $projectid = array_get( $_GET, 'projectid', '' );

if (! ($projectid)) {
    echo "download_images.php: missing or empty 'projectid' parameter.";
    exit;
}

// build images zip url and filename
$zipfile_path = "$dyn_dir/download_tmp/{$projectid}_images.zip";
$zipfile_url  = "$dyn_url/download_tmp/{$projectid}_images.zip";

/*
// if the images zip file exists, redirect to download it.
if (file_exists($zipfile_path)) {
    header( "Location: $zipfile_url" );
    exit;
}
*/

$projectpath = "$projects_dir/$projectid";

if (!is_dir($projectpath)) {
    echo "download_images.php: no project directory named '$projectid'.";
    exit;
}

mkdir_recursive( dirname($zipfile_path), 0777 );

$output = array();
$return_code = null;

// zip up the jpg and png files
$cmd = "zip -q -j $zipfile_path $projectpath/*.png $projectpath/*.jpg";
exec( $cmd, $output, $return_code );

var_dump($output);
var_dump($return_code);

if ($return_code != 0) {
    echo "download_images.php: the zip command failed.";
    exit;
}

header( "Location: $zipfile_url" );

// vim: sw=4 ts=4 expandtab
?>
