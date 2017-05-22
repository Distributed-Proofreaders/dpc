<?php
ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath = "../pinc/";
require_once $relPath."dpinit.php";
require_once $relPath."dpctls.php";

/** @var $User DpThisUser */
$User->IsLoggedIn()
    or redirect_to_home();

init_timer();

$projectid  = ArgProjectId();
$pagename   = ArgPageName();

$project    = new DpProject($projectid);
$langcode   = ArgLangCode($project->LanguageCode());
// $barpct     = CookieArg("profile", "barpct", "50");
$zoom       = CookieArg("profile", "zoom", "100%");
$fontface   = CookieArg("profile", "fontface", "Courier");
$fontsize   = CookieArg("profile", "fontsize", "14pt");
// $isvert     = CookieArg("profile", "isvert", "0");
$layout     = CookieArg("profile", "layout",  "horizontal");

if($pagename) {
    // The user clicked on a saved page.
    $page = new DpPage($projectid, $pagename);
}
else {
    // User clicked "Start Proofreading" or "Save & Proofread Next"
    // give them a new page
    $page = $project->CheckOutNextAvailablePage();
}

if (! $page) {
    $roundid = $project->RoundId();
    echo "<html><head>
        <title>". _("Unable to get an available page")."</title>
        </head>
        <body>"
            . sprintf(_("No page is available. 
            Return to the <a %s>project listing page</a>."),
            "href='round.php?roundid={$roundid}' target='_top'")."
        </body></html>";
    exit;
}

if($page->IsBad() && ! $page->UserMayManage()) {
    redirect_to_project($projectid);
}

$prooftext      = 
$previewtext    = h(rtrim($page->ActiveText()));
$pagename       = $page->PageName();
// $tweettext      = implode("\n", $page->Tweets());
$tweettext = "";
$action         = url_for_processtext();
$imgurl         = $page->ImageUrl();
$previmgurl     = $page->PrevImageUrl();
$nextimgurl     = $page->NextImageUrl();

// $previewtext   = preg_replace("/<(\/?).>/u", 
                // "&lt;$1&nbsp;&gt;", rtrim($page->ActiveText()));
// $layout        = $isvert
                    // ? "vertical"
                    // : "horizontal";

    // $langpicker    = LanguagePicker("pklangcode", $langcode,
                // "ctlcombo", "top.eLangcode(event)", "name_code");
    $wlistpicker   = WordListPicker("wordlist", "flagged",
                        "ctlcombo hide", "top.eWordlist()");

echo "<!doctype html>
<meta charset='utf-8'>
<html>
<head>
<title></title>
<style>
    * {
        box-sizing:border-box;
        -moz-box-sizing:border-box; /* Firefox */
        -webkit-box-sizing:border-box; /* Safari */
    }

    body#pfbody {
        margin: 0;
        padding: 0;
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 100%;
    }

    form#formedit {
        overflow: hidden;
    }

    #divsplitter {
        position: absolute;
        background-color: green;
        z-index: 2;
        top: 0;
        left: 0;
    }

    div#divleft {
        position: absolute;
        left: 0;
        top: 0;
        overflow: auto;
        visibility: hidden;
    }

    #divimage {
        width: 100%;
        height: 100%;
        overflow: auto;
    }

    div#divprevimage,
    div#divnextimage {
        position: absolute;
        left: 0;
        width: 100%;
        display: none;
    }
    div#divprevimage { top: 0; }
    div#divnextimage { bottom: 0; }

    img#nextimg ,
    img#previmg {
        height: 10%;
        width: 100%;
    }

    div#divright {
        position: absolute;
        right: 0;
        visibility: hidden;
    }

    div#divfratext {
        position: absolute;
        width: 100%;
        height: 100%;
        background-color: #FFF8DC;
        overflow: auto;
    }

    div#divtext {
        position: relative;
        top: 0;
        left: 0;
        overflow: hidden;
        padding: 5px;
        margin: 0;
        min-width: 100%;
        min-height: 100%;
    }

    pre#divpreview,
    div#divpreview {
        /* line-height: 1.22em;   for IE for overlay */
    }

    textarea#tatext,
    pre#divpreview,
    div#divpreview {
        position: absolute;
        top: 0;
        left: 0;
        min-width: 100%;
        min-height: 100%;
        margin: 0;
        padding: 5px;
        line-height: 1.5em;
    }

    textarea#tatext {
        border: 0;
        overflow: hidden;
        white-space: pre;
        color: black;
        background: transparent;
    }

    pre#divpreview,
    div#divpreview {
        white-space: pre;
        overflow: hidden;
        color: white;
        visibility: hidden;
    }

    div#tweet {
    }

    div#divcontrolbar {
        position: absolute;
        left: 0;
        width: 100%;
        padding: 1px 5px;
        background-color:#CDC0B0;
        text-align: left;
        border: 2px black solid;
        overflow: hidden;
        visibility: hidden;
    }

    div#divcontrolbar * {
        height: 24px;
        float: left;
    }
    div#divcontrolbar div {
        overflow: hidden;
        float: left;
    }

    div#divcontrolbar div#divctlnav {
        float: right;
    }

    div#divstatusbar {
        position: absolute;
        left: 0;
        bottom: 0;
        width: 100%;
        background-color: #CDC0B0;
        overflow: hidden;
        padding: 2px 5px;
        padding: 2px 5px;
        padding: 2px 5px;
    }

    select#pklangcode,
    select#selfontface {
        width: 9em;
    }

    .btnshift {
        padding: 0;
    }

    div#div_context_list {
        margin-top: solid gray 2px;
    }
        
    img#imgpage {
        position: relative;
        top: 0;
    }

    div#divFandR,
    iframe#uploadframe {
        position: absolute;
        padding: .3em;
        width:100%;
        display: none;
        overflow: hidden;
        border: 1px solid blue;
        background-color: #CDC0B0;
        z-index: 1;
    }

    div#divFandR div {
        float: left;
    }

    table#tblFandR {
        width: 100%;
    }

    div#divFandR input {
        font-size: .8em;
    }

    pre { margin: 0; }

    .lfloat { float: left; }
    .rfloat { float: right; }
    .clear  { clear: both; }
    .w100   { width: 100%; }
    .w75    { width: 75%; }
    .w90    { width: 90%; }
    .w80    { width: 80%; }
    .w65    { width: 65%; }
    .w50    { width: 50%; }
    .w35    { width: 35%; }
    .w25    { width: 25%; }
    .w20    { width: 20%; }
    .w15    { width: 15%; }
    .w10    { width: 10%; }
    .hide   { display: none; }

    .ctlcombo {
        background-color:#FFF8DC;
        font-size: 14px;
    }

    .quarter {
        width: 25%;
        text-align: center;
        float: left;
    }

    .likealink {
        margin: 0;
        padding: 0;
        color: blue;
        cursor: pointer;
        text-decoration: underline;
    }

    span#span_wccount {
        padding: .2em .5em;
    }

    span.accepted {
        border: 1px dotted black;
        margin: -1px;
        color: #cfc;
        background-color: #cfc;
    }


    span.wc {
        background-color: #FFC7D7;
        color: #FFC7D7;
    }

    span.wcs { border: 2px dotted green;
        margin: -2px;
    }

    span.wcb { border: 2px solid red;
        color: #FFC7D7;
        background-color: #FFC7D7;
        margin: -2px;
    }

    body.wchk {
        overflow: hidden;
    }

    div.wchkhdr {
        position: absolute;
        top: 0;
    }

    div.wchk {
        position: absolute;
        width: 100%;
        height: 98%;
        top: 1%;
    }

    div.wchkimg, div.wchktext {
        position: absolute;
        overflow: auto;
        height: 98%;
        width: 49%;
    }

    img.wchk { width: 100%; }

    div.wchkimg { left: 1%; right: 1%; top: 1%; bottom: 1%; }
    div.wchktext { right: 0; }

