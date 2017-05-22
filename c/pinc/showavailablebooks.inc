<?php

/*
function show_block_for_round( $round_number, $projects_filter ) {
	global $userP;
    global $User;

	$round = get_Round_for_round_number($round_number);

	$setting = "{$round->id}_order";

	// read saved sort orders from user_settings table 
    $order_old = $User->Setting($setting);
    if($order_old == "") {
		$order_old = 'DaysD';
	}


	// read new sort order from url, if any
    $order_new = Arg("url_param", $order_old);

	// if orders have changed, save them to database
	if ($order_new != $order_old) {
        $User->SetSetting($setting, $order_new);
	}

	echo "<a name='{$round->id}'></a>
	<table border=1>
	<tr bgcolor='{$round->listing_bgcolors[1]}'>\n";

	$title = _('Projects Currently Available');
	echo "
    <td colspan=2><h3>$title</h3></td>
    <td colspan=6>{$round->description}</td>
    </tr>\n";

    show_available_books( $round, $userP, $projects_filter, $order_new );

	echo "
    </table>
   <br>\n";
}

// -----------------------------------------------------------------------------

function show_available_books( $round, $userP, $where_filter = " ", $order = "DaysD" ) {
	global $code_url;
    global $dpdb;

	$flip_title = FALSE;
	$flip_author = FALSE;
	$flip_lang = FALSE;
	$flip_genre = FALSE;
	$flip_PM = FALSE;
	$flip_PgAv = FALSE;
	$flip_PgTot = FALSE;
	$flip_days = FALSE;
	
	if ( $order == 'TitleA' ) {
		$orderclause = 'nameofwork ASC';
		$flip_title = TRUE;
	}
	else if ( $order == 'TitleD' ) {
		$orderclause = 'nameofwork DESC';
	}
	else if ( $order == 'AuthorA' ) {
		$orderclause = 'authorsname ASC, nameofwork ASC';
		$flip_author = TRUE;
	}	
	else if ( $order == 'AuthorD' ) {
		$orderclause = 'authorsname DESC, nameofwork ASC';
	}
	else if ( $order == 'LangA' ) {
		$orderclause = 'language ASC, nameofwork ASC';
		$flip_lang = TRUE;
	}
	else if ( $order == 'LangD' ) {
		$orderclause = 'language DESC, nameofwork ASC';
	}
	else if ( $order == 'GenreA' ) {
		$orderclause = 'genre ASC, nameofwork ASC';
		$flip_genre = TRUE;
	}
	else if ( $order == 'GenreD' ) {
		$orderclause = 'genre DESC, nameofwork ASC';
	}
	else if ( $order == 'PMA' ) {
		$orderclause = 'username ASC, nameofwork ASC';
		$flip_PM = TRUE;
	}
	else if ( $order == 'PMD' ) {
		$orderclause = 'username DESC, nameofwork ASC';
	}
	else if ( $order == 'PgAvA' ) {
		$orderclause = 'n_available_pages ASC, nameofwork ASC';
		$flip_PgAv = TRUE;
	}
	else if ( $order == 'PgAvD' ) {
		$orderclause = 'n_available_pages DESC, nameofwork ASC';
	}
	else if ( $order == 'PgTotA' ) {
		$orderclause = 'n_pages ASC, nameofwork ASC';
		$flip_PgTot = TRUE;
	}
	else if ( $order == 'PgTotD' ) {
		$orderclause = 'n_pages DESC, nameofwork ASC';
	}

	// note that we SHOW "days since M", but *order* by M, so the logic is flipped

	else if ( $order == 'DaysA' ) {
		$orderclause = 'phase_change_date DESC, nameofwork ASC';
		$flip_days = TRUE;
	}
	else if ( $order == 'DaysD' ) {
		$orderclause = 'phase_change_date ASC, nameofwork ASC';
	}
	else {
		echo "showavailablebooks.inc: bad order value: '$order'";
		exit;
	}

	$order_param = "order{$round->id}";

	// The originating request may have query-string settings (other than
	// for $order_param). We should preserve those, and just append the
	// setting for $order_param.
	$other_settings = '';
	foreach ( $_GET as $name => $value ) {
		if ( $name != $order_param ) {
			$other_settings .= "$name=$value&amp;";
		}
	}

	$linkbase = "<a href='?{$other_settings}{$order_param}=";
	$linkend = "#{$round->id}'";

	// Always put the BEGIN projects at the top.
    $state = $round->project_available_state;
	$query = "
		SELECT *,
			(difficulty = 'beginner') AS is_beginner,
			DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(phase_change_date)) AS days_avail
		FROM projects p
		WHERE (state = '$state')
			$where_filter
	    GROUP BY p.projectid
		ORDER BY
			is_beginner DESC,
			$orderclause";
    echo html_comment($query);
    $books = $dpdb->SqlRows($query);

	echo "<tr class='center' bgcolor='{$round->listing_bgcolors[1]}'>";

	$word = _("Title");
 	$link = $linkbase.($flip_title ? "TitleD" : "TitleA")."$linkend>";
	echo "\n<td>$link<b>$word</b></a></td>";

	$word = _("Author");
 	$link = $linkbase . ($flip_author ? "AuthorD" : "AuthorA")."$linkend>";
	echo "\n<td>$link<b>$word</b></a></td>";

	$word = _("Language");
 	$link = $linkbase.($flip_lang ? "LangD" : "LangA")."$linkend>";
	echo "\n<td>$link<b>$word</b></a></td>";

	$word = _("Genre");
 	$link = $linkbase.($flip_genre ? "GenreD" : "GenreA")."$linkend>";
	echo "\n<td>$link<b>$word</b></a></td>";

	$word = _("Project Manager");
 	$link = $linkbase.($flip_PM ? "PMD" : "PMA")."$linkend>";
	echo "\n<td>$link<b>$word</b></a></td>";

	$word = _("Available Pages");
 	$link = $linkbase.($flip_PgAv ? "PgAvD" : "PgAvA")."$linkend>";
	echo "\n<td>$link<b>$word</b></a></td>";

	$word = _("Total Pages");
 	$link = $linkbase.($flip_PgTot ? "PgTotD" : "PgTotA")."$linkend>";
	echo "\n<td>$link<b>$word</b></a></td>";

	$word = _("Days");
 	$link = $linkbase.($flip_days ? "DaysD" : "DaysA")."$linkend>";
	echo "\n<td>$link<b>$word</b></a></td>";

	echo "</tr>";

	// Determine whether to use special colors or not
	// (this does not affect the alternating between two
	// background colors) in the project listing.
	// Regardless of the preference, don't display
	// special colors to newbies.

	// $numrows = mysql_num_rows($result);
	$rownum = 0;
	$rownum2 = 0;

    foreach($books as $book) {
		$bgcolor = $round->listing_bgcolors[$rownum % 2];
        $projectid = $book['projectid'];
        $state = $book['state'];

        echo "<tr bgcolor='$bgcolor'>";
        $eURL = "$code_url/project.php"
                        ."?id={$projectid}"
                        ."&amp;expected_state={$state}";
        if ($userP['i_newwin'] == 0) {
            $temp = "<a href='$eURL'>";
        }
        else {
            $temp = "<a href='$eURL' onclick='newProofWin(\"$eURL\"); return false;'>";
        }
        $nameofwork = maybe_convert($book['nameofwork']);
        $authorsname = maybe_convert($book['authorsname']);
        $language = $book['language'];
        $difficulty = $book['difficulty'];
        $genre = $book['genre'];
        $pm = $book['username'];
        $pm = link_to_pm($pm);

        $temp .= "{$nameofwork}</a>";
        echo "\n<td>$temp</td>";
        echo "\n<td>{$authorsname}</td>";
        echo "\n<td>{$language}</td>";
        if ($difficulty == "beginner") {
            if ( $round->is_a_mentee_round() )
                $genre = _("BEGINNERS ONLY")." ".$genre;
            else if ( $round->is_a_mentor_round() )
                $genre = _("MENTORS ONLY")." ".$genre;
        }
        else if ($difficulty == "easy") {
            $genre = _("EASY")." ".$genre;
        }
        else if ($difficulty == "hard") {
            $genre = _("HARD")." ".$genre;
        }
        echo "\n<td>$genre</td>";
        echo "\n<td>{$pm}</td>";

        echo "\n<td class='center'>{$book['n_available_pages']}</td>";
        echo "\n<td class='center'>{$book['n_pages']}</td>";

        echo "\n<td class='center'>{$book['days_avail']}</td>";

		$rownum++;
		$rownum2++;
	}
}
*/

