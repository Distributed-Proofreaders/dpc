<?PHP
global $relPath;
include_once($relPath.'dpinit.php');

function dpRandomRule () {
	global $code_url;
    global $dpdb;

    $num_rules = $dpdb->SqlOneValue("
        SELECT count(*) AS numrules FROM rules");

    $randid  = rand(0, $num_rules); 

    $rule = $dpdb->SqlOneRow("
        SELECT subject, rule, doc FROM rules 
        WHERE id = $randid");

    return <<<EOT

<p><strong>$rule[subject]</strong></p>

<p>$rule[rule]</p>

<p>See the <a href="$code_url/faq/document.php#$rule[doc]">$rule[subject]</a> section of the <a href="$code_url/faq/document.php">Proofreading Guidelines</a></p>
EOT;
}
?>
