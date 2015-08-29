<?php
/*
    button patterns - both operate with attached click events
        <a ...><img ....></a>  
        <input type-'image' src=url...>
*/

ini_set("display_errors", 1);
error_reporting(E_ALL);

$relPath = "../../pinc/";

include_once $relPath.'dpinit.php';
include_once "dpctls.php";

// Note: pagename is either provided if known,
// or the user wants the "next available" page.

/** @var $User DpThisUser */
$User->IsLoggedIn()
    or RedirectToLogin();

$projectid      = ArgProjectId() 
    or die("No projectid");
$pagename       = Arg("pagename");

$project        = new DpProject($projectid);
$page           = ($pagename 
                    ? new DpPage($projectid, $pagename)
                    : $project->NextAvailablePage());

$project->IsAvailableForActiveUser()
    or die("Project is not available.");

$page && $page->Exists() 
    or die("Page doesn't exist.");

$project->MayBeProofedByActiveUser()
    or die("Security violation.");

$jslink         = link_to_js("dp_edit.js");

global $site_abbreviation, $ajax_url, $site_url;

$title          = "{$site_abbreviation}: "
                    ."[{$project->RoundId()}] "
                    ."{$project->Title()}";


$langcode       = ArgLangCode($project->LanguageCode());
$zoom           = $User->ImageZoom();
$fontfamily     = $User->FontFamily();
$fontface       = $User->FontFace();
$fontsize       = $User->FontPointSize();
$layout         = $User->IsVerticalLayout() ? "vertical" : "horizontal";

$text           = maybe_convert(rtrim($page->ActiveText()));
$prooftext      = 
$previewtext    = h($text);
$pagename       = $page->PageName();
$tweettext      = "";
$action         = url_for_iprocesstext();
$imgurl         = $page->ImageUrl();
$previmgurl     = $page->PrevImageUrl();
$nextimgurl     = $page->NextImageUrl();

$imagewidth     = $User->ImageZoom();

$langpicker     = "";
$fface          = $fontface > 0
                    ? "font-family: $fontfamily;"
                    : "";

echo 
"<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<link href='/c/css/ctlbar.css' rel='stylesheet' type='text/css'>
<script>
    var AJAX_URL = '{$ajax_url}';
    var SITE_URL = '{$site_url}';
</script>
$jslink
<title>$title</title>
<style type='text/css'>

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
        font-size: $fontsize;
        $fface
    }
    img#imgpage {
        position: relative;
        top: 0;
        width: {$imagewidth}%;
    }
</style>
</head>
<body onload='eBodyLoad();' onresize='eResize()'>
<div id='divFindAndReplace'>
<table id='tblFindAndReplace'>
  <tr>
    <td>Find</td>
    <td class='right'>
      <input type='text' name='txtfind' id='txtfind'>
    </td>
    <td class='right'>
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
    <td class='right'>
      <input type='text' name='txtrepl' id='txtrepl'>
    </td>
    <td class='right'>
      <input type='checkbox' name='chkm' id='chkm' title='multi-line'/>
      multiline
    </td>
  </tr>
</table> <!-- tblFindAndReplace -->
</div> <!-- divFindAndReplace -->\n"

. upload_widget_iframe($projectid, $pagename)

."<div id='divleft'>
    <div id='divprevimage'>
        <img id='imgprev' src='$previmgurl' alt=''>
    </div> <!-- divprevimage -->
    <div id='divimage'>
        <img id='imgpage' src='$imgurl' alt=''>
    </div> <!-- divimage -->
    <div id='divnextimage'>
    <img id='imgnext' src='$nextimgurl' alt=''>
    </div> <!-- divnextimage -->
</div> <!-- divleft -->

<div id='divsplitter'> </div>

<div id='divright'>
<div id='divctlbar'>
    <div id='divctlimg'>
        <a id='linksync'>
        <img id='icosync' src='/graphics/brnsync.png' alt='' "
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
        <input type='image' src='gfx/savequit.png' title='$prompt' 
            id='savequit' name='savequit' alt=''>\n";

    $prompt = _("Quit");
    echo "
        <input type='image' id='quit' name='quit' title='$prompt' alt='$prompt'
            src='gfx/quit.png'>\n";

    $prompt = _("Mark this page bad.");
    $icon = "gfx/bad.png";
    $state = "notbad";
    echo "
        <input type='image' src='$icon' class='mini_spaced_image'
        id='badbutton' name='badbutton' title='$prompt' alt='' >

    </div> <!-- divctlnav -->
</div> <!-- divctlbar -->

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
    <input type='hidden' name='splitterpct' value='50'>
    
