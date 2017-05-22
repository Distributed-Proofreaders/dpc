<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
include_once $relPath . 'dpinit.php';

$w = new WpPost(367281);

$t = $w->ActiveText();
footnotes($t);
exit;
$lines = preg_split("/[\\n\\r]/u", $t);
// $lines = RegexSplit("[\\n\\r]", "u", $w->ActiveText());
unravel($lines);


exit;

function footnotes($t) {
    $a = multimatch("\[Footnote.*?\][\\r\\n]", "mui", $t);
    $b = multimatch("\[\d+\]", "u", $t);
    dump(count($a));
    dump(count($b));
    for($i = 0; $i < count($a); $i++) {
        dump($a[$i]);
        dump($b[$i]);
    }
}

function unravel($lines) {
    $stack  = array();

    foreach($lines as $line) {
        dump($line);
    }
}

class WpPost {
    private $_id;
    private $_row;
    private $_active_row;

    function __construct($id) {
        global $dpdb;
        $this->_id = $id;
        $dpdb->SetEcho();
        $sql = "
            SELECT * FROM wordpress.wp_posts
            WHERE ID = $id";
        $this->_row = $dpdb->SqlOneRow($sql);

        $sql = "
            SELECT COALESCE(p0.post_content, p.post_content) post_content
            FROM wordpress.wp_posts p
            LEFT JOIN wordpress.wp_posts p0
                ON p.ID = p0.post_parent
            WHERE p.ID = $id
            ORDER BY p0.post_date DESC
            LIMIT 1";
        $this->_active_row = $dpdb->SqlOneRow($sql);
    }

    public function ID() {
        return $this->_id;
    }

    public function OriginalText() {
        return $this->_row["post_content"];
    }

    public function ActiveText() {
        return $this->_active_row["post_content"];
    }
}
