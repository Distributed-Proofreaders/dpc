<?php
$relPath="../pinc/";
include_once($relPath.'dpinit.php');
include_once $relPath . "dpctls.php";

/** @var $User DpThisUser */
$User->IsLoggedIn()
    or RedirectToLogin();

$projectid  = ArgProjectId()
    or UnauthorizedDeath("no projectid");
$project = new DpProject($projectid);

$langcode = ArgLangCode()
    or $langcode = $project->LanguageCode();

                // regex, flagged, adhoc, suggested, good, bad
$mode           = Arg("mode"); 
$adhoclist      = Arg("adhoclist");
$txtfind        = Arg("txtfind");
$txtrepl        = Arg("txtrepl");
$isregex        = IsArg("chkregex");
$isignorecase   = IsArg("chkignorecase");
$btnadhoc       = IsArg("btnadhoc");
$btnfind        = IsArg("btnfind");

switch($mode) {
    default:
    case "flagged":
        $awords = $project->FlagWordsByCountAlpha($langcode);
        break;

    case "suggested":
        $awords = $project->AcceptedWordsByCountAlpha($langcode);
        break;

    case "adhoc":
        $awords = $project->AdHocWordCountArray( $langcode, $adhoclist);
        break;

    case "regex":
        $awords = $project->RegexMatchArray( $txtfind, $isignorecase);
        break;
    case "good":
        $awords = $project->GoodWordsByCountAlpha($langcode);
        break;

    case "bad":
        $awords = $project->BadWordsByCountAlpha($langcode);
        $tpl = _("Bad Words (%d) will be flagged.");
        break;
}

// $js = "
    // var AJAX_URL = '{$ajax_url}';
    // var SITE_URL = '{$site_url}';
// ";

// $args = array(
        // "js_text"       => $js,
        // "body_onload"   => "eContextInit()",
        // "js_file"       => "/c/js/dp_edit.js",
        // "css_file"      => "/c/css/context.css");

// $no_stats = 1;
// theme("Word Context", "header", $args);
$selected = " selected='selected'";

echo "
<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<link rel='shortcut icon' href='http://www.pgdpcanada.net/c/favicon.ico'>
<title>DPC: Word Context</title>
<script type='text/javascript'>
    var AJAX_URL = 'http://www.pgdpcanada.net/c/wc.php';
    var SITE_URL = 'http://www.pgdpcanada.net';
</script>
<script type='text/javascript' src='/c/js/sorttable.js'></script>
<script type='text/javascript' src='/c/js/wc.js'></script>
<link rel='stylesheet' href='/c/css/context.css'>
</head>
<body onload='eContextInit()'>
<div id='container'>
<form id='formcontext' name='formcontext' method='POST'>
<input type='hidden' name='projectid'  id='projectid' value='{$projectid}'>
<input type='hidden' name='activeword' id='activeword' value=''>
<div id='left-column'>
  <div id='command-section'>
    <div>"
     .link_to_project_words($projectid, _("Return to Word List Manager"))."<br>
      {$project->Author()} <br> {$project->Title()}<br/>
    </div>\n";
    if($mode != "regex") {
    echo "
    <div>
      ".LanguagePicker("langcode", $langcode, "",  "document.formcontext.submit()")."
    </div>\n";
    }
    echo "
    <div>
      ".WordlistPicker("mode", $mode, "", "document.formcontext.submit()")."
    </div>";

	if ($project->UserMayManage()) {
		echo "
		<div id='buttonbox'>
		  <input type='button' name='btngood'  id='btngood  value='Good'>
		  <input type='button' name='btnbad'   id='btnbad'   value='Bad'>
		  <input type='button' name='btnremove' id='btnremove'  value='Remove'>
		  <input type='submit' name='btnrefresh' id='btnrefresh'  value='Refresh'>

	<!--      <input type='button' name='btnreplace'  //calling bogus eReplaceWordClick()
										id='btnreplace'  value='Replace'> -->
		</div>\n";
	}

echo
  "
  </div>   <!-- command-section -->
  <div>\n";
  if($mode == "adhoc") {
    echo "
    <div id='adhoc'>
        <div id='adhocctl'>
            "._("Enter words")."
            <input type='submit' name='btnadhoc' 
                id='btnadhoc' value='"._("Submit")."'>
        </div>
        <textarea name='adhoclist' id='adhoclist'>"
        .$adhoclist."</textarea>
    </div>\n";
  }
  if($mode == "regex") {
    echo "
    <br>
    <div id='findreplace' class='center'>
            "._("Find:")."
        <input type='text' name='txtfind' id='txtfind'
                                    value = '$txtfind'>
        <br>
        "._("Replacement: ")
        ."<input type='text' name='txtrepl' id='txtrepl'
                                            value='$txtrepl'>
        <br>
        <input type='checkbox' name='chkregex' id='chkregex'
            ".($isregex ? " checked='checked'" : "").">
        regex
        <input type='checkbox' name='chkignorecase' id='chkignorecase'
            ".($isignorecase ? " checked='checked'" : "").">
        ignore case <br>
            <input type='submit' name='btnfind' 
                id='btnfind' value='"._("Find")."'>
    </div> <!-- findreplace -->\n";
  }

  echo "
  <select name='tblcontext' id='tblcontext'
                      size='30'
                      onchange='eTblContextChange(event)'>\n";
  if($mode == "regex") {
    $flags = ($isignorecase ? "ui" : "u");
    $c = RegexCount($txtfind, $flags, $project->ActiveText());
    echo "
    <option id=\"w_regex\" value=\"{$txtfind}\"
                            $selected>$txtfind ($c)</option>\n";
  }
  else {
      foreach($awords as $aword) {
          $w = $aword[0];
          if($w != "") {
              $c = $aword[1];
              echo "
            <option id=\"w_{$w}\" value=\"w_{$w}\"
                $selected>$w ($c)"
                . (isset($aword[2]) ? (" {" . $aword[2] . "}") : "")
                . "</option>\n";
              $selected = "";
          }
      }
  }
  echo "
  </select>
  </div>
</div>    <!-- left-column -->
<div id='div_context_box'>
  <div id='div_context_list'>
  </div> <!--  div_context_list   -->
  <div id='div_context_image'>
    <img id='imgcontext' src='' class='w75' alt='' onload='eContextImgLoad()'>
  </div> <!--  div_context_image   -->
</div> <!--  div_context_box   -->
</form>
</div>
</body>
</html>";

// vim: sw=4 ts=4 expandtab
?>
