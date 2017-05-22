<?php
class Quiz
{
    function Quiz($id, $name, $description, $pages) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->pages = $pages;
        // $this->pass_requirements = $pass_requirements;
        // Currently supported pass_requirements:
        // ['maximum_age'] => time_in_seconds:
        //     Passes recorded longer than time_in_seconds ago are not valid.
    }

    function user_has_passed($username) {
        global $dpdb;
        // foreach ($this->pages as $page) {
            // false would be more logical than string "no",
            // but you can't array_search on a boolean.
           // $pages_required_results[$page] = "no";
        // }
        // $max_age = $this->pass_results['maximum_age'];
        $sql = "
            SELECT COUNT(DISTINCT quiz_page)
            FROM quiz_passes
            WHERE username = '$username'
                AND result = 'pass'\n";
        $n = $dpdb->SqlOneValue($sql);
  
        return $n;
        // foreach($attempts as $attempt) {
            // if($attempt->result != "pass") 
                // continue;
            // if(isset($pages_required_results[$attempt->quiz_page])) {
                // $pages_required_results[$attempt->quiz_page] = $attempt->date;
            // }
            // else {
                //This quiz page isn't relevant to this quiz.
                // continue;
            // }
        // }

        // if (isset($this->pass_requirements['maximum_age'])) {
            // foreach ($pages_required_results as $page => $value) {
                // if($value == "no") {
                    // continue; // The user hasn't passed the quiz anyway.
                // }
                // if ((time() - $value) > $this->pass_requirements['maximum_age']) {
                    // $pages_required_results[$page] = "no";
                // }
            // }
        // }

        // If a user has passed the quiz, the $pages_required_results array should
        // have only pagename => timestamp  rows. If "no" values remain, user has not passed
        // all the pages required, or the result of that page is invalid (e.g. too old).
        // return array_search("no", $pages_required_results) === false) ;
    }

    function show_results_table($username) {
        global $code_url;
        echo "<p>
              <table border='0' cellpadding='2' style='border:2px solid black;'>
              <tr><th class='q' colspan='5'>$this->name</th></tr>
              <tr><td colspan='5'>$this->description</td></tr>
        ";
        $pages = $this->pages;
        foreach ($pages as $desc => $page) {
            echo "<tr><td>$desc</td>
                <td><a href='generic/main.php"
                                ."?type=$page"
                                ."&amp;quiz_id=$this->id'>Attempt this page</a></td>\n";
            if (!empty($username)) {
                $passed = user_has_passed_quiz_page($username, $page);
                $text = $passed ? _("Passed") : _("Not passed");
                $bgcolor = $passed ? '#ccffcc' : '#ffcccc';
                echo "<td bgcolor='$bgcolor'>$text</td>";
                $date = get_last_attempt_date_for_quiz_page($username, $page);
                $text = ($date != 0) ? strftime("%d-%b-%y",$date) : "Not attempted";
                // $max = $this->pass_requirements['maximum_age'];
                $date_ok = true;
                $bgcolor = $date_ok ? '#ccffcc' : '#ffcccc';
                echo "<td bgcolor='$bgcolor'>$text</td>";
                $text = ($passed && $date_ok) 
                    ? "<img src='$code_url/quiz/tick.png' alt='Tick!' 
                            title='Quiz page passed!' width='25' height='24' />\n"
                    : "<img src='$code_url/quiz/cross.png' alt='Cross' 
                            title='Quiz page not passed' width='22' height='25'  />\n";
                $bgcolor = ($passed && $date_ok) ? '#88ff88' : '#ff8888';
                echo "<td bgcolor='$bgcolor'>$text</td>";
            }
            else {
                echo "<td /><td /><td />";
            }
            echo "</tr>";
        }
        if (!empty($username)) {
            $total_pass = $this->user_has_passed($username);
            $text = $total_pass ? _("Quiz passed") : _("Quiz not passed");
            $bgcolor = $total_pass ? '#88ff88' : '#ff8888';
            echo "<tr><td colspan='5'  bgcolor='$bgcolor' 
                style='font-weight: bold;text-align:center;'>$text</td></tr>\n";
        }
        echo "</table></p>";
    }
}

# -----------------------------------------------------------------------------

function record_quiz_attempt($username, $quiz_page, $result) {
    global $dpdb;
    $n = $dpdb->SqlExecute("
            UPDATE quiz_passes SET date = UNIX_TIMESTAMP()
            WHERE username = '$username'
                AND quiz_page = '$quiz_page'");

    if($n == 0) {
        $dpdb->SqlExecute("
            INSERT INTO quiz_passes 
            VALUES
                ('$username', UNIX_TIMESTAMP(), '$quiz_page', '$result')");
    }
}

function user_has_passed_quiz_page($username, $quiz_page) {
    global $dpdb;
    // This function could look up the quizzes to see what $pass_requirements should be,
    // but without that argument, it's possible to tell the user *why* they haven't passed.
    return $dpdb->SqlExists("
                SELECT date FROM quiz_passes
                WHERE username = '$username' 
                    AND quiz_page ='$quiz_page'");
}

function get_last_attempt_date_for_quiz_page($username, $quiz_page) {
    global $dpdb;
    return $dpdb->SqlOneValue("
          SELECT date FROM quiz_passes
          WHERE username = '$username' 
              AND quiz_page ='$quiz_page'");
}

?>