<div id='divfratext'>
  <div id='divtext'>
    <pre id='divpreview'>{$previewtext}</pre>
    <textarea name='tatext' id='tatext' wrap='soft' >
{$prooftext}</textarea>
  </div> <!-- divtext -->
</div> <!-- divfratext -->

<!--
<div id='divtweet'>
    <textarea name='tatweet' id='tatweet'>{$tweettext}</textarea>
</div>
-->
</form>
</div> <!-- divright -->

<div id='divcontrolbar'>
<form accept-charset='UTF-8' name='formctls' target='_top'>
<div id='charpicker' onmouseover='return top.ePickerOver(event)'
                     onmouseout='return top.ePickerOut(event)' 
                     onclick='return top.eCharClick(event)'>
    <div id='selectors'>
    </div>
    <div id='pickers'>
    </div>
</div> <!-- charpicker -->
<div id='charshow'> 
 <div id='divchar'></div>
 <div id='divdigraph'></div>
</div> <!-- charshow -->
<div id='div_ctl_tags'>
  <div id='div_ctl_top' class='middle'>
    <div id='div_ctl_right'>
      <div id='ctl_tags_top' class='clear rfloat'>
            <button title='italics' 
                onclick='return top.eSetItalics();'>&lt;i&gt;</button>

            <button title='bold'
                onclick='return top.eSetBold();'>&lt;b&gt;</button>

            <button title='small-caps'
                onclick='return top.eSetSmallCaps()'>
                &lt;sc&gt; </button>

            <button title='gesperrt (spaced)'
                onclick='return top.eSetGesperrt()'>&lt;g&gt;</button>

            <button title='guillemets'
                onclick='return top.eSetGuillemets()'> « » </button>

            <button title='guillemetsR'
                onclick='return top.eSetGuillemetsR()'> » « </button>

            <button title='antiqua'
                onclick='return top.eSetAntiqua()'>&lt;f&gt;</button>

            <button title='dequotes'
                onclick='return top.eSetDeQuotes()'> „ “ </button>

            <button title='itquotes'
                onclick='return top.eSetItQuotes()'> “ „ </button>


            <button title='Remove markup'
                onclick='return top.eRemoveMarkup()'><span class='linethru'>&lt;×&gt;</span></button>
        </div> <!-- ctl_tags_top -->
        <div id='ctl_tags_middle' class='rfloat clear'>
            <button title='nowrap' onclick='return top.eSetNoWrap()'>
                 /* */ </button>

            <button title='note' onclick='return top.eSetNote()'>
                [** ]</button>

            <button title='blockquote'
                onclick='return top.eSetBlockQuote()'>
                 /# #/ </button>

            <button title='uppercase'
                onclick='return top.eSetUpperCase()'>
                 ABC </button>

            <button title='title case'
                onclick='return top.eSetTitleCase()'> Abc </button>

            <button title='lowercase'
                onclick='return top.eSetLowerCase()'> abc </button>
            <button title='brackets' 
                onclick='return top.eSetBrackets()'>[ ]</button>
            <button title='braces'
                onclick='return top.eSetBraces()'>{ }</button>
            <button title='curly quotes'
                onclick='return top.eCurlyQuotes()'> “</button>
        </div> <!-- ctl_tags_middle -->
        <div id='ctl_tags_bottom' class='rfloat clear'>
            <button title='thought break'
                onclick='return top.eInsertThoughtBreak()'>
                &lt;tb&gt;</button>
            <button title='footnote'
                onclick='return top.eSetFootnote()'>
                [Footnote: ]</button>
            <button title='illustration'
                onclick='return top.eSetIllustration()'>
                               [Illustration: ]</button>
            <button title='sidenote'
                onclick='return top.eSetSidenote()'>
                               [Sidenote: ]</button>
            <button title='Blank Page'
                onclick='return top.eSetBlankPage()'>
                                        [Blank Page]</button>
        </div> <!-- ctl_tags_bottom -->
      </div> <!-- div_ctl_right -->
    </div> <!-- div_ctl_top -->
</div> <!-- div_ctl_tags -->
</form>
</div> <!-- divcontrolbar -->

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
        ". _("Guidelines")."</a>
    </div>
</div> <!-- divstatusbar -->\n";
echo "
</html>";
exit;

function EchoFontFaceCombo($curface) {
    global $Context;

    echo "
<select class='ctlcombo' 
            id='selfontface' name='selfontface'
            title='change font'>\n";
    foreach($Context->FontFaces() as $face) {
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
    foreach($Context->FontSizes() as $size) {
        $selected = ($size == $cursize ? " SELECTED ":"");
        echo "<option value='$size' $selected>$size</option>\n";
    }
    echo "</select>\n";
}

// vim: sw=4 ts=4 expandtab

?>
