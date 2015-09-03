<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);


$relPath="../pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'gettext_setup.inc');
include_once($relPath.'stages.inc');
include_once($relPath.'theme.inc');
include_once($relPath.'user_is.inc');
include_once($relPath.'site_news.inc');

$pool_id = Arg("pool_id");

global $User;
/** @var DpThisUser $User */

if($pool_id == "PP") {
    divert("pp.php");
    exit;
}
if($pool_id == "PPV") {
    divert("ppv.php");
    exit;
}
exit;

$pool = get_Pool_for_id($pool_id);
if ( ! $pool ) {
    die("bad 'pool_id' parameter: '$pool_id'");
}

$available_filtertype_stem = "{$pool->id}_av";

// -----------------------------------------------------------------------------

theme("$pool->id: $pool->name", "header");

$username = $User->Username();
$usermay = $User->UserMayWorkInRound($pool_id);
$pool->page_top( $usermay );

show_news_for_page($pool->id);

echo "<br>\n";
echo implode( "\n", $pool->echo_array );

echo "
<br>
<p>If there's a project you're interested in, you can get to a page about that
project by clicking on the title of the work.  (We strongly recommend you
right-click and open this project-specific page in a new window or tab.) The
page will let you see the project comments and check the project in or out as
well as download the associated text and image files.</p>
";

if ( ! $usermay ) {
    echo "<hr width='75%'>\n";
    echo "<p>You have not received permission to work here.</p>\n";
}

// --------------------------------------------------------------
echo "<hr>\n";

$header = _('Books I Have Checked Out');

echo "
    <h2 align='center'>$header</h2>
    <a name='checkedout'></a>\n";
show_projects_in_state_plus( $pool, 'checkedout', " " );
echo "<br> <br><hr>\n";

$header = _('Books Available for Checkout');

echo "<h2 align='center'>$header</h2>\n";

// -------
$label = $pool->name;
$state_sql = " (state = '{$pool->project_available_state}') ";
$filtertype_stem = $available_filtertype_stem;
include($relPath.'filter_project_list.inc');
if (!isset($RFilter)) {
    $RFilter = ""; 
}
// -------

echo "
<a name='available'></a>
<center><b>$header</b></center>\n";
show_projects_in_state_plus( $pool, 'available', $RFilter );
echo "<br><br>";

theme("", "footer");

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

