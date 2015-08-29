<?PHP
// set $label, $filtertype_stem  and $state_sql before inclusion
global $User;
$username = $User->Username();

// by default, filters are shown for Language, Genre, PM and Special Day, 
// and hidden for PPers;
// these defaults can be overridden by assigning appropriate values for 
// the associated variables, below, in the enclosing file

if (!isset($showLangF)) {
    $showLangF = TRUE;
}
//if (!isset($showGenreF)) {
//    $showGenreF = TRUE;
//}
if (!isset($showPMsF)) {
    $showPMsF = TRUE;
}
if (!isset($showDiffF)) {
    $showDiffF = TRUE;
}
if (!isset($showSpecialF)) {
    $showSpecialF = TRUE;
}

if (!isset($showPPersF)) {
    $showPPersF = FALSE;
}


// if no filters enabled, don't show any

if ($showLangF
//    || $showGenreF
  || $showPMsF || $showDiffF 
  || $showSpecialF || $showPPersF) {

    // arrays to hold legal filter values (determined from database)
    // as some protection against "injection of SQL in URL" attacks

    $array_of_langs = array();
//    $array_of_genres = array();
    $array_of_PMs = array();
    $array_of_diffs = array();
    $array_of_PPers = array();

// get legal languages

if ($showLangF) {
    $langs = $dpdb->SqlValues("
    	    SELECT DISTINCT language FROM projects	
            WHERE $state_sql ORDER BY language");
    
    $i = 0;
    foreach($langs as $lang) {
        $array_of_langs[$i++] = $lang;
    }
}

// get legal genres

//if ($showGenreF) {
//    $genres = $dpdb->SqlValues("
//	    SELECT DISTINCT genre FROM projects
//        WHERE $state_sql ORDER BY genre");
//    $i = 0;
//    foreach($genres as $val) {
//        $array_of_genres[$i++] = $val;
//    }
//}


// get legal PMs
if ($showPMsF) {
    $vals = $dpdb->SqlValues("
	    SELECT DISTINCT username FROM projects	
        WHERE $state_sql ORDER BY username");
    $i = 0;
    foreach($vals as $val) {
        $array_of_PMs[$i++] = $val;
    }
}

// get legal PPers

if ($showPPersF) {
    $vals = $dpdb->SqlValues("
	    SELECT DISTINCT postproofer FROM projects	
        WHERE $state_sql ORDER BY postproofer");
    $i = 0;
    foreach($vals as $val) {
        $array_of_PPers[$i++] = $val;
    }
}


if ($showDiffF) {
    $array_of_diffs = array('beginner','easy','average','hard');
}

// build and show the filter selection section

global $label;
echo "<center>";
echo _("Filter available ").$label._(" books by").":";
echo "</center>\n";    
echo "<br>
    <form method='post' action=''>
    <input type='hidden' name='filter_".$filtertype_stem."' value='filter_on'>";


// checkboxes for selection of difficulty
if ($showDiffF) {
    echo "\r\n<table border=1>";
    echo "\r\n<tr>";

    echo "<td>"._("Difficulty")."</td>";
    echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"._("All")." <input type='checkbox' name='difficulty1[]' value=''>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    echo _("Beginners Only").' <input type="checkbox" name="difficulty1[]" value="beginner">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo _("Easy").' <input type="checkbox" name="difficulty1[]" value="easy">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo _("Average").' <input type="checkbox" name="difficulty1[]" value="average">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo _("Hard").' <input type="checkbox" name="difficulty1[]" value="hard"></td>';

    echo "\r\n</tr>";
    echo "</table>\n";
}

// drop down multiselect listboxes for Language, Genre, PM, Special Days, PPers (when enabled)


// In the <select> tags, we set the name attribute to 'whatever[]'.
// I'm pretty sure this doesn't mean anything to HTML/HTTP,
// but PHP takes it as a cue to make the multiple values of
// the select control available as an array.
// That is, $_POST['whatever'] will be an array containing
// all selected values.


// LANGUAGE filter

if ($showLangF) {

    echo "\r\n<table style='float: left;' border=1>\n<tr>
          <td>"._("Language")."</td></tr><tr>
        <td>
        <select name='language1[]' size=5 multiple>
	    <option value=''>"._("All languages")."</option>\n";

    echo build_selectlist( $state_sql, "language", $array_of_langs);

    echo "
        </select>
        </td>
      </tr>
     </table>
   ";
}


// GENRE filter

//if ($showGenreF) {
//    echo "\r\n<table style='float: left;' border=1>\n<tr>
//          <td>"._("Genre")."</td></tr><tr>
//        <td>
//        <select name='genre1[]' size=5  multiple>
//	    <option value=''>"._("All genres")."</option>\n";
//
//    echo build_selectlist( $state_sql, "genre", $array_of_genres);
//    echo "
//        </select>
//        </td>
//      </tr>
//     </table>
//   ";
//}


// PM filter

if ($showPMsF) {
    echo "\r\n<table style='float: left;' border=1>\n<tr>
          <td>"._("Project Manager")."</td></tr><tr>
        <td>
        <select name='PM1[]' size=5 multiple>
	    <option value=''>"._("All PMs")."</option>
    ";

    echo build_selectlist( $state_sql, "username", $array_of_PMs);
    echo "
        </select>
        </td>
      </tr>
     </table>
   ";
}


// PP filter

if ($showPPersF) {
    echo "\r\n<table style='float: left;' border=1>\n<tr>
          <td>"._("Post Processor")."</td></tr><tr>
        <td>
        <select name='PP1[]' size=5 multiple>
	    <option value=''>"._("All PPers")."</option>
    ";

    echo build_selectlist( $state_sql, "postproofer", $array_of_PPers);
    echo "
        </select>
        </td>
      </tr>
     </table>
   ";
}



// Stop the buttons floating next to the table:
echo "<br style='clear: both;' /><br />";


// buttons

echo "<center>";
echo "<input type='reset' value='".
	_("Reset New Filter")
     ."'>";
echo "<input type='submit' value='".
	_("Apply New Filter to Projects")
     ."'>";
echo "</center>\n";
echo "</form><br>";


// usage notes

echo "<center>";
echo _("If desired, you should be able to select multiple values for each (e.g., by holding down Ctrl).");
echo "<br><br>";
echo _("Changing the filter does not apply it. To see all projects again, Reset then Apply Filter.");
echo "</center><br><br>\n";    

// $filtertype_stem (which must be initialised in enclosing file) 
// allows different filters to be set for different list/pages/purposes
// (e.g. "proof" and "PP")

$FT_int = $filtertype_stem . "_internal";
$FT_dis = $filtertype_stem . "_display";


// read saved filters (actual and display) from user_filters table 

$RFilter = $dpdb->SqlOneValue("
        SELECT value FROM user_filters 
        WHERE username = '$username' 
            AND filtertype = '$FT_int'");

$display_filter = $dpdb->SqlOneValue("
        SELECT value FROM user_filters 
        WHERE username = '$username' 
            AND filtertype = '$FT_dis'");


// get new filters from passed arguments (if any) in HTTP request

// validate each set value against the arrays of legal values
// as some protection against the "injection of SQL into URL" attack

if ( isset($_POST["filter_$filtertype_stem"]) 
     && ($_POST["filter_$filtertype_stem"] == 'filter_on')) {

	$RFilter = " ";
	$display_filter = "";
	$show_df = FALSE;


	// construct language clause of new filter

	$condition1 = '1';
	if ( isset($_POST['language1']) && count($_POST['language1']) > 0 ) {
	    $condition1 .= " AND (0";
	    $display_filter .= "<br>Languages=";
        foreach( $_POST['language1'] as $lang ) {

            if ( $lang == '' ) {
                $condition1 .= " OR 1";
            }
            else {
                if (!in_array( $lang, $array_of_langs )) { echo "UHOH" & exit(); }
                $condition1 .= " OR language='$lang'";
                $display_filter .= $lang." ";
                $show_df = TRUE;
            }
        }
	    $condition1 .= ")";
	}

	// construct genre clause of new filter

//	if ( isset($_POST['genre1']) && count($_POST['genre1']) > 0 ) {
//	    $condition1 .= " AND (0";
//	    $display_filter .= "<br>Genres=";
//	    foreach( $_POST['genre1'] as $genre ) {
//            if ( $genre == '' ) {
//                $condition1 .= " OR 1";
//            }
//            else {
//                if (!in_array($genre, $array_of_genres )) { echo "UHOH" &  exit(); }
//                $condition1 .= " OR genre='$genre'";
//                $display_filter .= $genre." ";
//                $show_df = TRUE;
//            }
//	    }
//	    $condition1 .= ")";
//	}

	// construct PM clause of new filter

	if ( isset($_POST['PM1']) && count($_POST['PM1']) > 0 ) {
	    $condition1 .= " AND (0";
	    $display_filter .= "<br>PMs=";
        foreach( $_POST['PM1'] as $PM ) {
            if ( $PM == '' ) {
                $condition1 .= " OR 1";
            }
            else {
                if (!in_array($PM, $array_of_PMs )) {
                    echo "UHOH" & exit(); 
                }
                $condition1 .= " OR projects.username='$PM'";
                $display_filter .= $PM." ";
                $show_df = TRUE;
            }
        }
	    $condition1 .= ")";
	}


	// construct PP clause of new filter

	if ( isset($_POST['PP1']) && count($_POST['PP1']) > 0 ) {
	    $condition1 .= " AND (0";
	    $display_filter .= "<br>PPs=";
	    foreach( $_POST['PP1'] as $PP ) {
            if ( $PP == '' ) {
                $condition1 .= " OR 1";
            }
            else {
                if (!in_array($PP, $array_of_PPers )) { 
                    echo "UHOH" & exit(); 
                }
                $condition1 .= " OR postproofer='$PP'";
                $display_filter .= $PP." ";
                $show_df = TRUE;
            }
	    }
	    $condition1 .= ")";
	}

	// construct difficulty clause of new filter

	if ( isset($_POST['difficulty1']) && count($_POST['difficulty1']) > 0 )
	{
	    $condition1 .= " AND (0";
	    $display_filter .= "<br>Difficulty=";
	    foreach( $_POST['difficulty1'] as $diff )
	    {
		if ( $diff == '' )
		{
		    $condition1 .= " OR 1";
		}
		else
		{
		    if (!in_array($diff, $array_of_diffs )) { echo "UHOH" & exit(); }
		    $condition1 .= " OR difficulty='$diff'";
                    $display_filter .= $diff." ";
		    $show_df = TRUE;
		}
	    }
	    $condition1 .= ")";
	}

	$RFilter = " AND ".$condition1;

	$dpdb->SqlExecute("
		DELETE FROM user_filters 
        WHERE username = '$username' AND filtertype = '$FT_int'");

	$dpdb->SqlExecute("
		DELETE FROM user_filters 
        WHERE username = '$username' AND filtertype = '$FT_dis'");

	$dpdb->SqlExecute("
        INSERT INTO user_filters 
            (username, filtertype, value) 
        VALUES 
            ('$username', '$FT_int', '".addslashes($RFilter)."')");

	$dpdb->SqlExecute("
		INSERT INTO user_filters 
            (username, filtertype, value) 
        VALUES 
            ('$username', '$FT_dis', '".addslashes($display_filter)."')");

}


// report filter in use to user

if ((isset($show_df) && $show_df) || (isset($display_filter) && $display_filter)) {
	echo "<center>"._("Currently filtered by:")."<b> $display_filter </b></center><br><br>\n";
} else {
	echo "<center><b>"._("No filter, all books shown")."</b></center><br><br>\n";
}

// $RFilter is now set to new filter value 
// for passing to a project listing function in enclosing file


} // end if any filters enabled
function build_selectlist( $states, $field) {
    global $dpdb;
    $to_echo = "";
    $values = $dpdb->SqlValues("
		SELECT distinct $field FROM projects
        WHERE $states
        ORDER BY $field");

    foreach($values as $val) {
        $to_echo .= "<option value='$val'>$val</option\n";
    }
    return $to_echo;
}
