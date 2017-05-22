<?php

// Simple Proofreading Rules text, for inclusion in other files only

echo "<font face='$theme[font_mainbody]'><b>";
echo _("10 Simple Proofreading Rules:");
echo "</b></font><br><br>";

        echo "PRIMARY GOAL: Don't change what the author wrote!<br>\n";
        echo "1. put a blank line between paragraphs, don't indent a new paragraph<br>\n";
        echo "2. try to clean up any mess left by the OCR process to match the original scan<br>\n";
        echo "3. don't rewrap lines, leave ends of lines as they are in the image<br>\n";
        echo "4. BUT restore words that are broken across line ends, even removing hyphens<br>\n";
        echo "5. if you're not sure what to do, make it look like the scan and insert [**explanation of error or problem]<br>\n";
        echo "6. remove extra spacing around punctuation inserted by OCR<br>\n";
        echo "7. don't correct original spelling, author's choice of words, blasphemy<br>\n";
        echo "8. don't try to do any formatting, but leave any that you find (unless it confuses you, then remove it)<br>\n";
        echo "9. if the page ends with part of a word or a hyphen, add a *<br>\n";
        echo "10. remove page headers and footers, but NOT footnotes<br>\n";
        echo "REMEMBER: your mission is to improve the page, not try to make it perfect.<br>\n";
  
echo "<br><br>\n\n";


echo "<i>";
echo sprintf(_("The <a href='%s'>Proofreading Guidelines</a> (for reference) provide much more detail after you have tried proofing some pages."), $wiki_url . "/index.php/Proofreading_Guidelines");
echo "</i><br><br>\n";
echo _("BEGINNERS ONLY projects are reserved for new proofreaders. After you have done 5-15 pages in total from these projects, please leave them for proofreaders who are newer than you. EASY projects make a good next step.");
echo "<br><br>\n";




?>
