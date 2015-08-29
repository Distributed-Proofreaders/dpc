<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

global $site_abbreviation, $ajax_url, $site_url;

$relPath = "./../../pinc/";

include_once $relPath.'dpinit.php';

$User->IsLoggedIn()
    or RedirectToLogin();

$projectid      = ArgProjectId();
$pagename       = ArgPageName();

if(! $projectid)
    die("parameter 'projectid' is invalid");

$project = new DpProject($projectid);

$project->MayBeProofedByActiveUser()
    or die("Security violation.");

if($pagename != "") {
    $page = new DpPage($projectid, $pagename);
    $page->Exists()
        or die("Page $pagename does not exist.");
    $page->MayBeSelectedByActiveUser()
        or die("Page unavailable to you.");
}
else {
    $page = $project->CheckOutNextAvailablePage();
    if(! $page) {
        redirect_no_page_available($projectid);
    }
}

$is_foofing = ! ( $project->Phase() == "P1"
                || $project->Phase() == "P2"
                || $project->Phase() == "P3"
                );


// --------------------------------------------------------------
// from proof_frame.php
// --------------------------------------------------------------

$langcode   = ArgLangCode($project->LanguageCode());
$zoom       = CookieArg("profile", "zoom", "100%");
$fontface   = CookieArg("profile", "fontface", "Courier");
$fontsize   = CookieArg("profile", "fontsize", "14pt");
$layout     = CookieArg("profile", "layout",  "horizontal");

$prooftext      = h(rtrim(maybe_convert($page->ActiveText())));
$pagename       = $page->PageName();
$tweettext = "";
$action         = url_for_processpage();
$imgurl         = $page->ImageUrl();
$previmgurl     = $page->PrevImageUrl();
$nextimgurl     = $page->NextImageUrl();

if($page->IsBad()) {
    $bad_icon = "gfx/broken.png";
    $bad_state = "isbad";
}
else {
    $bad_icon = "gfx/bad.png";
    $bad_state = "notbad";
}

    $hv_png = ($layout == "vertical")
        ? "gfx/vert.png"
        : "gfx/horiz.png";




// --------------------------------------------------------------
// display page
// --------------------------------------------------------------


// $title = "{$site_abbreviation} [{$project->RoundId()}] {$project->Title()}";
$title = "[{$project->RoundId()}] (w) {$project->Title()}";

$jslink = "<script src='proofpage.js' charset='UTF-8'></script>";
$csslink = "<link rel='stylesheet' href='proofpage.css'>";
// $jslink  = link_to_url("proofpage.js");
// $csslink = link_to_url("proofpage.css"); 

$zoom_in_prompt = _("Zoom image larger");
$zoom_out_prompt = _("Zoom image smaller");
$hv_prompt = ($layout == "vertical")
        ? _("Switch to horizontal layout")
        : _("Switch to vertical layout");
$fr_prompt = _("Find and replace text");
$preview_prompt = _("Preview formatted page");
$wc_prompt = _("Wordcheck");
$return_prompt = _("Return page");
$next_prompt = _("Save text done - request another page");
$save_prompt = _("Save text and quit proofing");
$quit_prompt = _("Quit proofing without saving");
$bad_prompt = _("Request PM to fix bad page.");

echo 
"<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<script>
    var AJAX_URL = '{$ajax_url}';
    var SITE_URL = '{$site_url}';
</script>
$jslink
$csslink
<title>$title</title>


<body id='pfbody' onload='eBodyLoad()' onresize='eResize()'>
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
</div> <!-- divFandR -->
" . upload_widget_iframe($projectid, $pagename) . "

<div id='divleft'>
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
<form accept-charset='UTF-8' name='formedit' id='formedit' method='POST' action='$action'>
    <input type='hidden' name='is_sync' id='is_sync' value='0'>
    <input type='hidden' name='layout' value='$layout'>
    <input type='hidden' name='projectid' value='$projectid'>
    <input type='hidden' name='langcode' value='$langcode'>
    <input type='hidden' name='pagename' value='{$pagename}'>
    <input type='hidden' name='zoom' value='{$zoom}'>
    <input type='hidden' id='todo' name='todo' value=''>
    <input type='hidden' name='acceptwords' value=''>
    <input type='hidden' id='badreason' name='badreason' value=''>
    <input type='hidden' name='splitterpct' value='50'>
  <div id='ctlpanel'>
    <div id='divctlimg'>
        <a id='linksync'>
        <img id='icosync' src='/graphics/brnsync.png' "
. "title='" . _("Sync image and text") . "' alt=''></a>

        <a id='linkzoomin'>
        <img src='/graphics/zoomin.png' title='$zoom_in_prompt' alt='$zoom_in_prompt'>
        </a>

        <a id='linkzoomout'>
        <img src='/graphics/zoomout.png' title='$zoom_out_prompt' alt='$zoom_out_prompt'>
        </a>

        <a id='linklayout'>
        <img src='$hv_png' id='switchlayout' title='$hv_prompt' alt='$hv_prompt'>
        </a>\n";

EchoFontFaceCombo($fontface);
EchoFontSizeCombo($fontsize);

$url_guidelines = $is_foofing 
    ? url_for_formatting_guidelines()
    :  url_for_proofing_guidelines();


