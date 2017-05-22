<?php
/*
    process flow when responding to an uploaded project zip file
    - location of uploaded zip file: $uploadpath
    - original name of (presumably a zip) file - $filename 
    - directory where unzipped files end up - $project->UploadPath()

*/

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath = "./pinc/";
require_once $relPath."dpinit.php";

$rootpath               = "/var/sftp";
$userpath               = "/var/sftp/dpscans/{$User->Username()}";
if(IsArg("showpath")) {
	$showpath = Arg( "showpath" );
}
else if(file_exists($userpath) && is_dir($userpath)) {
	$showpath = $userpath;
}
else {
	$showpath = "/dpscans";
}

$truepath               = build_path($rootpath, $showpath);


$projectid              = ArgProjectId();

// directory to browse from
$chk_text               = ArgArray("chk_text");      // selected files
$chk_other_upload       = ArgArray("chk_other_upload");
$chk_delete             = ArgArray("chk_delete");

$submit_load            = IsArg("submit_load");      // submit button
$submit_delete          = IsArg("submit_delete");    // submit button
$submit_load_others     = IsArg("submit_load_others");
$submit_delete_others   = IsArg("submit_delete_others");

$newproject             = IsArg("newproject");

$username               = $User->Username();


//$newproject             = IsArg("newproject");
// -------------------------------------------------------

$User->IsLoggedIn()
	or RedirectToLogin();

$project = new DpProject( $projectid );

$project->Exists()
	or die( "Requested project ($projectid) does not exist." );

$project->UserMayManage()
	or die( "Permission problem." );

$readme = readme_data($truepath);
if($readme != "" && $project->CPComments() == "") {
	$project->SetCPComments($readme);
}

// -----------------------------------------------------------------
//  Submit processing
// -----------------------------------------------------------------


// process requested file deletions from project directory
if ( count( $chk_delete ) > 0 ) {
	do_chk_delete($chk_delete, $project);
}

// if requested to include or delete extra files, copy them over or delete them.
if ( count( $chk_other_upload ) > 0 ) {
	// permit_path($project->ProjectPath());
	foreach ( array_keys( $chk_other_upload ) as $otherfile ) {
		$otherpath = build_path( $truepath, $otherfile );
		if ( ! file_exists( $otherpath ) ) {
			continue;
		}
		if ( $submit_delete_others ) {
			unlink( $otherpath );
		}
		else if ( $submit_load_others ) {
			$topath = build_path( $project->ProjectPath(), $otherfile );
			// permit_path($topath);
			// permit_path($otherpath);
			if ( file_exists( $topath ) ) {
				unlink( $topath );
			}
			copy( $otherpath, $topath );

			$archivepath = build_path(ProjectArchivePath($projectid), $otherfile);
			copy( $otherpath, $archivepath);

			unlink( $otherpath );
		}
	}
}

// gather protopages as union of database and files
$protopages = gather_page_set($project, $truepath);


// load pages requested
if ( $submit_load && count( $chk_text ) > 0 ) {
	LoadPageFiles($project, $chk_text, $truepath, $protopages);
}

// delete pages requested
if ( $submit_delete && count( $chk_text ) > 0 ) {
	DeletePages($project, $chk_text);
}


//    $files = ($projectid && $project ? $project->NonPageFiles() : array());
$ofary = array();
foreach($protopages as $apage) {
	if($apage['imagefile']) {
		$ofary[] = $apage;
	}
}
$oftbl = new DpTable("other_table", "dptable margined");
$oftbl->AddColumn("<File", 0, "efile");
$oftbl->AddColumn("^", 0, "edelete");
$oftbl->SetRows($ofary);




// -----------------------------------------------------------------
//  Setup
// -----------------------------------------------------------------

// build the directory list display


$str_directory_list = " <pre id='directory_list' class='left w50'>\n";
// if not at root, prompt for Up
if($truepath != "/var/sftp/dpscans") {
	$str_directory_list .= "<span class='left likealink' onclick='eSetPath(\"..\");'> Up</span>\n";
}

$dirpaths = DirectoryList( $truepath );
// build directory display


