<?PHP
// A file for (UI) code that's used by both user and team stats pages.

// -----------------------------------------------------------------------------

// Return a snippet of HTML that visually conveys
// the change in rank between $previous_rank and $current_rank.
function showChangeInRank( $previous_rank, $current_rank ) {
    global $code_url;
    if ( ! $current_rank || ! $previous_rank || $current_rank == $previous_rank ) {
        return "&nbsp;";
    }

//    $color = $current_rank < $previous_rank ? "green" : "red";
    $arrow = $current_rank < $previous_rank ? "up"    : "down";
//    $sign  = $current_rank < $previous_rank ? "+"     : "-";
    $abs   = abs($current_rank - $previous_rank);

    return " (<img src='{$code_url}/graphics/$arrow.gif' alt=''> ".number_format($abs).")\n"; 
}

// vim: sw=4 ts=4 expandtab
?>
