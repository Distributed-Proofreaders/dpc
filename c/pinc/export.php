<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);

/*
function round_export_text($projectid, $roundid, $is_proofers, $exact) {
    global $dpdb;
    // only people who can see names on the page details page
    // can see names here.

    if($exact) {
        $textfield =
            ($roundid == "OCR")
                ? "master_text"
                : ($roundid == "P1")
                ? "round1_text"
                : ($roundid == "P2")
                    ? "round2_text"
                    : ($roundid == "P3")
                        ? "round3_text"
                        : ($roundid == "F1")
                            ? "round4_text"
                            : "round5_text";
    }
    else {
        switch($roundid) {
            case "OCR":
                $textfield = "master_text";
                break;
            case "P1":
                $textfield = "COALESCE(NULLIF(round1_text, ''),
                                       master_text)";
                break;
            case "P2":
                $textfield = "COALESCE(NULLIF(round2_text, ''),
                                       NULLIF(round1_text, ''),
                                       master_text)";
                break;
            case "P3":
                $textfield = "COALESCE(NULLIF(round3_text, ''),
                                       NULLIF(round2_text, ''),
                                       NULLIF(round1_text, ''),
                                       master_text)";
                break;
            case "F1":
                $textfield = "COALESCE(NULLIF(round4_text, ''),
                                       NULLIF(round3_text, ''),
                                       NULLIF(round2_text, ''),
                                       NULLIF(round1_text, ''),
                                       master_text)";
                break;
            case "F2":
            case "PP":
            case "newest":
            case "":
                $textfield = "COALESCE( NULLIF(round5_text, ''),
                                        NULLIF(round4_text, ''),
                                        NULLIF(round3_text, ''),
                                        NULLIF(round2_text, ''),
                                        NULLIF(round1_text, ''),
                                        master_text)\n";
                break;
            default:
                die("Unanticipated round_id: $roundid");
        }
    }

    $sql = "
        SELECT  image,
                round1_user,
                round2_user,
                round3_user,
                round4_user,
                round5_user,
                $textfield AS pagetext
        FROM $projectid
        ORDER BY fileid";

    $rows = $dpdb->SqlRows($sql);

    $text = "";
    foreach($rows as $row) {
        $text .= maybe_convert(rowtext($row, $is_proofers));
        // $text = maybe_convert($text);
    }
    $text = preg_replace("/\\r/", "", $text);
    return $text;
}
*/

/*
function export_round($project, $roundid, $is_proofers, $exact) {
    global $Context;
	$text = $project->ExportText($is_proofers);
    $Context->ZipSendString($project->ProjectId(), $text);
}
*/


// download_project_zip( $project, $round_id, $exact, $is_proofers);

/*
function rowtext($row, $is_proofers) {
    if ($is_proofers) {
        $names = "\\" . $row["round1_user"];
        if($row["round2_user"])
            $names .= ("\\" . $row["round2_user"]);
        if($row["round3_user"])
            $names .= ("\\" . $row["round3_user"]);
        if($row["round4_user"])
            $names .= ("\\" . $row["round4_user"]);
        if($row["round5_user"])
            $names .= ("\\" . $row["round5_user"]);
    }
    else {
        $names = "";
    }
        
    $separator = "-----File: " . $row['image'] . "---$names";
    $separator = str_pad($separator, 75, "-", STR_PAD_RIGHT);
    
    return $separator . "\r\n" . $row["pagetext"] . "\r\n";
}
*/

//function clean_up_temp($dirname, $textfile_path) {
//    unlink($textfile_path);
//    rmdir($dirname);
//}

//function send_zip( $data, $filename ) {
//    global $Context;
//    $Context->ZipSendString($filename, $data);
//}