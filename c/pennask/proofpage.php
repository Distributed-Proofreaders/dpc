<?php

/**
 * Project page: Start Formatting, or Start Proofreading, link to
 * tools/proofers/proof.php.  That file, diverts to us.
 *
 * Action of form directs to processpage.php; processpage either
 * redirects back to us, or back to the project.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

 $relPath = "../pinc/";

include_once $relPath . 'dpinit.php';
include_once $relPath . 'dpctls.php';
include_once $relPath . "DpEnchant.class.php";

global $site_abbreviation, $ajax_url, $site_url;

$User->IsLoggedIn()
	or RedirectToLogin();

$projectid      = ArgProjectid();
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
	$page->CheckOutPage();
}
else {
    $page = $project->CheckOutNextAvailablePage();
    if(! $page) {
        redirect_no_page_available($projectid);
    }
}

$phase = $project->Phase();
$is_foofing = ( $phase == "F1" || $phase == "F2" );


$langcode     = ArgLangCode($project->LanguageCode());
$zoom         = CookieArg("zoom", "100");
$fontface     = CookieArg("fontface", "Courier");
$fontsize     = CookieArg("fontsize", "14pt");
//$editor       = Arg("editor",  CookieArg("editor", $User->Editor()));
$layout       = CookieArg("layout",  "horizontal");
$issync       = CookieArg("issync", "1");

$lineheight   = CookieArg("lineheight", "lh10");
$iswordcheck  = CookieArg("iswordcheck", "1");
$isrunalways  = CookieArg("runalways", "0");
//$ispunc      = CookieArg("ispunc", "1");

$prooftext      = h(rtrim($page->ActiveText())) . "\n";
$pagename       = $page->PageName();
$tweet          = $page->Tweet();
$imgurl         = $page->ImageUrl();
$previmgurl     = $page->PrevImageUrl();
$nextimgurl     = $page->NextImageUrl();
if ($previmgurl != null)
    $setprevimg = "document.getElementById('imgprev').src = '$previmgurl';";
else
    $setprevimg = "";
if ($nextimgurl != null)
    $setnextimg = "document.getElementById('imgnext').src = '$nextimgurl';";
else
    $setnextimg = "";

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


$title              = "{$site_abbreviation}: "
            ."[{$project->RoundId()}] "
            ."{$project->Title()}";

//<script src='www.pgdpcanada.net/c/js/domready.js' charset='UTF-8'></script>
$jslink                 = "
                <script src='proofpage.js?ver=.209' charset='UTF-8'></script>
                \n";
$csslink                = "<link rel='stylesheet' href='proofpage.css'>";

$zoom_in_prompt         = _("Zoom image larger");
$zoom_out_prompt        = _("Zoom image smaller");
$rotate_prompt          = _("Rotate image 90°");
$hv_prompt              = ($layout == "vertical")
							? _("Switch to horizontal layout")
							: _("Switch to vertical layout");
$fr_prompt              = _("Find and replace text");
$preview_prompt         = _("Preview formatted page ALT-p");
$wc_prompt              = _("Wordcheck");
$bad_prompt             = _("Request PM to fix bad page.");
$quit_prompt            = _("Quit proofing without saving");
$save_prompt            = _("Save text and quit proofing");
$next_prompt            = _("Save text done - request another page");
$return_prompt          = _("Return page");
$tweet_prompt           = _("View or edit page message");

$prompt_return_quit     = _("Return Page and Quit");
$prompt_prompt          = _("Click for options...");
$prompt_submit_continue = _("Submit and Continue ALT-c");
$prompt_submit_quit     = _("Submit and Quit");
$prompt_draft_continue  = _("Save Draft and Continue");
$prompt_draft_quit      = _("Save Draft and Quit");
$prompt_mark_bad        = _("Mark Bad Page");
$prompt_test            = _("Test");
$prompt_gear            = _("Select Icons or Menu");


$url_guidelines         = $is_foofing
                            ? url_for_formatting_guidelines()
                            : url_for_proofing_guidelines();

// --------------------------------------------------------------------------------
// --------------------------------------------------------------------------------

$langpicker             = LanguagePicker("pklangcode", $langcode,
							"ctlcombo", "eLangcode(event)", "name_code");

$wcclass = ($iswordcheck ? "block" : "hide");
$wcchecked = ($iswordcheck ? "checked" : "");

$proofers = $page->Proofers();
$page_info =  _("Phase: ") . $phase . ", " . _("Page: ") . $page->PageName() . " &mdash; " . $proofers;


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

<body id='pfbody' onresize='eResize()' onload='loaded()'>

<form accept-charset='UTF-8' name='formedit' id='formedit' method='POST' action='processpage.php'>
<div id='divGear' class='hide'>
<fieldset id='fsSpacing'>
  <legend>Line Spacing</legend>
    <label><input type='radio' name='rdoLineHeight' id='rdolh10' value='lh10'> 1 </label>
    <label><input type='radio' name='rdoLineHeight' id='rdolh15' value='lh15'> 1½ </label>
    <label><input type='radio' name='rdoLineHeight' id='rdolh20' value='lh20'> 2 </label>
</fieldset>
<fieldset id='fsCmd'>
  <legend>Commands</legend>
    <label><input type='radio' name='rdoEditor' id='rdoicons' value='icons'> Icons </label><br>
    <label><input type='radio' name='rdoEditor' id='rdomenu' value='menu'> Menu </label>
</fieldset>
<fieldset id='fsWC'>
  <legend>WordCheck</legend>
    <label><input type='checkbox' name='chkIsWC' id='chkIsWC' {$wcchecked}> Enabled </label><br>
    <label><input type='checkbox' name='chkIsPunc' id='chkIsPunc'> Hilite Punc </label>
    <label><input type='checkbox' name='runAlways' id='runAlways'> Run Always </label>
</fieldset>
<input type='button' name='btnCloseGear' id='btnCloseGear' class='rfloat' value='Close'/>
</div>

<div id='divPreviewErrors' class='previewErrors'>
&#160;
</div>

<div id='divFandR'>
    <table id='tblFandR'>
      <tr>
        <td>Find</td>
        <td class='r'>
          <input list='findlist' name='txtfind' tabindex=1 id='txtfind' size='20'>
          <datalist id='findlist'>
          </datalist>
        </td>
        <td rowspan='2' class='r'>
          <input type='checkbox' name='chki' id='chki' title='ignore case'/>
          ignore case<br>
          <input type='checkbox' name='chkr' id='chkr' title='regex'/>
          regular expression<br>
          <input type='checkbox' name='chkm' id='chkm' title='multiline'/>
          multiline
        </td>
        <td rowspan='2'>
          <input type='button' value='Find' tabindex=3
                    title='Find next' id='btnfind'/>
          <input type='button' value='Repl'
                    title='Replace' id='btnrepl'/>
          <input type='button' value='Repl+Find' tabindex=4
                    title='Repl+Find' id='btnreplnext'/>
          <input type='button' value='Repl All' tabindex=5
                    title='Repl All' id='btnreplall'/>
          <input type='button' value='Close' title='Close find' tabindex=6
                    name='btnclose' id='btnclose'/>
        </td>
      </tr>
      <tr>
        <td>Replace</td>
        <td class='r'>
          <input list='repllist' name='txtrepl' id='txtrepl' tabindex=2 size='20'>
          <datalist id='repllist'>
          </datalist>
        </td>
      </tr>
    </table>
</div> <!-- divFandR -->
<div id='divtweet'>
   <textarea id='tweet' name='tweet'>$tweet</textarea>
</div>   <!-- divtweet -->
<div id='digraphframe' style='display:none'>
    <div id='digraph-content'>
        <span id='digraph-close'>&times;</span>
        <h1 style='text-align: center;'>Digraph Codes</h1>
        <p>Digraphs are ways to type special characters. They are two character
        codes, which describe a character not normally found on your keyboard.
        In the editing pane, you type the two characters, separated by the
        backspace key.  For example, if you type the letter e, backspace, then
        the greater-than symbol, you get an e-circumflex: ê.</p>
        <div style='overflow:auto;'>
            <table id='digraph'>
            </table>
        </div>
    </div>
</div>
\n"

//. upload_widget_iframe($projectid, $pagename) . "

. "<div id='divleft'>
    <div id='divprevimage'>
        <img id='imgprev' src='about:blank' alt=''>
    </div> <!-- divprevimage -->
    <div id='divimage'>
        <img id='imgpage' src='$imgurl' alt=''>
    </div> <!-- divimage -->
    <div id='divnextimage'>
    <img id='imgnext' src='about:blank' alt=''>
    </div> <!-- divnextimage -->
</div> <!-- divleft -->

<script>
function loaded() {
    eInit();
    $setprevimg
    $setnextimg
}
</script>

<div id='divsplitter'> </div>

<div id='divright'>
    <input type='hidden' name='is_sync' id='is_sync' value='0'>
    <input type='hidden' name='projectid' value='$projectid'>
    <input type='hidden' name='langcode' value='$langcode'>
    <input type='hidden' name='pagename' value='{$pagename}'>
    <input type='hidden' name='phase' value='{$phase}'>
    <input type='hidden' name='todo' value=''>
    <input type='hidden' name='acceptwords' value=''>
    <input type='hidden' name='badreason' value=''>

  <div id='divfratext'>
    <div id='divtext'>
      <pre id='prepreview' class='lh10'><span id='spanpreview'>{$prooftext}</span><br></pre>
      <textarea name='tatext' id='tatext' class='lh10' wrap='soft'>
{$prooftext}</textarea>
    </div>
  </div> <!-- divfratext -->
</div> <!-- divright -->
	  <div id='ctlpanel'>
        <a id='hidectls'>
            <img id='imghidectls' src='gfx/a1_down.png' title='"._("Hide controls")."' alt=''>
            <img id='imgshowctls' src='gfx/a1_up.png' title='"._("Show controls")."' alt=''>
		</a>
		<div id='divctlimg'>
			<a id='linksync'>
				<img id='icosync' src='/graphics/brnsync.png' title='" . _("Sync image and text") . "' alt=''></a>
            <a id='linkzoomin'>
                <img src='/graphics/zoomin.png' title='$zoom_in_prompt' alt='$zoom_in_prompt'></a>
			<a id='linkzoomout'>
				<img src='/graphics/zoomout.png' title='$zoom_out_prompt' alt='$zoom_out_prompt'></a>
			<a id='linkrotate'>
				<img src='gfx/rotate.png' title='$rotate_prompt' alt='$rotate_prompt'></a>
			<a id='linklayout'><img src='$hv_png' id='switchlayout' title='$hv_prompt' alt='$hv_prompt'></a>\n";

		EchoFontFaceCombo($fontface);
		EchoFontSizeCombo($fontsize);

		echo "
			<input id='btnFandR' type='button' title='$fr_prompt' value='F&amp;R'>
            <img id='imgtweet' src='/graphics/tweet2.png' alt='$tweet_prompt' title='$tweet_prompt'>\n";
		if($is_foofing) {
			echo "
                <img id='imgpvw' src='/graphics/preview_off.png' alt='$preview_prompt' title='$preview_prompt'>\n
                <span id='span_fmtcount' class='ctlcombo'> 0 </span>
            ";
		}

	echo "</div> <!-- divctlimg -->\n";

//	if(! $is_foofing) {
		echo "
			<div id='divctlwc' class='{$wcclass}'>
				<a id='linkwc'>
					<img src='gfx/wchk-off.png' id='imgwordcheck' title='$wc_prompt' alt='$wc_prompt'>
				</a>
				<span id='span_wccount' class='ctlcombo'> 0 </span>
					$langpicker
			</div> <!-- divctlwc -->\n";
//        }
	echo "
		<div id='divctlnav'>\n";
	echo "
        <img id='imggear' title='$prompt_gear' alt='$prompt_gear' src='gfx/gear.png'>

		<div id='divicons' class='block'>
			<input type='image' id='opt_submit_continue' name='opt_submit_continue'  src='gfx/savenxt.png'      title='$prompt_submit_continue'  alt='$prompt_submit_continue'>
			<input type='image' id='opt_submit_quit'     name='opt_submit_quit'      src='gfx/savequit.png'     title='$prompt_submit_quit'     alt='$prompt_submit_quit'>
			<input type='image' id='opt_draft_continue'  name='opt_draft_continue'   src='gfx/save2.jpg'        title='$prompt_draft_continue'  alt='$prompt_draft_continue'>
			<input type='image' id='opt_draft_quit'      name='opt_draft_quit'       src='gfx/quit.png'         title='$prompt_draft_quit'      alt='$prompt_draft_quit'>
			<input type='image' id='opt_return_quit'     name='opt_return_quit'      src='gfx/returnpage.png'   title='$prompt_return_quit'     alt='$prompt_return_quit'>
			<input type='image' id='opt_mark_bad'        name='opt_mark_bad'         src='$bad_icon'            title='$prompt_mark_bad'        alt='$prompt_mark_bad'>

		</div> <!-- divicons -->\n";

	echo "
		<div id='divmenu' class='hide'>
		<select name='seltodo' id='seltodo'>
			<option value='opt_prompt'>$prompt_prompt</option>
			<option value='opt_submit_continue'>$prompt_submit_continue</option>
			<option value='opt_submit_quit'>$prompt_submit_quit</option>
			<option value='opt_draft_continue'>$prompt_draft_continue</option>
			<option value='opt_draft_quit'>$prompt_draft_quit</option>
			<option value='opt_return_quit'>$prompt_return_quit</option>
			<option value='opt_mark_bad'>$prompt_mark_bad</option>
		</select>
		</div>\n";

	echo "
		</div> <!-- divctlnav -->
    </div> <!-- ctlpanel -->

	<div id='divcontrols' class='block'>
		<div id='divcharpicker'>
		  <div id='selectors'>
		  </div>
		  <div id='pickers'>
		  </div>
		</div> <!-- divcharpicker -->
		<div id='divcharshow'>
		  <div id='divchar'></div>
		  <div id='divdigraph'></div>
		</div> <!-- divcharshow -->
		<div id='divmarkup'>
		  <div id='ctl_right'>
			<div id='ctl_tags_top' class='clear rfloat proofbutton'>
				<button title='Remove markup'
					onclick='return top.eRemoveMarkup()'>
					<span class='linethru'>&lt;X&gt;</span></button>";

		if($is_foofing) {
			echo "
            <button title='italics ALT-i' class='proofbutton'
                onclick='return top.eSetItalics();'>&lt;i&gt;</button>

            <button title='bold ALT-b'
                onclick='return top.eSetBold();'>&lt;b&gt;</button>

            <button title='small-caps ALT-s'
                onclick='return top.eSetSmallCaps()'>
                &lt;sc&gt; </button>

            <button title='underline ALT-n'
                onclick='return top.eSetUnderline()'>
                &lt;u&gt; </button>

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
            <button title='Chapter Header'
                onclick='return top.eSetChapter()'>Chapter</button>

            <button title='left-justified paragraph'
                onclick='return top.eSetNoIndent()'>[**noindent]</button>

            <button title='nowrap ALT-w' onclick='return top.eSetNoWrap()'>
                 /* */ </button>

            <button title='block quote ALT-q'
                onclick='return top.eSetBlockQuote()'>
                 /# #/ </button>";
		}

		echo "
            <button title='uppercase ALT-u'
                onclick='return top.eSetUpperCase()'>
                 ABC </button>

            <button title='title case ALT-t'
                onclick='return top.eSetTitleCase()'> Abc </button>

            <button title='lowercase ALT-l'
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
            <button title='footnote ALT-f'
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
            <button id='dehyphen' name='dehyphen' onclick='return top.eDeHyphen()'
                title='Dehyphenate across line break. Be sure to include trailing space. ALT-j'>
				&gt;-&lt;</button>
            <button title='note' onclick='return top.eSetNote()'>
                [** ]</button>
            <button title='note' onclick='return top.eSpaceNote()'>
                [**space?]</button>

            <button title='Blank Page'
                onclick='return top.eSetBlankPage()'>
                                        [Blank Page]</button>

        </div> <!-- ctl_tags_bottom -->
      </div> <!-- ctl_right -->
    </div> <!-- divmarkup -->
</div>  <!-- divcontrols -->

<div id='divstatusbar'>
	  <div>$page_info</div>    <!-- proofers etc. -->
		<div style='float:right'>
		({$User->Username()})
		<a href='".url_for_help()."' target='_blank'> " . _("Help")."</a>
		</div>
		<div><a target='_blank' href='"  . url_for_project($page->ProjectId())  . "'> " . _("Project comments") . "</a></div>
	   <div><a href='$url_guidelines' target='_blank'>" . _("Guidelines") . "</a></div>
       <div><a id='showdigraphs' class='likealink'>". _("Digraphs")."</a></div>
</div> <!-- divstatusbar -->
  </form>
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

function name_code($code, $name) {
    return "{$name} ($code)";
}

// vim: ts=4 sw=4 expandtab
