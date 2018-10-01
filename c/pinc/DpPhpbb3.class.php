<?php
error_reporting(E_ALL);

global $phpbb_root_path;

if(!isset($relPath))
    $relPath = __DIR__ . "/";
include_once $relPath . "site_vars.php";

if(!defined('IN_PHPBB')) {
	define('IN_PHPBB', true);
}
$phpEx = substr(strrchr(__FILE__, '.'), 1);

global $phpbb_root_path;

// common.php instantiates $user and $auth
require_once $phpbb_root_path . "common.php";
$request->enable_super_globals();
require_once $phpbb_root_path . "includes/functions_posting.php";
require_once $phpbb_root_path . "includes/functions_privmsgs.php" ;

// phpbb functions to establish a session and identify the session user
$user->session_begin();
$auth->acl($user->data);

/*
 *   phpbb provides us with the $user object and, when it performs a login for us, $_user_row and $_username
 */

class DpPhpbb3
{
    private $_username;
    private $_user_row;

    public function __construct() {
        global $user;

        $this->_username = $user->data['username'];
    }

    public function UsernameExists($name) {
        global $dpdb, $phpbb_database_name, $forum_users_table;
        $utable = $phpbb_database_name . ".".$forum_users_table;
        $sql = "SELECT COUNT(1) FROM $utable
                WHERE username_clean = ?";
        $args = [&$name];
        $isname = $dpdb->SqlOneValuePS($sql, $args);
        return $isname == 1;
    }

//    public function UserData() {
//        global $user;
//        return $user->data;
//    }

    public function __destruct() {
}
    
//    public function IsRegistered() {
//        global $user;
//        return $user->data['is_registered'];
//    }

    public function IsLoggedIn() {
        return $this->_username != "" && strtolower($this->_username) != "anonymous";
    }

    // Called from DpThisUser if supplied with username and password.
    // Login succeeds if $_user_row is populated and $_username is populated.
    // Redundant since $_username = $_user_row['username'] (but should be 'username_clean'?)
    public function DoLogin($username, $password) {
        global $auth;
        $autologin = false;
        $login = $auth->login($username, $password, $autologin);

        if(! $login || $login['status'] != LOGIN_SUCCESS) {
            $this->_user_row = null;
            $this->_username = null;
            return false;
        }
        $this->_user_row = $login['user_row'];
        $this->_username = $login['user_row']['username'];
        if(empty($this->_username) || strtolower($this->_username) == 'anonymous') {
            $this->_user_row = null;
            $this->_username = null;
            return false;
        }
        return true;
    }

    public function Email() {
        return empty($this->_user_row)
            ? null
            : $this->_user_row['user_email'];
    }

    public function Language() {
        global $user;
        return empty($user->data['user_lang'])
            ? null
            : $user->data['user_lang'];
    }

    public function DataUserName() {
        global $user;
        return $user->data['username_clean'];
    }
    public function UserName() {
        return $this->_username;
    }
    public function SessionId() {
        global $user;
        return $user->session_id;
    }

    public function SessionUsername() {
        return $this->DataUserName();
    }

    public function DoLogout() {
        global $user;
        $user->session_kill();
    }

    public function InboxCount() {
        global $user;
        return $user->data['user_unread_privmsg'];
    }

