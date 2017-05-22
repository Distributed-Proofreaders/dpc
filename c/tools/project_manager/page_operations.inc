<?PHP
global $relPath;
include_once($relPath.'dpinit.php');
include_once($relPath.'project_states.inc');
include_once($relPath.'stages.inc');
//include_once($relPath.'DPage.inc');
include_once($relPath.'page_tally.inc');

// -----------------------------------------------------------------------------

/*
function page_del( $projectid, $fileid ) {
    global $dpdb;
    $image = $dpdb->SqlOneValue("
        SELECT image FROM $projectid
        WHERE fileid = '$fileid'");

    global $pguser;
    Page_delete( $projectid, $image, $pguser );

    // Delete the image file from the project directory.
    global $projects_dir;
    $image_path = "$projects_dir/$projectid/$image";
    if ( ! unlink($image_path) ) {
        return sprintf( _('Unable to remove file %s'), $image_path );
    }
}
*/

// -----------------------------------------------------------------------------

function page_clear( $projectid, $fileid ) {
    global $dpdb;
    // echo "page_clear( $projectid, $fileid )<br>\n";

    $projstate = $dpdb->SqlOneValue("
        SELECT state FROM projects WHERE projectid = '$projectid'");

    $round = get_Round_for_project_state($projstate);

    if (is_null($round)) {
        return _("The project is not in a state that allows pages to be cleared.");
    }

    $row = $dpdb->SqlOneRow("
        SELECT image, state, {$round->user_column_name} AS proofer
        FROM $projectid
        WHERE fileid = '$fileid'");

    $image = $row['image'];
    $page_state = $row['state'];
    if ( $page_state != $round->page_save_state ) {
        return _("The page is not in a state that allows it to be cleared.");
    }

    // ------------------------------------------------
    // page will be cleared, so decrement page tallies for user & teams

    // page_counts_add( $round->id, $proofer, -1 );

    // ------------------------------------------------
    // now clear the page

    global $pguser;
    Page_clearRound( $projectid, $image, $round, $pguser );
    return "";
}

?>
