<?php

$relPath="../../pinc/";
require_once $relPath."dpinit.php";
require_once $relPath . "dpctls.php";

init_timer();

$projectid  = ArgProjectId();
$project    = new DpProject($projectid);

$pagename   = ArgPageName();

$User->IsLoggedIn() or RedirectToLogin();

if(! $projectid)
    die("parameter 'projectid' is invalid");
    
if(! $project->MayBeProofedByActiveUser())
    die("Security violation.");

$roundid    = $project->RoundId();
$title          = "{$site_abbreviation}: "
                    ."[{$project->RoundId()}] "
                    ."{$project->Title()}";
$langcode   = ArgLangCode($project->LanguageCode());
//$barpct     = $User->BarPct();
$barpct     = $User->ImageFramePct();
$zoom       = $User->ImageZoom();
$fontface   = $User->FontFace();
$fontsize   = $User->FontSize();
$is_sync    = Arg("is_sync", "0");

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

$pagename      = $page->PageName();
$prooftext     = h($page->ActiveText());
$previewtext   = preg_replace("/<(\/?).>/u", 
                        "&lt;$1&nbsp;&gt;", $page->ActiveText());
$layout        = $User->IsVerticalLayout()
                    ? "vertical"
                    : "horizontal";

$action = url_for_processtext();
$imgurl = $page->ImageUrl();
$previmgurl = $page->PrevImageUrl();
$nextimgurl = $page->NextImageUrl();

// </head>
// <body id='pfbody' name='pfbody'
            // onload='eBodyLoad()' onresize='eResize()'>
echo "<!doctype html>
<meta charset='utf-8'>
<html>
<head>
<title></title>
<script>
    var AJAX_URL = '{$ajax_url}';
    var SITE_URL = '{$site_url}';
</script>
";

