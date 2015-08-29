<?php
$relPath = "../pinc/";
include_once $relPath.'dpinit.php';
include_once $relPath.'dpctls.php';

$User->IsLoggedIn()
    or RedirectToLogin();

$projectid          = ArgProjectId();
$projectid != ""
    or die("missing or invalid project id");
$project            = new DpProject($projectid);

$project->Exists()
    or die("Project $projectid doesn't exist.");
$project->UserMayManage()
     or redirect_to_project($projectid);



$isQuit             = isArg("quit");
$isSaveAndProject   = IsArg("saveAndProject");
$isSaveAndPM        = IsArg("saveAndPM");
$isSave             = IsArg("save");
$langcode           = Arg("pklang");
$isToGood           = IsArg("btnleft");
$isToBad            = IsArg("btnright");
$isToDelete         = IsArg("btndelete");
$sugwords           = ArgArray("pksug");
$flagwords          = ArgArray("pkflag");
$txtgood            = Arg("ta_goodwords");
$txtbad             = Arg("ta_badwords");
$listmode           = Arg("listmode");
$isflagged          = ($listmode == "flagged");
$issuggested        = ($listmode == "suggested" || $listmode == NULL);

if($langcode == "") {
    $langcode = $project->LanguageCode();
}
assert($langcode != "");

($User->IsProjectManager() || $User->IsSiteManager()
                           || $User->IsProjectFacilitator())
    or die('permission denied');

if($isQuit) {
    redirect_to_project_manager();
    exit;
}

if(count($sugwords) > 0) {
    $applwords = $sugwords;
}
else if(count($flagwords) > 0) {
    $applwords = $flagwords;
}

if(isset($applwords)) {
    $applwords = preg_replace("/^w_/usi", "", $applwords);

    if($isToGood) {
        $project->SubmitGoodWordsArray($langcode, $applwords);    
    }

    if($isToBad) {
        $project->SubmitBadWordsArray($langcode, $applwords);    
    }

    if($isToDelete) {
        $project->DeleteSuggestedWordsArray($langcode, $applwords);
    }

    $project->RefreshSuggestedWordsArray($langcode);
}

if ($isSaveAndProject || $isSave ) {
    // dump("save");
    // dump($txtgood);
    $project->WriteGoodWordsList($langcode,      $txtgood);
    $project->WriteBadWordsList($langcode,       $txtbad);
    // $project->WriteSuggestedWordsList($langcode, $suggested_words);
    if ($isSaveAndProject) {
        redirect_to_project($projectid);
    }
    // else if ($isSaveAndPM) {
        // redirect_to_pm();
    // }
}


$asrc = $project->GoodWordsByCountAlpha($langcode);
$agood = array();
foreach($asrc as $wc) {
    // prefix in case it's a number
    $agood["w_{$wc[0]}"] = "{$wc[0]} ($wc[1])";
}

if($isflagged) {
    $asrc = $project->FlagWordsByCountAlpha($langcode);
    $aflag = array();
    foreach($asrc as $wc) {
        $aflag["w_{$wc[0]}"] = "{$wc[0]} ($wc[1])";
    }
}

else {
    $asrc = $project->SuggestedWordsByCountAlpha($langcode);
    $asug = array();
    foreach($asrc as $wc) {
        $asug["w_{$wc[0]}"] = "{$wc[0]} ({$wc[1]})";
    }
}

$asrc = $project->BadWordsByCountAlpha($langcode);
$abad = array();
foreach($asrc as $wc) {
    $abad["w_{$wc[0]}"] = "{$wc[0]} ($wc[1])";
}

// $agood = $project->GoodWordsArray($langcode);
// natcasesort(&$agood);
$goodwords = implode("\n", $agood);
$badwords  = implode("\n", $abad);
$wordpick  = (isset($asug)
            ?  multipicker($asug, "pksug[]", "", "wordlist")
            :  multipicker($aflag, "pkflag[]", "", "wordlist"));

$page_heading = _("Project Word Lists");

