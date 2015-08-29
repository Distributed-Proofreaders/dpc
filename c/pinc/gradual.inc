<?PHP
global $relPath;
//include_once($relPath.'page_tally.inc');
include_once($relPath.'theme.inc');

// This file deals with the gradual revelation of site features,
// based on the number of pages proofed by the user.
// (So far, it only has the code that is shared between multiple files.
// Maybe it should include unshared stuff too, for completeness.)


// Retrieve the number of pages proofed by the current user.
// For demo purposes, allow the user (via the URL parameter 'numofpages')
// to pretend to have a different number of pages,
// as long as it's less than their actual number.
function get_pages_proofed_maybe_simulated() {
    global $User;
    return $User->PageCount();
//    global $pguser;
//
//    $pagesproofed = user_get_ELR_page_tally( $pguser );
//
//    return $pagesproofed;
}

function welcome_see_beginner_forum( $pagesproofed ) {
    global $theme, $beginners_site_forum_url;

    if ($pagesproofed <= 100) {
        echo "<hr width='75%'>\n";

        echo '<font face="'. $theme['font_mainbody'] .'" color = "blue" size=+3><br><b>';
        echo _("Welcome");
        echo "</b></font><br><br>";
        echo _("Please see our");
        echo " <a href='$beginners_site_forum_url'>";
        echo _("Beginner's Forum");
        echo "</a> ";
        echo _("for answers to common questions.");
        echo "<br><br>\n";
    }
}

//MS
function welcome_see_beginner_forum_newuser( $pagesproofed ) {
    global $theme, $beginners_site_forum_url;

    if ($pagesproofed <= 100) {  
		echo "<hr width='75%'>\n";
        echo '<font face="'. $theme['font_mainbody'] .'" color = "blue" size=+3><br><b>';
        echo _("Welcome!  To start read the 10 steps and pick a BEGIN project at the page bottom");
        echo "</b></font><br><br>";
        echo _("If you have more questions, you can visit the ");
        echo " <a href='$beginners_site_forum_url'>";
        echo _("Beginner's Forum");
        echo "</a> ";
        echo _("anytime for answers to common questions.  Jump in and try one page out from the BEGINNERS projects in the table below, they are very easy.");
        echo "<br><br>\n";
        echo "<br><br>\n";
    }
}

/*
function thoughts_re_mentor_feedback( $pagesproofed ) {
    global $forums_url;

    if ($pagesproofed >= 15 && $pagesproofed < 200)
    {
        echo "<hr width='75%'>\n";

        echo _("New Proofreaders:");
        echo " <a href='$forums_url/viewtopic.php?t=388'>";
        echo _("What did you think of the Mentor feedback you received?");
        echo "</a><br><br>";
    }
}
*/

// vim: sw=4 ts=4 expandtab
