<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath = "../../pinc/";
require_once $relPath . "dpinit.php";

$projectid      = Arg("projectid");
$selected_pages = ArgArray("chk");
$submit_clear   = ArgArray("submit_clear");
$submit_delete  = ArgArray("submit_delete");
$operation      = Arg("operation");

// -----------------------------------------------------------------------------
// Check for required parameters.

if($projectid == "") {
    die("Argument 'projectid' is required.");
}

$project = new DpProject( $projectid );

if(! $project->UserMayManage()) {
    die("Security violation.");
}

// pages selected to be cleared
if(count($submit_clear) > 0) {
    $aclear = array_keys($submit_clear);
	$project->ClearPages($aclear);
    divert(url_for_project_level($projectid, 4));
    exit;
}

else if(count($submit_delete) > 0) {
    $adelete = array_keys($submit_delete);
    $project->DeletePages($adelete);
    divert(url_for_project_level($projectid, 4));
    exit;
}

// -----------------------------------------------------------------------------
// Check the set of selected pages.

//else {
//    echo _("You did not select any pages.") ;
//    theme("","footer");
//    exit;
//}
//
// -----------------------------------------------------------------------------
// Check the requested operation.

foreach ( $selected_pages as $pagename => $setting ) {
    $page = new DpPage($projectid, $pagename);

    if($operation == "clear") {
        $page->Clear();
        echo "Page $pagename cleared.<br>\n";
    }
    else if($operation == "delete") {
        $page->Delete();
        echo "Page $pagename deleted.<br>\n";
    }
}
echo link_to_project($projectid, "Return to project");

$no_stats = 1;
theme( _("Edit Pages Confirmation"), "header");
theme("","footer");

// vim: sw=4 ts=4 expandtab
?>
