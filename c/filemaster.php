<?php
/*
    process flow when responding to an uploaded project zip file
    - location of uploaded zip file: $uploadpath
    - original name of (presumably a zip) file - $filename 
    - directory where unzipped files end up - $project->UploadPath()

*/

/*
 * protopage fields - projectid
 *                    name
 *                    external_text
 *                    external_image
 *                    other - file that's not image or text
 */
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath = "./pinc/";
require_once $relPath."dpinit.php";

$User->IsLoggedIn()
	or RedirectToLogin();


global $sftp_path;
global $dpscans_path;

//      /var/sftp/dpscans/dkretz
$userpath               = build_path($dpscans_path, $User->Username());

//  showpath   /dpscans (+ ...)
if(IsArg("showpath")) {
	$showpath = Arg( "showpath" );
}
else if(file_exists($userpath) && is_dir($userpath)) {
	$showpath = build_path("/dpscans", $User->Username());
}
else {
	$showpath = "/dpscans";
}

// e.g. /var/sftp/dpscans/....
$truepath               = build_path($sftp_path, $showpath);

$projectid              = ArgProjectId();

// directory to browse from
$chk_text               = ArgArray("chk_text");      // selected files

$chk_other              = ArgArray("chk_other");
$chk_delete             = ArgArray("chk_delete");

$submit_load            = IsArg("submit_load", IsArg("submit_load2"));      // submit button
$submit_delete          = IsArg("submit_delete", IsArg("submit_delete2"));      // submit button
$submit_load_others     = IsArg("submit_load_others", IsArg("submit_load_others2"));      // submit button
$submit_delete_others   = IsArg("submit_delete_others", IsArg("submit_delete_others2"));      // submit button

$newproject             = IsArg("newproject");
$useHolding             = IsArg("UseHolding");

$username               = $User->Username();

// -------------------------------------------------------


$project = new DpProject( $projectid );

$project->Exists()
	or die( "Requested project ($projectid) does not exist." );

$project->UserMayManage()
	or die( "Permission problem." );

$readme = readme_data($truepath);
if($readme != "" && $project->CPComments() == "") {
	$project->SetCPComments($readme);
}

$holdingpath = build_path($project->ProjectPath(), "holding");

//echo "<pre>"; print_r($_POST); echo "</pre>\n";

// Operations against the project holding area.
if (isArg("submit_upload")) {
    $msg = uploadToHolding($_FILES['uploadfiles'], $holdingpath);
    if ($msg != '')
        die($msg);
    $useHolding = true;
}

if (isArg("submit_remove_all")) {
    emptyHolding($holdingpath);
    $useHolding = true;
}

// ****TEMP UNTIL FTP WORKING
$useHolding = true;

if ($useHolding) {
    $truepath = build_path($project->ProjectPath(), "holding");
    @mkdir($truepath);
    $holdingChecked = 'checked';
} else
    $holdingChecked = '';

// -----------------------------------------------------------------
//  Submit processing
// -----------------------------------------------------------------

// gather protopages as union of database and files
$protopages = gather_page_set($project, $truepath);


// process requested file deletions from project directory
if ( count( $chk_delete ) > 0 ) {
	do_chk_delete($chk_delete, $project);
}

// if requested to include or delete extra files, copy them over or delete them.
if ( count( $chk_other ) > 0 ) {
	// permit_path($project->ProjectPath());
	foreach ( array_keys( $chk_other ) as $otherkey ) {
		$proto = $protopages[$otherkey];
		//$otherpath = $proto['external_image'];
        if (isset($proto['external_image']))
            $otherpath = $proto['external_image'];
        else if (isset($proto['external_text']))
            $otherpath = $proto['external_text'];
        else
            $otherpath = $proto['external_other'];
		$otherfile = basename($otherpath);
        $topath = build_path( $project->ProjectPath(), $otherfile );

		if ( ! file_exists( $otherpath ) ) {
			assert(false);
			continue;
		}
		if ( $submit_delete_others ) {
            // this fails due to file permissions
            @permit_path($otherpath);
			@unlink( $topath );
            @unlink( $otherpath );
		}
		else if ( $submit_load_others ) {
			// permit_path($topath);
			// permit_path($otherpath);
			if ( file_exists( $topath ) ) {
				unlink( $topath );
			}

			assert(copy( $otherpath, $topath ));

			@unlink( $otherpath );
		}
	}
}


