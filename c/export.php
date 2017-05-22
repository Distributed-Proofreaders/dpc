<?php
ini_set("display_errors", true);
error_reporting(E_ALL);

function export_round($project, $roundid, $proofers, $exact) {
    global $dpdb, $Context;
    /** @var DpProject $project */
// only people who can see names on the page details page
// can see names here.
    if( $proofers && ! $project->UserMaySeeNames()) {
        $proofers = false;
    }
    $projectid = $project->ProjectId();

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
        $text .= rowtext($row, $proofers);
        $text = maybe_convert($text);
    }
    $text = preg_replace("/\\r/", "\r\n", $text);
    $Context->ZipSendString($projectid, $text);
}


function rowtext($row, $proofers) {
    if ($proofers) {
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
    
    return $separator . "\n" . $row["pagetext"] . "\n";
}

