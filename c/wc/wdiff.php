<?php
$relPath="../pinc/";
require_once $relPath.'dpinit.php';
require_once "word_freq_table.php";

$projectid = ArgProjectId();
$project = new DpProject($projectid);
$langcode  = ArgLangCode($project->LanguageCode());
$format    = Arg("format");

$project->UserMayManage()
    or die("Unauthorized access");


theme_header($project->TitleAuthor());


$wdiff_output = Wdiff($project);
echo "<pre>\n";
echo $wdiff_output ;
echo "</pre>\n";
theme_footer();

exit;


function wdiff($project)
{
    file_put_contents(
        $project->OCRTextFilePath(), $project->OCRText());
    file_put_contents(
        $project->ActiveTextFilePath(), $project->ActiveText());

    $cmd = "wdiff -3 "
          ."{$project->OCRTextFilePath()} "
          ."{$project->ActiveTextFilePath()}";

    return shell_exec($cmd);
}


// vim: sw=4 ts=4 expandtab
?>
