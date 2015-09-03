<?PHP
global $relPath;

function echo_page_table( $project, $pgsel = NULL ) {
    /** @var DpProject $project */
	global $theme, $projects_dir, $code_url;
    global $User;
    global $dpdb;
	global $Round_for_round_id_;

	$can_edit = $project->UserMayManage() ;

	// want PPers able to see names
	$can_see_names_for_all_pages = $project->UserMaySeeNames();

	$projectid = $project->ProjectId();
//	$state = $project->State();
	$phase = $project->Phase();

	$show_delete = $can_edit 
                && ($phase == 'NEW' || $phase == 'PR' || $phase == 'PAGE_EDITING');

	$project_round = $project->RoundId();

	// This project may have skipped some rounds, and/or it may have rounds
	// yet to do, so there may be some round-columns with no data in them.
	// Figure out which ones to display.
	//

    $rounds_to_display = array();

    if($phase != "NEW") {
        switch($projectid) {

            case "P1":
                $rounds_to_display[] = $Round_for_round_id_["P1"];
                break;

            case "P2":
                $rounds_to_display[] = $Round_for_round_id_["P1"];
                $rounds_to_display[] = $Round_for_round_id_["P2"];
                break;

            case "P3":
                $rounds_to_display[] = $Round_for_round_id_["P1"];
                $rounds_to_display[] = $Round_for_round_id_["P2"];
                $rounds_to_display[] = $Round_for_round_id_["P3"];
                break;

            case "F1":
                $rounds_to_display[] = $Round_for_round_id_["P1"];
                $rounds_to_display[] = $Round_for_round_id_["P2"];
                $rounds_to_display[] = $Round_for_round_id_["P3"];
                $rounds_to_display[] = $Round_for_round_id_["F1"];
                break;

            default:
                $rounds_to_display[] = $Round_for_round_id_["P1"];
                $rounds_to_display[] = $Round_for_round_id_["P2"];
                $rounds_to_display[] = $Round_for_round_id_["P3"];
                $rounds_to_display[] = $Round_for_round_id_["F1"];
                $rounds_to_display[] = $Round_for_round_id_["F2"];
                break;
        }
    }

	$upload_colspan = 2;

	if ( $can_edit ) {
		echo "
        <form name='pagesform' method='post'
        action='$code_url/tools/project_manager/edit_pages.php'> <input
        type='hidden' name='projectid' value='$projectid'>\n";
	}

	echo "<table id='page_table' class='bordered'>\n";

	$n_cols_per_round = 3; // Diff, Date, Text
	// Even if the user can't see names for all pages, they can see names
	// for the pages they've worked on, so we always include the User column.
	$n_cols_per_round += 1; // User
	if ($can_edit) {
        $n_cols_per_round += 1; // Clear
    }

	// Top header row

    echo "<tr>\n";
    if ($can_edit) {
        echo "    <td class='center' colspan='1'>&nbsp;</td>\n";
    }
    echo "
    <td class='center' colspan='1'>&nbsp;</td>
    <td class='center' colspan='$upload_colspan'>"._("Upload")."</td>
    <td class='center' colspan='1'>&nbsp;</td>\n";

    foreach ( $rounds_to_display as $round ) {
        if ($round == $project_round && $phase == 'PAGE_EDITING') {
            $n_cols_per_round++;
        }

        echo "    <td class='center' colspan='$n_cols_per_round'>$round->id</td>\n";
    }
    echo "</tr>\n";


	// Bottom header row
    echo "<tr class='headerbar'>\n";
    $td_start = "<td class='center headerbar'>\n";
    $td_end   = "</td>\n";

    if ($can_edit) {
        echo "{$td_start}X{$td_end}";
    }
    echo "{$td_start}I{$td_end}";

    echo "{$td_start}"._("Image")."{$td_end}";

    echo "{$td_start}"._("Text")."{$td_end}";

    echo "{$td_start}"._("Page State")."{$td_end}";

    foreach ( $rounds_to_display as $round ) {
        assert($round);
        echo "{$td_start}"._("Diff")."{$td_end}";
        echo "{$td_start}"._("Date")."{$td_end}";
        echo "{$td_start}"._("User")."{$td_end}";
        echo "{$td_start}"._("Text")."{$td_end}";
        if ($can_edit) {
            echo "{$td_start}"._("Clear")."{$td_end}";
        }
    }

    if ($phase == 'PAGE_EDITING') {
        echo "{$td_start}"._("Edit")."{$td_end}";
    }

    if ($can_edit) {
        echo "{$td_start}"._("Bad<br/>Fix")."{$td_end}";
    }

    if ($show_delete) {
        echo "{$td_start}"._("Delete")."{$td_end}";
    }

	echo "</tr>";

	$avail_pages="";

	$path = "$projects_dir/$projectid/";

	$fields_to_get = "fileid AS pagename, image, length(master_text), state";
	$tables = $projectid;
	$prev_text_column_name = 'master_text';
	foreach ( $rounds_to_display as $round ) {
		$rn = $round->round_number;

		$tallyboard = new TallyBoard( $round->id, 'U' );
		list($joined_with_user_page_tallies,$user_page_tally_column) =
			$tallyboard->get_sql_joinery_for_current_tallies( "users$rn.u_id" );

		$fields_to_get .= ",
			BINARY REPLACE($prev_text_column_name,'\r\n','\n')=REPLACE($round->text_column_name,'\r\n','\n') AS $round->textdiff_column_name,
			$round->time_column_name,
			$round->user_column_name,
			$user_page_tally_column AS `$rn.user_page_tally`,
			length($round->text_column_name)";
		$tables .= "
			LEFT JOIN users AS users$rn
				ON (users$rn.username = $round->user_column_name)
			$joined_with_user_page_tallies";
		$prev_text_column_name = $round->text_column_name;
	}

	if ( is_null($pgsel) ) {
		// Select all pages
		$where_condition = "1";
	}
	else if ( is_string($pgsel) ) {
		// Select all pages of a particular user.
		$username_for_selecting_pages = $pgsel;

		$where_condition = "0";
		foreach ( $rounds_to_display as $round ) {
			$where_condition .= "
				OR $round->user_column_name='$username_for_selecting_pages'
			";
		}

		$pgsel = NULL;
	}
	else {
		// $pgsel is an array whose keys are fileids of the pages to select
        $pgnames = array_keys($pgsel);
		$pagenames = implode(", ", $pgnames);
//		foreach ( $pgsel as $fileid => $dummy ) {
//			if ( !empty($fileids_str) ) $fileids_str .= ',';
//			$fileids_str .= "'$fileid'";
//		}
		$where_condition = "fileid IN ($pagenames)";
	}

	$sql = "
		SELECT $fields_to_get FROM $tables
	       	WHERE $where_condition
	       	ORDER BY image ASC";
    html_comment($sql);
	$rows = $dpdb->SqlRows($sql);
	$num_rows = count($rows);

	// for ( $rownum = 0; $rownum < $num_rows; $rownum++ ) {
    $rownum = 0;
    $odd_even = true;
    foreach($rows as $page_res) {
		// $page_res = mysql_fetch_array( $res, MYSQL_ASSOC );

		$pagename = $page_res['pagename'];

        $odd_even = ! $odd_even;
        $row_color = $odd_even
            ? $theme['color_mainbody_bg'] 
            : $theme['color_navbar_bg']; 
		// if ($rownum % 2 ) {
            // $row_color = $theme['color_mainbody_bg'];
		// }
        // else {
            // $row_color = $theme['color_navbar_bg'];
		// }

		echo "<tr class='bg$row_color'>";

		// --------------------------------------------
		// Selector
		if ( $can_edit ) {
			echo "<td><input type='checkbox' name='selpage[$pagename]'></td>\n";
		}

		// --------------------------------------------
		// Index
		$index = $rownum + 1;
		echo "<td class='right'>$index</td>\n";

		// --------------------------------------------
		// Upload Block

		// Image
		$imagename = $page_res['image'];
        $pagename  = $page_res['pagename'];
		if (file_exists($path . $imagename)) {
			$bgcolor = $row_color;
		}
        else {
			$bgcolor = "#FF0000";
		}
		echo "<td class='bg$bgcolor'>\n";
        echo link_to_view_image($projectid, $pagename, $imagename);

		// Master Text
		$master_text_length = $page_res['length(master_text)'];
		echo "<td class='right'>
            <a href='$code_url/tools/project_manager/downloadproofed.php"
                        ."?projectid=$projectid"
                        ."&amp;pagename=$pagename"
                        ."&amp;round_num=0'>$master_text_length&nbsp;b</a></td>\n";

		// --------------------------------------------

		// Page State
		$page_state = $page_res['state'];
		echo "<td>$page_state</td>\n";

		if (page_state_is_an_avail_state($page_state))
			$avail_pages.="$index,";

		// --------------------------------------------
		// Per-Round Info

		if ($can_see_names_for_all_pages) {
			$can_see_names_for_this_page = TRUE;
		}
		else {
			// The user can see the names of all the proofers for this page
			// only if they are one of those proofers.
			$can_see_names_for_this_page = FALSE;
			foreach ( $rounds_to_display as $round ) {
				$proofer_name = $page_res[$round->user_column_name];
				if ( $proofer_name != '' && $proofer_name == $User->Username() ) {
					$can_see_names_for_this_page = TRUE;
					break;
				}
			}
		}

	    $rounds = array("P1", "P2", "P3", "F1", "F2");
		$prev_rn = 0;
		    for($rn = 0; $rn < 5; $rn++) {
//		foreach ( $rounds_to_display as $round ) {
//			$rn = $round->round_number;
            echo_cells_for_round($rn, $prev_rn, $page_res, $projectid, $phase,
                        $can_edit, $can_see_names_for_this_page); 
            $prev_rn = $rn;
		}

		// (It's annoying that we have to pass all those args,
		// or else make them global, which I don't want to do.)

		// --------------------------------------------
		// Link to proofing interface
		if ($phase == 'PAGE_EDITING') {
			if ($page_res[$project_round->user_column_name] == $User->Username()
				&& $page_state != $project_round->page_avail_state)  {
                $link = link_to_proof_page($projectid, $pagename, "Edit");
				// $url = url_for_pi_do_particular_page(
					// $projectid, $state, $imagename, $page_state );
				echo "<td>$link</td>\n";
			}
			else {
				echo "<td></td>";
            }
		}

		// --------------------------------------------
		// PM/Admin project editing
		if ($can_edit) {

			// Bad Page
			$page_is_in_bad_state = page_state_is_a_bad_state($page_state);

			echo "<td>";
			if ($page_is_in_bad_state) {
				echo "<a class='center bold' href='$code_url/tools/project_manager/handle_bad_page.php"
                            ."?projectid=$projectid"
                            ."&amp;pagename=$pagename'>Bad</a>";
			} 
            else {
				echo "
                <a class='center' href='$code_url/tools/project_manager/handle_bad_page.php"
                        ."?projectid=$projectid"
                        ."&amp;pagename=$pagename'>Fix</a>\n";
			}
			echo "</td>\n";

			// Delete
			if ($show_delete) {
				echo "<td><a href='$code_url/tools/project_manager/edit_pages.php"
                        ."?projectid=$projectid"
                        ."&amp;selpage=$pagename"
                        ."&amp;operation=delete'>Delete</a></td>\n";
			}
		}

		echo "</tr>";
	}
	echo "</table>";

    if( $can_edit ) {

?>
<script type="text/javascript"><!--
// var avail_pages = new Array(<? echo substr($avail_pages, 0, -1); ?>);
var avail_pages = {<?php echo substr($avail_pages, 0, -1); ?>};
var pagesform   = document.getElementById("pagesform");

function changeSelection(sel) {
    var i;
	switch(sel) {
		case "all":
			for(i = 1; i <= <? echo $num_rows; ?>; i++) {
				pagesform.elements[i].checked=true;
            }
			break;

		case "unproofed":
			changeSelection("clear");
			// for(i in avail_pages) {
            for(i = 1; i <= <? echo $num_rows; ?>; i++) {
				pagesform.elements[avail_pages[i]].checked=true;
            }
			break;

		case "invert":
			for(i = 1; i <= <? echo $num_rows; ?>; i++) {
				pagesform.elements[i].checked = ! pagesform.elements[i].checked;
            }
			break;

		case "clear":
			for(i = 1; i <= <? echo $num_rows; ?>; i++) {
				pagesform.elements[i].checked=false;
            }
			break;

		default:
			break;
	}
}
// --></script>
<?PHP
		echo "<br>\n";
		echo "<select name='select' onChange='changeSelection(this.options[this.selectedIndex].value)'>\n";
		echo "<option value='0'>"._("Select...")."</option>";
		echo "<option value='all'>"._("Select all")."</option>";
		echo "<option value='unproofed'>"._("Select all unproofread")."</option>";
		echo "<option value='invert'>"._("Invert selection")."</option>";
		echo "<option value='clear'>"._("Clear selection")."</option>";
		echo "</select>. ";
		echo _("For each selected page:") . "\n";
		echo "<select name='operation'>\n";
		// echo "  <option value='bad'   >" . _("Mark as bad") . "</option>\n";
		echo "  <option value='clear' >" . _("Clear effects of current round") . "</option>\n";
		echo "  <option value='delete'>" . _("Delete") . "</option>\n";
		echo "</select>\n";
		echo "<input type='submit' value='" . _("Go") . "'>\n";
		echo "</form>\n";

		// We can't easily do a multipage mark-as-bad,
		// because it requires a per-page reason.
	}

}

