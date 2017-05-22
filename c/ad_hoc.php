<?PHP
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
require $relPath . "dpinit.php";

$projectid = 'p150906002';
$pagename = "029";
$lineindex = 20;
$word = "Govemment";
$repl = "Government";
$page = new DpPage($projectid, $pagename);
print_r($page->ReplaceLineWord($lineindex, $word, $repl));
//$lines = $page->ActiveLines();
//dump($lines[19]);
//dump($lines[20]);



/*
$from = "eagle";
$to = "dkretz";
$subject = "Message Subject";
$message = "A somewhat longer text string.
 <a href='http://www.pgdpcanada.net/projects/p150919002/smooth/menwithoutwomen-a5.pdf'>pdf</a>
 <a href='http://www.pgdpcanada.net/projects/p150919002/smooth/menwithoutwomen.epub'>epub</a>
 <a href='http://www.pgdpcanada.net/projects/p150919002/smooth/menwithoutwomen.html'>html</a>
 <a href='http://www.pgdpcanada.net/projects/p150919002/smooth/menwithoutwomen.txt'>txt</a>
 <a href='http://www.pgdpcanada.net/projects/p150919002/smooth/menwithoutwomen_2016-01-02_15-21-06.mobi'>mobi</a>
With maybe a paragraph break";
$Context->SendForumMessage($from, $to, $message, $subject);
//$r = enchant_broker_init();
//$dicts = enchant_broker_list_dicts($r);
//$alang = array();
//foreach($dicts as $dict) {
//    $alang[] = $dict['lang_tag'];
//}
//enchant_broker_free($r);
//dump($alang);
*/
