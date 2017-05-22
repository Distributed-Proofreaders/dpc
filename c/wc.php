<?php
/*
    js constructs the "new Object()".
    Then serializes it with 
        var jq = 'jsonqry=' + JSON.stringinify(obj);
    ajax POSTs it in form variable jsonqry to wc.php (this file)
    php loads POST with 
        $jq = Arg("jsonqry");
    Then unserializes it with 
        $json = json_decode($jq);
    giving a php assoc. array with the first key = "querycode",
        and other keys as required for the specific query.

    encoding:

    Payloads should be encoded to protect php, javascript, json, etc.
        php rawurlencode is equivalent to js encodeURIComponent.
    A php assoc. array is mapped to a javascript object.

    So we should be able to encode everything with encodeURIComponent after serialization
        in js (var jq) and decode it in PHP with rawurldecode before json_decode in php.
    

    $msgtype spellcheck
    accepts: language 
             text to check
    returns: same text marked up with things to check

    $msgtype goodword
    accepts: 
*/

error_reporting(E_ALL);
$relPath = "./pinc/";
require_once $relPath."dpinit.php";
require_once $relPath."DpPage.class.php";
include_once($relPath.'links.php');

//$Log->SetOn();

$jq             = Arg("jsonqry");
$jq             = rawurldecode($jq);
wc_log($jq);
$json           = json_decode($jq);


if(! is_object($json)) {
    $err = "wc: json not-an-object error: {$jq}";
    send_alert($err);
    echo "not an object :: $jq";
    exit;
}


$querycode          = $json->querycode;
$username           = @$json->username;
$projectid          = @$json->projectid;
$pagename           = @$json->pagename;
$lineindex          = @$json->lineindex;
$langcode           = @$json->langcode;
$text               = @$json->text;
$word               = @$json->word;
$repl               = @$json->repl;
$data               = @$json->data;
$acceptwords        = @$json->acceptwords;
$mode               = @$json->mode;
$token              = @$json->token;
$flags              = @$json->flags;
$tweet              = @$json->tweet;


// if a username is provided, reset active User
if($username) {
    $User               = new DpUser($username);
}

//	$Log->logWrite("wc recv: $querycode");