// display a link for each directory
foreach ( $dirpaths as $dir ) {
	$dname = preg_replace( "/^.*\//", "", $dir );
	$str_directory_list .= "<span class='likealink' onclick='eSetPath(\"$dname\");'>$dname</span>\n";
}
$str_directory_list .= "</pre>\n";



// build other uploaded files display
$other_upload_table = new DpTable( "tbl_other_uploads", "dptable" );
$other_upload_array = array();
foreach ( $chk_other_upload as $other ) {
	$oname                = preg_replace( "/^.*\//", "", $other );
	$other_upload_array[] = array( "filename" => $oname );
	// $other_upload_list .= "<input type='checkbox' name='includefile[$other]'>$other\n";
}
$other_upload_table->AddColumn( "<File", "filename" );
$other_upload_table->AddColumn( chkAll2(), "filename", "echeck2" );
$other_upload_table->SetRows( $other_upload_array );
// $other_upload_list .= "</pre>\n";

//$other_upload_table->EchoTable();



// -----------------------------------------------------------------
// Main page table
// -----------------------------------------------------------------

$tblpages = new DpTable( "page_table", "w98 margined dptable" );
$tblpages->AddCaption( "", 1);
$tblpages->AddCaption( "In project", 2, "Image and text already loaded" );
$tblpages->AddCaption( "Available<br>to load", 2, "Files in selected directory" );

$tblpages->AddColumn( "^Page<br/>name", null, "saypagename" );
//$tblpages->AddColumn( ">In<br>project", null, "is_page" );
$tblpages->AddColumn( ">Image", "imagefile", "eImage" );
$tblpages->AddColumn( ">Text", "version", "eText" );
$tblpages->AddColumn( ">Image", null, "extimgpvw" );
$tblpages->AddColumn( ">Text", null, "exttxtlen" );
$tblpages->AddColumn( "^Encoding", null, "encoding" );
$tblpages->AddColumn( chkall(), null, "textcheck" );

$tblpages->SetRows( $protopages );





// setup up UI ----------------------------------

$delcaption     = _("Delete selected items");
$loadcaption    = _("Load selected items");
$uploadcaption  = _("Browse files to upload");

    // -----------------------------------------------------------------
    // Other project files table
    // -----------------------------------------------------------------




// ---------------------------------------------------
// page display
// ---------------------------------------------------

    $args = array("js_file"  => "./js/filemaster.js",
                  "css_file" => "./css/filemaster.css");
    $no_stats = 1;

	$title = ( $projectid && $project ? $project->NameOfWork() : _("New Project"));
    theme("FileMaster - " . $title, "header", $args);
//	if( $projectid && $project) {
		echo "<p>" . link_to_project( $projectid, "Return to project" ) . "</p>";
		echo "
  <div class='pagetitle'> {$project->TitleAuthor()} </div>
  <div id='divwork'>\n";
//	}

  echo "
    <div class='center' id='divworkform'>
	<form name='workform' id='workform' method='POST' action=''>

        <div id='divleft' class='lfloat padded center w50'>
          <div id='dirs_and_files' class='center margined bordered padded' style='margin-left: 2em;'>
            <p>host = www.pgdpcanada.net, userid = dpscans, password = 2C4ever</p>
            <div id='divdirs' style='max-height: 20em;'>
                <h4 class='center w100'>
                    Directory
                </h4>

              <h5 class='center'>$showpath </h5>

              <input type='hidden' id='rootpath' name='rootpath'  value='$rootpath' />
              <input type='hidden' id='showpath' name='showpath'  value='$showpath' />
            $str_directory_list
            </div> <!-- divdirs -->

        </div> <!-- dirs_and_files -->\n";


//  if(count($chk_other_upload) > 0) {
    echo "
        <div id='divother' class='margined center bordered padded' style='margin-left: 2em;'>
          <h4>Image Files</h4>
          <input type='submit' name='submit_load_others' value='Load Selected' />
          <input type='submit' name='submit_delete_others' ' value='Delete Selected' />\n";
          $other_upload_table->EchoTableNumbered();
          // $other_upload_list
    echo "
          <input type='submit' name='submit_load_others' value='Include Selected' />
          <input type='submit' name='submit_delete_others' ' value='Delete Selected' />
        </div> <!-- divother -->
  ";
