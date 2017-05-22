<?php
global $relPath;
include_once($relPath.'dpinit.php');

    // If this is for real, send the message.
    // If we're testing, just report what would have been sent.
function maybe_mail( $to, $subject, $message, $additional_headers ) {
    mail( $to, $subject, $message, $additional_headers, "-r <dphelp@pgdpcanada.net>" );
}
// $project is either a string containing a projectid,
// or an associative array containing various attributes of a project.
function maybe_mail_project_manager( $project, $info, $prefix) {
	global $code_url, $auto_email_addr;
    global $dpdb;

	if (is_string($project)) {
		$project = $dpdb->SqlOneRow("
			SELECT * FROM projects WHERE projectid='$project'");
	}

	$projectid  = $project['projectid'];
	$nameofwork = $project['nameofwork'];
	$username   = $project['username'];

	$body =
_("Hello")." $username,\n".
_("This is an automated message from the Distributed Proofreaders Canada site.")."

"._("Regarding").":
\"$nameofwork\"
  ($projectid)
  $code_url/project.php?id=$projectid

$info

"._("Thank You!")."
"._("Distributed Proofreaders Canada")."
";

	$email_addr = $dpdb->SqlOneValue("
		SELECT email FROM users WHERE username = '$username'");

	maybe_mail($email_addr, "$prefix: \"$nameofwork\"", $body,
		"From: $auto_email_addr\r\nReply-To: $auto_email_addr\r\n");
}