</style>

</head>
<body id='pfbody'>
<div id='divFandR'>
<table id='tblFandR'>
  <tr>
    <td>Find</td>
    <td class='r'>
      <input type='text' name='txtfind' id='txtfind'>
    </td>
    <td class='r'>
      <input type='checkbox' name='chki' id='chki' title='ignore case'/>
      ignore case
    </td>
    <td rowspan='2'>
      <input type='button' value='*'
                title='Replace' id='btnrepl'/>
      <input type='button' value='>'
                title='Find next' id='btnfind'/>
      <input type='button' value='*>'
                title='Repl+Find' id='btnreplnext'/>
      <input type='button' value='Close' title='Close find'
                name='btnclose' id='btnclose'/>
    </td>   
  </tr>
  <tr>
    <td>Replace</td>
    <td class='r'>
      <input type='text' name='txtrepl' id='txtrepl'>
    </td>
    <td class='r'>
      <input type='checkbox' name='chkm' id='chkm' title='multi-line'/>
      multiline
    </td>
  </tr>
</table>
</div> <!-- divFandR -->\n"
.upload_widget_iframe($projectid, $pagename)
."
<div id='divcontrolbar'>
    <div id='divctlimg'>
        <a id='linksync'>
        <img id='icosync' src='/graphics/brnsync.png' "
        ."title='"._("Sync")."'></a>\n";

    $prompt = _("Zoom in");
    echo "
        <a id='linkzoomin'>
        <img src='/graphics/zoomin.png' title='$prompt' alt='$prompt'>
        </a>\n";

    $prompt = _("Zoom out");
    echo "
        <a id='linkzoomout'>
        <img src='/graphics/zoomout.png' title='$prompt' alt='$prompt'>
        </a>\n";
        
    // switch orientation
    $prompt = ($layout == "vertical")
        ? _("Switch to horizontal")
        : _("Switch to vertical");
    $png = ($layout == "vertical")
        ? "gfx/vert.png"
        : "gfx/horiz.png";

    echo "
        <a id='linklayout'>
        <img src='$png' id='switchlayout' title='$prompt' alt='$prompt'>
        </a>\n";

    // EchoTextShifter();
    EchoFontFaceCombo($fontface);
    EchoFontSizeCombo($fontsize);

    $prompt = _("Find and replace");
    echo "
        <a id='linkfind'>
        <img src='/graphics/search.png' title='$prompt' alt='$prompt'></a>\n";

    // wordcheck
    $prompt = _("Wordcheck");
    echo "
        <a id='linkwc'>
        <img src='gfx/wchk-off.png' id='imgwordcheck' title='$prompt' alt='$prompt'>
        </a>
    </div> <!-- divctlimg -->

    <div id='divctlwc' class='hide'>
        <span id='span_wccount' class='ctlcombo'> 0 </span>
    $langpicker
    $wlistpicker
    </div> <!-- divctlwc -->

    <div id='divctlnav'>\n";
    $prompt = _("Return page");
    echo "
        <input type='image' id='returnpage' name='returnpage'
            src='gfx/returnpage.png' alt='$prompt'  title='$prompt'>\n";

    $prompt = _("Save in progress");
    echo "
        <input type='image' id='savetemp' name='savetemp'
            title='$prompt' alt='$prompt' src='gfx/savetmp.png'>\n";

    $prompt = _("Save done - request next");
    echo "
        <input type='image' id='savenext' name='savenext'
            title='$prompt' alt='$prompt' src='gfx/savenxt.png'>\n";

    $prompt = _("Save and quit");
    $url = url_for_project($page->ProjectId());
    echo "
        <input type='image' id='savequit' name='savequit'
            src='gfx/savequit.png' title='$prompt' alt='$prompt'>\n";

    $prompt = _("Quit");
    echo "
        <input type='image' id='quit' name='quit' title='$prompt' alt='$prompt'
            src='gfx/quit.png'>\n";

    if($page->IsBad()) {
        // $prompt = _("Release bad page.");
        // $prompt = _("Bad reason:\n".$page->BadReason());
        $prompt = _("Bad page. Request PM to fix.");
        $icon = "gfx/broken.png";
        $state = "isbad";
    }
    else {
        $prompt = _("Bad page. Request PM to fix.");
        $icon = "gfx/bad.png";
        $state = "notbad";
    }

    echo "
        <input type='image' id='badbutton' name='badbutton'
            title='$prompt' alt='$state' src='$icon'>

    </div> <!-- divctlnav -->