// load pages requested
if ( $submit_load && count( $chk_text ) > 0 ) {
	LoadPageFiles($project, $chk_text, $protopages);
}

// delete pages requested
if ( $submit_delete && count( $chk_text ) > 0 ) {
    $ary = array();
    foreach(array_keys($chk_text) as $chk) {
        $ary[] = preg_replace("/pg_/", "", $chk);
    }
	DeletePages($project, $ary);
}

// do it again to incorporate changes
$protopages = gather_page_set($project, $truepath);

//echo "<pre>\n"; print_r($protopages); echo "</pre>\n";


// combine existing pages with potential pages
// (pairs of files)
/**
 * @param DpProject $project
 * @param $path
 * @return array
 * @internal param DpProject $project
 */
function gather_page_set($project, $path) {
	$ary = array();
	$pages = $project->PageRows(true);
	$projectid = $project->ProjectId();
	foreach($pages as $row) {
		$key = "pg_" . $row['pagename'];
		$ary[$key]["row_id"] = $key;
		$ary[$key]["projectid"] = $projectid;
		$ary[$key]["name"] = $row['pagename'];
		$ary[$key]["imagefile"] = $row['imagefile'];
		$ary[$key]["is_in_db"] = true;
	}

	//	$fpaths = glob("$path/*");
	$fpaths = gather_files($path);

	foreach($fpaths as $fpath) {
		//	    $fname = basename($fpath);
		//	    if($fname == "readme.txt" || $fname == "~readme.txt") {
		//		    continue;
		//	    }
		$pgname = rootname($fpath);
		$key = "pg_" . $pgname;
		// may or may not be a match,
		// may or may not add a row
		$ary[$key]["projectid"] = $projectid;
		$ary[$key]["name"] = $pgname;
		$ary[$key]["row_id"] = $key;

		switch(FileNameExtension($fpath)) {
			case "txt":
				$ary[$key]["external_text"] = $fpath;
				break;

			case "png":
			case "jpg":
			case "gif":
			case "tif":
			case "tiff":
				$ary[$key]["external_image"] = $fpath;
				break;

			default:
				$ary[$key]["external_other"] = $fpath;
				break;
		}
	}

    // Now look in the project for files which are already other files.
    $fpaths = $project->ExtraFilePaths();
    //echo "<pre>"; print_r($fpaths); echo "</pre>";
    foreach ($fpaths as $fpath) {
        $pgname = rootname($fpath);
        $key = "pg_" . $pgname;
		$ary[$key]["projectid"] = $projectid;
		$ary[$key]["row_id"] = $key;
        $ary[$key]["external_other"] = $fpath;
        $ary[$key]["name"] = $pgname;
    }

	ksort($ary);
//		dump($ary);
//		die();
	return $ary;
}

function gather_files($dir) {
	$a_res = array();
	$fpaths = glob($dir . "/*");
	foreach($fpaths as $fpath) {
		switch($fpath) {
			case "readme.txt":
			case "~readme.txt":
			case ".":
			case "..":
				continue 2;
			default:
				break;
		}
		if(is_dir($fpath)) {
			continue;
		}
		$a_res[] = $fpath;
	}
	return $a_res;
}


// -----------------------------------------------------------------
//  Setup
// -----------------------------------------------------------------

// build the directory list display


$str_directory_list = " <pre id='directory_list' class='left w50'>\n";
// if not at root, prompt for Up
if($truepath != "/var/sftp/dpscans") {
	$str_directory_list .= "<span class='left likealink' onclick='eSetPath(\"..\");'> Up</span>\n";
}

if (!is_dir($truepath))
    $pathError = "Server configuration error. $showpath is missing or inaccessible";

// build directory display
$dirpaths = DirectoryList( $truepath );


// display a link for each directory
foreach ( $dirpaths as $dir ) {
	$dname = preg_replace( "/^.*\//", "", $dir );
	$str_directory_list .= "<span class='likealink' onclick='eSetPath(\"$dname\");'>$dname</span>\n";
}
$str_directory_list .= "</pre>\n";




// -----------------------------------------------------------------
// Main page table
// -----------------------------------------------------------------

$tblpages = new DpTable( "page_table", "w98 margined dptable" );

$tblpages->AddCaption( "^In Database", 2, $project->ProjectId() );
$tblpages->AddCaption( "^Pre Load Files", 3, $truepath );

