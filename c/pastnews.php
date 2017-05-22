<?php
$relPath="./pinc/";
include_once($relPath.'dpinit.php');
include_once($relPath.'site_news.inc');

$news_page_id   = Arg("news_page_id");
$num            = Arg("num");
if($news_page_id == "") {
    echo _("No news_page_id specified, exiting.");
    exit();
}

// Very basic display of the 'recent' news stories for the given news page
//
// Sorts the news by their id's and then prints one by one.

$news_page_id = $_GET['news_page_id'];
if ( isset($NEWS_PAGES[$news_page_id]) ) {
    $news_subject = get_news_subject($news_page_id);
    theme("Recent Site News Items for ".$news_subject, "header");
    echo "<br>";
}
else {
   echo 
   _("Error").": <b>".$news_page_id."</b> "._("Unknown news_page_id specified, exiting.");
   exit();
}


// echo "<center>Feeds: <a href='$code_url/feeds/backend.php?content=news'><img src='$code_url/graphics/xml.gif'></a>";
// echo "<a href='$code_url/feeds/backend.php?content=news&type=rss'><img src='$code_url/graphics/rss.gif'></a>";

if ($num ) {
    $limit = " LIMIT $num";
    echo " <a href='pastnews.php?news_page_id=$news_page_id'>
        Show All $news_subject News</a>\n";
}
else
    $limit = "";

echo "</center>";

$rows = $dpdb->SqlRows("
    SELECT * FROM news_items 
    WHERE news_page_id = '$news_page_id' 
        AND status = 'recent'
    ORDER BY id DESC
    $limit");

$total = 1;

if (count($rows) < 1) {
  echo "<br><br>"._("No recent news items for ").$news_subject;
}
else {
    foreach($rows as $news_item) {
        $date_posted = strftime(_("%A, %B %e, %Y"),$news_item['date_posted']);
        echo "
        <br><a name='".$news_item['id']."'>$date_posted<br>"
        .$news_item['content']."<br><hr align='center' width='75%'><br>";
    }
}



theme("", "footer");

?>
