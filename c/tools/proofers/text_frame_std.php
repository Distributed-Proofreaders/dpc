<?PHP
$relPath="../../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'http_headers.inc');
include_once($relPath.'button_defs.inc');
include_once($relPath.'slim_header.inc');

// This script is invoked only for the standard interface now.
assert(! $User->IsEnhancedLayout());

$projectid  = Arg("projectid");
$pagename   = Arg("pagename");
if($pagename == "") {
    $imagefile  = Arg("imagefile");
    $pagename = imagefile_to_pagename($imagefile);
}
assert($pagename != "");

$page = new DpPage($projectid, $pagename);
$imagefile = $page->ImageFile();
$imgzoom = $User->ImageZoom();

slim_header("Text Frame", true, false);

echo "
<style type='text/css'>
.nowrap {
    overflow: scroll;
}

.wrap {
    white-space: pre-wrap;
    overflow: auto;
    width: 100%;
}
</style>
</head>
<body style='text-align: center' onload='top.initializeStuff(0)'>
<form name='editform' method='POST' action='processtext.php' target='proofframe'
                                             accept-charset='utf-8'>\n";

echo_proofing_textarea($page);

echo "<br>\n";

// <input type='hidden' value='$proj_state' name='proj_state' id='proj_state'>
// <input type='hidden' value='$page_state' name='page_state' id='page_state'>
echo "
<input type='hidden' value='$pagename' name='pagename' id='pagename'>
<input type='hidden' value='$imgzoom' name='imgzoom' id='imgzoom'>
<input type='hidden' value='$projectid' name='projectid' id='projectid'>\n";


echo_button(SAVE_AS_IN_PROGRESS, 's');
echo_button(SAVE_AS_DONE_AND_PROOF_NEXT, 's');
echo_button(SAVE_AS_DONE_AND_QUIT, 's');
echo_button(QUIT, 's');

echo "<br>\n";

echo_button(CHANGE_LAYOUT, 's');
echo_button(PREVIEW_TEXT, 's');
echo_button(RETURN_PAGE, 's');
echo_button(REPORT_BAD_PAGE, 's');

$comments_url = "$code_url/project.php"
            . "?id={$projectid}";

$image_url = "$projects_url/{$projectid}/{$imagefile}";

echo "<br>\n";

echo_info($page);

echo "&nbsp;";

echo _("View:")
    ." <a href='$comments_url' title='"
    . _("View Project Comments in New Window")
    ."' target='viewcomments'>"
    ._("Project Comments")."</a>| <a href='$image_url' title='"
    . _("View Image in New Window")
    ."' target='lg_image'>"
    ._("Image")."</a>
    <br>\n";

echo _("Image Resize:");

$z1title = _("Zoom in +10%");
$z2title = _("Zoom out -10%");
$z3title = _("100%");
echo "
<input title='$z1title' type='button' value='+10%' onclick='top.ImgBigger()'>
<input title='$z2title' type='button' value='-10%' onclick='top.ImgSmaller()'>
<input title='$z3title' type='button' value='100%' onclick='top.ResetZoomValue()'>
</form>
</body>
</html>";

/** @var DpPage $page */
function echo_proofing_textarea($page) {
    global $f_f, $f_s;
    global $User;

    /** @var DpPage  $page */
    $page_text = maybe_convert($page->ActiveText());
//    $page_text = $page->ActiveText();


    $n_cols = $User->TextChars();
    $n_rows = $User->TextLines();
    $font_face_i = $User->FontFace();
    $font_size_i = $User->FontSize();
    /*
    if ( $userP['i_layout'] == 1 ) {
        // "vertical"
        $n_cols      = $userP['v_tchars'];
        $n_rows      = $userP['v_tlines'];
        // $line_wrap   = $userP['v_twrap'];
        $font_face_i = $userP['v_fntf'];
        $font_size_i = $userP['v_fnts']; 
    }
    else {
        // "horizontal"
        $n_cols      = $userP['h_tchars'];
        $n_rows      = $userP['h_tlines'];
        // $line_wrap   = $userP['h_twrap'];
        $font_face_i = $userP['h_fntf'];
        $font_size_i = $userP['h_fnts']; 
    }
    */
    $wrap = $User->TextWrap() ? "class='wrap' wrap='soft'" 
                              : "class='nowrap' wrap='off'";

    echo "<textarea
        name='text_data'
        id='text_data'
        $wrap
        cols='$n_cols'
        rows='$n_rows' ";
        // dir='".lang_direction($lang)."' ";
    // if ( !$line_wrap ) {
        // echo "wrap='off' ";
    // }

    $font_face = $f_f[$font_face_i];
    $font_size = $f_s[$font_size_i];
    echo "style='";
    if ( $font_face != '' && $font_face != BROWSER_DEFAULT_STR ) {
        echo "font-family: $font_face;";
        echo " ";
    }
    if ( $font_size != '' && $font_size != BROWSER_DEFAULT_STR ) {
        echo "font-size: $font_size;";
    }
    echo "padding-left: 0.25em;' ";


    echo ">\n";

    // SENDING PAGE-TEXT TO USER
    // We're sending it in an HTML document, so encode special characters.
    echo htmlspecialchars( $page_text, ENT_NOQUOTES );

    echo "</textarea>";
}

function echo_info($page) {
    /** @var DpPage $page */
    $proofers = array();
    foreach(array("P1", "P2", "P3", "F1", "F2") as $rnd) {
        if($rnd == $page->RoundId()) {
            break;
        }
        $proofer = $page->RoundUser($rnd);
        $proofers[] = $rnd . ": " .link_to_pm($proofer);
    }
    $str = _("Page: ").$page->PageName()." &mdash; " . implode(", ", $proofers);
    echo "<p style='font-size: .8em;'>$str</p>\n";
    return;
}

// vim: sw=4 ts=4 expandtab
?>
