<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);
// Give information on smooth reading
// including (most importantly) the list of projects currently available

$relPath = '../pinc/';
include_once($relPath.'dpinit.php');
include_once($relPath.'site_news.inc');
include_once($relPath.'page_header.inc');

// ---------------------------------------

$User->IsLoggedIn()
	or RedirectToLogin();

$sql = "
        SELECT p.projectid,
               p.nameofwork,
               p.authorsname,
               p.language,
               p.seclanguage,
               l1.name langname,
               l2.name seclangname,
               p.difficulty,
               p.genre,
               p.n_pages,
               p.postproofer,
               LOWER(p.postproofer) ppsort,
               DATEDIFF(DATE(FROM_UNIXTIME(p.smoothread_deadline)),
                    CURRENT_DATE()) days_left,
               p.username as PM,
               LOWER(p.username) as pmsort
        FROM projects p
        LEFT JOIN languages l1 ON p.language = l1.code
        LEFT JOIN languages l2 ON p.seclanguage = l2.code
        WHERE DATE(FROM_UNIXTIME(smoothread_deadline)) >= CURRENT_DATE()
        ORDER BY p.smoothread_deadline";

$rows = $dpdb->SqlRows($sql);
//foreach($rows as $row) {
//    $projectid = $row['projectid'];
//    UnzipSmoothZipFile($projectid);
//}

$tbl = new DpTable();
$tbl->AddColumn("<Title", "nameofwork", "etitle");
$tbl->AddColumn("<Author", "authorsname");
$tbl->AddColumn("<Language", "langname", "elanguage");
$tbl->AddColumn("<Genre", "genre");

$tbl->AddColumn("<PM", "PM", "epm", "sortkey=pmsort");
$tbl->AddColumn("<PPer", "postproofer", "epm", "sortkey=ppsort");
$tbl->AddColumn("<Download<br>to read", "projectid", "edownload", "sorttable_nosort");
$tbl->AddColumn("^Upload<br>with notes", "projectid", "eupload", "sorttable_nosort");

$tbl->AddColumn(">Pages", "n_pages");
$tbl->AddColumn("^Days<br/>left", "days_left", "edaysleft");
$tbl->SetRows($rows);

$no_stats = 1;
$header_text = _("Smooth Reading Projects");

theme( $header_text, 'header');
echo "<div class='w95'>
<h1 class='center'>$header_text</h1>\n";
show_news_for_page( "SR" );

    echo "
    <p> The goal of 'Smooth Reading' is to read the text attentively, as for
        pleasure, with special attention to punctuation and layout.
        Comparison with the scans is not needed.  Note problems that
        disrupt the sense or the flow of the book.</p>

    <p> There are two primary ways to provide feedback.</p>
	<p><b>1. Take notes in a separate file</b>, associating your remarks with text locations however
	is most convenient for you.</p>
     <p><b>2. Annotate the text as you read.</b>
     Some reader environments let you make notes in the text as you read.
     Report errors by adding, at the location of the issue, a comment of the form
        <span class='red'>[**correction or query]</span>.</p>
    <p>Example: \"that was the end,[** comma?] However, the next day\".</p>

      <p>You can submit either your free-form notes or the annotated file (zip not required)
      using the prompt on the Project Page in the Smooth Reading section,
      or by using the button on this Smooth Reading page, in the 'Upload with notes' column.</p>


<p class='bold'>Detailed instructions are available in the <a href='/wiki/index.php?title=Smooth-reading_FAQ'>
Smooth Reading FAQ</a>.\n</p>

<p class='bold'>HTML and txt formats will open in your browser. If you want
to use your external editor, Right-Click, and select Save Link As...</p>\n

";

$tbl->EchoTable();

echo  "</div>\n";

theme('', 'footer');
exit;

function elanguage($langname, $row) {
	return $langname
	       . ($row['seclangname'] == "" ? "" : "/" . $row['seclangname']);
}

function eupload($projectid) {
	return link_to_smoothed_upload($projectid, "upload");
}
function edownload($projectid) {
    $surls = ProjectSmoothDownloadUrls($projectid);
    $links = [];
    foreach($surls as $type => $url) {
        $links[] = link_to_url($url, $type);
    }
    return implode(" ", $links);
}

function edaysleft($val) {
    return $val == 0
        ? "ends today"
        : $val;
}

function epm($str) {
    return link_to_pm($str);
}

function etitle($title, $row) {
    $projectid = $row["projectid"];
    return link_to_project($projectid, $title);
}

// vim: sw=4 ts=4 expandtab

