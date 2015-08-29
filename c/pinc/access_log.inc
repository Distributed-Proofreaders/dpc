<?PHP

function log_access_change( $username, $modifier, $activity_id, $action_type ) {
    global $dpdb;
    $dpdb->SqlExecute(
    "INSERT INTO access_log
       SET
         timestamp = CURRENT_TIMESTAMP(),
         subject_username = '$username',
         modifier_username = '$modifier',
         action = '$action_type',
         activity = '$activity_id'");
}

function get_first_granted_date($username, $activity) {
    global $dpdb;
    return $dpdb->SqlOneValue("
             SELECT MIN(timestamp) FROM access_log
             WHERE subject_username = '$username'
                AND activity = '$activity'
                AND action = 'grant'");
}
?>
