<?php

/*
    module to process an uploaded, zipped file.
    Accepts loadpath (from $_FILES) and a 
    DpProject object responsible for applying
    files after they are unzipped.
*/

function enumeratePaths($loadpath) {
    $zip = new ZipArchive();
    $zip->open($loadpath);
    for($i = 0 ; $i < $zip->numFiles ; $i++) {
        $path = $zip->getNameIndex($i);
        say($path);
    }
    $zip->close();
}

// if an upload, loadpath will be the browser-generated temp path

function UnzipProject($loadpath, $project) {
    // unzip to the project upload path which is 
    // (TEMP_DIR)/(projectid)/

    $targetpath = $project->LoadFilePath();

    // php ZipArchive object
    $zip = new ZipArchive();
   
    $b = $zip->open($loadpath);
    if($b) {
        for($i = 0; $i < $zip->numFiles; $i++) {
            $filepath = $zip->getNameIndex($i);
            $fileinfo = pathinfo($filepath);
            $basename = $fileinfo['basename'];
            $frompath = "zip://{$loadpath}#{$filepath}";
            $topath   = build_path($targetpath, $basename);
            copy($frompath, $topath);
        }                  
    }                  
    $zip->close();
    // unlink($loadpath);
}
?>
