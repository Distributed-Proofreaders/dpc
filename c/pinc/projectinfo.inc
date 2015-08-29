<?PHP

function Project_getNumPagesInState( $projectid, $page_state ) {
    global $dpdb;
    return $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM $projectid 
        WHERE state='$page_state'");
}

function Project_getNumPages( $projectid ) {
    global $dpdb;
    return $dpdb->SqlOneValue("
    SELECT COUNT(*) FROM $projectid");
}

// vim: sw=4 ts=4 expandtab
?>