//(Note: here a long multiline string is assigned)
$args = array("js_data" =>
" var pklang;

function $(elem) {
    return document.getElementById(elem);
}

function submitform() {
    document.wcform.submit();
}

//((Note: (this is an unused function, but its aim seems nice: do not display
//  the buttons if the list is empty) 
//  the element suggested_words used here does not exist...
//  probably the code refers to what is now one of the three possible 
//  <select/> 'pksug[]' or 'pkflag[]'.
//  (I don't know if .value.length applies and how the '[]' in the name
//  disturbs the test). ))

function eSetTextMovers(e) {
    e = e ? e : window.event;
    document.wcform.btnleft.disabled =
    document.wcform.btnright.disabled = 
        (document.wcform.suggested_words
            .value.length == 0);
}

//(unused, see comment for eSetTextMovers(e))
function eWordsToGood() {
    var t = SelectedText(document.wcform.suggested_words);
    if(t.length > 0) {
        document.wcform.good_words.value += ('\\n' + t);
        DeleteSelection(document.wcform.suggested_words);
    }
}

//(unused, see comment for eSetTextMovers(e))
function eWordsToBad() {
    var t = SelectedText(document.wcform.suggested_words);
    if(t.length > 0) {
        document.wcform.bad_words.value += ('\\n' + t);
        DeleteSelection(document.wcform.suggested_words);
    }
}

function DeleteSelection(ctl) {
    if (document.selection) {
        // Internet Explorer
        var r = document.selection.createRange();
        r.text = '';
        document.selection.empty();
    }
    else {
        // Mozilla
        var itop    = ctl.scrollTop;
        var istart  = ctl.selectionStart;
        ctl.value = ctl.value.substring(0, istart)
            + ctl.value.substring(ctl.selectionEnd);
        ctl.selectionEnd = ctl.selectionStart = istart;
        ctl.scrollTop = itop;
    }
    ctl.focus();
    return false;
}

function SelectedText(txtarea) {
    if(! txtarea)
        return '';
    if(! txtarea.value.length)
        return '';

    if(document.selection) {
        var sel = document.selection;
        if(!sel ||  !sel.createRange)
            return '';
        var ierange = sel.createRange();
        var seltext = ierange.text;
    }
    else if(txtarea.selectionEnd) {
        var seltext 
            = txtarea.value.substring(txtarea.selectionStart,
                                      txtarea.selectionEnd);
    }
    else
        return '';

    if(! seltext || ! seltext.length)
        return '';

    return seltext;
}
");


$args = array("css_file" => "/c/css/dp.css");

$no_stats = 1;

theme($page_heading, "header", $args);

echo "
<div id='div_words_header' class='center'>
    <h2>$page_heading</h2>
        <h3>{$project->Author()}: {$project->Title()}</h3>
</div> <!-- div_words_header -->\n";

echo "
<div class='w95' id='div_words_admin'>
<form name='wcform' method='post' enctype='multipart/form-data' accept-charset='UTF-8'>\n";

echo "<div id='div_words_menu' class='center divwords w100'>"
	. _("Language")
	. LanguagePicker("pklang", $langcode, "", "submitform()",  "code_and_name") . "<br>\n";

	echo link_to_adhoc_words($projectid, 'Ad hoc words (context)') . "<br>\n";
	echo link_to_regex_words($projectid, 'Find/replace (context)');
	//        <li>".link_to_hyphenated_words($projectid, 'Hyphenated words')."</li>

echo "
    </div> <!-- div_words_menu -->\n";

echo "
<div id='div_words' class='clear'>\n";
//<table id='div_words' class='clear'>
//<tr>\n";
//echo "
//<td>
    echo "
    <div>
    <div id='div_good_words' class='lfloat divwords w30'>\n"
        ._("Good words")
        .link_to_good_words($projectid, _(" (context)"), true)."
        <br>
        <textarea name='ta_goodwords' class='wordlist w100'
        >{$goodwords}</textarea>
    </div> <!-- div_good_words -->\n";
//</td>\n";

//echo "
//<td>
	echo "
    <div id='div_to_good' class='vstrip w05'>
        <input name='btnleft' type='submit' value='&lt;'>
        <input name='btndelete' id='btndelete' type='submit' value='X'>
    </div>\n";
//</td>\n";

//<td>
echo "
    <div id='div_suggested_words' class='lfloat divwords w30'>
        <input type='radio' name='listmode' id='suggested'
            value='suggested' onclick='this.form.submit();'
            ".($issuggested ? "checked='checked'" : "")
            ."/> Accepted words "
            .link_to_suggested_words($projectid, _(" (context)"))
            ."<br/>
        <input type='radio' name='listmode' id='flagged'
            ".($isflagged ? "checked='checked'" : "") ."
            value='flagged' onclick='this.form.submit();'/>
            Flagged words"
            .link_to_wordcheck_flags($projectid, _(" (context)"))
            ."<br/>\n"
        . $wordpick
        ."
    </div>   <!-- div_suggested_words -->\n";
//<td>\n";

//<td>
echo "
    <div id='div_to_bad' class='vstrip w05'>
        <input name='btnright' type='submit' value='&gt;'>
    </div>\n";
//<td>\n";

//<td>
echo "
    <div id='div_bad_words' class='lfloat divwords w30'>\n"
        ._("Bad words")
        .link_to_bad_words($projectid, " (context)", true)
        ."<br>
        <textarea name='ta_badwords' class='wordlist w100'
        >{$badwords}</textarea>
	</div>
    </div>   <!-- div_bad_words -->\n";
//</td>
//</tr>
//</table>  <!-- div_words -->\n";

echo "
	</div>
    <div id='div_words_ctl' class='center w100 divwords'>
    <p>"
        .link_to_url("{$code_url}/faq/wordcheck-faq.php",
            _("See the WordCheck FAQ for more information 
            on word lists."), true)
        ."</p>
        <input type='submit' name='saveAndPM' 
            value='". _("Go To PM Page"). "'> 
        <input type='submit' name='saveAndProject' 
            value='". _("Go To Project"). "'>
        <input type='submit' name='save'
            value='". _("Save"). "'>
        <input type='submit' name='reload' 
            value='". _("Refresh Word Lists"). "'>
    </div> <!-- div_words_ctl -->
</form>
</div>\n";

theme("", "footer");
exit;

function code_and_name($code, $name) {
    return "{$code} ($name)";
}


// vim: sw=4 ts=4 expandtab
