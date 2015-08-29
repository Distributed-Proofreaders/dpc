<?PHP

ini_set('display_errors', 1);
error_reporting(E_ALL);
// Display lists of image sources, or lists of projects that used image sources
// List contents vary with user permissions

/*
 CREATE TABLE `image_sources` (
  `code_name` varchar(10)
  `display_name` varchar(30)
  `full_name` varchar(100)
  `info_page_visibility` int
  `is_active` int
  `url` varchar(200)
  `credit` varchar(200)
  `ok_keep_images` int
  `ok_show_images` int
  `public_comment` varchar(4000)
  `internal_comment` varchar(4000)
 */

$relPath = "../../pinc/";
include_once $relPath . 'dpinit.php';

    $header_text = _("Image Sources");
    $no_stats = 1;
    theme($header_text, "header");
    echo "<h1 class='center'>{$header_text}</h1>\n";


    $query = "
            SELECT src.code_name,
                   src.display_name,
                   src.full_name,
                   is_active,
                   src.url,
                   src.credit,
                   src.internal_comment,
                   src.public_comment,
                   SUM(CASE WHEN phase IN ('P1', 'P2', 'P3', 'F1', 'F2', 'PP', 'PPV')
                       THEN 1 ELSE 0 END)
                   in_progress_count,
                   SUM(CASE WHEN phase = 'POSTED' THEN 1 ELSE 0 END)
                   completed_count
            FROM image_sources src
            LEFT JOIN projects p ON src.code_name = p.image_source
            GROUP BY src.display_name";
    $rows = $dpdb->SqlRows($query);

    echo "<form method='POST' action='image_source_edit.php'>\n";
    echo "
            <div class='right w90'>
            <input type='submit' class='right' id='submit_create_source' name='submit_create_source'
                value='Create New Source' />
            </div>\n";
    $tbl = new DpTable();
    $tbl->AddColumn("^Code<br/>(Edit)", "code_name");
    $tbl->AddColumn("", "code_name", "eedit");
    $tbl->AddColumn("<Display Name", "display_name");
    $tbl->AddColumn("<Full Name", "full_name", "eurl");
    $tbl->AddColumn("^Works in progress", "in_progress_count");
    $tbl->AddColumn("^Works completed", "completed_count");
    $tbl->AddColumn("<Description<hr/>Notes", "public_comment", "ecomment");
    $tbl->SetRows($rows);
    $tbl->EchoTable();
    echo "</form>\n";

theme("", "footer");
exit;

function ecomment($value, $row) {
    return "<div>$value</div><hr/><div>{$row['internal_comment']}</div>\n";
}

function eurl($value, $row) {
    return "<a href=\"{$row['url']}\">$value</a>\n";
}

function eedit($code)
{
    return "<input type='submit' name='src[{$code}]' id='src[\"{$code}\"]' value='Edit'>\n";
}
