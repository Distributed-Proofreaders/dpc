<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
$relPath = "./pinc/";

include_once $relPath . "dpinit.php";
include_once $relPath . "showstartexts.inc";
include_once($relPath.'site_news.inc');


$limit = 10;

theme(_("Welcome"), "header");

// Show the number of users that have been active over various recent timescales.

$sql = "SELECT
    SUM(FROM_UNIXTIME(t_last_activity) > DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)) users_day,
    SUM(FROM_UNIXTIME(t_last_activity) > DATE_SUB(CURRENT_DATE(), INTERVAL 1 WEEK)) users_week,
    SUM(FROM_UNIXTIME(t_last_activity) > DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) users_month
    FROM users";

$row = $dpdb->SqlOneRow($sql);
$msg = _("{$row['users_day']} active in the past 24 hours.<br>
		  {$row['users_week']} active in the past week.<br>
		  {$row['users_month']} active in the past month.");

echo "<p class='center italic'>$msg</p>\n";

echo "<div id='home_main'>
	<h3 class='red'>" . _('About This Site') . "</h3>";

echo _("<p>Distributed Proofreaders Canada (DPC) was founded in 2007
by Michael Shepard and David Jones to support the digitization of Public Domain
books. Our inspiration was <a href='www.pgdp.net'>Distributed Proofreaders
International (DP)</a>, which was originally conceived to prepare texts for
Project Gutenberg (PG). Just as DP is now the main
source of PG e-books, we expect DPC to become the main source of e-books for <a
href='http://www.gutenberg.ca'>Project Gutenberg Canada (PGC)</a> that started
on July 1, 2007. All our proofreaders, managers, developers and so on are
volunteers. The main principles of our mission are to: (1) preserve Canadiana,
one page at a time, (2) take advantage of the favourable copyright laws in
Canada to make books published after 1923, written by authors who died at least
50 years ago, more available to the public.</p>

<p>DPC software is licensed and available under GNU GPL v2. Source code can be found 
<a href='https://code.google.com/p/dpcanada'>here</a>.\n");

 show_news_for_page("FRONT");

echo "<h3 class='red'>". _("Site Concept") ."</h3>\n";

echo _("<p>This site provides a web-based method of easing the proofreading work
associated with the digitization of Public Domain books into Project Gutenberg
Canada e-books. By breaking the work into individual pages many proofreaders
can be working on the same book at the same time. This significantly speeds up
the proofreading/e-book creation process.</p>");

echo _("<p>When a proofreader elects to proofread a page of a particular
book, the text and image file are displayed on a single web page. This allows
the page text to be easily reviewed and compared to the image file, thus
assisting the proofreading of the page text. The edited text is then submitted
back to the site via the same web page that it was edited on. A second
proofreader is then presented with the work of the first proofreader and the
page image. Once they have verified the work of the first proofreader and
corrected any additional errors the page text is again submitted back to the
site. The book then progresses through two formatting rounds using the same web
interface.</p>");

echo _("<p>Once all pages for a particular book have been processed, a
post-processor joins the pieces, properly formats them into a Project Gutenberg
Canada e-book, optionally makes it available to interested parties for 'smooth
reading', and submits it to the PGC archive.</p>\n");

echo "<h3 class='red'>". _("How You Can Help") ."</h3>\n";

echo sprintf(_("<p>The first step to take to help us out would be to <a
href='$registration_url'>register</a> to be a new proofreader.
($registration_url also appears at the top of the screen.)  After you register
be sure to read over both the email you receive as well as  <a
href='%s/faq/faq_central.php'>FAQ Central</a> which provides helpful resources
on how to proofread.  (See also the 'Help' at the top of any screen.)  After
you have registered &amp; read through some of the intro documents, choose an
interesting-looking book from our Current Projects and try proofreading a page
or two.</p>\n"), $wiki_url);

echo _("<p>You don't even have to register to have a look at the <a
href='tools/post_proofers/smooth_reading.php'>Smooth Reading Pool Preview</a>,
though you do to upload corrections. Follow the link for more information.</p>");

echo _("<p>Remember that there is no commitment expected on this site. Proofread
as often or as seldom as you like, and as many or as few pages as you like.  We
encourage people to do 'a page a day', but it's entirely up to you! We hope you
will join us in our mission of 'preserving the literary history of the world in
a freely available form for everyone to use'.</p>");

echo "<hr class='w100 margined' style='margin: 1em auto'>\n";

echo "<h3 class='red'>" . _("Recent Projects") ."</h3>

<div>\n";
//Gold E-texts
showstartexts($limit, 'Gold');
echo "
</div>
<hr class='w100 margined' style='margin: 1em auto'>
<div>\n";
//Silver E-texts
showstartexts($limit, 'Silver');
echo "
</div>
<hr class='w100 margined' style='margin: 1em auto'>
<div>\n";
//Bronze E-texts
showstartexts($limit, 'Bronze');
echo "</div>  <!-- home_main -->
</div>\n";
theme("", "footer");
