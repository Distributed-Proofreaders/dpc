<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

global $site_abbreviation, $ajax_url, $site_url;

 $relPath = "../pinc/";

include_once $relPath.'dpinit.php';
include_once $relPath . "DpEnchant.class.php";

if(! $User->IsLoggedIn()) {
    RedirectToLogin();
    exit;
}

$kretz = ($User->Username() == "dkretz");

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

$is_foofing = ( $project->Phase() == "F1" || $project->Phase() == "F2" );


// --------------------------------------------------------------
// from proof_frame.php
// --------------------------------------------------------------

$langcode   = ArgLangCode($project->LanguageCode());
$zoom       = CookieArg("zoom", "100");
$fontface   = CookieArg("fontface", "Courier");
$fontsize   = CookieArg("fontsize", "14pt");
$editor     = CookieArg("editor",  "whistler");
$layout     = CookieArg("layout",  "horizontal");
$issync     = CookieArg("issync", "1");
$lineheight = CookieArg("lineheight", "1");

if($lineheight = Arg("lineheight")) {
    $lineheight = (int) $lineheight;
    if($lineheight >= 1 && $lineheight <= 2) {
        SetCookieArg("lineheight", $lineheight);
    }
}

$prooftext      = h(rtrim(maybe_convert($page->ActiveText())));
$pagename       = $page->PageName();
$tweettext = "";
$action         = url_for_proof_processpage();
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


$title = "{$site_abbreviation}: "
            ."[{$project->RoundId()}] "
            ."{$project->Title()}";

$jslink = "<script src='proofpage.js?2' charset='UTF-8'></script>";
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

$prompt_return_quit     = _("Return Page and Quit");
$prompt_prompt          = _("Click for options...");
$prompt_submit_continue = _("Submit and Continue");
$prompt_submit_quit     = _("Submit and Quit");
$prompt_draft_continue  = _("Save Draft and Continue");
$prompt_draft_quit      = _("Save Draft and Quit");
$prompt_mark_bad        = _("Mark Bad Page");
$prompt_test            = _("Test");


$url_guidelines = $is_foofing
    ? url_for_formatting_guidelines()
    : url_for_proofing_guidelines();

// --------------------------------------------------------------------------------
// --------------------------------------------------------------------------------

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

<body id='pfbody' onresize='eResize()' onload='eInit()'>
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
    <input type='hidden' name='projectid' value='$projectid'>
    <input type='hidden' name='langcode' value='$langcode'>
    <input type='hidden' name='pagename' value='{$pagename}'>
    <input type='hidden' name='todo' value=''>
    <input type='hidden' name='acceptwords' value=''>
    <input type='hidden' name='badreason' value=''>
  <div id='ctlpanel'>
     <a id='hidectls'>
    <img id='imghidectls' src='gfx/a1_down.png' title='"._("Hide controls")."' alt=''>
    <img id='imgshowctls' src='gfx/a1_up.png' title='"._("Show controls")."' alt=''>
    </a>
    <div id='divctlimg'>
        <a id='linksync'>
        <img id='icosync' src='/graphics/brnsync.png' title='" . _("Sync image and text") . "' alt=''></a>

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



$proofers = array();
foreach(array("P1", "P2", "P3", "F1", "F2") as $rnd) {
    if($rnd == $page->Phase()) {
        break;
    }
    $proofer = $page->RoundUser($rnd);
    $proofers[] = $rnd . ": " .link_to_pm($proofer);
}
$page_info =  _("Page: ") . $page->PageName() . " &mdash; " . implode(", ", $proofers);

echo "
    <img id='imgpvw' src='/graphics/search.png' alt='$preview_prompt' title='$preview_prompt'></a>

    </div> <!-- divctlimg -->

    <div id='divctlwc' class='wc hide'>
        <a id='linkwc'>
        <img src='gfx/wchk-off.png' id='imgwordcheck' title='$wc_prompt' alt='$wc_prompt'>
        </a>

        <span id='span_wccount' class='ctlcombo'> 0 </span>
    </div> <!-- divctlwc -->

    <div id='divctlnav'>
    <select name='seltodo' id='seltodo'>
        <option value='opt_prompt'>$prompt_prompt</option>
        <option value='opt_submit_continue'>$prompt_submit_continue</option>
        <option value='opt_submit_quit'>$prompt_submit_quit</option>
        <option value='opt_draft_continue'>$prompt_draft_continue</option>
        <option value='opt_draft_quit'>$prompt_draft_quit</option>
        <option value='opt_return_quit'>$prompt_return_quit</option>
        <option value='opt_mark_bad'>$prompt_mark_bad</option>\n";
if($kretz) {
    echo "<option value='opt_test'>$prompt_test</option>'\n";
}
echo "
    </select>
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
  <div class='lfloat'>$page_info</div>
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

class DpCookie
{
    private $v_zoom;
    private $v_fontface;
    private $v_fontsize;
    private $v_barpct;

    private $h_zoom;
    private $h_fontface;
    private $h_fontsize;
    private $h_barpct;

    private $_layout;   // h or v
    private $_editor;   // whistler, std, enhanced
    private $_lineheight;
    private $_issync;

    function __construct($zoom = "100", $fontface = "Courier", $fontsize = "14pt", $editor = "whistler") {
    }

    function Layout() {
        return $this->_layout;
    }

    function Zoom() {
        return $this->Layout() == "horizontal"
            ? $this->h_zoom
            : $this->v_zoom;
    }
    function BarPct() {
        return $this->Layout() == "horizontal"
            ? $this->h_barpct
            : $this->v_barpct;
    }
    function Editor() {
        return $this->_editor;
    }
    function IsSync() {
        return $this->_issync;
    }
    function FontFace() {
        return $this->Layout() == "horizontal"
            ? $this->h_fontface
            : $this->v_fontface;
    }
    function FontSize() {
        return $this->Layout() == "horizontal"
            ? $this->h_fontsize
            : $this->v_fontsize;
    }
    function LineHeight() {
        return $this->Layout() == "horizontal"
            ? $this->_lineheight
            : $this->_lineheight;
    }
}