// A wrapped version of show_projects_in_state
// that handles getting and saving the table's sort order.
function show_projects_in_state_plus( $pool, $checkedout_or_available, $RFilter) {
    global $User;
    global $dpdb;
    global $username;

    $ch_or_av = substr( $checkedout_or_available, 0, 2 );
    $order_setting_name = "{$pool->id}_{$ch_or_av}_order";
    $order_param_name = "order_{$checkedout_or_available}";

    // Get saved sort order
    $saved_order = $User->Setting($order_setting_name);
    if(! $saved_order) {
        $saved_order = 'DaysD';
    }

    // Get new sort order, if any
    $new_order = Arg($order_param_name, $saved_order );

    // If order has changed, save it to database
    if ($new_order != $saved_order) {
        $User->SetSetting($order_setting_name, $new_order);
    }

    // -------------------------------------------------------------------

    $table = 1;

    $flip_title = FALSE;
    $flip_author = FALSE;
    $flip_lang = FALSE;
    $flip_genre = FALSE;
    $flip_PgTot = FALSE;
    $flip_Person = FALSE;
    $flip_days = FALSE;

    // $theme = $GLOBALS['theme'];

    if ( $new_order == 'TitleA' ) {
        $orderclause = 'nameofwork ASC';
        $flip_title = TRUE;
    }
    else if ( $new_order == 'TitleD' ) {
        $orderclause = 'nameofwork DESC';
    }
    else if ( $new_order == 'AuthorA' ) {
        $orderclause = 'authorsname ASC, nameofwork ASC';
        $flip_author = TRUE;
    }
    else if ( $new_order == 'AuthorD' ) {
        $orderclause = 'authorsname DESC, nameofwork ASC';
    }
    else if ( $new_order == 'LangA' ) {
        $orderclause = 'language ASC, nameofwork ASC';
        $flip_lang = TRUE;
    }
    else if ( $new_order == 'LangD' ) {
        $orderclause = 'language DESC, nameofwork ASC';
    }
    else if ( $new_order == 'GenreA' ) {
        $orderclause = 'genre ASC, nameofwork ASC';
        $flip_genre = TRUE;
    }
    else if ( $new_order == 'GenreD' ) {
        $orderclause = 'genre DESC, nameofwork ASC';
    }
    else if ( $new_order == 'PgTotA' ) {
        $orderclause = 'n_pages ASC, nameofwork ASC';
        $flip_PgTot = TRUE;
    }
    else if ( $new_order == 'PgTotD' ) {
        $orderclause = 'n_pages DESC, nameofwork ASC';
    }
    else if ( $new_order == 'PersonA' ) {
        $orderclause = "{$pool->foo_field_name} ASC, nameofwork ASC";
        $flip_Person = TRUE;
    }
    else if ( $new_order == 'PersonD' ) {
        $orderclause = "{$pool->foo_field_name} DESC, nameofwork ASC";
    }
    
    // note that we SHOW "days since M", but *order* by M, so the logic is flipped
    else if ( $new_order == 'DaysA' ) {
        $orderclause = 'modifieddate DESC, nameofwork ASC';
        $flip_days = TRUE;
    }
    else if ( $new_order == 'DaysD' ) {
        $orderclause = 'modifieddate ASC, nameofwork ASC';
    }
    else {
        echo "show_projects_in_state.inc: bad order value: '$new_order'";
        exit;
    }


    if ( $checkedout_or_available == 'checkedout' ) {
        $proj_state = $pool->project_checkedout_state;
    }
    else {
        $proj_state = $pool->project_available_state;
    }

    $query = "
        SELECT
            projectid,
            nameofwork,
            authorsname,
            language,
            genre,
            username,
            postproofer,
            ppverifier,
            modifieddate,
            difficulty, 
            round((unix_timestamp() - modifieddate)/(24 * 60 * 60)) as days_avail,
            n_pages,
            comments
        FROM projects p
        WHERE state='$proj_state'
            $RFilter";
    if ( $checkedout_or_available == 'checkedout' ) {
        // The project must be checked-out to somebody.
        // We're only interested if it's checked out to the current user.
        $query .= " AND ppverifier = '$username'";
    }

    $query .= " ORDER BY $orderclause";
    $rows = $dpdb->SqlRows($query);

    if ($table) {
        echo "
            <table align='center' border=1 width=630 bordercolor='#111111'>\n";
    }

    $tds = "<td align='center'";
    $tdm = "";
    $tdc = "</a></td>";

    echo "<tr>";

    $linkbase = "<a href=pool.php?pool_id={$pool->id}&amp;{$order_param_name}=";
    $linkend  = "#{$checkedout_or_available}>";

    $word = _("Title");
    $link = $linkbase . ($flip_title ? "TitleD" : "TitleA") . $linkend;
    echo "$tds>$link$tdm<b>$word$tdc\n";

    $word = _("Author");
    $link = $linkbase . ($flip_author ? "AuthorD" : "AuthorA") . $linkend;
    echo "$tds>$link$tdm<b>$word$tdc\n";

    $word = _("Language");
    $link = $linkbase . ($flip_lang ? "LangD" : "LangA") . $linkend;
    echo "$tds width='85'>$link$tdm<b>$word$tdc\n";

    $word = _("Genre");
    $link = $linkbase . ($flip_genre ? "GenreD" : "GenreA") . $linkend;
    echo "$tds width='85'>$link$tdm<b>$word$tdc\n";

    $word = _("Pages");
    $link = $linkbase . ($flip_PgTot ? "PgTotD" : "PgTotA") . $linkend;
    echo "$tds width='45'>$link$tdm<b>$word$tdc\n";

    $word = $pool->foo_Header;
    $link = $linkbase . ($flip_Person ? "PersonD" : "PersonA") . $linkend;
    echo "$tds width='70'>$link$tdm<b>$word$tdc\n";

    $word = _("Days");
    $link = $linkbase . ($flip_days ? "DaysD" : "DaysA") . $linkend;
    echo "$tds width='35'>$link$tdm<b>$word$tdc\n";

    echo "</tr>";


    $rownum = 0;
    foreach($rows as $book) {
        echo "<tr>\n";

        $title = $book['nameofwork'];
        $author = $book['authorsname'];

        $bgcolor = $pool->listing_bgcolors[$rownum % 2];
        $bgcolor_attr = " bgcolor='$bgcolor'";
        $foo_cell = $book[$pool->foo_field_name];

        $url = url_for_project($book['projectid']);
        echo "\n<td $bgcolor_attr><a href='$url'>$title</a></td>";
        echo "\n<td $bgcolor_attr> $author </td>";
        echo "\n<td $bgcolor_attr align=center> ". $book['language']. " </td>";

        if ($book['difficulty'] == "easy") {
            $genre = _("EASY")." ".$book['genre'];
        }
        else if ($book['difficulty'] == "hard") {
            $genre = _("HARD")." ".$book['genre'];
        }
        else {
            $genre = $book['genre'];
        }

        echo
        "<td $bgcolor_attr align=center>$genre</td>
        <td $bgcolor_attr align=center> ". $book['n_pages']. " </td>
        <td $bgcolor_attr align=center>$foo_cell</td>
        <td $bgcolor_attr align=center> ". $book['days_avail']. " </td>
        </tr>\n";
    }

    if ($table) {
        echo "</table>\n";
    }
}

// vim: sw=4 ts=4 expandtab
