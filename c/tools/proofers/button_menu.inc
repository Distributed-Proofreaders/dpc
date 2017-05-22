<?
global $relPath;
include_once($relPath.'dpinit.php');
include_once($relPath.'prefs_options.inc');
include_once($relPath.'button_defs.inc');

echo "
<select name='fntFace' ID='fntFace' title='Change Font Face' class='boxnormal' 
                        onChange='top.chFFace(this.selectedIndex)'>\n";
$ff = $User->FontFace();
// $ff = $userP['i_layout'] == 1
    // ? $userP['v_fntf']
    // : $userP['h_fntf'];

$ff_len = count($f_f);

for ($i = 0; $i < $ff_len; $i++) {
    $optP = "<option value='$i'";
    if ($i == $ff) {
        $optP .= " selected";
    }
    $optP .= ">" . $f_f[$i] . "</option>";
    echo $optP;
}
echo "
</select>
<select name='fntSize' ID='fntSize'  class='dropsmall top' title='Change Font Size'
onChange='top.chFSize(this.selectedIndex)'>\n";

$fs = $User->FontSize();
// $fs = ($userP['i_layout'] == 1)
        // ? $userP['v_fnts']
        // : $userP['h_fnts'];

$fs_len = count($f_s);
for ($i = 0; $i < $fs_len; $i++) {
    $optP = "<option \r\n value=\"$i\"";
    if ($i == $fs) {
      $optP .= " selected";
    }
    $optP .= ">$f_s[$i]</option>";
    echo $optP;
}

$zoom = $User->ImageZoom();
// $zoom = ($userP['i_layout'] == 1)
    // ? $userP['v_zoom']
    // : $userP['h_zoom'];

echo "
</select>

<input type='text' value='$zoom' name='imgzoom' ID='imgzoom' class='boxnormal
top'  title='Input Zoom %' size='1'>\n";

echo_button(SET_IMAGE_ZOOM,'a');
echo "\n";

// $imgurl = $ppage->url_for_image();
$imgurl = $page->ImageUrl();

echo_button(REVERT_TO_ORIGINAL, 'a');
echo "\n";

$comments_url = url_for_project($page->ProjectId());

echo "
<a href='#' onclick='top.showNW()'>";
echo_img(PREVIEW_TEXT, 'a'); 
echo "</a>
<a href='{$comments_url}' target='viewcomments'>\n";
echo_img(PROJECT_COMMENTS, 'a'); 
echo "</a>\n";

echo_button(CHANGE_LAYOUT, 'a');
echo "\n";
echo_button(RETURN_PAGE, 'a');
echo "\n";

echo_button(REPORT_BAD_PAGE, 'a');
echo "\n";

echo_button(SAVE_AS_IN_PROGRESS, 'a');
echo "\n";

echo_button(SAVE_AS_DONE_AND_PROOF_NEXT, 'a');
echo "\n";

echo_button(SAVE_AS_DONE_AND_QUIT, 'a');
echo "\n";
echo_button(QUIT, 'a');
?>
