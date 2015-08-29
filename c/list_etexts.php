<?php
$relPath="./pinc/";
include_once($relPath.'dpinit.php');

$rowsperpage = 500;

$metal    = Arg("metal", "Gold");
$pagenum  = Arg("pagenum", "1");
$pageup   = IsArg("pageup");
$pagedown = IsArg("pagedown");

$boilerplate = "These e-texts are the product of hundreds of hours of labor
    donated by all of our volunteers. The list is sorted with the most recently
    submitted e-texts at the top. You can sort them based upon your own preferences
    by clicking below. Enjoy!!";

switch($metal) {
    case "Gold":
        $info = "Below is the list of Gold e-texts that have been produced on
			this site. Gold e-texts are books that have passed through all phases of
			proofreading, formatting, and post-processing. They have been submitted
			to FadedPage.com, and are now available for your enjoyment and download.";
        $where = "phase = 'POSTED'";
        break;

    case "Silver":
        $info = "Below is the list of Silver e-texts that have almost completed
			processing on our site. Silver e-texts are books that have passed
			through all phases of proofreading and formatting and are now in
			the post-processing phase. Post-processing is the final assembly
			stage in which one volunteer performs a series of checks for consistency
			and correctness before the e-book is submitted to Project Gutenberg Canada
			for your enjoyment and download.";
        $where = "phase IN ('PP', 'PPV')";
        break;

    case "Bronze":
        $info = "Below is the list of Bronze e-texts that are currently
			available for proofreading on this site. Bronze e-texts are what our
			newest volunteers see and what you can work on now by logging in.
			These e-texts are in the initial stages of proofreading
			where everyone has a chance to correct any OCR errors which may be found.
			After going through a number of other phases, the e-text then goes to
			an experienced volunteer for final assembly (post-processing), after
			which the e-text is submitted to Project Gutenberg Canada for your enjoyment
			and download.";
        $where = "phase IN ('P1', 'P2', 'P3', 'F1', 'F2')";
        break;

   default:
       $info = "";
       break;
}
theme("$metal E-Texts", "header");
echo "
<p class='ph2'>$metal E-Texts</p>\n";

echo "<p>$info</p>\n";

if ($metal == "Gold") {
    echo "<a href='list_etexts.php?metal=Silver'>Silver</a> 
        | <a href='list_etexts.php?metal=Bronze'>Bronze</a>";
} else if ($metal == "Silver") {
    echo "<a href='list_etexts.php?metal=Gold'>Gold</a>   
        | <a href='list_etexts.php?metal=Bronze'>Bronze</a>";
} else if ($metal == "Bronze") {
    echo "<a href='list_etexts.php?metal=Gold'>Gold</a>   
        | <a href='list_etexts.php?metal=Silver'>Silver</a>";
}

$nproj = $dpdb->SqlOneValue( "SELECT COUNT(1) FROM projects WHERE $where" );

$pagenum = (int) $pagenum;

if($pagedown) {
    $pagenum++;
}
if($pageup) {
    $pagenum--;
}
$maxpage = ceil((int) $nproj / $rowsperpage);
$pagenum = max($pagenum, 1);
$pagenum = min($pagenum, $maxpage);
$updisabled = $pagenum > 1 ? "" : "disabled";
$downdisabled = ($pagenum < $maxpage ? "" : "disabled");

echo "<br>
        <form name='frmarchive' id='frmarchive' method='POST' action=''>
        <input type='hidden' name='metal' value='$metal' />
        <input type='hidden' name='pagenum' value='$pagenum' />
        <input type='hidden' name='nproj' value='$nproj' />
        <input type='hidden' name='maxpage' value='$maxpage' />
        <input type='submit' name='pageup' id='pageup' value='Page Up' $updisabled>
        Page {$pagenum} of {$maxpage}
        <input type='submit' name='pagedown' id='pagedown' value='Page Down' $downdisabled>
        </form>\n";

list_projects($where, $pagenum);
theme("", "footer");

function metal_count($where) {
    global $dpdb;
    return $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM projects
        $where");
}

// List the specified projects,
// giving brief information about each.
function list_projects( $where, $pagenum ) {
    global $dpdb;
    global $rowsperpage;

    $minrow = $rowsperpage * ($pagenum - 1);
    $sql = "
        SELECT
            projectid,
            nameofwork title,
            authorsname author,
            language,
            n_pages,
            postednum,
            DATE_FORMAT(DATE(FROM_UNIXTIME(phase_change_date)), '%b %D, %Y') moddate
        FROM projects
        WHERE $where
        ORDER BY phase_change_date DESC
        LIMIT $minrow, $rowsperpage";

    echo html_comment($sql);
    $rows = $dpdb->SqlRows($sql);



    $counter = $minrow;
    foreach($rows as $project) {
        $counter++;
        $title = maybe_convert($project['title']);
        $author = maybe_convert($project['author']);
        $language = $project['language'];
        $n_pages = $project["n_pages"];
        $moddate = $project["moddate"];
        $postednum = $project['postednum'];

        echo "<p>$counter) \"$title\" by <b>$author</b> ($language)<br>
            " . _("$n_pages pages; posted ") . "$moddate\n";
        // Download info
        if ( $postednum ) {
            echo link_to_fadedpage_catalog($postednum);
        }
        echo "</p>\n";
    }
}

