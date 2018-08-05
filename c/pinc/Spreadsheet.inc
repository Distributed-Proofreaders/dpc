<?php

$googleKey = "1lU5VeCLMIPhkqqGc-g0iE7Ld5EU5b-sI7DcoKiUwZPo";
$googleURL = "https://spreadsheets.google.com/feeds/list/$googleKey/od6/public/full?alt=json";

// Load the Clearance Spreadsheet.
// Returns an array, each entry being a row of the spreadsheet
function loadClearanceSpreadsheet($qtitle = '', $qauthor='')
{
    global $googleKey, $googleURL;

    // See https://developers.google.com/sheets/api/v3/worksheets
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $googleURL);
    curl_setopt($curl, CURLOPT_TIMEOUT, 300);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($result, $assoc = TRUE);
    //echo html_comment(print_r($result, TRUE));
    $feed = $result['feed'];
    $rows = $feed['entry'];
    $n = count($rows);
    //echo "<pre>";
    //echo print_r($result, TRUE);
    //print_r($n);
    //print_r($rows[$n-1]);
    //echo "</pre>";

    //echo html_comment(print_r($rows[0], TRUE));
    //echo html_comment(print_r($rows[5], TRUE));
    // row[0] = Last Updated...
    // row[1] = _cre1l: CP/PM, _chk2m: Year of, _ciyn3: DPC Clearance, _clrrx: Posting Number
    // row[2] = _cokwr: Contributor First Name, _cpzh4: Title, _cre1l: requestor, _chk2m: Public'n, _ciyn3: Code #, _clrrx: at FP
    // Data starts on row[3], remove the first three rows.
    unset($rows[0]);
    unset($rows[0]);
    unset($rows[0]);

    $title_cell = 'gsx$_cpzh4';
    $last_cell = 'gsx$_cn6ca';
    $first_cell = 'gsx$_cokwr';
    $fpid_cell = 'gsx$_clrrx';
    $published_cell = 'gsx$_chk2m';
    $clearance_cell = 'gsx$_ciyn3';
    $pm_cell = 'gsx$_cre1l';

    $r = array();
    foreach ($rows as $row) {
        $title = getCell($row, $title_cell);
        $author = getCell($row, $last_cell);
        $first = getCell($row, $first_cell);
        if ($first != '')
            $author .= ', ' . $first;

	// If neither title nor author is given, return everything
	if ($qtitle != '' || $qauthor != '')
	    // If either is given, then filter by either or both.
	    if (
		($qtitle == ''  || stripos($title, $qtitle) === FALSE)
	    &&  ($qauthor == '' || stripos($author, $qauthor) === FALSE)
	    )
		// Does not match criteria
		continue;

        $fpid = getCell($row, $fpid_cell);
        $published = getCell($row, $published_cell);
        $clearance = getCell($row, $clearance_cell);
        $pm = getCell($row, $pm_cell);
        $r[] = array(
            'id'=>$fpid,
            'title'=>$title,
            'author'=>$author,
            'published'=>$published,
            'clearance'=>$clearance,
            'pm'=>$pm
        );
    }
    return $r;
}

function getCell($row, $cell)
{
    if (isset($row[$cell]))
        return trim($row[$cell]['$t']);
    else
        return '';
}
?>
