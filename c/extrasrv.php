<?php

/*
 * Download the so-called extra files found under the project directory.
 * This is the target of the ``Download extra files'' link on the project
 * page. Currently it downloads only the files, no directories, and no
 * zip files.
 */
$relPath = "./pinc/";
require_once "pinc/dpinit.php";

$User->IsLoggedIn()
    or redirect_to_home();

$projectid = ArgProjectid();

if (! ($projectid)) {
    echo "extrasrv.php: missing or empty 'projectid' parameter.";
    exit;
}

$project = new DpProject($projectid);
$zipname = "{$projectid}_extras";
$zippaths = $project->ExtraFilePaths();
if (empty($zippaths)) {
    echo "No extra files found.<br>
        Download extra files only includes files directly under the project
        directory, excluding sub-directories and zip files.<br>";
    exit;
}

/*
 * Can't figure out how to terminate the file transfer to actually
 * show the browser our error message.
 *
 * However, the only reason we have been getting memory errors, is for
 * whatever reason buffering is turned on by default.  Turning off
 * buffering with ob_end_clean(), means it isn't going to get an
 * out of memory error.  Which is now done in helpers.php, send_file
 *
set_error_handler(function($code, $string, $file, $line) {
    error_log("error_handler");
    error_log("error_handler: " . $code);
    throw new ErrorException($string, null, $code, $file, $line);
});

register_shutdown_function(function() {
    error_log("Shutdown function");
    $error = error_get_last();
    error_log("Shutdown function" . $error['message']);
    if ($error !== null) {
        header_remove();
        ob_clean();
        flush();
        die("Fatal error: " . $error['message']);
    }
});*/

try {
    $Context->ZipSendFileArray($zipname, $zippaths);
} catch (Exception $e) {
    die("Caught exception: " . $e->getMessage() . "\n");
}
