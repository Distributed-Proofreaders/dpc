<?PHP
global $relPath;
include_once($relPath.'dpinit.php');

    // If this is for real, send the message.
    // If we're testing, just report what would have been sent.
function maybe_mail( $to, $subject, $message, $additional_headers ) {
    mail( $to, $subject, $message, $additional_headers, "-r <dphelp@pgdpcanada.net>" );
}
