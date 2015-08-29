<?php
error_reporting(E_ALL);
$relPath="./../../pinc/";
include_once($relPath.'dpinit.php');
// include_once($relPath.'project_states.inc');
// include_once($relPath.'stages.inc');

$returnto   = Arg("returnto", RefererUrl());
$resolution = Arg("resolution");
$projectid  = Arg("projectid");
$pagename   = Arg("pagename", Arg("image"));
$modify     = Arg("modify");
$page_text  = Arg("page_text");
$replace_text  = IsArg("submit_replace_text");
$replace_image = IsArg("submit_replace_image");

$page = new DpPage($projectid, $pagename);
$image_file = $page->ImageFile();
$phase      = $page->Phase();

switch($resolution) {
    case "fixed":
        $page->ClearBad();
        divert($returnto);
        exit;

    case "unfixed":
        divert($returnto);
        exit;

    case "image":
        $page->ClearBad();
        divert($returnto);
        exit;

    case "text":
        $page->ClearBad();
        divert($returnto);
        exit;
    default:
        break;
}

$header = _("Fix Page");

//Display form
theme($header, "header");

echo "
    <br>
    <h3 class='center'>{$page->Title()}</h3>
     <h4 class='center'>Page {$page->PageName()}</h4>

    <form action='' method='POST'>
      <input type='hidden' name='returnto' value='$returnto'>
      <input type='hidden' name='projectid' value='$projectid'>
      <input type='hidden' name='pagename' value='$pagename'>

      <div class='center'>
        <table class='bordered w50 center'>
          <tr> <td class='headerbar center' colspan='2'>$header</td> </tr>\n";

if($page->BadReporter() != "") {
    echo "
          <tr>
            <td class='navbar left padded'> " . _("Reported by:") . " </td>
            <td class='center padded'> " . link_to_pm($page->BadReporter(), "Private Message") . " </td>
          </tr>\n";
}
echo "
          <tr>
            <td class='center padded'>
              " . link_to_download_text($projectid, $pagename, $phase,  "View text", true) . "
            </td>
            <td class='center padded'>
              " . link_to_view_image($projectid, $pagename, "View image", true) . "
            </td>
          </tr>
          <tr>
            <td class='center padded'>
              <input type='submit' name='submit_replace_text' value='Replace Text' />
            </td>
            <td class='center padded'>
              <input type='submit' name='submit_replace_image' value='Replace Image' />
            </td>
          </tr>\n";

echo "
          <tr>
            <td colspan='2' class='center padded'>
              <input name='resolution' type='radio' value='fixed' checked>
                  "._("Fixed")."
              <input name='resolution' type='radio' value='unfixed'>
                  "._("Not Fixed")."
            </td>
          </tr>\n";

echo "
              <tr>
                <td colspan='2' class='headerbar center padded'>
                  <input type='submit' value='" . _("Continue") . "'>
                </td>
              </tr>
            </table>
          </div>
        </form><br><br>";

if ($replace_text) {
    $page_text = $page->ActiveText();

    echo "
        <form action='' method='POST'>
          <input type='hidden' name='modify' value='text'>
          <input type='hidden' name='projectid' value='$projectid'>
          <input type='hidden' name='image' value='$image_file'>
        " . _("<p>The textarea below contains the current page text for page $pagename.</p>
               <p>You may use it as-is, or insert other replacement text for this page:<p>") . "
          <textarea name='page_text' cols=70 rows=10>"
        . h($page_text) . "
          </textarea>
          <br><br>
          <input type='submit' name='submit_update_text' value='" . _("Update Text") . "'>
        </form>\n";

}

if ($replace_image) {
    echo "
        <form enctype='multipart/form-data' action='' method='POST'>
          <input type='hidden' name='projectid' value='$projectid'>
          <input type='hidden' name='image' value='$image_file'>
          " . _("Select an image to upload and replace $pageename with:") ."
          <br>
          <input type='file' name='image_upload' size=30>
          <br><br>
          <input type='submit' value='" . _("Update Original Image") . "'>
        </form>\n";

    $orig_image_ext = right($image_file, 4);
    $temp_image_path = $_FILES['image_upload']['name'];
    $temp_image_ext = right($temp_image_path, 4);

    if ( $temp_image_ext == ".png" || $temp_image_ext == ".jpg" ) {
        if ( $temp_image_ext == $orig_image_ext ) {
            copy($temp_imate_path, $image_file)
                or die("Could not install new image!");
            echo _("<b>Update of Original page $pagename image Complete!<b>\n");
        } else {
            $link = "handle_bad_page.php"
                        ."?projectid=$projectid"
                        ."&pagename=$pagename"
                        ."&modify=image";
            echo _("<p class='bold'>Image NOT updated.</p>
                    <p>The uploaded file type ($temp_image_ext) does not match the original
                       file type ($orig_image_ext).</p>");
            echo _( sprintf(_("<p class='bold'>
                        Click <a href='%s'>here</a> to return.</b>\n"), $link));

        }
    } else {
        echo sprintf(_("<b>The uploaded file must be a PNG or JPG file!</b>
                Click <a href='%s'>here</a> to return."),
                "handle_bad_page.php?projectid=$projectid&pagename=$pagename&modify=image");
    }
}

theme("", "footer");


// vim: sw=4 ts=4 expandtab