// these queries come from dp_edit.js
switch($querycode) {
    // user explicitly requests temp save
    // send back updated tags

//	case "clearlog":
//		$Log->logClear();
//		exit;

    case "savetemp":
        $page = new DpPage($projectid, $pagename);
        $page->SaveOpenText($text);
         if($acceptwords && count($acceptwords) > 0) {
             $words = preg_split("/\t/", $acceptwords);
             $page->AcceptWordsArray($langcode, $words);
         }

        $a          = [];
         $wct        = $page->WordCheckText($langcode, $text);
         list($wccount, $wcscount, $wcbcount, $pvwtext) = $wct;
        $a["querycode"] = "do" . $querycode;
        $a["token"]     = $token;
        $a["alert"]     = _("Saved.");
        // $a["wccount"]   = $wccount;
        // $a["wcscount"]  = $wcscount;
        // $a["wcbcount"]  = $wcbcount;
        // $a["pvwtext"]   = $pvwtext;
        json_echo($a);
        exit;

    case "savequit":
    case "savenext":
        if (!$User->Username()) {
            send_alert("Couldn't save! You are no longer signed in!\n"
                       . "Please log again using another window and retry saving.");
            exit;
        }
        $a                  = [];
        $a["querycode"]     = "do" . $querycode;
        json_echo($a);
        exit;
        
    // user hits wordcheck button for initial wordcheck,
    // or to resume suspended wordcheck 
    // Word species: 1) spellwords a. on good list b. suggested
    // c. ok here d. bad e. untouched;
    // 2) bad words a. on good list b. suggested; c. ok'ed d. virgin
    // 3) suggested words a. 
    // ?) ?say something here about suspect words?
    case "wctext":
        $page           = new DpPage($projectid, $pagename);
        // wordcheck the text and return marked-up version
        $wct            = $page->WordCheckText($langcode, $text);
        list($wccount, $wcscount, $wcbcount, $pvwtext) = $wct;
        $a              = [];
        $a["querycode"] = "wctext";
        $a["token"]     = $token;
        $a["wccount"]   = $wccount;
        $a["wcscount"]  = $wcscount;
        $a["wcbcount"]  = $wcbcount;
        $a["pvwtext"]   = $pvwtext;
        json_echo($a);
        exit;
/*
     case "wctext2":
         $page           = new DpPage($projectid, $pagename);
         $text           = utf8_uridecode($text);
        // wordcheck the text and return marked-up version
         $wct            = $page->WordCheckText($langcode, $text);
         $wct            = utf8_uriencode($text);
         list($wccount, $wcscount, $wcbcount, $pvwtext) = $wct;
         $a              = [];
         $a["querycode"] = "wctext";
         $a["token"]     = $token;
         $a["wccount"]   = $wccount;
         $a["wcscount"]  = $wcscount;
         $a["wcbcount"]  = $wcbcount;
         $a["pvwtext"]   = $pvwtext;
         json_echo2($a);
         exit;
*/

     case "wccontext":
         $project  = new DpProject($projectid);
         switch($mode) {
             default:
             case "flagged":
                 $awords = $project->FlagWordCountArray($langcode);
                 $ak = array_keys($awords);
                 $av = array_values($awords);
                 array_multisort( $av, SORT_DESC, $ak, SORT_ASC, $awords);
                 break;
//
             case "suggested":
                 $awords = $project->AcceptedWordCountArray($langcode);
                 $ak = array_keys($awords);
                 $av = array_values($awords);
                 array_multisort( $av, SORT_DESC, $ak, SORT_ASC, $awords);
                 break;
//
             case "good":
                 $av = $project->GoodWordCountArray($langcode);
                 break;
//
             case "bad":
                 $av = $project->BadWordCountArray($langcode);
                 break;

         }
         $a                  = [];
         $a["querycode"]     = "wccontext";
         $a["wordarray"]     = $av;
         json_echo($a);
         exit;

     case "wordcontext":
         $project  = new DpProject($projectid);
         $wpc      = $project->WordContexts($word);
		 $nwpc     = count($wpc["contexts"]);
		 $a        = [];

		 if($nwpc > 500) {
			 $a["warning"] = "Too many cases ($nwpc).";
			 $wpc["contexts"] = array_slice($wpc["contexts"], 0, 100);
		 }
		 else {
			 $a["warning"] = "OK";
		 }
//
         $a["querycode"]     = "wordcontext";
         $a["projectid"]     = $projectid;
         $a["word"]          = $word;
         $a["contextinfo"]   = $wpc;
         json_echo($a);
         exit;

/*
    case "hyphenated":
        $project            = new DpProject($projectid);
        $hypwords           = HyphenatedWords($project->ActiveText());
        $a                  = [];
        $a["querycode"]     = "hyphenated";
        $a["projectid"]     = $projectid;
        $a["hypwords"]      = $hypwords;
        json_echo($a);
        exit;

    case "regexcontext":
        $project            = new DpProject($projectid);
        $rc                 = $project->RegexContexts($word, $flags);

        $a                  = [];
        $a["querycode"]     = "regexcontext";
        $a["projectid"]     = $projectid;
        $a["word"]          = $word;
        $a["contextinfo"]   = $rc;
        json_echo($a);
        exit;
*/

    case "addgoodword":
        $project            = new DpProject($projectid);
//        $Log->logWrite("addgoodword $langcode $word");
        $project->AddGoodWord($langcode, $word);

        $a                  = [];
        $a["querycode"]     = $querycode;
        $a["response"]      = "ack";
//        $Log->logWrite("post add " . serialize($a));
        json_echo($a);
        exit;

    case "addbadword":
        $project            = new DpProject($projectid);
        $project->AddBadWord($langcode, $word);

        $a                  = [];
        $a["querycode"]     = $querycode;
        $a["response"]      = "ack";
        json_echo($a);
        exit;

    case "get":
        $page               = new DpPage($projectid, $pagename);
        $a                  = [];
        $a["querycode"]     = "gettweet";
        $a["tweet"]         = $page->Tweet();
        json_echo($a);
        exit;

    case "puttweet":
        $page               = new DpPage($projectid, $pagename);
        $page->SetTweet($tweet);
        $a                  = [];
        $a["querycode"]     = "gettweet";
        $a["response"]      = "ack";
        json_echo($a);
        exit;

    case "doreplace":
        $page               = new DpPage($projectid, $pagename);
        $page->ReplaceLineWord($lineindex, $word, $repl);
        $a                  = [];
        $a["querycode"]     = $querycode;
        $a["response"]      = "ack";
        json_echo($a);
        exit;

    case "doreplaceall":
        $project            = new DpProject($projectid);
        $project->ReplaceWord($word, $repl);
        $a                  = [];
        $a["querycode"]     = $querycode;
        $a["response"]      = "ack";
        json_echo($a);
        exit;

//    case "suggestedtogoodword":
//        $project            = new DpProject($projectid);
//        $project->AddGoodWord($langcode, $word);
//        $a                  = [];
//        $a["querycode"]     = $querycode;
//        $a["response"]      = "ack";
//        json_echo($a);
//        exit;
}

function send_alert($msg) {
    $a          = [];
    $a["querycode"] = "popupalert";
    $a["alert"]     = _($msg);
    json_echo($a);
}

function json_echo2($rsp) {
    echo unampersand(json_encode($rsp));
}

function json_echo($rsp) {
    $rsp = unampersand(json_encode($rsp));
    $rsp = rawurlencode($rsp);
    echo $rsp;
}

function unampersand($str) {
    return preg_replace("/&/", "~~", $str);
}

function reampersand($str) {
    return preg_replace("/~~/", "&", $str);
}

function wc_log($str) {
    global $ajax_log_path;
    file_put_contents($ajax_log_path, TimeStampString() . "  " . $str . "\n", FILE_APPEND);
}
