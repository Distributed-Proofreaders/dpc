<?PHP

function log_project_event( $projectid, $who, $event_type, $details1 = '', $details2 = '' ) {
    global $dpdb;
    // $esc_details1 = mysql_escape_string($details1);
    // $esc_details2 = mysql_escape_string($details2);

    $sql = "
        INSERT INTO project_events
        SET timestamp  = UNIX_TIMESTAMP(),
            projectid  = '$projectid',
            who        = '$who',
            event_type = '$event_type',
            details1   = '$details1',
            details2   = '$details2'";
    return $dpdb->SqlExecute($sql) == 1 ? "" : "Proj log failed"  ;
}

// vim: sw=4 ts=4 expandtab
?>