</div> <!-- controlbar -->

<div id='divleft'>
    <div id='divprevimage'>
        <img id='imgprev' src='$previmgurl'>
    </div> <!-- divprevimage -->
    <div id='divimage'>
        <img id='imgpage' src='$imgurl'>
    </div> <!-- divimage -->
    <div id='divnextimage'>
    <img id='imgprev' src='$nextimgurl'>
    </div> <!-- divnextimage -->
</div> <!-- divleft -->

<div id='divsplitter'> </div>

<div id='divright'>
<form accept-charset='UTF-8' name='formedit' id='formedit'
            method='POST' target='_top' action='$action'>
    <input type='hidden' name='is_sync' id='is_sync' value='0'>
    <input type='hidden' name='layout' value='$layout'>
    <input type='hidden' name='projectid' value='$projectid'>
    <input type='hidden' name='langcode' value='$langcode'>
    <input type='hidden' name='pagename' value='{$pagename}'>
    <input type='hidden' name='zoom' value='{$zoom}'>
    <input type='hidden' name='todo' value=''>
    <input type='hidden' name='acceptwords' value=''>
    <input type='hidden' name='badreason' value=''>
    <input type='hidden' name='splitterpct' value='50'>
    
<div id='divfratext'>
  <div id='divtext'>
    <pre id='divpreview' class='dpeditor'>{$previewtext}</pre>
    <textarea name='tatext' id='tatext' class='dpeditor' wrap='off' >
{$prooftext}</textarea>
  </div>