$tblpages->AddColumn( "^Page<br/>name", "name");
$tblpages->AddColumn( ">In project", null, "eInDB" );
$tblpages->AddColumn( ">Load Image", "external_image", "epathlink" );
$tblpages->AddColumn( ">Load Text", "external_text", "epathlink" );
$tblpages->AddColumn( "^Encoding", "encoding");
$tblpages->AddColumn( chkall(), "xx", "etextcheck" );
$tblpages->AddColumn("<projectid", "projectid", null, "hidden");
$tblpages->AddColumn("<imgpath", "external_image", null, "hidden");
$tblpages->AddColumn("<imgpath", "external_text", null, "hidden");

$pagerows = array();
$otherrows = array();

foreach($protopages as $protopg) {
	if(isset($protopg['is_in_db'])) {
		$pagerows[] = $protopg;
	}
	else if(isset($protopg['external_image']) && isset($protopg['external_text'])) {
		$pagerows[] = $protopg;
	}
	else {
		$otherrows[] = $protopg;
	}
}

// Test each file's encoding.
foreach ($pagerows as &$row) {
    //dump($row);
    if (isset($row['external_text'])) 
        $row['encoding'] = encoding($row['external_text']);
    else
        $row['encoding'] = "";
}

$tblpages->SetRows( $pagerows );



// build other uploaded files display
$tblother = new DpTable( "tblother", "dptable" );
$tblother->AddColumn( "<Name", "name", null, "hidden" );
$tblother->AddColumn( "<File name", null, "eOther" );
$tblother->AddColumn( "<In<br>project", null, "eInProject");
$tblother->AddColumn( chkall2(), "name", "othercheck" );
$tblother->SetRows( $otherrows );


// setup up UI ----------------------------------

$delcaption     = _("Delete selected");
$loadcaption    = _("Load selected");

// ---------------------------------------------------
// page display
// ---------------------------------------------------

    $args = array("js_file"  => "./js/filemaster.js",
                  "css_file" => "./css/filemaster.css");
    $no_stats = 1;

	$title = ( $projectid && $project ? $project->NameOfWork() : _("New Project"));
    theme("FileMaster - " . $title, "header", $args);

	echo "<script type='text/javascript'>
		init();
/*
 * This needs special checking because you can delete any page, so
 * need to be able to check off any page. But you can't load any page,
 * and we don't know until they decide which button to hit. Which
 * probably means it is a bad UI.
 */
function submitLoadCheck(e) {
    var tbl = $('page_table');
    var nrows = tbl.rows.length;
    // rows 0&1 are titles
    for (var i = 2; i < nrows; i++) {
        var row = tbl.rows[i];
        var chk = row.getElementsByTagName('input');
        if (chk.length > 0 && chk[0].checked) {
            var encoding = row.cells[4].innerHTML;
            var pagename = row.cells[0].innerHTML;
            var loadImage = row.cells[3].innerHTML;
            if (loadImage == '') {
                alert('The page ' + pagename + ' has no text file to load!');
                e.preventDefault();
                break;
            }
            if (encoding != 'utf-8') {
                alert('The page ' + pagename + ' is not utf-8 and may not be loaded!');
                e.preventDefault();
                break;
            }
        }
    }
    return false;
}

function changeHold()
{
    var check = document.getElementById('UseHolding').checked;
    console.log('CHECKED: ' + check);
    $('workform').submit();
}

		</script>\n";
		echo "<p>" . link_to_project( $projectid, "Return to project" ) . "</p>";
		echo "
    <div class='pagetitle'> {$project->TitleAuthor()} </div>
    <div id='divwork'>\n";

    // We have two forms, which may be nested.  All <input> elements for
    // this main form need to use <input form='workform'>
    // Create the form here.
    echo "<form name='workform' id='workform' method='POST'></form>\n";

    echo "<div class='center' id='divworkform'>\n";

    // Left checkbox, right FTP or Holding
    echo "<div>\n";
    echo "<div id='divleft' class='half lfloat padded'>\n";
    emitHoldingCheckbox($holdingChecked);
    echo "</div> <!-- divleft -->\n";
    echo "<div id='divright' class='rfloat padded center' style='width:50%'>\n";
    if ($useHolding)
        emitHolding($holdingpath);
    else
        emitFTPSelector($showpath, $pathError, $dpscans_path,
            $str_directory_list);
    echo "</div>  <!-- divright -->\n";
    echo "</div>\n";

    echo "<hr style='height:.4em; width:100%; background-color:black;'>\n";

    echo "<div id='divright' class='rfloat padded center' style='width:50%'>\n";
    emitOtherFiles($tblother);
    echo "</div>  <!-- divright -->\n";

    echo "<div id='divleft' class='half lfloat padded'>\n";
    emitProjectPages($tblpages, $delcaption, $loadcaption);
    echo "</div> <!-- divleft -->\n";

    echo "</div> <!-- divworkform (center) -->\n";
    echo "</div> <!-- divwork -->\n";

    theme("", "footer");
    exit;

