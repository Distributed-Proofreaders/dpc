<?PHP
ini_set("display_errors", true);
error_reporting(E_ALL);
$relPath = "../../pinc/";
include_once($relPath.'dpinit.php');

global $Context;

$User->IsLoggedIn()
    or redirect_to_home();

$projectid          = Arg("projectid");
$round_id           = Arg("round_id", "F2");
$eq_or_le           = Arg("which_text", "LE");
$is_proofers        = Arg("is_proofers", false);

$projectid != ""
    or die( "parameter 'projectid' is empty or unset" );

$project = new DpProject($projectid);

// only people who can see names on the page details page
// can see names here.
if( $is_proofers && ! $project->UserMaySeeNames()) {
    $is_proofers = false;
}

if( $round_id == "F2") {
    $text = "";
	$rows = $dpdb->SqlRows("
		SELECT pvlast.pagename,
                pg.imagefile,
				pvlast.username,
				pvlast.version,
				GROUP_CONCAT(IFNULL(pv1.username, ''),
							IFNULL(pv2.username, ''),
							IFNULL(pv3.username, ''),
							IFNULL(pv4.username, ''),
							IFNULL(pv5.username, '')
							SEPARATOR ' ') proofers

		FROM page_last_versions pvlast

		LEFT JOIN pages pg ON pvlast.projectid = pg.projectid
            AND pvlast.pagename = pg.pagename

		LEFT JOIN page_versions pv1 ON pg.projectid = pv1.projectid
			AND pg.pagename = pv1.pagename AND pv1.phase = 'P1'

		LEFT JOIN page_versions pv2 ON pg.projectid = pv2.projectid
			AND pg.pagename = pv2.pagename AND pv2.phase = 'P2'

		LEFT JOIN page_versions pv3 ON pg.projectid = pv3.projectid
			AND pg.pagename = pv3.pagename AND pv3.phase = 'P3'

		LEFT JOIN page_versions pv4 ON pg.projectid = pv4.projectid
			AND pg.pagename = pv4.pagename AND pv4.phase = 'F1'

		LEFT JOIN page_versions pv5 ON pg.projectid = pv5.projectid
			AND pg.pagename = pv5.pagename AND pv5.phase = 'F2'

        WHERE pvlast.projectid = 'p150730001'

		GROUP BY pg.pagename

		ORDER BY pg.pagename
		" );

    foreach($rows as $row) {
        $text .= rowtext($row, $is_proofers);
        $text = PageVersionText($projectid, $row['pagename'], $row['version']);
    }

    $filepath = build_path($project->ProjectPath(), $projectid);
    $Context->ZipSendString($filepath, $text);
//    file_put_contents($filepath, $text);
//    $zipfilename = $projectid . ".zip";
//
//    send_zip($text, $zipfilename);
    exit;
}

//if($eq_or_le == "eq") {
//    $fields = array("P1" => "round1_text",
//                    "P2" => "round2_text",
//                    "P3" => "round3_text",
//                    "F1" => "round4_text",
//                    "F2" => "round5_text");
//
//    if(! isset($fields[$round_id]))
//        exit;
//
//    $textfield = $fields[$round_id];

    $rows = $dpdb->SqlRows("
        SELECT  pv.pagename,
                pv.username,
                pv.version
        FROM page_versions pv
	    WHERE pv.projectid = '$projectid'
	    	AND pv.phase = '$round_id'
        ORDER BY pv.pagename");

    $text = "";
    foreach($rows as $row) {
        $text .= rowtext($row, $is_proofers);
        $text .= PageVersionText($projectid, $row['pagename'], $row['version']);
    }
    $filepath = build_path($project->ProjectPath(), $projectid);
    $Context->ZipSendString($filepath, $text);
//    $filepath = "$projects_dir/{$projectid}/projectid.txt";
//    file_put_contents($filepath, $text);
//    $zipfilename = $projectid . ".zip";
//
//    send_zip($text, $zipfilename);
//}

//switch($round_id) {
//    case "P1":
//        $textfield = "COALESCE(round1_text, master_text) pagetext";
//        break;
//    case "P2":
//        $textfield = "COALESCE(round2_text, round1_text, master_text) pagetext";
//        break;
//    case "P3":
//        $textfield = "COALESCE(round3_text, round2_text, round1_text, master_text) pagetext";
//        break;
//    case "F1":
//        $textfield = "COALESCE(round4_text, round3_text, round2_text, round1_text, master_text) pagetext";
//        break;
//    default:
//        die("Unanticipated round_id: $roundid");
//}
//
//$rows = $dpdb->SqlRows("
//    SELECT  image,
//            round1_user,
//            round2_user,
//            round3_user,
//            round4_user,
//            round5_user,
//            $textfield pagetext
//    FROM $projectid
//    ORDER BY fileid");

//$text = "";
//foreach($rows as $row) {
//    $text .= rowtext($row, $is_proofers);
//    $text = maybe_convert($text);
//}
//$filepath = build_path($project->ProjectPath(), $projectid);
//$filepath = "$projects_dir/{$projectid}/projectid.txt";
//file_put_contents($filepath, $text);
//$zipfilename = $projectid . ".zip";
//
//send_zip($text, $zipfilename);
//exit;


// download_project_zip( $project, $round_id, $eq_or_le, $is_proofers);

function rowtext($row, $is_proofers) {
    if ($is_proofers) {
	    $proofers = " proofer names go here ";
//        $proofers = $row["username"];
//        if($row["round2_user"])
//            $proofers .= (", " . $row["round2_user"]);
//        if($row["round3_user"])
//            $proofers .= (", " . $row["round3_user"]);
//        if($row["round4_user"])
//            $proofers .= (", " . $row["round4_user"]);
//        if($row["round5_user"])
//            $proofers .= (", " . $row["round5_user"]);
    }
    else {
        $proofers = "";
    }
        
    $info_str = "-----File: (" . $row['image'] . ") ---\\{$proofers}";
    $info_str = str_pad($info_str, 75, "-", STR_PAD_RIGHT);
    
    return $info_str . "\n" . $row["pagetext"];
}

//function clean_up_temp($dirname, $textfile_path) {
//    unlink($textfile_path);
//    rmdir($dirname);
//}
//
//function send_zip( $data, $filename ) {
//    $dirname = "/sharehome/temp/export/".uniqid("_export");
//
//    mkdir($dirname, 0777);      // and make the directory
//     put the data into a .txt file in the temp directory
//    $outfile = build_path($dirname, $filename) . ".txt";
//    file_put_contents($outfile, $data);
//    register_shutdown_function("clean_up_temp", $dirname, $outfile);
//    $zipfile = $filename . ".zip";
//
//    header('Content-type: application/zip');
//    header('Content-Disposition: attachment; filename="'.$zipfile.'"');
//    passthru("zip -q -j - $outfile", $return_code);
//}

