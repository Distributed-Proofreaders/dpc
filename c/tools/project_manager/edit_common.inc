<?PHP
global $relPath;
//require_once $relPath . "lists.php";

//function encodeFormValue($value) {
//  return htmlspecialchars($value,ENT_QUOTES);
//}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function just_echo( $field_value ) {
    echo h($field_value);
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function text_field( $field_value, $field_name ) {
    $enc_field_value = h($field_value);
    echo "<input type='text' size='67' name='$field_name' value='$enc_field_value'>";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function DP_user_field( $field_value, $field_name ) {
    $enc_field_value = h($field_value);
    echo "<input type='text' size='25' name='$field_name' value='$enc_field_value'>";
}


// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/*
function language_list($language) {
    if (strpos($language, "with") > 0) {
        $pri_language = trim(substr($language, 0, strpos($language, "with")));
        $sec_language = trim(substr($language, (strpos($language, "with")+5)));
    }
    else {
        $pri_language = $language;
        $sec_language = '';
    }

    echo "
    <select name='pri_language'>
    <option value=''>Primary Language</option>\n";

    foreach($array_list as $item) {
    // for ($i = 0; $i<count($array_list); $i++) {
        $lang_name = $item['lang_name'];
        echo "<option value='"
            . encodeFormValue($lang_name) . "'";

       if ($pri_language == $lang_name) {
            echo " SELECTED";
       }
        echo ">$lang_name</option>\n";
    }
    echo "</select>\n";

    echo "
    &nbsp;&nbsp;<select name='sec_language'>
    <option value=''>Secondary Language</option>\n";

    foreach($array_list as $item) {
        $lang_name = $item['lang_name'];
        echo "<option value='"
            . encodeFormValue($lang_name) . "'";

        if ($sec_language == $lang_name) {
            echo " SELECTED";
        }
        echo ">$lang_name</option>";
        echo "\n";
    }
    echo "</select>\n";
}
*/

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function genre_list($genre) {
    global $Context;
    echo "<select name='genre'>\n";
    foreach($Context->GenreArray() as $key => $value) {
        echo "<option value='$key'"
        . ($genre == $key ? " SELECTED" : "")
        . ">$value</option>\n";
    }
    echo "</select>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function difficulty_list($difficulty = "Average") {
    $difs = array("Beginner", "Easy", "Average", "Hard");

    foreach($difs as $dif) {
        $checked = (strtolower($dif) == strtolower($difficulty)
                        ? " CHECKED"
                        : "");
        echo "
        <input type='radio' name='difficulty' value='$dif' 
            $checked>$dif&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    }
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

/*
function special_list($special) {
    // get info on special days
    $specs_result = mysql_query("
        SELECT
            spec_code,
            display_name,
            DATE_FORMAT(concat('2000-',open_month,'-',open_day),'%e %b') as 'Start Date'
        FROM special_days
        WHERE enable = 1
        ORDER BY open_month, open_day
    ");

    // it'd be nice to make this static, or something, so it only was loaded once
    $specials_array = array();

    // put list into array
    while ($s_row = mysql_fetch_assoc($specs_result)) {
        $show = $s_row['display_name']." (".$s_row['Start Date'].")";
        $code = $s_row['spec_code'];
        $specials_array["$code"] = $show;
    }

    $bdaymonth = 0;
    $bdayday = 0;

    // drop down select box for which special day
    echo "<select name='special_code'>";

    // add special case values first
    echo "<option value=''>NONE</option>";
    echo "\n";

    echo "<option value='Birthday'";
    if (strncmp ( $special, 'Birthday', 8) == 0)
    {
        echo " SELECTED";
        $bdaymonth = substr($special, 9, 2);
        $bdayday = substr($special, 11, 2);
    }
    echo ">Birthday</option>";
    echo "\n";

    echo "<option value='Otherday'";
    if (strncmp ( $special, 'Otherday', 8) == 0)
    {
        echo " SELECTED";
        $bdaymonth = substr($special, 9, 2);
        $bdayday = substr($special, 11, 2);
    }
    echo ">Otherday</option>";
    echo "\n";


    // add the rest of the special days (the "ordinary" special days ;) )
    foreach($specials_array as $k=>$v)
    {
        echo "<option value='".encodeFormValue($k)."'";
        if ($special == $k) { echo " SELECTED"; }
        echo ">$v</option>";
        echo "\n";
    }
    echo "</select>";

    echo " <a href='show_specials.php'>Special Days Info</a><br>";

    // drop down selects for month and date, used for Birthday and Otherday specials
    echo " Birthday/Otherday: (month) <select name='bdaymonth'>";
    echo "<option value=''></option>";
    echo "\n";
    $i = 1;
    while ($i <= 12) {
        $v = sprintf("%02d", $i);
        echo "<option value='$v'";
        if ($v == $bdaymonth) { echo " SELECTED"; }
        echo ">$v</option>";
        echo "\n";
        $i++;
    }
    echo "</select>";

    echo " (day) <select name='bdayday'>";
    echo "<option value=''></option>";
    echo "\n";
    $i = 1;
    while ($i <= 31) {
        $v = sprintf("%02d", $i);
        echo "<option value='$v'";
        if ($v == $bdayday) { echo " SELECTED"; }
        echo ">$v</option>";
        echo "\n";
        $i++;
    }
    echo "</select>";
}
*/

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function image_source_list($image_source) {
    global $dpdb;
    // get info on image_sources
//    $imso_result = mysql_query("
    $sql = "
        SELECT code_name, display_name
        FROM image_sources
        WHERE is_active = 1
        ORDER BY display_name
    ";

    $srcs = $dpdb->SqlRows($sql);
    // it'd be nice to make this static, or something, so it only was loaded once
//    $imso_array = array();

    // put list into array
//    while ($i_row = mysql_fetch_assoc($imso_result))
//    {
//        $show = $i_row['display_name'];
//        $code = $i_row['code_name'];
//        $imso_array["$code"] = $show;
//    }

    // drop down select box for which image source
    echo "<select name='image_source'>";

    // add special case value "DP Internal"
    echo "<option value='_internal' ";
    if (strcmp ( $image_source, '_internal') == 0) { echo " SELECTED"; }
    echo ">"._("DP Internal")."</option>";
    echo "\n";

    // add the pre-defined image_sources
    foreach($srcs as $src) {
//    foreach($imso_array as $k=>$v)
//    {
        $code = $src["code_name"];
        $displ = h($src["display_name"]);
        echo "<option value='".$code."'";
        if ($image_source == $code) { echo " SELECTED"; }
        echo ">$displ</option>";
        echo "\n";
    }

    echo "</select> ";
    echo "<font size=-1>". _("'DP Internal' if scanned by or for a DP user.")."</font>";
    echo " <br><a href='image_sources.php'>"._("Details of Image Sources")."</a><br>";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function proj_comments_field($comments) {
    $enc_comments = h($comments);

    echo "<div class='center'>
    <textarea name='comments' cols='74' rows='16'>$enc_comments</textarea>
    </div>\n";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function extra_credits_field($extra_credits) {
    $enc_credits = h($extra_credits);

    echo "<div class='center'>";
    echo "<textarea name='extra_credits' cols='74' rows='4'>$enc_credits</textarea>";
    echo "</div>";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX



function description_field( $description, $field_name ) {
    $enc_description = h($description);

    echo "<div class='center'>";
    echo "<textarea name='$field_name' cols='74' rows='6'>$enc_description</textarea>";
    echo "</div>";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

// vim: sw=4 ts=4 expandtab

