<?PHP
global $relPath;

class Page
{
    private $_page;
    private $_projectid;
    private $_image;
    private $_project_state;
    private $_page_state;

    function __construct( $projectid, $image ) {
        global $dpdb;

        $this->_projectid = $projectid;
        $this->_image = $image;

        $_page = $dpdb->SqlOneObject("
            SELECT pg.projectid,
                   pg.image,
                   pg.fileid as pagename,
                   pg.state as pagestate,
                   pg.master_text,
                   pg.round1_time,
                   pg.round1_user,
                   pg.round1_text,
                   pg.round2_time,
                   pg.round2_user,
                   pg.round2_text,
                   pg.round3_time,
                   pg.round3_user,
                   pg.round3_text,
                   pg.round4_time,
                   pg.round4_user,
                   pg.round4_text,
                   pg.round5_time,
                   pg.round5_user,
                   pg.round5_text,
                   pg.b_user,
                   p.state as projstate
            FROM {$this->_projectid}
            WHERE image = '$image'");
    }

    public function ProjectId() {
        return $this->_projectid;
    }

    public function Image() {
        return $this->_image;
    }

    public function PageState() {
        return $this->_page_state;
    }

    public function ProjectState() {
        return $this->_project_state;
    }
}

// vim: sw=4 ts=4 expandtab