$proofers = array();
foreach(array("P1", "P2", "P3", "F1", "F2") as $rnd) {
    if($rnd == $page->Phase()) {
        break;
    }
    $proofer = $page->RoundUser($rnd);
    $proofers[] = $rnd . ": " .link_to_pm($proofer);
}
$page_info =  _("Page: ") . $page->PageName() . " — " . implode(", ", $proofers);

echo "
        <input id='btnFandR' type='button' title='$fr_prompt' value='F&amp;R'>
        <img id='imgpvw' src='/graphics/search.png' alt='$preview_prompt' title='$preview_prompt'></a>
    <!--
        <a id='linkwc'>
        <img src='gfx/wchk-off.png' id='imgwordcheck' title='$wc_prompt' alt='$wc_prompt'>
        </a>
    -->
    </div> <!-- divctlimg -->

    <div id='divctlwc' class='hide'>
        <span id='span_wccount' class='ctlcombo'> 0 </span>
    </div> <!-- divctlwc -->

    <div id='divctlnav'>
        <input type='image' id='returnpage' name='returnpage'
            src='gfx/returnpage.png' alt='$return_prompt'  title='$return_prompt'>
        <input type='image' id='savenext' name='savenext'
            title='$next_prompt' alt='$next_prompt' src='gfx/savenxt.png'>
        <input type='image' id='savequit' name='savequit'
            src='gfx/savequit.png' title='$save_prompt' alt='$save_prompt'>
        <input type='image' id='quit' name='quit' title='$quit_prompt' alt='$quit_prompt'
            src='gfx/quit.png'>
        <input type='image' id='badbutton' name='badbutton'
            title='$bad_prompt' alt='$bad_state' src='$bad_icon'>
    </div> <!-- divctlnav -->
  </div> <!-- ctlpanel -->

  <div id='divfratext'>
    <div id='divtext'>
      <pre id='divpreview' class='dpeditor'>
{$prooftext}</pre>
      <textarea name='tatext' id='tatext' class='dpeditor' wrap='off'>
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
  <div class='lfloat w35'>$page_info</div>
  <div class='rfloat w65'>
        <a class='rfloat quarter' 
            href='".url_for_help()."' target='_blank'>
        " . _("Help")."</a>
        " . ($page->UserMayManage()
                ? " <a id='linkupload' class='rfloat quarter likealink'>
                    ". _("Replace image")."</a>\n"
                : "") 
        . " <a class='rfloat quarter' target='_blank'
            href='"  . url_for_project($page->projectId())  . "'> "
        . _("Project comments") . "</a>
           <a class='rfloat quarter' 
            href='$url_guidelines' target='_blank'>" 
        . _("Guidelines")
        . "</a>
  </div>
</div> <!-- divstatusbar -->

<div id='divcontrols'>
  <form accept-charset='UTF-8' name='formctls' target='_top'>
    <div id='divcharpicker'
                onmouseover='return top.ePickerOver(event)'
                onmouseout='return top.ePickerOut(event)'
                onclick='return top.eCharClick(event)'>
      <div id='selectors'>
      </div>
      <div id='pickers'>
      </div>
    </div> <!-- divcharpicker -->
    <div id='divcharshow'>
      <div id='divchar'></div>
      <div id='divdigraph'></div>
    </div> <!-- divcharshow -->
    <div id='divctls'>
      <div id='ctl_right'>
        <div id='ctl_tags_top' class='clear rfloat proofbutton'>

            <button title='Remove markup'
                onclick='return top.eRemoveMarkup()'>
                <span class='linethru'>&lt;X&gt;</span></button>";

if($is_foofing) {
    echo "
            <button title='italics' class='proofbutton'
                onclick='return top.eSetItalics();'>&lt;i&gt;</button>

            <button title='bold'
                onclick='return top.eSetBold();'>&lt;b&gt;</button>

            <button title='small-caps'
                onclick='return top.eSetSmallCaps()'>
                &lt;sc&gt; </button>

            <button title='gesperrt (spaced)'
                onclick='return top.eSetGesperrt()'>&lt;g&gt;</button>

            <button title='antiqua'
                onclick='return top.eSetAntiqua()'>&lt;f&gt;</button>";
}

echo "
            <button title='guillemets'
                onclick='return top.eSetGuillemets()'> « » </button>

            <button title='reverse guillemets'
                onclick='return top.eSetGuillemetsR()'> » « </button>

            <button title='de quotes'
                onclick='return top.eSetDeQuotes()'> „ “ </button>

            <button title='it quotes'
                onclick='return top.eSetItQuotes()'> “ „ </button>
        </div> <!-- ctl_tags_top -->

        <div id='ctl_tags_middle' class='rfloat clear proofbutton'>\n";

if($is_foofing) {
    echo "
            <button title='nowrap' onclick='return top.eSetNoWrap()'>
                 /* */ </button>

            <button title='blockquote'
                onclick='return top.eSetBlockQuote()'>
                 /# #/ </button>";
}

echo "
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
        </div> <!-- ctl_tags_middle -->
        <div id='ctl_tags_bottom' class='rfloat clear proofbutton'>";

if($is_foofing) {
    echo "
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
                               [Sidenote: ]</button>";
}
echo "
            <button title='note' onclick='return top.eSetNote()'>
                [** ]</button>

            <button title='Blank Page'
                onclick='return top.eSetBlankPage()'>
                                        [Blank Page]</button>";


echo "
        </div> <!-- ctl_tags_bottom -->
      </div> <!-- ctl_right -->
    </div> <!-- divctls -->
  </form>
</div>  <!-- divcontrols -->
</body>
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