function eFileName($row) {

}
function eInProject( $row) {
	global $project;
	if ( isset( $row["external_image"] ) ) {
		$fname = basename($row["external_image"]);
		$fpath = build_path($project->ProjectPath(), $fname);
	}
	else if ( isset( $row["external_text"] ) ) {
		$fname = basename($row["external_text"]);
		$fpath = build_path($project->ProjectPath(), $fname);
	}
	else if ( isset( $row["external_other"])) {
		$fname = basename($row["external_other"]);
		$fpath = build_path($project->ProjectPath(), $fname);
	}
	else {
		return "";
	}
	return file_exists($fpath) ? "Yes" : "No";
}

//function eMaybeImage($row) {
//	if ( isset( $row["external_image"] ) ) {
//			return basename( $row["external_image"] );
//	}
//	else if( isset( $row["external_text"] ) ) {
//		return basename( $row["external_text"] );
//	}
//	else if( isset( $row["external_other"])) {
//		return basename( $row["external_other"] );
//	}
//	else {
//		return "";
//	}
//}

function eOther($row) {
	if(isset($row["external_image"])) {
		return basename($row['external_image']);
	}
	if(isset($row["external_text"])) {
		return basename($row['external_text']);
	}
	if (isset($row["external_other"])) {
		return basename($row['external_other']);
	}
	return "";
}

function eInDb($row) {
	return isset($row['imagefile'])
		? "Yes" : "No";
}
function chkall() {
    return "^<input form='workform' type='checkbox' id='chkall' name='chkall'
                    onclick='eCheckAll(event)'>"._("All");
}


function chkall2() {
    return "^<input form='workform' type='checkbox' id='chkall2' name='chkall2'
                    onclick='eCheckAll2(event)'>"._("All");
}

function efile($row) {
    return $row;
}

function readme_data($path) {
	$rmpath = build_path($path, "readme.txt");
	if(file_exists($rmpath)) {
		return file_get_contents($rmpath);
	}

	$rmpath = build_path($path, "~readme.txt");
	return file_exists($rmpath)
		? $rmpath
		: "";
}

function DirectoryList($path) {
	$files = glob($path . "/*");
	$dirpaths = array();
	foreach($files as $fname) {
		if(is_dir($fname)) {
			$dirpaths[] = $fname;
		}
	}
	return $dirpaths;
}

function encoding($path) {
	if(! file_exists($path)) {
		return "";
	}
	$text = file_get_contents($path);
	return is_utf8($text) ? "utf-8" : "other";
}

function etextcheck($page, $row) {
    $enable = "";
    $name = $row['name'];
    $key = "pg_" . $name;
//    if (isset($row['encoding']) && $row['encoding'] == 'other')
//        $enable = "disabled";
	return "<input form='workform' type='checkbox' name='chk_text[{$key}]' {$enable}>\n";
//	return ( isset( $proto['external_image'] )
//	         && isset( $proto['external_text'] ) )
//		? "<input type='checkbox' name='chk_text[{$key}]'>\n"
//		: "\n";
}

function othercheck($name) {
//	global $protopages;
//	$name = $row['name'];
	$key  = "pg_" . $name;
//	$proto = $protopages[ $key ];
//	$frompath = isset($proto['external_image']) ? $proto['external_image'] : $proto['external_text'];
	return "<input form='workform' type='checkbox' name='chk_other[$key]'>";
}

function exttxtlen($page) {
	if(is_object($page)) {
		return "tbd";
	}
	else if(isset($page['external_text'])) {
		return $page['external_text'];
	}
	else {
		return "";
	}
}


/**
 * @param DpProject $project
 * @param array $pages
 */
function DeletePages($project, $pages) {
    $project->DeletePages($pages);
}

