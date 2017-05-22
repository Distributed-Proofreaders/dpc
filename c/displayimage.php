<?php
$relPath = "./pinc/";
require_once $relPath."dpinit.php";
require_once $relPath."DpPage.class.php";
require_once $relPath."DpProject.class.php";

// get variables passed into page
$projectid      = ArgProjectId();
$pagename       = ArgPageName();
if(! $pagename) {
    $imagefile  = Arg("imagefile");
    $pagename   = imagefile_to_pagename($imagefile);
}
    
$page           = new DpPage($projectid, $pagename);
$imagefile      = $page->ImageFile();
$project        = new DpProject($projectid);

$title = _("Display Image for Page {$pagename}");
$projecttitle = $page->Title();

$previous   = _("Previous");
$next       = _("Next");
$imgsrv     = "imgsrv.php";

$script = "<script type='text/javascript'>
    var pageimg;
    var jumpto;
    var pctbox;
    var btnnext1;
    var btnprev1;

    function $(id) {
        return document.getElementById(id);
    }

    function init() {
        pageimg  = $('pageimg');
        jumpto   = $('jumpto');
        pctbox   = $('pctbox');
        btnnext1 = $('btnnext1');
        btnprev1 = $('btnprev1');

        $('jumpto').value = '$pagename';
        jumpto.onchange = set_image;
        pctbox.value = getviewpct();
        pageimg.style.height = getviewpct() + '%';
        pageimg.style.display = 'block';
        // setviewpct();
    }

    function donext() {
        var i = jumpto.selectedIndex;
        if(i < jumpto.length) {
            jumpto.selectedIndex++;
            set_image();
        }
    }

    function doprev() {
        var i = jumpto.selectedIndex;
        if(i > 0) {
            jumpto.selectedIndex--;
            set_image();
        }
    }

    function set_image() {
        pageimg.style.display = 'none';
        pageimg.src = '{$imgurl}'
            + '?projectid={$projectid}'
            + '&pagename=' + jumpto[jumpto.selectedIndex].innerHTML;
        btnnext1.disabled =
            (jumpto.selectedIndex >= (jumpto.length - 1));
        btnprev1.disabled = (jumpto.selectedIndex <= 0);
        $('pagename').innerHTML = jumpto[jumpto.selectedIndex].innerHTML;
    }

    function eImgLoad() {
        if(pageimg) {
            pageimg.style.height     = getviewpct() + '%';
            pageimg.style.display = 'block';
        }
        divimage.scrollTop = '0px';
    }

    function setpct() {
        var ht = pctbox.value;
        pageimg.style.height = ht + '%';
        setviewpct();
    }

    function setviewpct() {
        var date = new Date();
        date.setTime(date.getTime() + 365*24*60*60*1000);
        document.cookie = 'viewpct='
                + pctbox.value
                + ';'
                + ' expires='
                + date.toGMTString()
                + '; path=/';
    }

    function getviewpct() {
        var c = document.cookie.split(';');
        for(var i = 0; i < c.length; i++) {
            var v = c[i].split('=');
            if(v[0] && v[0] == 'viewpct') {
                return v[1] ? v[1] : '100';
            }
            return '100';
        }
	}

</script>\n";

$style = "
<style>
    body { 
        text-align: center;
        font-size: .8em;
        overflow: hidden;
    }
    h3        { margin: 0; padding: 0; }
    #hdrbar   { margin: auto; overflow: hidden; }
    #divimage  {
        position: absolute; 
        width:100%;
        overflow: auto;
        top: 5em;
        bottom: 0;
    }
    #linksdiv { float: left; margin-left: 3em;}
    #pageimg  { border: 1px solid gray; display: none; margin: auto;}
    #jumpto, #btnresize,
    #btnprev1, #btnnext1 { margin: .3em;}
    #pctbox   { margin: .3em; width: 2em;}
</style>\n";

$imageurl = url_for_page_image($projectid, $pagename);

$pgselect = 
    "<select name='jumpto' id='jumpto'>\n";
foreach($project->PageRows() as $row) {
    $pgname   = $row['fileid'];
    $filename = $row['image'];

    $pgselect .= "<option value='{$pgname}'"
        . ($pgname === $pagename ? " selected" : "")
        .">{$pagename}</option>\n";
}
$pgselect .= "</select>\n";

echo "<!doctype html>
<html>
<head>
<meta charset='utf-8'>
".favicon()."
<title>$title</title>
{$script}
{$style}
</head>
<body onload='init()'>
<form id='imgform' name='imgform'>
<div id='linksdiv'>"
. link_to_project($projectid, "Project page")."<br>"
. link_to_page_detail($projectid, "Page list") 
."</div>
<div id='hdrbar'>
<h3>{$projecttitle} (<span id='pagename'>{$pagename}</span>)</h3>
<input type='hidden' name='projectid' value='$projectid'>
<input type='text' maxlength='3' name='pctbox' id='pctbox'>%
<input type='button' id='btnresize' value='Resize' onclick='setpct()'>
&nbsp;|&nbsp; "._("View page:")."
{$pgselect}&nbsp;|&nbsp;"
."&nbsp;|&nbsp;
<input type='button' id='btnprev1'
            value='$previous' onclick='doprev()'>
<input type='button' id='btnnext1'
            value='$next' onclick='donext()'>
</div>
<div id='divimage'>
<img id='pageimg' alt='' src='$imageurl' onload='eImgLoad()'>
</div>
</form>
    </body>
</html>";

// vim: sw=4 ts=4 expandtab
?>