    public function CreateTopic($subj, $msg, $poster) {
        global $dpdb;
        global $waiting_projects_forum_idx;
        $dpsubject = utf8_normalize_nfc($subj);
        $dpmessage = utf8_normalize_nfc($msg);
	    $pname = lower($poster);

        // variables to hold the parameters for submit_post
        $poll = $uid = $bitfield = $options = '';
                  
        generate_text_for_storage($dpsubject, $uid, $bitfield, 
                                $options, true, true, true);
        generate_text_for_storage($dpmessage, $uid, $bitfield, 
                                $options, true, true, true);

        $data = [
            'forum_id'          => $waiting_projects_forum_idx,
            'icon_id'           => false,
         
            'enable_bbcode'     => true,
            'enable_smilies'    => true,
            'enable_urls'       => true,
            'enable_sig'        => true,
     
            'message'           => $dpmessage,
            'message_md5'       => md5($dpmessage),
     
            'bbcode_bitfield'   => $bitfield,
            'bbcode_uid'        => $uid,
     
            'post_edit_locked'  => 0,
            'topic_title'       => $dpsubject,
            'notify_set'        => false,
            'notify'            => true,
            'post_time'         => 0,
            'forum_name'        => '',
            'enable_indexing'   => true,
     
            // 3.0.6
            'force_approved_state'  => true,
        ];
        
        submit_post('post', $dpsubject, $poster, POST_NORMAL,  $poll, $data);

        $post_id = $data['post_id'];
        $topic_id = $data['topic_id'];
        $pm_id = forum_user_id_for_username($pname);
//	    $bb_users_table = build_forum_users_table();
//        $pm_id = $dpdb->SqlOneValue("
//                SELECT user_id FROM $bb_users_table
//                WHERE username_clean = '$pname'");
            
        if($poster != "") {
            $bb_topics_table = build_forum_topics_table();
            $bb_posts_table = build_forum_posts_table();
            $bb_watch_table = build_forum_watch_table();
            $sql = "
                UPDATE $bb_posts_table
                SET poster_id = $pm_id
                WHERE post_id = $post_id";
            $dpdb->SqlExecute($sql);
            $dpdb->SqlExecute("
                UPDATE $bb_topics_table
                SET topic_poster = $pm_id,
                    topic_first_poster_name = '$poster',
                    topic_last_poster_name = '$poster',
                    topic_last_poster_id = $pm_id
                WHERE topic_id = $topic_id");
            $sql = "
                UPDATE $bb_watch_table
                SET user_id = $pm_id
                WHERE topic_id = $topic_id";
            $dpdb->SqlExecute($sql);
                
        }
        return $topic_id;
    }

    public function SubmitTopicPost($topic_id, $subject, $message) {
        $message = utf8_normalize_nfc($message);
        $subject = utf8_normalize_nfc($subject);
        $poll = $uid = $bitfield = $options = '';
        generate_text_for_storage($subject, $uid, $bitfield, 
                                $options, true, true, true);
        generate_text_for_storage($message, $uid, $bitfield, 
                                $options, true, true, true);

        $data = [
            'topic_id'          => $topic_id,
            'icon_id'           => false,
         
            'enable_bbcode'     => true,
            'enable_smilies'    => true,
            'enable_urls'       => true,
            'enable_sig'        => true,
     
            'message'           => $message,
            'message_md5'       => md5($message),
     
            'bbcode_bitfield'   => $bitfield,
            'bbcode_uid'        => $uid,
     
            'post_edit_locked'  => 0,
            'topic_title'       => $subject,
            'notify_set'        => false,
            'notify'            => false,
            'post_time'         => 0,
            'forum_name'        => '',
            'enable_indexing'   => true,
     
            // 3.0.6
            'force_approved_state'  => true,
        ];
        submit_post('post', $subject, '', POST_NORMAL,  $poll, $data);
    }

    public function LatestTopicPostTime($topic_id) {
        global $dpdb;
//	    global $forumdb, $forumpfx;
	    $topics_table = build_forum_topics_table();
        $sql = "SELECT
                    DATE_FORMAT(FROM_UNIXTIME(topic_last_post_time), '%b %e %Y %H:%i') AS post_time
                FROM $topics_table
                WHERE topic_id = $topic_id";
        return $dpdb->SqlOneValue($sql);
    }

    public function TopicExists($topic_id) {
        global $dpdb;
//	    global $forumdb, $forumpfx;
	    $topics_table = build_forum_topics_table();
        $sql = "SELECT 1
                FROM $topics_table
                WHERE topic_id = $topic_id";
        return $dpdb->SqlExists($sql);
    }

    public function TopicReplyCount($topic_id) {
        global $dpdb;
//	    global $forumdb, $forumpfx;
	    $topics_table = build_forum_topics_table();
        $sql = "SELECT COUNT(1)
                FROM $topics_table
                WHERE topic_id = $topic_id";
        return $dpdb->SqlOneValue($sql);
    }

    public function MoveTopicForumId($topic_id, $forum_id) {
        global $dpdb;
	    $topics_table = build_forum_topics_table();
        $sql = "UPDATE $topics_table
                SET forum_id = ?
                WHERE topic_id = ?";
        $args = [&$forum_id, &$topic_id];
        $dpdb->SqlExecutePS($sql, $args);
    }

    public static function SendPrivateMessage($subject, $message, $sender_username, $to_username) {
        $nmessage = utf8_normalize_nfc($message);
        $nsubject = utf8_normalize_nfc($subject);
        $sender_id = forum_user_id_for_username($sender_username);
        $uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
        $allow_urls = $allow_bbcode = $allow_smilies = true;
        generate_text_for_storage($nmessage, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
        $to_id = forum_user_id_for_username($to_username);
        $address_list = ["u" => [$to_id => "to"]];

        $pm_data = [
            'from_user_id'       => $sender_id,
            'from_user_ip'       => "127.0.0.1",
            'from_username'      => $sender_username,
            'enable_sig'         => false,
            'enable_bbcode'      => true,
            'enable_smilies'     => true,
            'enable_urls'        => true,
            'icon_id'            => 0,
            'bbcode_bitfield'    => $bitfield,
            'bbcode_uid'         => $uid,
            'message'            => $nmessage,
//            'address_list'        => array('u' => array($userid => 'to')),
            'address_list'        => $address_list
        ];
	    //Now We Have All Data Lets Send The PM!!
	    submit_pm('post', $nsubject, $pm_data, false);
	}
}

//function ForumUserIdForUsername($username) {
//    return forum_user_id_for_username($username) ;
//}

// for addressing a PM based on username
function forum_user_id_for_username($username) {
    global $dpdb;
	$username = lower($username);
	$bb_users_table = build_forum_users_table();
    return $dpdb->SqlOneValuePS("
            SELECT user_id FROM $bb_users_table
            WHERE username_clean = ?", [&$username]);
}


$avatar_path = build_path($site_url,  "forumdpc/download/file.php?avatar=");

class DpForumUser
{
    private $_row;

    public function __construct($username) {
        global $dpdb;

        if($username == "") {
            return;
        }
	    $username = lower($username);
	    $bb_users_table = build_forum_users_table();

        $this->_row = $dpdb->SqlOneRowPS(
            "SELECT * FROM $bb_users_table
             WHERE username_clean = ?", [&$username]);
    }

    public function AvatarFile() {
        return $this->_row['user_avatar'];
    }

    public function AvatarUrl() {
        global $avatar_path;
        return $avatar_path . $this->_row["user_avatar"];
    }

    public function Sig() {
        return $this->_row['user_sig'];
    }

    public function Location() {
        return isset($this->_row['user_from']) ? $this->_row['user_from'] : "";
    }

    public function WebSiteUrl() {
        return isset($this->_row['user_website']) ? $this->_row['user_website'] : "";
    }

    public function Occupation() {
        return isset($this->_row['user_occ']) ? $this->_row['user_occ'] : "";
    }

    public function Interests() {
        return isset($this->_row['user_interests']) ? $this->_row['user_interests'] : "";
    }

    public function Email() {
        return isset($this->_row['user_email']) ? $this->_row['user_email'] : "";
    }
}

function build_forum_table($stub) {
	global $forumdb, $forumpfx;
	return $forumdb . '.' . $forumpfx . $stub;
}

function build_forum_users_table() {
	return build_forum_table("users");
}

function build_forum_posts_table() {
	return build_forum_table("posts");
}

function build_forum_watch_table() {
	return build_forum_table("topics_watch");
}

function build_forum_topics_table() {
	return build_forum_table("topics");
}
