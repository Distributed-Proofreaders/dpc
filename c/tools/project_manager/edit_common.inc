<?PHP
global $relPath;

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function just_echo( $field_value ) {
    echo h($field_value);
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function text_field( $field_value, $field_name, $disabled = false ) {
    $enc_field_value = h($field_value);
    if (!$disabled)
        $dis = '';
    else
        $dis = 'disabled';
    echo "<input type='text' size='67' name='$field_name' value='$enc_field_value' $dis>";
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function DP_user_field( $field_value, $field_name ) {
    $enc_field_value = h($field_value);
    echo "<input type='text' size='25' name='$field_name' value='$enc_field_value'>";
}


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

function image_source_list($image_source) {
    global $dpdb;
    // get info on image_sources
    $sql = "
        SELECT code_name, display_name
        FROM image_sources
        WHERE is_active = 1
        ORDER BY display_name
    ";

    $srcs = $dpdb->SqlRows($sql);

    // drop down select box for which image source
    echo "<select name='image_source'>";

    // add special case value "DP Internal"
    echo "<option value='_internal' ";
    if (strcmp ( $image_source, '_internal') == 0) { echo " SELECTED"; }
    echo ">"._("DP Internal")."</option>";
    echo "\n";

    // add the pre-defined image_sources
    foreach($srcs as $src) {
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

