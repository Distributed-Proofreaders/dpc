<?PHP
global $relPath;
include_once($relPath.'DpDbi.class.php');
include_once($relPath.'DpPhpbb3.class.php');
include_once($relPath.'user_is.inc');
include_once($relPath.'project_states.inc');
// include_once($relPath.'topic.inc'); // topic_create
include_once($relPath.'helpers.php'); // topic_create

class Project
{
    private $row;
    public $projectid;
    public $state;
    public $phase;
    public $nameofwork;
    public $authorsname;
    public $username;
    public $difficulty;
    public $image_source;
    public $image_source_name;
    public $url;
    public $dir;
    public $can_be_managed_by_current_user;
    public $names_can_be_seen_by_current_user;
    public $PPer_is_current_user;
    public $PPVer_is_current_user;
    public $postproofer;
    public $ppverifier;
    // public $xheckedoutby;
    public $image_preparer;
    public $text_preparer;
    public $extra_credits;
    public $scannercredit;
    public $topic_id;
    public $_state_counts;
    public $_page_count = 0;

    function __construct( $arg ) {
        global $pguser;
        global $dpdb;

        if ( is_string($arg) ) {
            // $arg is the projectid.
            $res = mysql_query("
                SELECT p.*
                FROM projects p
                WHERE projectid = '$arg'") or die(mysql_error());
            if (mysql_num_rows($res) == 0) {
                die("no project with projectid='$arg'");
            }
            $this->row = mysql_fetch_assoc($res);
        }
        elseif ( is_array($arg) ) {
            // $arg is assumed to be an associative array, such
            // as would be returned by mysql_fetch_assoc().
            $this->row = $arg;
        }
        else {
            $arg_type = gettype($arg);
            die( "Project::Project(): 'arg' has unexpected type $arg_type" );
        }

        // expose fields as named variables
        foreach ( $this->row as $key => $value ) {
            $this->$key = $value;
        }
        $this->phase = get_phase_containing_project_state($this->state);

        // -------------------------------------------------

        // Maybe set $this->image_source_name.
        if (isset($this->image_source)) {

            $imso_code = $this->image_source;
            if ( strcmp($imso_code,'_internal') != 0 )
            // if $imso_code = _internal, the images were done by or for a DP user, 
            // whose username should be recorded in image_provider, and the credit handled by
            // _create_credit_line() below; otherwise, we need to look up info on the
            // specific "external" image source
            {
                $imso_res = mysql_fetch_assoc(mysql_query("
                    SELECT full_name, credit
                    FROM image_sources
                    WHERE code_name = '$imso_code'"));
                $this->image_source_name = $imso_res['full_name'];
                $image_source_credit = $imso_res['credit'];
            }
        }

        // Set $this->credits_line.
        $this->credits_line = $this->_create_credit_line();
        if (isset($image_source_credit)) {
            // Can't put a . at the end of the output of _create_credit_line, since it ends
            // with a url and the . breaks the url in some browsers; yet the image source credit,
            // grammatically and aestehtically, needs some sort of separation from the 
            // "main" credit line - so we enclose it in ( )
            $this->credits_line = $this->credits_line." (".$image_source_credit.")";
        }

        // -------------------------------------------------

        global $projects_url, $projects_dir;

        $this->url = "$projects_url/$this->projectid";
        $this->dir = "$projects_dir/$this->projectid";
        $this->dir_exists = is_dir($this->dir);

        $this->pages_table_exists = $dpdb->IsTable($this->projectid);
        // $this->pages_table_exists = 
            // ( mysql_query("DESCRIBE $this->projectid") != FALSE );

        // -------------------------------------------------

        $this->can_be_managed_by_current_user = $this->can_be_managed_by_user( $pguser );
        $this->names_can_be_seen_by_current_user = $this->names_can_be_seen_by_user( $pguser );
        $this->PPer_is_current_user = $this->PPer_is_user( $pguser);
        $this->PPVer_is_current_user = $this->PPVer_is_user( $pguser);

        $this->_state_counts = array();
        if(! $dpdb->IsTable($this->projectid)) {
            $this->_state_counts = array();
        }
        else {
            $rows = $dpdb->SqlObjects("
                SELECT state, COUNT(1) scount FROM $this->projectid
                GROUP BY state");
            foreach($rows as $row) {
                $this->_state_counts[$row->state] = $row->scount;
            }
            $this->_page_count = count($rows);
        }
    }

    public function PageCount() {
        return $this->_page_count;
    }

    public function Round() {
        return get_Round_for_project_state($this->state);
    }

    /*
    public function can_be_managed_by_current_user() {
        global $pguser;
        return $this->can_be_managed_by_user( $pguser );
    }
    public function names_can_be_seen_by_current_user() {
        global $pguser;
        return $this->names_can_be_seen_by_user( $pguser );
    }
    public function PPer_is_current_user() {
        global $pguser;
        return $this->PPer_is_user( $pguser);
    }
    public function PPVer_is_current_user() {
        global $pguser;
        return $this->PPVer_is_user( $pguser);
    }
    */

    public function ProjectManager() {
        return $this->username;
    }

    // -------------------------------------------------------------------------

    function PPer_is_user($username) {
        if ( ! $username ) 
            return FALSE;
        return lower($this->postproofer) == lower($username);
    }

    // -------------------------------------------------------------------------

    function PPVer_is_user($username) {
        if ( ! $username ) 
            return FALSE;
        return lower($this->ppverifier) == lower($username);
    }
        // You might think that we should only allow
        //     $this->state == 'proj_post_second_checked_out'
        // But if someone with PPV-ability is PPing a project,
        // they can (and probably will) directly post to PG,
        // with no explicit PPV phase. Thus, they are effectively
        // both the PPer and PPVer of the project.

    // -------------------------------------------------------------------------

    // this class has a function and a public property with the same name.
    public function can_be_managed_by_username( $username ) {
        return $this->can_be_managed_by_user( $username );
    }
    function can_be_managed_by_user( $username ) {
        if ( ! $username ) 
            return FALSE;
        return lower($username) == lower($this->username)
              || that_user_is_a_sitemanager($username)
              || that_user_is_proj_facilitator($username);
    }

    // -------------------------------------------------------------------------

    function names_can_be_seen_by_user( $username) {
        if ( ! $username ) 
            return FALSE;

        return $this->can_be_managed_by_user($username)
             || $this->PPer_is_user($username)
             || $this->PPVer_is_user($username);
        
    }
    // -------------------------------------------------------------------------

    // These should be treated as constants.
    // (PHP doesn't appear to allow constants as class members.)
    var $CBP_OKAY                         = 0;
    var $CBP_PROJECT_NOT_IN_ROUND         = 1;
    var $CBP_PROJECT_NOT_AVAILABLE        = 2;
    var $CBP_USER_NOT_QUALIFIED_FOR_ROUND = 3;

    // (where "proofed" means "worked on in a round, right now".)
    // Returns an array consisting of:
    // -- one of the above codes, and
    // -- a suggested error message.
    function can_be_proofed_by_current_user() {
        global $code_url, $pguser;

        $state = $this->state;
        $round = get_Round_for_project_state($state);

        if (is_null($round)) {
            // The project is not in any round.
            return array( $this->CBP_PROJECT_NOT_IN_ROUND,
                        _('The project is not in a round.'));
        }

        if ( $state != $round->project_available_state ) {
            // The project is in a round, but isn't available.
            return array( $this->CBP_PROJECT_NOT_AVAILABLE,
                _('Proofers are not allowed to work on the project in its current state.'));
        }

        /** @var Round $round */
        $uao = $round->user_access($pguser);
        if ( !$uao->can_access ) {
            // The project is available, but the current user isn't
            // allowed to work in the round that the project is in.
            return array( $this->CBP_USER_NOT_QUALIFIED_FOR_ROUND,
                        sprintf(
                    _('You have not yet been cleared to work on projects in %s (%s).'),
                    $round->name,
                    $round->id)
                . "<br>"
                . sprintf( _("Please visit <a href='%s'>the %s home</a> to find out what happens in this round and how you can qualify to work in it."),
                    "$code_url/tools/proofers/round.php?round_id=$round->id",
                    $round->id));
        }

        return array( $this->CBP_OKAY,
                "You may work on this project.");
    }

    // -------------------------------------------------------------------------

    // The string will not be localized, since it should be ready
    // to be included with the finished project.
    function _create_credit_line() {
        global $site_url;

        $credits = array();

        $creditables = array( 'pm' => $this->username,        // username
                              'pp' => $this->postproofer,     // username
                              'ip' => $this->image_preparer,  // username
                              'tp' => $this->text_preparer,   // username
                              'ec' => $this->extra_credits,   // arbitrary text
                              'cp' => $this->scannercredit);  // username or arbitrary text

        foreach ($creditables as $role => $name) {
            if ( $name == '' ) 
                continue;
    
            $credit = NULL;
    
            if ($role == 'cp') {
                // $name ($project->scannercredit) could be
                // a username or just a typed-in string
                $res = mysql_query("
                    SELECT username FROM users
                    WHERE username='$name'");
                if (!$res) {
                    // $name is not a username.
                    // So use it as the credit.
                    $credit = $name;
                }
            }

            if ($role == 'ec') {
                // $name ($project->extra_credits) should not be a username;
                // It is just a typed-in string, and will be presented as is,
                // as part of the list. 
                $credit = $name;
            }
    
            if ( is_null($credit) ) {
                // $name is a username.
                $username = $name;
    
                if (!wants_anonymity($username, $role)) {
                    $credit = get_credit_name($username);
                }
            }
    
            if ( !is_null($credit) ) {
                if (!in_array($credit, $credits))
                    array_push($credits, $credit);
            }
        }

        if (count($credits) > 0) {
            $credits_line = join(', ', $credits) . " and the Online Distributed Proofreading Canada Team at $site_url";
        }
        else {
            $credits_line = "The Online Distributed Proofreading Canada Team at $site_url";
        }

        return $credits_line;
    }

    // -------------------------------------------------------------------------

	/*
    function ensure_topic() {
        global $dpdb;
        if ( ! empty($this->topic_id) ) 
            return $this->topic_id;

        $post_subject = "\"{$this->nameofwork}\"    by {$this->authorsname}";

        global $code_url;
        $post_body = <<<EOS
This thread is for discussion specific to "$this->nameofwork" by $this->authorsname.

Please review the [url=$code_url/project.php?id={$this->projectid}]project comments[/url] before posting, as well as any posts below, as your question may already be answered there.

(This post is automatically generated.)
EOS;

        $bb = new DpPhpbb3();
        $topic_id = $bb->CreateTopic($post_subject, $post_body, $this->ProjectManager());
        // Save $topic_id in db and in $this.
        $dpdb->SqlExecute("
            UPDATE projects
            SET topic_id = $topic_id
            WHERE projectid = '{$this->projectid}' ");
        $this->topic_id = $topic_id;
        return $topic_id;
    }
*/

    function PageCountForState($state) {
        return isset($this->_state_counts[$state])
            ? $this->_state_counts[$state]
            : 0;
    }
    function Comments() {
        return $this->row['comments'];
    }
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

// Return the username of the user to whom the project will be
// automatically checked out for PPing when it reaches the PP stage,
// or NULL if the project will merely go into the available-for-PP state.
function project_get_auto_PPer( $projectid ) {
    $project = new DpProject($projectid);
    if($project->PPer() != "") {
        return $project->PPer();
    }
    else {
        return $project->ProjectManager();
    }
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

// The following two functions don't particularly belong here, as they aren't
// project-specific. However, nobody else uses them yet.

// $activity should be one of 'cp', 'pm', 'pp', 'ip' and 'tp'.
function wants_anonymity( $login_name, $activity ) {
    $usr = new DpUser($login_name);
    return $usr->IsAnonymousActivity($activity);
}

// -----------------------------------------------------------------------------

// Returns the real name OR the username OR a user-specified 'other'.
// (If the user hasn't specified anything in the preferences, the
// real name will be returned.
function get_credit_name( $login_name ) {
    if ($login_name == '') {
        return '(no name)';
    }

    $usr = new DpUser($login_name);
    $credit = $usr->Setting("credit_name");
    if($credit == "") {
        $credit = $usr->Setting("real_name");
    }

    if ($credit == 'username') {
        $name = $login_name;
    }
    else if ($credit == 'other') {
        $name = $usr->Setting('credit_other');
    }
    else {      // default: real_name 
        $res = mysql_query("
            SELECT real_name FROM users
            WHERE username='$login_name'");
        if (mysql_num_rows($res) > 0) {
            $name = mysql_result($res, 0);
        }
        else {
            $name = $login_name;
        }
    }
    return $name;
}

// vim: sw=4 ts=4 expandtab
