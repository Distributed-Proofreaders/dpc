<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);


/*
$tag = 'en_US';
$r = enchant_broker_init();
$bprovides = enchant_broker_describe($r);
echo "Current broker provides the following backend(s):\n";
print_r($bprovides);

$dicts = enchant_broker_list_dicts($r);
print_r($dicts);
if (enchant_broker_dict_exists($r,$tag)) {
    $d = enchant_broker_request_dict($r, $tag);
    $dprovides = enchant_dict_describe($d);
    echo "dictionary $tag provides:\n";
    $wordcorrect = enchant_dict_check($d, "soong");
    print_r($dprovides);
    if (!$wordcorrect) {
        $suggs = enchant_dict_suggest($d, "soong");
        echo "Suggestions for 'soong':";
        print_r($suggs);
    }
    enchant_broker_free_dict($d);
} else {
}
enchant_broker_free($r);
*/

// global $site_abbreviation, $ajax_url, $site_url;

 $relPath = "../pinc/";

include_once $relPath.'dpinit.php';

include_once $relPath . "DpEnchant.class.php";
include_once($relPath.'smoothread.inc');           // functions for smoothreading

$projectid      = "projectID51ed225bb560d";
$project = new DpProject($projectid);

dump($project->WordContexts("Magloire--"));
exit;
$pagename       = ArgPageName();
$page = new DpPage($projectid, $pagename);
$langcode   = $page->LanguageCode();
dump($langcode);

/** @var DpEnchantedWords $ench */
$ench = $project->EnchantedWords($langcode);
dump($ench->WordLinesArray());
//dump($project->FlagWordsArray($langcode));
//dump($project->SpellWordsArray($langcode));