</div> <!-- divfratext -->
<!--
<div id='divtweet'>
    <textarea name='tatweet' id='tatweet' class='tweet' >{$tweettext}</textarea>
</div>
-->
</form>
</div> <!-- divright -->

<div id='divstatusbar'>
    <div class='lfloat w25'>
        "._("Page: ")."{$pagename}
    </div>
    <div class='rfloat w75'>
        <a class='rfloat quarter' 
            href='/faq/prooffacehelp.php' target='_blank'>
        "._("Help")."</a>\n"

        .($page->UserMayManage()
            ? "
        <a id='linkupload' class='rfloat quarter likealink'>
        ". _("Replace image")."</a>\n"
            : "") ."

        <a class='rfloat quarter' target='_blank'
            href='".url_for_project($page->projectId())."'>
        ". _("Project comments")."</a>

        <a class='rfloat quarter' 
            href='/faq/document.php' target='_blank'>
        ". _("Guidelines")."</a>"

        // .get_timer()
        ."
    </div>
</div> <!-- divstatusbar -->
</body>
</html>";
exit;

function name_code($code, $name) {
    return "{$name} ($code)";
}


/*
function EchoTextShifter() {
    echo "
    <input type='button' class='btnshift ctlcombo' value='<'
                    id='btntxtleft'>
    <input type='button' class='btnshift ctlcombo' value='>'
                    id='btntxtright'>\n";
}

function ProfileCookie($key) {
}
*/

function EchoFontFaceCombo($curface) {
    global $Context;

    echo "
<select class='ctlcombo' 
            id='selfontface' name='selfontface'
            title='change font'>\n";
    foreach($Context->FontFaces() as $idx => $face) {
        assert($idx);
        $selected = ($face == $curface ? " SELECTED ":"");
        echo "<option value='$face' $selected>$face</option>\n";
    }
    echo "</select>\n";
}

function EchoFontSizeCombo($cursize) {
    global $Context;

echo "
<select class='ctlcombo' 
        id='selfontsize' name='selfontsize'
        title='"._("Change font size")."'>\n";
foreach($Context->FontSizes() as $size)
{
    $selected = ($size == $cursize ? " SELECTED ":"");
    echo "<option value='$size' $selected>$size</option>\n";
}
echo "</select>\n";
}

// vim: sw=4 ts=4 expandtab
?>
