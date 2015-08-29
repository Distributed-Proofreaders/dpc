<?PHP

function build_selectlist( $states, $field) {
    global $dpdb;
	$to_echo = "";
	$values = $dpdb->SqlValues("
		SELECT distinct $field FROM projects
        WHERE $states
        ORDER BY $field");

    foreach($values as $val) {
	    $to_echo .= "<option value='$val'>$val</option\n";
	}
    return $to_echo;
}

?>
