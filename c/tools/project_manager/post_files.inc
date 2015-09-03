<?PHP

global $relPath;
require_once $relPath . 'dpinit.php';
include_once($relPath.'comment_inclusions.inc');
include_once($relPath.'stages.inc');
include_once($relPath.'DpProject.class.php');

// -----------------------------------------------------------------------------
// generate a zip file on the fly and download it
function download_project_zip($project, $round_id, $eq_lt = "LT", $proofer_names = false) {
    global $dpdb;
    /** @var DpProject $project */
    $projectid = $project->ProjectId();
    $filename = $project->ProjectId();
    if ($round_id == '[OCR]') {
        $filename .= '_OCR';
    }
    else {
        $filename .= '_' . $round_id;
        $filename .= ('EQ' == $eq_lt)
            ? '_saved'
            : '_latest';
    }

    switch($round_id) {
        case "[OCR]":
            $txtfld = "master_text";
                break;

        case "P1":
            $txtfld = ('EQ' == $eq_lt)
                ? "round1_text"
                : "COALESCE(round1_text, master_text)";
                break;
        case "P2":
            $txtfld = ('EQ' == $eq_lt)
                ? "round2_text"
                : "COALESCE(round2_text, round1_text, master_text)";
                break;
        case "P3":
            $txtfld = ('EQ' == $eq_lt)
                ? "round3_text"
                : "COALESCE(round3_text, round2_text, round1_text, master_text)";
                break;
        case "F1":
            $txtfld = ('EQ' == $eq_lt)
                ? "round4_text"
                : "COALESCE(round4_text, round3_text, round2_text, round1_text, master_text)";
                break;
        case "F2":
            $txtfld = ('EQ' == $eq_lt)
                ? "round5_text"
                : "COALESCE(round5_text, round4_text, round3_text, round2_text, round1_text, master_text)";
                break;

        default:
            die("Invalid round requested for export: $round_id");
    }
    $rows = $dpdb->SqlRows("
        SELECT image, fileid, state AS pagestate, 
            round1_user, round2_user, round3_user, round4_user, round5_user, 
            $txtfld AS pagetext
        FROM $projectid
        ORDER BY fileid");
    $filedata = "";
    // join the page texts together
    foreach($rows as $row) {
        $txt = build_page_text($row, $proofer_names);
        $filedata .= $txt;
    }

    // zip it all up

    send_zip($filedata, $filename);
    return;
    
}

/*
// Generate the files needed for post-processing.
function generate_post_files( $project,  $round_id, $eq_lt = "LT", $proofer_names = false ) {
    global $projects_dir;

    $projectid = $project->ProjectId();
    $projectpath = "$projects_dir/$projectid";
    $pathbase = "$projectpath/$projectid";

    // Generate comments html file.
    $comments_path = "{$pathbase}_comments.html";
    $txt = adorn_project_comments($project->Comments());
    file_put_contents($comments_path, $txt);

    $concat_text = ConcatenatedText($project, $round_id, $eq_lt, $proofer_names);

    // Join all the page texts into a plain text file...
    $plain_path = "{$pathbase}.txt";
    file_put_contents($plain_path, $concat_text);
    $plain_zip_path = "{$pathbase}.zip";
    exec ("zip -j $plain_zip_path $plain_path $comments_path");

    // Join all the page texts into a TEIXLITE file...
    $tei_path = "{$pathbase}_TEI.txt";
    $txt = join_proofed_text_tei($project, $proofer_names);
    file_put_contents($tei_path, $txt);
    $tei_zip_path = "{$pathbase}_TEI.zip";
    $z = zip_open($tei_zip_path);
    zip_close($z);
}
*/

function clean_up_temp($dirname, $textfile_path) {
    // now we must clean up behind ourselves
    unlink($textfile_path);
    rmdir($dirname);
}

function send_zip( $data, $filename ) {
    $dirname = "/sharehome/temp/export/".uniqid("_export");

    mkdir($dirname, 0777);      // and make the directory
    $outfile = build_path($dirname, $filename) . ".txt";
    file_put_contents($outfile, $data);
    register_shutdown_function("clean_up_temp", $dirname, $outfile);
    $zipfile = $filename . ".zip";

 

    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename="'.$zipfile.'"');
    passthru("zip -q -j - $outfile", $return_code);
}


// -----------------------------------------------------------------------------

function adorn_project_comments($comments) {
    $comments = parse_project_comments($comments); // insert e.g. templates and biographies
    return "<html><body>
    $comments
    </body></html>";
}

// -----------------------------------------------------------------------------

$carriagereturn = chr(13);   
$linefeed = chr(10);


function join_proofed_text_tei ($project, $proofer_names) {
    // Join the round 2 page-texts of the given project,
    /** @var DpProject $project */

    global $code_url, $projects_url;

    $projectid = $project->ProjectId();
    $nameofwork  = $project->NameOfWork();
    $authorsname = $project->AuthorsName();
    $postednum   = $project->PostedNumber();

    $rows = $project->PageRows();

    $out = "<TEI.2>\r\n";
    // The info for this might preferably come from
    // a database of MARC records.
    $out .= "    <teiHeader>\r\n";
    $out .= "        <fileDesc>\r\n";
    $out .= "            <titleStmt>\r\n";
    $out .= "                <title>$nameofwork</title>\r\n";
    $out .= "                <author>$authorsname</author>\r\n";
    $out .= "            </titleStmt>\r\n";
    $out .= "            <publicationStmt>\r\n";
    $out .= "                <publisher>Project Gutenberg</publisher>\r\n";
    $out .= "                <pubPlace>Urbana</pubPlace>\r\n";
    $out .= "                <date>2003</date>\r\n";
    $out .= "                <idno type='PGnum'>$postednum</idno>\r\n";
    $out .= "                <idno type='DPid'>$projectid</idno>\r\n";
    $out .= "                <availability><p>Public Domain</p></availability>\r\n";
    $out .= "            </publicationStmt>\r\n";
    $out .= "            <sourceDesc>\r\n";
    $out .= "                <p>(Project Gutenberg doesn't like to be specific\r\n";
    $out .= "                as to particular source edition.)</p>\r\n";
    $out .= "            </sourceDesc>\r\n";
    $out .= "        </fileDesc>\r\n";
    $out .= "        <encodingDesc>\r\n";
    $out .= "            <projectDesc>\r\n";
    $out .= "                <p>Produced by [project manager], [post-processor], [scanner?],\r\n";
    $out .= "                and the Online Distributed Proofreading Team at\r\n";
    $out .= "                &lt;$code_url&gt;.</p>\r\n";
    $out .= "                <p>Page-images available at\r\n";
    $out .= "                &lt;$projects_url/$projectid/&gt;</p>\r\n";
    $out .= "            </projectDesc>\r\n";
    $out .= "        </encodingDesc>\r\n";
    $out .= "    </teiHeader>\r\n";
    $out .= "\r\n";
    $out .= "<text>\r\n";
    $out .= "<front>\r\n";
    $out .= "<titlePage>\r\n";
    $out .= "<!-- \r\n";
    $out .= "Move Title Page text to here.\r\n";
    $out .= "Mark it up something like this example:\r\n";
    $out .= "<docTitle>\r\n";
    $out .= "<titlePart type='main'>LOST ON THE MOON</titlePart>\r\n";
    $out .= "<titlePart>OR</titlePart>\r\n";
    $out .= "<titlePart type='alt'>IN QUEST OF THE FIELD OF DIAMONDS</titlePart>\r\n";
    $out .= "<docDate>1911</docDate>\r\n";
    $out .= "</docTitle>\r\n";
    $out .= "<byline>BY<docAuthor>ROY ROCKWOOD</docAuthor>, AUTHOR OF [various other works]</byline>\r\n";
    $out .= " -->\r\n";
    $out .= "</titlePage>\r\n";
    $out .= "<div type='contents'>\r\n";
    $out .= "<!-- \r\n";
    $out .= "Move Table of Contents text to here.\r\n";
    $out .= "Mark it up like so:\r\n";
    $out .= "<list type='ordered'>\r\n";
    $out .= "<item>[title of chapter one]</item>\r\n";
    $out .= "<item>[title of chapter two]</item>\r\n";
    $out .= "...\r\n";
    $out .= "</list>\r\n";
    $out .= " -->\r\n";
    $out .= "</div>\r\n";
    $out .= "</front>\r\n";
    $out .= "<body>\r\n";

    foreach($rows as $row) {
        $text_data = $row['text_data'];
        $filename = $row['image'];

        $proofer_attributes = "";
        if ($proofer_names) {
            $proofer_names = array();
            for($i = 1; $i <= 5; $i++ ) {
                $p = $row["round{$i}_user"];
                if($p) {
                    $proofer_names[] = $p;
                }
            }
            foreach ( $proofer_names as $proofer_name ) {
                $i++;
                $proofer_attributes .= " proofer$i='$proofer_name'";
            }
            $out .= "\r\n\r\n<pb id='$filename'$proofer_attributes/>\r\n\r\n";
        }
        // SENDING PAGE-TEXT TO USER
        // It's an XML file, so the non-markup angle-brackets and ampersands
        // should be entity-encoded, but it's tricky to find them.
        $out .= put_page_text($text_data);
    } //end else

    $out .= "        </body>\r\n";
    $out .= "        <back>\r\n";
    $out .= "        </back>\r\n";
    $out .= "    </text>\r\n";
    $out .= "</TEI.2>\r\n";
    return $out;
}

function put_page_text( $page_text ) {
    // global \r\n;

    $out = "";

    $page_text = preg_replace( "/<i>/ui", "<hi rend='italic'>", $page_text );
    $page_text = preg_replace( "/</i>/ui", "</hi>", $page_text );
    $page_text = preg_replace( "/<b>/ui", "<hi rend='bold'>", $page_text );
    $page_text = preg_replace( "/</b>/ui", "</hi>", $page_text );

    $page_text = preg_replace(
        "/\[Illustration: (.*)\]/ui",
        "\r\n\r\n<figure><head>\1</head></figure>\r\n\r\n",
        $page_text );

    $page_text = preg_replace_callback(
        ';/\*(.*?)\*/;s', 'massage_preformatted_chunk',
        $page_text );

    $page_text = trim( $page_text );



    $chunks = preg_split( "/\r\n(\r\n)+/", $page_text );

    // Handle each chunk:
    for ( $i = 0; $i < count($chunks); $i++ ) {
        $chunk = $chunks[$i];

        if ( $i > 0 ) {
            $out .= "\r\n";
        }

        if ( startswith( $chunk, '<figure>' ) || startswith( $chunk, '<lg>' ) ) {
            $out .= "$chunk\r\n";
        }
        else {
            $out .= "<p>\r\n$chunk\r\n</p>\r\n";
        }
    }
    return $out;
}

// Handle a chunk of preformatted text.
function massage_preformatted_chunk($matches) {

    $s = $matches[1];

    $s = preg_replace_callback(
        '/(\n)( *)([^ ].*) *(\r)/',
        'mark_up_poetry_line',
        $s );

    // And mark the whole thing as poetry.
    return "\r\n\r\n<!-- poem -->$s<!-- poem -->\r\n\r\n";
}

function mark_up_poetry_line($m) {
    $nl     = $m[1];
    $indent = $m[2];
    $words  = $m[3];
    $cr     = $m[4];

    $i = mb_strlen($indent);
    $attr = ( $i > 0 ? " rend='indent($i)'" : "" );
    return "$nl<l$attr>$words</l>$cr";
}

// -----------------------------------------------------------------------------

function ConcatenatedText( $project, $round_id, $eq_lt = "LT", $proofer_names = false ) {
    /** @var DpProject $project */
    $pagerows = $project->PageRows();
    $req_round_index = RoundIndexForId($round_id);

    $projtext = "";
    foreach($pagerows as $pagerow) {
        $projtext .= build_page_text($pagerow, $proofer_names);
    }
    return $projtext;
}

function build_page_text($pagerow, $proofer_names) {

    $imagefile  = $pagerow['image'];
    $pgtext     = $pagerow['pagetext'];
    $pgstate    = $pagerow['pagestate'];

    $separator = "-----File: ($imagefile) $pgstate ---";
    if ($proofer_names) {
        $aproofers = array($pagerow['round1_user'],
                           $pagerow['round2_user'],
                           $pagerow['round3_user'],
                           $pagerow['round4_user'],
                           $pagerow['round5_user']);
        for($i = 0; $i < 5; $i++) {
            if(! $aproofers[$i]) {
                unset($aproofers[$i]);
            }
        }
        $proofer_names = implode(", ", $aproofers);

        $separator .= ("\\" . $proofer_names);
    }
    $separator = str_pad( $separator, 75, '-', STR_PAD_RIGHT );
    return $separator . "\n" . $pgtext . "\n";
}

// vim: sw=4 ts=4 expandtab

