<?php
ini_set("display_errors", true);

error_reporting(E_ALL);

$relPath = "./../../pinc/";
include_once($relPath.'dpinit.php');
//include_once('edit_common.inc');

$page_title = _("Create a Project");
theme($page_title, "header");


$User->MayCreateProjects()
    or die('permission denied');
$username = $User->Username();

$saveAndQuit    = IsArg("saveAndQuit");
$saveAndEdit    = IsArg("saveAndEdit");
$saveAndLoad    = IsArg("saveAndLoad");
$quit           = IsArg("quit");

$errormsg       = "";

if($quit) {
	theme("", "footer");
    divert(url_for_activity_hub());
    exit;
}


$cp         = Arg("cp", $username);
$title      = Arg("title");
$author     = Arg("author");
$projectmgr = Arg("projectmgr", $User->Username());
$projecttype= Arg("projecttype", "normal");

for( $i = 0; $i < 1 ; $i++ ) {
    if(! $saveAndQuit && ! $saveAndEdit && ! $saveAndLoad) {
        break;
    }
        
    if(! $title || ! $author) {
        break;
    }

	if(! $Context->UserExists($projectmgr)) {
		$errmsg = "<br>$projectmgr is not a user.";
		$projectmgr = $User->Username();
		break;
	}

    $projectid = DpProject::CreateProject($title, $author, $projectmgr, $cp, $projecttype);

    if( $saveAndQuit ) {
	    theme("", "footer");
        divert(url_for_project_manager());
        exit;
    }

    if ( $saveAndEdit ) {
	    theme("", "footer");
        divert(url_for_edit_project($projectid));
        exit;
    }

    if($saveAndLoad) {
	    theme("", "footer");
        divert(url_for_project_files($projectid));
        exit;
    }
    break;
}



echo "
    <br>
    <h1 class='center'>$page_title</h1>
    <div class='left'>
    <form method='POST' action='' accept-charset='UTF-8'>
    <table class='bordered w90 center'>
    ";
echo "
    <tr><td class='left cccccc padded'>Title</td>
        <td><input type='text' value='$title' name='title' size='67'></td></tr>
    <tr><td class='left cccccc padded'>Author</td>
        <td><input type='text' value='$author' name='author' size='67'></td></tr>
    <tr>
    <tr><td class='left cccccc padded'>Project Manager<br>(Remove your name if not you)</td>
        <td><input type='text' value='$projectmgr' name='projectmgr' size='67'>$errormsg</td></tr>
    <tr>
        <td class='left cccccc padded'>Project Type</td>
        <td>
            <select name='projecttype' id='projecttype'>
                <option value='normal' selected='selected'>Normal</option>
                <option value='harvest'>Harvest</option>
                <option value='recovery'>Recovery</option>
            </select>
        </td>
    </tr>
    <tr>
    <td class='cccccc center' colspan='2'>
    <input type='submit' name='saveAndQuit' value='"._("Save and Quit")."'>
    <input type='submit' name='saveAndEdit' value='"._("Save and Edit")."'>
    <input type='submit' name='saveAndLoad' value='"._("Save and Load")."'>
    <input type='submit' name='quit' value='"._("Quit without saving")."'>
    </td>
    </tr>
    </table>
    </form>
    </div>\n";

theme("", "footer");
exit;

// vim: sw=4 ts=4 expandtab

