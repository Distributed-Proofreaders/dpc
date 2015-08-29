<?PHP
ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
include_once $relPath . 'dpinit.php';

$w = new WpPost(367281);

$lines = RegexSplit("[\\n\\r]", "u", $w->ActiveText());
dump(count($lines));
exit;

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

        $this->_active_row = $dpdb->SqlOneRow("
            SELECT COALESCE(p0.post_content, p.post_content) post_content,
            *
            FROM wordpress.wp_posts p
            LEFT JOIN wordpress.wp_posts p0
                ON p.ID = p0.post_parent
            WHERE p.ID = $id
            ORDER BY p0.post_date DESC
            LIMIT 1");
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
