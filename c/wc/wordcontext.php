<?php
/*
 * allcount and allpalpha not showing a list
 * what does what that works provide in the array (w, c)?
 * what do these provide? Two versions of ActiveWords
 */
$relPath="../pinc/";
include_once($relPath.'dpinit.php');
include_once $relPath . "dpctls.php";

/** @var $User DpThisUser */
$User->IsLoggedIn()
    or RedirectToLogin();

$projectid  = ArgProjectid()
    or UnauthorizedDeath("no projectid");
$project = new DpProject($projectid);


$project->Exists()
	or die("Project $projectid doesn't exist.");
$project->UserMayManage()
	or redirect_to_project($projectid);

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


switch($mode) {
    default:
		die("unknown mode = $mode");
		break;

    case "flagged":
		// $a[] = array[word, count]
        $awords = $project->FlagWordsByCountAlpha($langcode);
        break;

    case "suggested":
        $awords = $project->AcceptedWordsByCountAlpha($langcode);
        break;

    case "adhoc":
        $awords = $project->AdHocWordCountArray( $langcode, $adhoclist);
        break;

	// Find/Replace
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

    case "allcount":
        $awords = $project->ActiveWordsByCount();
        $tpl = _("Project words by count (%d).");
        break;

    case "allalpha":
		// word => count
        $awords = $project->ActiveWordsAlpha();
        $tpl = _("Alphabetic project words (%d).");
        break;

//    case "hyphenated":
//        $awords = $project->HyphenatedWords();
//        $tpl = _("Words hyphenated across lines and/or pages");
//        break;

}

/*
$js = "
    var AJAX_URL = '{$ajax_url}';
    var SITE_URL = '{$site_url}';
";

$args = array(
        "js_text"       => $js,
        "body_onload"   => "eContextInit()",
        "js_file"       => "/c/js/dp_edit.js",
        "css_file"      => "/c/css/context.css");

$no_stats = 1;
theme("Word Context", "header", $args);
*/
$selected = " selected='selected'";

echo "
<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<link rel='shortcut icon' href='http://www.pgdpcanada.net/c/favicon.ico'>
<title>DPC: Word Context</title>
<script type='text/javascript'>
    var AJAX_URL = '{$ajax_url}';
    var SITE_URL = '{$site_url}';
</script>
<script type='text/javascript' src='/c/js/sorttable.js'></script>
<script type='text/javascript' src='/c/js/wc.js'></script>
<link rel='stylesheet' href='/c/css/context.css'>
</head>
<body onload='eContextInit()' class='wcbody'>
  <form id='formcontext' name='formcontext' method='POST'>
    <input type='hidden' name='projectid'  id='projectid' value='{$projectid}'>
    <input type='hidden' name='activeword' id='activeword' value=''>
    <div id='left-column'>
      <div id='command-section'>
        <div id='command-1'>"
         .link_to_project_words($projectid, _("Return to Word List Manager"))."<br>
          {$project->Author()} : {$project->Title()}<br>
        </div> <!-- command-1 -->\n";
	if($mode != "regex") {
          echo "
        <div id='command-2'>
        ".LanguagePicker("langcode", $langcode, "",  "document.formcontext.submit()")."
        </div>   <!-- command-2 -->\n";
    }
	// mode selector
	// this is too indirect and confusing. WordlistPicker is located in dpctls.php.
	// It has onchange event to submit formcontext after setting select to next mode
    echo "
        <div id='command-3'>
        ".WordlistPicker("mode", $mode, "", "document.formcontext.submit()")."
        </div>   <!-- command-3 -->\n";

/*
    Disposition buttons - good, bad, remove, refresh,
            replace     (replacement)
*/
	if ($project->UserMayManage()) {
		echo "
		<div id='buttonbox' class='block'>
		  <input type='button' name='btngood'  id='btngood'  value='Good'>
		  <input type='button' name='btnbad'   id='btnbad'   value='Bad'>
		  <input type='button' name='btnremove' id='btnremove'  value='Remove'>
		  <input type='button' name='btnrefresh' id='btnrefresh'  value='Refresh'>
	      <input type='button' name='btnreplace'  id='btnreplace'  value='Replace'>
		</div>   <!-- buttonbox -->
        <div id='replacebox' class='none'>
		  Replace <input type='text'   name='txtreplace'  id='txtreplace'  size='20'>
		  <br>
		  With  <input type='text'   name='txtwith'  id='txtwith'  size='20'>
		  <br>
		  <input type='button' name='doreplace'   id='doreplace'   value='Replace'>
		  <input type='button' name='donext' id='donext'  value='Skip/Next'>
		  <input type='button' name='doreplnext' id='doreplnext'  value='Repl/Next'>
		  <input type='button' name='donereplace' id='donereplace'  value='Done Repl'>
		  <br>
		  <input type='button' name='doreplaceall'   id='doreplaceall'   value='Replace All'>
		</div>   <!-- buttonbox -->\n";
	}

echo
  "
      </div>   <!-- command-section -->\n";
  if($mode == "adhoc") {
    echo "
      <div id='adhoc'>
        <div id='adhocctl'>
            "._("Enter words")."
            <input type='submit' name='btnadhoc' 
                id='btnadhoc' value='"._("Submit")."'>
        </div>  <!-- adhocctl -->
        <textarea name='adhoclist' id='adhoclist'>"
        .$adhoclist."</textarea>
      </div>   <!-- adhoc -->\n";
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
            ".($isignorecase ? " checked=checked'" : "").">
        ignore case <br>
            <input type='button' name='btnadhocfind'
                id='btnadhocnd' value='"._("Find")."'>
      </div> <!-- findreplace -->\n";
  }

  $asize = min(count($awords), 100);
  echo "
      <div id='divtblcontext'>
        <select name='tblcontext' id='tblcontext' size='$asize'>\n";
  if($mode == "regex") {
	  if ( $txtfind != "" ) {
		  $flags = ( $isignorecase ? "ui" : "u" );
		  $c     = RegexCount( $txtfind, $flags, $project->ActiveText() );
		  $c     = ( $c > 0 ? " ($c)" : "" );
		  echo "
			<option id=\"w_regex\" value=\"{$txtfind}\"
								$selected>$txtfind ($c)</option>\n";
	  }
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
      </div>  <!-- divtblcontext -->
    </div>    <!-- left-column -->\n";


echo "
    <div id='divright'>
      <div id='div_context_image'>
        <img id='imgcontext' src='' alt=''>
      </div> <!--  div_context_image   -->
      <div id='div_context_list'>
      </div> <!--  div_context_list   -->
    </div> <!--  divright   -->
    </form>
</body>
</html>";

// vim: sw=4 ts=4 expandtab