// -----------------------------------------------------------------------------

// Returns an array of Rounds representing those rounds
// for which the given project has some useful data.
/*
function get_rounds_with_data( $projectid ) {
    global $dpdb;
	$rounds_with_data = array();

	// Currently, when a project skips a round, the time and text of the
	// corresponding slot are still set. It's this data that we exclude by
	// talking about "useful data" above.
	// So a column of empty users is the best indication of a skipped round.
	// (And also works for a not-yet-reached round.)
    // But not if a project is set back a round due to an error.

	global $Round_for_round_id_;

	$sums = array();
	foreach ( $Round_for_round_id_ as $round_id => $round ) {
		$sums[] = "SUM({$round->user_column_name} != '') AS $round_id";
	}
	$body = join($sums, ',');

	$res = mysql_query("
		SELECT $body FROM $projectid") or die(mysql_error());
	$num_filled_user_fields_for_round_ = mysql_fetch_assoc($res);

	foreach ( $Round_for_round_id_ as $round_id => $round ) {
        if($num_filled_user_fields_for_round_ > 0) {
            $rounds_with_data[] = $round;
        }
	}

	return $rounds_with_data;
}
*/

// -----------------------------------------------------------------------------

function echo_cells_for_round( $round_num, $diff_round_num, $page_res,
            $projectid, $phase, $can_edit, $can_see_names_for_this_page) {

	global $User, $code_url;

	// test
	//$can_edit = FALSE;
    assert(isset($diff_round_num));

	// $imagename  = $page_res['image'];
	$pagename     = $page_res['pagename'];
	$page_state = $page_res['state'];

	$round = get_Round_for_round_number($round_num);
	assert( !is_null($round) );

	$R_username = $page_res[$round->user_column_name];

    $cellcolor = "";

   // colour-code pages proofread by the user
    if ($R_username == $User->Username()) {

        // a page saved as DONE by the user has a greenish background
        if ($page_state == $round->page_save_state) {
                $cellcolor = "bg99FF66";
        }

        // a page saved as IN PROGRESS by the user has an orangey background
        else if ($page_state == $round->page_temp_state 
            || $page_state == $round->page_out_state) {
                $cellcolor = "bgFFCC66";
        }

        // a page by the user but not available for editing has a reddish background
        else if ($page_state != $round->page_avail_state) {
                $cellcolor = "bgFF3366";
        }
    }

	// ------------------------------
	// Diff

	echo "<td class='center'>";
	if ($page_state != $round->page_avail_state) {
		if($page_res[$round->textdiff_column_name]) {
			echo _("no diff");
		}
        else {
	        echo link_to_diff($projectid, $pagename, $round->id, true);
//			echo "<a href='$code_url/tools/project_manager/diff.php"
//                    ."?projectid=$projectid"
//                    ."&amp;pagename=$pagename"
//                    ."&amp;roundid={$round->id}'>"._("diff")."</a>";
		}
	}
	echo "</td>\n";


	// ------------------------------
	// Date

	$R_time = $page_res[$round->time_column_name];
	if ($R_time == 0) {
		$R_time_str = '';
	}
	else {
		$R_time_str = strftime(_("%Y %b %e %H:%M"), $R_time);
	}
	echo "<td>$R_time_str</td>\n";

	// ------------------------------
	// User

    if ($R_username == '') {
        echo "<td></td>\n";
    }
    else if ($can_see_names_for_this_page) {
        $R_pages_completed = $page_res["$round_num.user_page_tally"];

        echo "<td class='center $cellcolor' >\n";
        echo link_to_pm($R_username, "$R_username ($R_pages_completed)") ."</td>\n";
    }
    else {
            echo "<td></td>\n";
    }

	// ------------------------------
	// Text

	$R_text_length = $page_res["length($round->text_column_name)"];

	if ( $R_text_length == 0 ) {
		echo "<td class='$cellcolor'> </td>\n";
	}
	else {
		echo "<td class='right $cellcolor' >
            <a href='$code_url/tools/project_manager/downloadproofed.php"
                ."?projectid=$projectid"
                ."&amp;pagename=$pagename"
                ."&amp;round_num=$round_num'>$R_text_length&nbsp;b</a></td>\n";
	}

	// ------------------------------
	// Clear

	if ($can_edit) {
		// Anticipate the tests in page_clear()
		if ( $phase=='PAGE_EDITING' && $page_state == $round->page_save_state) {
			echo "<td><a href='$code_url/tools/project_manager/edit_pages.php"
                                ."?projectid=$projectid"
                                ."&amp;selpage=$pagename"
                                ."&amp;operation=clear'>"._("Clear")."</a></td>";
		}
		else {
			// page_clear() won't let anything happen
			echo "<td></td>";
		}
	}
	echo "\n";
}

// -----------------------------------------------------------------------------

function page_state_is_an_avail_state($page_state) {
	for ( $rn = 1; $rn <= MAX_NUM_PAGE_EDITING_ROUNDS; $rn++ ) {
		$round = &nbsp;get_Round_for_round_number($rn);
		if ( $page_state == $round->page_avail_state ) {
			return TRUE;
		}
	}
	return FALSE;
}

function page_state_is_a_bad_state($page_state) {
	for ( $rn = 1; $rn <= MAX_NUM_PAGE_EDITING_ROUNDS; $rn++ ) {
		$round = get_Round_for_round_number($rn);
		if ( $page_state == $round->page_bad_state ) {
			return TRUE;
		}
	}
	return FALSE;
}

?>
