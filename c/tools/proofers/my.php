<?PHP
$relPath='../../pinc/';
include_once($relPath.'dpinit.php');
include_once($relPath.'theme.inc');
// include_once($relPath.'dpsql.inc');
include_once($relPath.'stages.inc');
include_once($relPath.'project_states.inc');
include_once($relPath.'DpTable.class.php');

$username = Arg("username", $pguser);
$sorting  = Arg("sort");
$ordercol = Arg("ordercol", "time");
$orderdir = Arg("orderdir", "D");

if ($userP['i_newwin'] == 1) {
    $newProofWin_js = include($relPath.'js_newwin.inc');
    $theme_args['js_data'] = $newProofWin_js;
    $link_js = "onclick=\"newProofWin('%s'); return false;\"";
}
else {
    $theme_args = array();
    $link_js = '';
}

$qs_username = '';
if ( user_is_a_sitemanager() || user_is_proj_facilitator() ) {
    if ( $username != $pguser ) {
        $qs_username = "username=" . urlencode($username) . '&amp;';
    }
}
else {
    $username = $pguser;
}

if ( $username == $pguser ) {
    $out_title = _("My Projects");
    $heading_proof = sprintf( _("%s, here's a list of the projects you've
    helped format and/or proof"), $username );
    $heading_reserved = sprintf( _("%s, these projects are reserved for you to
    post-process"), $username );
}
else {
    $out_title = $heading_proof = sprintf( _("Projects that '%s' has worked
    on"), $username );

    $heading_reserved = sprintf( _("These projects are reserved for '%s' to
    post-process"), $username );

}

$no_stats = 1;
theme( $out_title, 'header', $theme_args );

echo "<a name='proof' id='proof'></a><h2>$heading_proof</h2>";

// ---------------

/*
$colspecs = array(
    'title' =>
        array(
            'label' => _('Title'),
            'sql'   => 'projects.nameofwork',
        ),
    'state' =>
        array(
            'label' => _('Current State'),
            'sql'   => sql_collater_for_project_state('projects.state'),
        ),
    'round' =>
        array(
            'label' => _('Round Worked In'),
            'sql'   => sql_collater_for_round_id('page_events.round_id'),
        ),
    'time' =>
        array(
            'label' => _('Time of Last Activity'),
            'sql'   => 'max_timestamp',
        ),
);
*/

// if ($sorting == 'proof') {
    // list( $ordercol, $orderdir ) = get_sort_col_and_dir($ordercol, $orderdir);
// }
// ------------------------

// $sql_order = sql_order_spec( $ordercol, $orderdir );

/*
if ( $ordercol != $default_order_col ) {
    // Add the default ordering as a secondary ordering.
    $sql_order .= ", " . sql_order_spec( $default_order_col, $default_order_dir );
}
*/

// $res = dpsql_query("
$rows = $dpdb->SqlRows("
    SELECT  page_events.projectid,
            page_events.round_id,
            FROM_UNIXTIME(MAX(page_events.timestamp)) AS max_timestamp,
            projects.nameofwork,
            projects.state
    FROM page_events 
    LEFT JOIN projects USING (projectid)
    WHERE page_events.username='$username'
        AND page_events.event_type IN ('saveAsDone','saveAsInProgress', 'markAsBad')
        AND projects.archived = 0
        AND projects.state != '".PROJ_DELETE."'
    GROUP BY page_events.projectid, page_events.round_id");

// echo "<table border='1'>";
$tbl = new DpTable();
$tbl->AddColumn("<Title", "nameofwork");
$tbl->AddColumn("^Current state", "state");
$tbl->AddColumn("^Round worked in", "round_id");
$tbl->AddColumn("^Time of last activity", "max_timestamp");
$tbl->SetRows($rows);
$tbl->EchoTable();
exit;

// show_headings($colspecs, 'proof');

/*
while ( $row = mysql_fetch_object($res) ) {
    echo "<tr>\n";

    echo "<td>";
    $url = "$code_url/project.php?id=$row->projectid";
    echo "<a href='$url' ".sprintf($link_js,$url).">$row->nameofwork</a>";
    echo "</td>\n";

    echo "<td nowrap>";
    echo project_states_text( $row->state );
    echo "</td>\n";

    echo "<td align='center'>";
    echo $row->round_id;
    echo "</td>\n";

    echo "<td nowrap>";
    echo strftime( '%Y-%m-%d %H:%M:%S', $row->max_timestamp );
    echo "</td>\n";

    echo "</tr>\n";
}
*/

// echo "</table>\n";
// echo "<br>\n";

// -----------------------------------------------------------------------------

// unset($colspecs);

/*
$colspecs = array(
    'title' =>
        array(
            'label' => _('Title'),
            'sql'   => 'nameofwork',
        ),
    'manager' =>
        array(
            'label' => _('Project Manager'),
            'sql'   => 'username',
        ),
    'state' =>
        array(
            'label' => _('Current State'),
            'sql'   => 'state',
        )
);
*/

// By default, order by state, descending.
// $default_order_col = $ordercol = 'state';
// $default_order_dir = $orderdir = 'D';

// if ($sorting == 'reserved') {
    // list( $ordercol, $orderdir ) = get_sort_col_and_dir();
// }

// $sql_order = sql_order_spec( $ordercol, $orderdir );

// if ( $ordercol != $default_order_col ) {
    // Add the default ordering as a secondary ordering.
    // $sql_order .= ", " . sql_order_spec( $default_order_col, $default_order_dir );
// }

// We're interested in projects that have been created, but haven't *finished*
// being proofread.
// $psd = get_project_status_descriptor('created');
// $antipsd = get_project_status_descriptor('proofed');

$tbl = new DpTable();
$tbl->AddColumn("<Title", "nameofwork");
$tbl->AddColumn("^Project Manager", "username");
$tbl->AddColumn("^Current state", "state");

$sql = "
	SELECT  projectid,
            nameofwork,
            username,
		    state
	FROM projects
	WHERE checkedoutby = '$username'
		AND (state LIKE 'P1%' OR state LIKE 'P2%' OR state LIKE 'P3%'
            OR state LIKE 'F1%' OR state LIKE 'F2%')";

$rows = $dpdb->SqlRows($sql);

$tbl->SetRows($rows);

/*
if(count($rows) > 0) {
    echo "<a name='reserved' id='reserved'></a><h2>$heading_reserved</h2>\n";
    // echo "<table border='1'>";
    // show_headings($colspecs, 'reserved');
    while ( $row = mysql_fetch_object($result) ) {
        echo "<tr>\n";

        echo "<td>";
        echo "<a href='$code_url/project.php?id=$row->projectid'>$row->nameofwork</a>";
        echo "</td>\n";

        echo "<td align='center'>";
        echo $row->username;
        echo "</td>\n";

        echo "<td nowrap>";
        echo project_states_text( $row->state );
        echo "</td>\n";

        echo "</tr>\n";
    }

    echo "</table>\n";
    echo "<br>\n";
}
*/

theme( '', 'footer' );

// -----------------
/*
function get_sort_col_and_dir($ordercol, $orderdir) {
    global $colspecs,$default_order_col, $default_order_dir;
    // $order_col = array_get( $_GET, 'order_col', $default_order_col );
    // $order_dir = array_get( $_GET, 'order_dir', $default_order_dir );

    if ( !isset( $colspecs[$ordercol] ) ) {
        echo "Invalid order_col parameter: '$ordercol'. Assuming '$default_order_col'.<br>\n";
        $ordercol = $default_order_col;
    }

    if ( $orderdir != 'A' && $orderdir != 'D' ) {
        echo "Invalid order_dir parameter: '$orderdir'. Assuming '$default_order_dir'.<br>\n";
        $orderdir = $default_order_dir;
    }
    return array($ordercol,$orderdir);
}
*/

/*
function show_headings($colspecs, $sort_type)
{
    global $qs_username, $orderdir, $ordercol;
    echo "<tr>\n";
    foreach ( $colspecs as $col_id => $colspec ) {
        if ( $col_id == $ordercol ) {
            // This is the column on which the table is being sorted.
            // If the user clicks on this column-header, the result should be
            // the table, sorted on this column, but in the opposite direction.
            $link_dir = ( $orderdir == 'A' ? 'D' : 'A' );
        }
        else {
            // This is not the column on which the table is being sorted.
            // If the user clicks on this column-header, the result should be
            // the table, sorted on this column, in ascending order.
            $link_dir = 'A';
        }
        echo "<th>";
        echo "<a href='?{$qs_username}order_col=$col_id&amp;order_dir=$link_dir&amp;sort=$sort_type#$sort_type'>";
        echo $colspec['label'];
        echo "</a>";
        echo "</th>";
    }
    echo "</tr>\n";
}
*/

/*
function sql_order_spec( $ordercol, $orderdir ) {
    global $default_order_col, $default_order_dir;
    global $colspecs;
    return
        $colspecs[$ordercol]['sql']
        . ' '
        . ( $orderdir == 'A' ? 'ASC' : 'DESC' );
}
*/

// vim: sw=4 ts=4 expandtab
?>