echo link_to_js("dp_edit.js");
echo "
<style type='text/css'>
    * {
        box-sizing:border-box;
        -moz-box-sizing:border-box; /* Firefox */
        -webkit-box-sizing:border-box; /* Safari */
    }

    body#pfbody {
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 100%;
    }

    .blue {
        color: green;
    }

    form#formedit {
        overflow: hidden;
    }

    #divimage {
        position: absolute;
        left: 0;
        top: 0;
        width: " . ($layout == 'vertical'
                    ? "{$barpct}%"
                    : "100%").";
        height: " . ($layout == 'vertical'
                    ? "100%"
                    : "{$barpct}%").";
        overflow: auto;
        visibility: hidden;
    }

    div#divprevimage,
    div#divnextimage {
        position: absolute;
        left: 0;
        width: 100%;
        height: 10%;
        display: none;
    }
    div#divprevimage { top: 0; }
    div#divnextimage { bottom: 0; }

    div#divfratext
    {
        position: absolute;
        right: 0;
        background-color: #FFF8DC;
        border: 0;
        overflow: auto;
        left: 50%;
        height: 100%;
        min-height: 100%;
        visibility: hidden;
    }

    div#divtext {
        overflow: hidden;
        padding: 5px;
        margin: 0;
        min-width: 100%;
        min-height: 100%;
    }

    textarea.dpeditor,
    div.dpeditor {
        line-height: 1.2em;
    }

    textarea#tatext,
    div#divpreview {
        position: absolute;
        top: 0;
        left: 0;
        min-width: 100%;
        min-height: 100%;
        margin: 0;
        padding: 5px;
    }

    textarea#tatext {
        border: 0;
        overflow: hidden;
        white-space: pre;
        color: black;
        background: transparent;
    }

    div#divpreview {
        white-space: pre;
        overflow: hidden;
        color: white;
        /* background-color: #efe; */
        visibility: hidden;
    }

    div#divcontrolbar {
        position: absolute;
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

    img#imgpage {
        width: {$zoom}%;
    }

    iframe#uploadframe {
        width:100%;
        height:2em;
        display: none;
        overflow: hidden;
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
        padding: 0 .5em;
        border: 1px black solid;
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

    span.wcs { border: 2px solid red;
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
";
echo link_to_css("ctlbar.css");
echo "
</head>
<body id='pfbody' onload='eBodyLoad()'>
<div id='divproof'>
    <div id='divimage'>
    <div id='divprevimage'>
        <img id='imgprev' src='$previmgurl'/>
    </div>
    "
    .upload_widget_iframe($projectid, $pagename)
    ."
        <img id='imgpage' src='{$page->ImageUrl()}'>
    <div id='divnextimage'>
        <img id='imgnext' src='$nextimgurl'/>
    </div>
    </div> <!-- divimage -->\n";

//    $langpicker    = LanguagePicker("pklangcode", $langcode,
//                "ctlcombo", "eLangcode(event)", "name_code");
//    $wlistpicker   = WordListPicker("wordlist", "flagged",
//                        "ctlcombo hide", "eWordlist()");

    echo "
    <div id='divcontrolbar'>

    <div id='divctlimg'>
    <a id='linksync'>
    <img id='icosync' "
        .($is_sync == "0"
            ? "src='/graphics/brnsync.png' "
            : "src='/graphics/blusync.png' ")
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

    echo " <a id='linklayout'>
        <img src='$png' id='switchlayout'
            title='$prompt' alt='$prompt'>
    </a>\n";

    EchoTextShifter();
    EchoFontFaceCombo($fontface);
    EchoFontSizeCombo($fontsize);

    $prompt = _("Search and replace");
    echo "<a id='linksearch'>
    <img src='/graphics/search.png'
        title='$prompt' alt='$prompt'></a>\n";

    // wordcheck
    $prompt = _("Wordcheck");
    echo "
        <a id='linkwc'>
        <img src='gfx/wchk-off.png' id='imgwordcheck' 
                title='$prompt' alt='$prompt'>
        </a>
    </div> <!-- divctlimg -->

    <div id='divctlwc' class='hide'>
        <span id='span_wccount' class='ctlcombo'> 0 </span>
    $langpicker
    $wlistpicker
    </div>

    <div id='divctlnav'>\n";
    $prompt = _("Return page");
    echo "
    <input type='button' name='returnpage' id='returnpage'
        src='gfx/rtnpg.png' alt='$prompt'  title='$prompt'>\n";

    $prompt = _("Save in progress");
    echo "
    <input type='image' id='savetemp' name='savetemp'
            title='$prompt' alt='$prompt' 
            src='gfx/savetmp.png'>\n";

    $prompt = _("Save done - request next");
    echo "
    <input type='image' id='savenext' name='savenext'
            title='$prompt' alt='$prompt'
            src='gfx/savenxt.png'>\n";

    $prompt = _("Save and quit");
    $url = url_for_project($page->ProjectId());
    echo "
    <input type='image' id='savequit'
            src='gfx/savequit.png' title='$prompt' alt='$prompt'
            name='savequit'>\n";

    $prompt = _("Quit");
    echo "
    <input type='image' id='quit' name='quit'
        title='$prompt' alt='$prompt'
        src='gfx/quit.png'>

    </div> <!-- divctlnav -->
    </div> <!-- controlbar -->\n";

echo "
    <form accept-charset='UTF-8' name='formedit' id='formedit'
            method='POST' target='_top' action='$action'>
    <input type='hidden' name='is_sync' id='is_sync' value='"
        .($is_sync ? "1" : "0")."'>
    <input type='hidden' name='layout' value='$layout'>
    <input type='hidden' name='barpct' value='$barpct'>
    <input type='hidden' name='projectid' value='$projectid'>
    <input type='hidden' name='langcode' value='$langcode'>
    <input type='hidden' name='pagename' value='{$pagename}'>
    <input type='hidden' name='zoom' value='{$zoom}'>
    <input type='hidden' name='todo' value=''>
    <div id='divfratext'>
      <div id='divtext'>
        <div id='divpreview' class='dpeditor'>{$previewtext}</div>
        <textarea name='tatext' id='tatext' class='dpeditor'
            wrap='off' >
{$prooftext}</textarea>
      </div>
    </div> <!-- divfratext -->
</form>
<div id='divstatusbar'>
    <div class='lfloat w25'>
        "._("Page: ")."{$pagename}
    </div>
    <div class='rfloat w75'>
        <a class='rfloat quarter' 
            href='/faq/prooffacehelp.php' target='_blank'>
        "._("Help")."</a>

        <a id='linkupload' class='rfloat quarter likealink'>
        ". _("Replace image file")."</a>

        <a class='rfloat quarter' 
            href='/faq/document.php' target='_blank'>
        ". _("Guidelines")."</a>

        <a class='rfloat quarter' target='_blank'
            href='".url_for_project($page->projectId())."'>
        ". _("Project comments")."</a>"
        // .get_timer()
        ."
    </div>
</div> <!-- divstatusbar -->
</div> <!-- divproof -->

<div id='divctls' onload='eCtlInit()'>
<form accept-charset='UTF-8' name='formctls' target='_top'>
<div id='charpicker' 
                onmouseover='return ePickerOver(event)'
                onmouseout='return ePickerOut(event)' 
                onclick='return eCharClick(event)'>
    <div id='selectors'>
    </div>
    <div id='pickers'>
    </div>
</div>
<div id='charshow'> </div>
<div>
    <div id='ctl_top' class='middle'>
        <div id='ctl_right' class='w35'>
        <div id='ctl_tags_top' class='clear rfloat proofbutton'>
            <button title='italics' class='proofbutton'
                onclick='return eSetItalics();'>&lt;i&gt;</button>

            <button title='bold'
                onclick='return eSetBold();'>&lt;b&gt;</button>

            <button title='small-caps'
                onclick='return eSetSmallCaps()'>
                &lt;sc&gt; </button>

            <button title='gesperrt (spaced)'
                onclick='return eSetGesperrt()'>&lt;g&gt;</button>

            <button title='guillemets'
                onclick='return eSetGuillemets()'> « » </button>

            <button title='antiqua'
                onclick='return eSetAntiqua()'>&lt;f&gt;</button>


            <button title='Remove markup'
                onclick='return eRemoveMarkup()'><span class='linethru'>&lt;×&gt;</span></button>
        </div>
        <div class='rfloat clear proofbutton'>
            <button title='nowrap' onclick='return eSetNoWrap()'>]
                 /* */ </button>

            <button title='note' onclick='return eSetNote()'>
                [** ]</button>

            <button title='blockquote'
                onclick='return eSetBlockQuote()'>
                 /# #/ </button>

            <button title='uppercase'
                onclick='return eSetUpperCase()'>
                 ABC </button>

            <button title='title case'
                onclick='return eSetTitleCase()'> Abc </button>

            <button title='lowercase'
                onclick='return eSetLowerCase()'> abc </button>
            <button title='brackets' 
                onclick='return eSetBrackets()'>[ ]</button>
            <button title='braces'
                onclick='return eSetBraces()'>{ }</button>
        </div> <!-- ctl_tags_top -->
        <div id='ctl_tags_bottom' class='rfloat clear proofbutton'>
            <button title='footnote'
                onclick='return eSetFootnote()'>
                [Footnote: ]</button>
            <button title='illustration'
                onclick='return eSetIllustration()'>
                               [Illustration: ]</button>
            <button title='sidenote'
                onclick='return eSetSidenote()'>
                               [Sidenote: ]</button>
            <button title='Blank Page'
                onclick='return eSetBlankPage()'>
                                        [Blank Page]</button>
        </div> <!-- ctl_tags_bottom -->
        </div> <!-- ctl_left -->
    </div> <!-- ctl_bottom -->
</div>
</form>

</div> <!-- divctls -->
</body>
</html>";
exit;

function name_code($code, $name) {
    return "{$name} ($code)";
}


function EchoTextShifter() {
    echo "
    <input type='button' class='btnshift ctlcombo' value='<'
                    id='btntxtleft'>
    <input type='button' class='btnshift ctlcombo' value='>'
                    id='btntxtright'>\n";
}

function EchoFontFaceCombo($curface) {
    global $Context;

    echo "
<select class='ctlcombo' 
            id='selfontface' name='selfontface'
            title='change font'>\n";
    foreach($Context->FontFaces() as $face)
    {
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