//  }

  echo "
      </div>  <!-- divleft -->\n";

  echo "
       <div id='divright' class='half bordered lfloat padded'>
        <h3 class='center'>Project Pages</h3>
          <div id='divbuttons' class='w100'>
            <input type='submit' name='submit_delete' class='rfloat margined' value='$delcaption' onclick='return econfirm(event)' />
            <input type='submit' name='submit_load' class='rfloat margined' value='$loadcaption' />
          </div> <!-- divbuttons -->\n";
          $tblpages->EchoTable();
  echo "
                                      <div id='projfiles' class='margined center w45 bordered padded'>
          <h3 class='center'>Other Project Pages and Files</h3>\n";
          $oftbl->EchoTableNumbered();
          echo "
                                                       <input type='submit' value='Delete Selected' />
        </div> <!-- projfiles -->

        </div> <!-- divright -->\n";

echo "
    </form>

  </div> <!-- divwork -->
    </div>\n";

    theme("", "footer");
    exit;


function chkall() {
    return "^<input type='checkbox' id='chkall' name='chkall'
                    onclick='eCheckAll(event)'>"._("All");
}

// List of directories for navigation

function echeck2($filename) {
    return "<input type='checkbox' name='chk_other_upload[$filename]'>\n";
}

function chkall2() {
    return "^<input type='checkbox' id='chkall2' name='chkall2'
                    onclick='eCheckAll2(event)'>"._("All");
}

function efile($row) {
    return $row;
}

function edelete($image) {
    return "<input type='checkbox' name='chk_delete[$image]' value='Delete' />\n";
}


function readme_data($path) {
	if(file_exists($fpath = build_path($path, "readme.txt"))) {
		return file_get_contents($fpath);
	}
	if(file_exists($fpath = build_path($path, "~readme.txt"))) {
		return file_get_contents($fpath);
	}
	return "";
}

function DirectoryList($path) {
	$glob = build_path($path, "*");
	$files = glob("$glob");
	$dirpaths = array();
	foreach($files as $fname) {
		if(is_dir($fname)) {
			$dirpaths[] = $fname;
		}
	}
	return $dirpaths;
}

function to_key($key) {
	return "pg_".$key;
}

