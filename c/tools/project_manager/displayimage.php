<?PHP
$relPath = '../../pinc/';
require_once $relPath . 'dpinit.php';
require_once $relPath . 'DpPage.class.php';

$projectid      = Arg('projectid');
$pagename       = Arg('pagename');
$submit_replace = IsArg("submit_replace");

$project = new DpProject($projectid);
$page    = new DpPage($projectid, $pagename);

$imagefile   = $page->Image();

/** @var DpPage $page */
if($project->UserMayManage() && $submit_replace && count($_FILES) > 0 ) {
    $upfilename  = $_FILES["upfile"]["name"];
    if(mb_strlen($upfilename) >= 5) {
        $uptempname  = $_FILES["upfile"]["tmp_name"];
        $upfiletype  = $_FILES["upfile"]["type"];
        $upfilesize  = $_FILES["upfile"]["size"];
        if(right($upfiletype, 3) != right($imagefile, 3)) {
            die("Cannot replace $imagefile with $upfilename (different types)");
        }
        if($upfilesize == 0) {
            die("Cannot replace $imagefile with $upfilename (zero length file)");
        }
        $page->ReplaceImage($uptempname);
        divert("?projectid=$projectid&pagename=$pagename");
    }
}

$rows = $dpdb->SqlRows("
        SELECT pagename pgname
        FROM pages
        WHERE projectid = '$projectid'
        ORDER BY pagename");

if(! $pagename) {
    $pagename = $rows[0]['pgname'];
}

$opts = "";
$i = 0;
foreach($rows as $row) {
    $pgname     = $row['pgname'];
    if($pgname == $pagename) {
        $opts .= "<option value='$pgname' selected='selected'>$pgname</option>\n";
    }
    else {
        $opts .= "<option value='$pgname'>$pgname</option>\n";
    }
}

$state = $project->Phase();
$title = $project->NameOfWork();
$returnto = _("Return to Project Page");
$serverpath = "/c/imgsrv.php";

echo "
<!DOCTYPE HTML>
<html>
<head>
<title>DPC Page Image</title>
<meta charset='utf-8'/>
<script>

var serverpath = '$serverpath';
var jumpto;

function $(ref) {
    return document.getElementById(ref);
}

function save_zoom(pct) {
    var date = new Date();
    date.setDate(date.getDate() + 365 * 5);
    document.cookie = 'image_zoom=' + pct.toString() + ';' +
        ' expires=' + date.toUTCString() + '; samesite=strict';
}

function getCookie(cname) {
    var name = cname + '=';
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ')
            c = c.substring(1);
        if (c.indexOf(name) == 0)
            return c.substring(name.length, c.length);
    }
    return '';
}

function init_zoom() {
    var pct = parseInt($('image_zoom').value);

    if(isNaN(pct))
        pct = parseInt(getCookie('image_zoom'));
    if(isNaN(pct))
        pct = 100;
    pct = Math.max(pct, Math.min(pct, 200), 5);

    $('image_zoom').value = pct.toString();
    $('pageimage').style.width = pct.toString() + '%';
    save_zoom(pct);
}

function eZoom() {
    init_zoom();
    return false;
}

function ebody() {
    jumpto = $('jumpto');
	eJumpTo();
    init_zoom();

    $('image_zoom').addEventListener('keyup', function(event) {
        if (event.keyCode === 13) {
            event.preventDefault();
            $('submit_resize').click();
        }
    });
    document.body.addEventListener('keyup', function(event) {
        if (event.target == $('image_zoom'))
            return;
        switch (event.keyCode) {
        case 37:    // Left arrow
            ePrevClick();
            break;
        case 39:    // Right arrow
            eNextClick();
            break;
        default:
            return;
        }
        event.preventDefault();
    });
}

//function imgpath(pgname) {
//	var pid = $('projectid').value;
//	return serverpath + '?projectid=' + pid + '&pagename=' + pgname;
//}

function set_page_image(pgname) {
	var pid = $('projectid').value;
	var imgpath = serverpath + '?projectid=' + pid + '&pagename=' + pgname;
    $('pageimage').src = imgpath;
    $('pagename').value = pgname;
}

function eJumpTo() {
    var i = jumpto.selectedIndex;
    set_page_image(jumpto.value);
//    $('pageimage').src = src;
}

function ePrevClick() {
    var i = $('jumpto').selectedIndex;
    if(i <= 0) {
        return;
    }
    $('jumpto').selectedIndex--;
    set_page_image(jumpto.value);
//	var src = imgpath(jumpto.value);
//    $('pageimage').src = src;
}

function eNextClick() {
    var i = $('jumpto').selectedIndex;
    if(i >= $('jumpto').length) {
        return;
    }
    $('jumpto').selectedIndex++;
    set_page_image(jumpto.value);
//	var src = imgpath(jumpto.value);
//    $('pageimage').src = src;
}

</script>
</head>

<body onload='ebody()'>
<form name='imgform' id='imgform' 
    enctype='multipart/form-data' method='POST'
    style='margin: 0;'>
  <input type='hidden' name='projectid' id='projectid' value='$projectid'>
  <input type='hidden' name='pagename' id='pagename' value='$pagename'>
  <div style='width: 100%; text-align: center'>
    <a style='float: left' href='$code_url/project.php?projectid=$projectid'>$returnto</a>
    <h3 style='margin: 0;'>$title</h3> 
  </div>
  <div style='float: left'>
    Width:
    <input type='text' maxlength='3' name='image_zoom' id='image_zoom' size='3' value=''> %
    <input type='button' value='Resize' id='submit_resize' name='submit_resize' onclick='eZoom()'>
  </div>
  <div style='float: right'>
    Page:
    <select name='jumpto' id='jumpto' onChange='eJumpTo()'>
      $opts
    </select>
    <input type='button' accesskey='p' value='Previous' onClick='ePrevClick()'>
    <input type='button' accesskey='n' value='Next' onClick='eNextClick()'>
  </div>\n";

if($project->UserMayManage()) {
    echo "
  <div style='clear: both; text-align: center'>
    <input type='file' name='upfile' id='upfile' style='border: 1px solid gray; width: 25em;'>
    <input type='submit' value='Replace Image File' name='submit_replace'>
  </div>\n";
}
echo "
</form>
<br>
<img id='pageimage' src='' style='border: 1px solid gray;' alt=''>
</body></html>";
// vim: sw=4 ts=4 expandtab