// chk_pages has the heys, protopage has external_image, external_text
function LoadPageFiles($project, $chk_pages, $protopages ) {
	global $Context;
	// array to hold pages real and prospective
	/** @var $protopgs DpPage[] */
	/** @var $pgs array */
	/** @var $project DpProject */

	$projectid = $project->ProjectId();

	foreach(array_keys($chk_pages) as $key) {
//		dump($protopages);
//		die();
		$proto = $protopages[$key];
		if((! isset($proto['external_image']) || (! isset($proto['external_text'])))) {
			assert(false);
			dump($proto);
			continue;
		}
		$protoimagefile = $proto['external_image'];
		$prototextfile = $proto['external_text'];
		$pagename  = $proto['name'];
		// load image and text for this page

		$Context->AddPage($projectid, $pagename, $protoimagefile, $prototextfile);
	}
}

/*
function read_readme($readme, $path) {
	$ary = array();
	$tregex = "Title -\s+(.+)$";
	$aregex = "Personal Name:\s+(.*?)\d\d\d\d.*$";
	$ary["title"] = RegexMatch( $tregex, "uis", $readme );
	$ary["author"] = RegexMatch( $aregex, "uis", $readme );
	$spath = build_path($path, "scans");
	$tpath = build_path($path, "text");
	if(file_exists($spath) && file_exists($tpath)
	   && is_dir($spath) && is_dir($tpath)) {
		$ary["spath"] = $spath;
	}
	return $ary;
}
*/


/**
 * @param array $chk_delete
 * @param DpProject $project
 */
function do_chk_delete( $chk_delete, $project ) {
	foreach ( array_keys( $chk_delete ) as $filename ) {
		$project->DeleteProjectFile( $filename );
	}
}

function epathlink($path) {
	if(! file_exists($path)) {
		return "";
	}
	return basename($path);
}

function emptyHolding($target) {
    $hfiles = @scandir($target);
    if (!$hfiles)
        $hfiles = array();
    foreach ($hfiles as $f) {
        if ($f == '.' || $f == '..')
            continue;
        //echo "<pre>Unlinking $target/$f\n</pre>";
        unlink("$target/$f");
    }
    echo "<pre>rmdir $target\n</pre>";
    rmdir($target);
}

function uploadToHolding($f, $target) {
    $msg = '';
    foreach ($f['tmp_name'] as $index => $tmp) {
        $name = $f['name'][$index];
        $error = $f['error'][$index];
        $size = $f['size'][$index];
        //error_log("$name, $tmp, $error, $size");
        if ($error != UPLOAD_ERR_OK) {
            $msg .= "File $name upload failed: $error\n";
            continue;
        }
        filenameValidation($name, array('jpg', 'png', 'txt'));
        if (!rename($tmp, "$target/$name")) {
            $err = error_get_last();
            $msg .= "Rename $tmp to $target/$name: " . $err['message'];
        }
    }
    return $msg;
}

function filenameValidation($file, $exts)
{
    $parts = pathinfo($file);
    if (empty($parts['extension']))
        fatal("Empty extension");
    if (!in_array($parts['extension'], $exts))
        fatal("Cannot act on extension '" . $parts['extension'] . "'");
    if (substr($file, 0, 1) == "/")
        fatal("No absolute paths allowed");
    if (strpos($parts['dirname'], '.') != false)
        fatal("No dots allowed");
    if ($parts['dirname'] != '.')
        fatal("No directories allowed!");
    if (strpos($parts['dirname'], '\\') != false)
        fatal("No backslashes");
    if (substr($parts['filename'], 0, 1) == '.')
        fatal("No leading dot for filename");
}

function showHolding($p)
{
    echo "<p>";
    $hfiles = @scandir($p);
    if (!$hfiles)
        $hfiles = array();
    $first = true;
    foreach ($hfiles as $f) {
        if ($f == '.' || $f == '..')
            continue;
        if ($first) {
            echo "<p>Existing files in holding area:</p>";
            echo "<p style='margin-left:1em; margin-right:1em;'>\n";
            $first = false;
        } else
            echo ", ";
        echo $f;
    }
    if ($first)
        echo "<p>No files currently in the holding area.";
    else
        echo "</p><p><input form='holdingform' type='submit' name='submit_remove_all' value='Remove All'>\n";
    echo "</p>\n";
}

