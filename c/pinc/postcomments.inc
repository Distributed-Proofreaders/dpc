<?PHP

// Return the PP-ers' comments for a certain project
// They will be HTML-encoded and with line breaks
// converted to <br/> .
function get_formatted_postcomments( $projectid ) {
    global $dpdb;
    $postcomments = $dpdb->SqlOneValue("
      SELECT postcomments FROM projects WHERE projectid='$projectid'");

  return htmlspecialcharswithnewlines($postcomments);
}

function htmlspecialcharswithnewlines($string) {
  $string = htmlspecialchars($string);
  $string = str_replace("\n", "<br />", $string);
  return $string;
}

?>