/** @var DpProject $project */
function gather_page_set($project, $path) {
	global $dpdb;
	$projectid = $project->ProjectId();
	$pages = $dpdb->SqlRows("
		SELECT pg.projectid,
			   pg.pagename,
			   MIN(pv.version) AS version,
			   pg.imagefile
		FROM pages pg
		LEFT JOIN page_versions pv
			ON pg.projectid = pv.projectid
			AND pg.pagename = pv.pagename
		WHERE pg.projectid = '$projectid'
		GROUP BY pg.projectid, pg.pagename
		ORDER BY pg.projectid, pg.pagename
		");
	$ary = array();
	foreach($pages as $page) {
		$key = to_key($page['pagename']);
		$ary[$key] = $page;
//		$ary[$key]['pagename'] = $pg['pagename'];
	}

	$fpaths = glob("$path/*");

    foreach($fpaths as $fpath) {
	    $pgname = rootname($fpath);
	    // skip directories
	    if(is_dir($fpath)) {
//			$pages[$pgname]['dir'] = $fpath;
            continue;
        }
	    $key = to_key( $pgname);
	    $is_page = isset($ary[$key]);
	    if($is_page) {
		    $arow = &$ary[$key];
	    }
	    else {
		    $arow = array("pagename" => $pgname);
	    }

	    // may or may not be a match,
	    // may or may not add a row
//	    $ary[$key]["name"] = $pgname;

        switch(FileNameExtension($fpath)) {
            case "txt":
                $arow["external_text"] = basename($fpath);
                break;

            case "png":
            case "jpg":
            case "gif":
            case "tif":
            case "tiff":
                $ary["external_image"] = basename($fpath);
                break;

            default:
	            $ary["other"] = basename($fpath);
                break;
        }

	    if(! $is_page) {
		    $ary[$key] = $arow;
	    }

//	    dump($ary[$key]);
    }

    return $ary;
}

function echo_upload_form() {
    echo "
        <form enctype='multipart/form-data' method='post' action=''
            name='upform' id='upform'>
        Select a zip file:
    <input type='file' name='projectzipfile' size='50'
            onchange='eFileSelect()'>
    <input type='button' value='Upload file' onclick='eUpClick()'
        name='upbutton' id='upbutton'>
    <span id='uploading'> Uploading....</span>
	</form>\n";
}

function saypagename($page) {
	return $page['pagename'];
}


function is_page($page) {
	return isset($page['projectid']) ? "Yes" : "No";
}


function encoding($page) {
	/** @var $page DpProtoPage */
	if(! isset( $page['external_text'])) {
		return "";
	}
	return "tbd";
}

function row_path($row) {
	$projectid = $row['prijectid'];
	$pagename = $row['pagename'];
	$version = $row['version'];
	return PageVersionPath($projectid, $pagename, $version);
}

function row_text($row) {
	$projectid = $row['prijectid'];
	$pagename = $row['pagename'];
	$version = $row['version'];
	return PageVersionText($projectid, $pagename, $version);
}

function extimgpvw( $page) {
	/** @var $page DpProtoPage */
	if(isset($page['external_image'])) {
		return $page['external_image'];
	}
	else {
		return "";
	}
}

function textcheck($page) {
	global $protopages;
	$name = $page['pagename'];
	$key  = "pg_" . $name;
	if ( ! isset( $protopages[ $key ] ) ) {
		dump( $name );
		dump( $key );
		dump( $protopages );
		die();
	}
	$proto = $protopages[ $key ];

	return ( isset( $proto['external_image'] )
	         && isset( $proto['external_text'] ) )
		? "<input type='checkbox' name='chk_text[{$key}]'>\n"
		: "\n";
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


function DeletePages($project, $chk_pages) {

}

/** @var DpProject $project */
function LoadPageImageTextFiles($project, $proto, $truepath) {
	$projectid = $project->ProjectId();
	$pagename = $proto['pagename'];
	if((! isset($proto['external_image']) || (! isset($proto['external_text'])))) {
		assert( false );
		return;
	}

	// add a page record
	$imagepath  = build_path($truepath, $proto['external_image']);
	$textpath = build_path($truepath, $proto['external_text']);
	$project->AddPageRecord($projectid, $pagename, $imagepath);
	$project->AddPageImageFile($pagename, $imagepath);
	AddInitVersionFile($projectid, $pagename, $textpath);
//	$project->AddPageTextFile($pagename, $textpath);
}

// chk_pages has the heys, protopage has external_image, external_text
function LoadPageFiles($project, $chk_pages, $truepath, $protopages ) {
	// array to hold pages real and prospective
	/** @var $protopgs DpPage[] */
	/** @var $pgs array */
	/** @var $project DpProject */

	foreach(array_keys($chk_pages) as $key) {
		$proto = $protopages[$key];
		if((! isset($proto['external_image']) || (! isset($proto['external_text'])))) {
			assert(false);
			dump($proto);
			die();
			continue;
		}
		LoadPageImageTextFiles($project, $proto, $truepath);
	}
}

function read_readme($readme, $truepath) {
	$ary = array();
	$tregex = "Title -\s+(.+)$";
	$aregex = "Personal Name:\s+(.*?)\d\d\d\d.*$";
	$ary["title"] = RegexMatch( $tregex, "uis", $readme );
	$ary["author"] = RegexMatch( $aregex, "uis", $readme );
	$spath = build_path($truepath, "scans");
	$tpath = build_path($truepath, "text");
	if(file_exists($spath) && file_exists($tpath)
	   && is_dir($spath) && is_dir($tpath)) {
		$ary["spath"] = $spath;
	}
	return $ary;

}


/**
 * @param array $chk_delete
 * @param DpProject $project
 */
function do_chk_delete( $chk_delete, $project ) {
	foreach ( array_keys( $chk_delete ) as $filename ) {
		$project->DeleteProjectFile( $filename );
	}


}