function emitHoldingCheckbox($holdingChecked)
{
    echo "
        <div class='center margined'>
        <div style='margin-bottom:.5em;'>
        <p>Files are normally uploaded using ftp to the dpscans directory.
        Use this checkbox to instead upload them directly to a holding
        directory specific to this project.
        The project holding directory is not directly accessible,
        but is only an intermediary area used by the tables below.
        </p>
        <p>Until ftp to dpscans is working again, this checkbox is forced on!</p>
        <input form='workform' type='checkbox' id='UseHolding' name='UseHolding' onchange='changeHold();' $holdingChecked>
        <label for='UseHolding'>Operate using Project Holding Area</label>
        <p>Files which are paired, i.e. both a .png/.jpg and .txt are found with
        the same name, will appear in the <i>Project Pages</i> table below left.
        If they are not paired, they will appear in the <i>Other Files</i> table
        below right.  Thus, if you are using multiple directories, after
        uploading the images directory, for example,
        they will first appear in the <i>Other Files</i> table.
        When you subsequently
        load all the text files they will be paired and then appear in
        the <i>Project Pages</i> table.</p>
        <p>Unpaired files whose base name match an existing page name will also
        appear in the <i>Project Pages</i> table, however you cannot re-load
        those pages without also uploading their pair.</p>
        <p>Files loaded below will be automatically deleted from the
        project holding area. Files loaded from the ftp repository will
        not be automatically deleted and must be deleted manually via ftp.
        <p>No page naming convention is imposed by the software.</p>
        </div>
        </div>
    ";
}

function emitHolding($holdingpath)
{
    echo "
        <div class='center margined bordered' id='divuploadform' style='margin-left:2em;'>
        <div style='margin-bottom:1em;'>
        <h3 class='center'>File Upload to Project’s Holding Area</h3>
        <form name='holdingform' id='holdingform' multipart='' method='POST' enctype='multipart/form-data'></form>
    ";

    showHolding($holdingpath);

    echo "
            <input form='holdingform' type='file' name='uploadfiles[]' multiple='multiple' accept='.jpg, .png, .txt'>
            &nbsp;<input form='holdingform' type='submit' name='submit_upload' value='Upload'>
        </div>
        </div>
    ";
}

function emitFTPSelector($showpath, $pathError, $dpscans_path, $str_directory_list)
{
    echo "
      <div id='dirs_and_files' class='center margined bordered' style='margin-left: 2em;'>
        <p>host = www.pgdpcanada.net, userid = dpscans, password = 2C4ever</p>
        <div id='divdirs' style='max-height: 20em;'>
            <h4 class='center w100'> Directory </h4>
            <h5 class='center clear'>$showpath</h5>
    ";

    if (isset($pathError))
          echo "<h5 class='center clear red'>$pathError</h5>";

    echo "
			<!-- the next two are to pass them along to js -->
            <input form='workform' type='hidden' id='dpscans_path' name='dpscans_path'  value='$dpscans_path' />
            <input form='workform' type='hidden' id='showpath' name='showpath'  value='$showpath' />
            $str_directory_list
        </div> <!-- divdirs -->
      </div> <!-- dirs_and_files -->
    ";
}

function emitOtherFiles($tblother)
{
    echo "
          <div class='margined bordered' style='margin-left:2em;'>
          <div style='margin-bottom: 1em;'>
          <h3>Other Files</h3>
          <input form='workform' type='submit' name='submit_load_others' value='Load selected'
            '/>
          <input form='workform' type='submit' name='submit_delete_others' value='Delete selected'
            />
    ";
    $tblother->EchoTableNumbered();
    echo "
          <input form='workform' type='submit' name='submit_load_others2' value='Load selected'
          />
          <input form='workform' type='submit' name='submit_delete_others2' value='Delete selected'
           />
        </div>
        </div>
    ";
}

function emitProjectPages($tblpages, $delcaption, $loadcaption)
{
    echo "
      <div class='margined bordered'>
        <h3 class='center'>Project Pages</h3>
          <div id='divbuttons' class='w100'>
            <input form='workform' type='submit' name='submit_delete' class='rfloat margined' value='$delcaption'
             />
            <input form='workform' type='submit' name='submit_load' class='rfloat margined' value='$loadcaption' onclick='submitLoadCheck(event);'
            />
          </div> <!-- divbuttons -->
    ";
    $tblpages->EchoTable();
    echo "
       <div id='divbuttons2' class='w100'>
            <input form='workform' type='submit' name='submit_delete2' class='rfloat margined' value='$delcaption'
             />
            <input form='workform' type='submit' name='submit_load2' class='rfloat margined' value='$loadcaption'
            />
          </div> <!-- divbuttons2 -->
        </div>
    ";
}

function fatal($msg)
{
    die($msg);
}

// vim: sw=4 ts=4 expandtab
