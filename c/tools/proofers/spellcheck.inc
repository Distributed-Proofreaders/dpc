<?php
$relPath = "./../../pinc/";
include_once($relPath.'slim_header.inc');
include_once($relPath.'links.php'); // new_window_link
include_once('spellcheck_text.inc');
include_once('image_block_enh.inc');

// text_data should be set before we get here -- should take place in processtext.php
#$text_data = isset($_POST['text_data'])? stripslashes($_POST['text_data']): $text_data;

$revert_text = isset($_POST['revert_text']) 
    ? stripslashes($_POST['revert_text']) 
    : $text_data;

$is_header_visible = array_get($_SESSION, "is_header_visible", 1);

slim_header(_("WordCheck"), TRUE, FALSE);

if($userP['i_type'] == 1)
    ibe_echo_script();

spellcheck_echo_script();

echo "
<script type='text/javascript'>
function ldAll(){
  top.initializeStuff(";
  if ($userP['i_type'] == 1) {
    echo '2';
  }
  else {
    echo '3';
  }
echo ");
}

function toggleWCHeader() {
    var header = document.getElementById('wc_header');
    var headerLink = document.getElementById('wc_header_link');
    var headerStateSave = document.getElementById('is_header_visible');
    if(header) {
        if(header.style.display == '') {
            header.style.display = 'none';
            headerLink.innerHTML = '" . _("Show WordCheck Header") . "';
            headerStateSave.value = 0;
        } else{
            header.style.display = '';
            headerLink.innerHTML = '" . _("Hide WordCheck Header") . "';
            headerStateSave.value = 1;
        }
    }
    return false;
}

</script>
";

echo "
<style type='text/css'>
body {
  font-family: verdana, arial, helvetica, sans-serif;
  font-size: 12px;
  color:#000000;
  background-color:#CDCDC1;
  text-align:center;
  overflow:auto;
  margin-top: 0;
  }\n";

if($userP['i_type'] == 1)
    ibe_echo_styles();

echo "
#tbtext {
  border:1px solid #000000;
  text-align:left;
  } 
#tdtop {
  font-size:12px;
  border:1px solid #000000;
  background-color:#CDC0B0;
  text-align:left;
  padding:2px;
  }
#tdtext {
  border:1px solid #000000;
  background-color:#FFF8DC;
  padding:2px;
  }
-->
</style>
</head><body 
  onload = 'ldAll()'>
";

// print basic image html
if ($userP['i_type'] == 1) {
    if ($userP['i_layout'] == 1) {
      $iWidth = $userP['v_zoom']; 
    }
    else {
      $iWidth = $userP['h_zoom']; 
    }
    $iWidth = round((1000*$iWidth)/100);

    ibe_echo_block( $page->ImageUrl(), $iWidth );

    echo '<div id="controlframe">';
} 
else {
    echo '<div>';
}

echo "
<form name='spcorrects' action='processtext.php' method='POST'>
";
    // change all EOL characters to [lf]
    $revert_text = str_replace(array("\r","\n\n","\n"),array("\n","[lf]","[lf]"),$revert_text);
    echo "<input type='hidden' name='revert_text' value='" . html_safe($revert_text) . "'>\n";
    echo "<input type='hidden' id='is_changed' name='is_changed' value='0'>\n";
    echo "<input type='hidden' id='accepted_words' name=\"accepted_words\" value='" 
        . html_safe(implode(' ',$accepted_words)) . "'>\n";
    echo "<input type='hidden' id='is_header_visible' name=\"is_header_visible\" value='$is_header_visible'>\n";

    // run the text through the spellcheck - returns the form contents and a list of languages the page was checked against
    list($page_contents,$languages,$messages) =
        spellcheck_text( $text_data, $page->ProjectId(), $page->ImageFile(), $aux_language, $accepted_words );

    // start the div containing the link to show/hide the WordCheck header
    echo "<div>";
    echo "<a id='wc_header_link' href='#' onClick='return toggleWCHeader();'>";
    if($is_header_visible) {
        echo _("Hide WordCheck Header");
        $header_style="";
    } else {
        echo _("Show WordCheck Header");
        $header_style="style='display: none;'";
    }
    echo "</a>";
    echo "</div>";

    // start the div that consists of the WordCheck header
    // that can be toggled shown/hidden
    echo "<div id='wc_header' $header_style>";
    if ( count($messages) != 0 )
    {
        // warnings or errors were raised, print them out
        echo "<p class='warning'>" . _("The following warnings/errors were raised:") . "<br>\n";
        foreach ( $messages as $message ) {
            echo "$message<br>\n";
        }
        echo "</p>";
    }

    // start the first line of the WordCheck header
    // printing any dictionaries used
    echo "<p>";
    if(count($languages) > 0) {
        echo sprintf(_("Dictionaries used: <b>%s</b>."),implode(', ', $languages));
    } else {
        echo "<b>" . _("No check against a dictionary has been made.") . "</b>";
    }

    // for the vertical layout, stick in a line break
    // for the horizontal one, stick in a space
    if ($userP['i_layout']==1) 
        echo "<br>";
    else 
        echo " ";

