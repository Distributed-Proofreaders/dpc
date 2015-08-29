<?PHP
// The following variables must be defined by the file that includes this one:
// $relPath
// $ppage
// $userP

include_once($relPath.'http_headers.inc');
include_once($relPath.'slim_header.inc');

$userP = $User->UserP();

if($User->IsVerticalLayout()) {
    $rows_or_cols = "COLS='*,{$userP['v_tframe']}%'";
    $text_scrolling = ( 
        $userP['v_tscroll'] == 0 
            ? "SCROLLING='no'"
            : "" );
}
else {
    $rows_or_cols = "ROWS='*,{$userP['h_tframe']}%'";
    $text_scrolling = ( 
        $userP['h_tscroll'] == 0 
            ? "SCROLLING='no'"
            : "" );
}

$imgurl = "image_frame_std.php"
            ."?projectid=$projectid"
            ."&amp;pagename=$pagename";

$texturl = "text_frame_std.php"
            ."?projectid=$projectid"
            ."&amp;pagename=$pagename";

// -------------------------------------

echo "
    <!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Frameset//EN'
    'http://www.w3.org/TR/html4/frameset.dtd'>
";

slim_header($projectid, FALSE, FALSE);

echo "
    </head>
    <frameset $rows_or_cols>
    <frame name='imageframe' src='$imgurl'>
    <frame name='textframe' src='$texturl'>
    </frameset>
    <noframes>your browser is not frames capable!</noframes>
    </html>";

// vim: sw=4 ts=4 expandtab
?>