/*
    // output the code allowing the user to select another language
    echo _("Use additional: ");
    echo "<select name='aux_language'>";
    echo "<option value=''>" . _("Language") . "</option>\n";

    // get a list of languages with dictionaries installed on the system
    $dict_list = get_languages_with_dictionaries();
    asort($dict_list);
    foreach($dict_list as $langcode => $language) {
        // skip the languages that we just used.
        if(in_array($language, $languages)) 
            continue;
        echo "<option value='" .  htmlspecialchars($language, ENT_QUOTES) . "'";
        if(strcmp($language, $aux_language) == 0) 
            echo " selected";
        echo ">$language</option>\n";
    }
    echo "</select>";
*/

echo "
<input type='submit' name='rerunauxlanguage' value='" . _("Check") . "
    title='" . _("Check page against an additional language") . "'>
<br>\n";

    // show help blurb on the UA&S icon
    echo sprintf(
        _("The %s icon accepts the word for this page and suggests it for the dictionary."),
        "<img src=\"$code_url/graphics/Book-Plus-Small.gif\" border=\"0\">"
    );
    echo "</p>";
    echo "</div>";
    // here ends the div for the WordCheck header

?>
<table id="tbtext" cellpadding="10" align="center">
<tr><td id="tdtop">
<?php echo _("Resize image:"); ?>
    <input title="<?php echo attr_safe(_("Zoom Out 25%")); ?>" id="zoomout_button" type="button" value="-25%" onclick="top.reSizeRelative(0.75)?true:this.disabled=true;">
    <input title="<?php echo attr_safe(_("Zoom In 25%")); ?>" type="button" value="+25%" onclick="top.reSizeRelative(1.25); document.getElementById('zoomout_button').disabled=false;">
    <input title="<?php echo attr_safe(_("Zoom to Original Size")); ?>" type="button" value="100%" onclick="top.reSizeRelative(-1); document.getElementById('zoomout_button').disabled=false;">
<?php
    // print the link to the WordCheck FAQ
    echo " &nbsp; ";
    echo new_window_link( "$code_url/faq/wordcheck-faq.php", _("WordCheck FAQ") );
?>
</td></tr>
<tr>
<td 
  id="tdtext" 
  valign="top">
<?php
echo "
<input type='hidden' value='{$page->ImageFile()}' name='imagefile' id='imagefile'>
<input type='hidden' value='{$page->ProjectId()}' name='projectid' id='projectid'>\n";
    echo $page_contents;
?>
</td>
</tr>
<tr>
    <td id="tdtop">
        <input
            type="submit"
            name="spcorrect"
            value="<?php echo attr_safe(_("Submit Corrections")); ?>"
            title="<?php echo attr_safe(_("Keep corrections and return to proofreading this page")); ?>"
        >
        &nbsp;&nbsp;&nbsp;
        <input
            type="submit"
            name="spexit"
            value="<?php echo attr_safe(_("Quit WordCheck")); ?>"
            title="<?php echo attr_safe(_("Abandon corrections and return to proofreading this page")); ?>"
            onClick="return confirmExit();"
        >
    </td>
</tr>
</table>
</form>
</div>
<?php
slim_footer();

// vim: sw=4 ts=4 expandtab
