<?php
ini_set("error_display", true);
error_reporting(E_ALL);

global $relPath;

class DpUser
{
    protected $_row;
    protected $_page_count;

    public function __construct($username = "") {

        $this->FetchUser($username);
    }

    public function FetchUser($username) {
        global $dpdb;
        $sql = "SELECT
                    u.username,
                    u.real_name,
                    u.date_created,
                    FROM_UNIXTIME(u.date_created) date_created_dt,
                    DATEDIFF(CURRENT_DATE(), DATE(FROM_UNIXTIME(u.date_created))) dp_age,
                    u.t_last_activity,
                    u.u_privacy,
                    u.u_intlang,
                    u.u_lang,
                    u.emailupdates,
                    u.u_neigh,
                    u.u_top10,
                    u.credits
            FROM users u
            WHERE u.username = ?";
        $args = [&$username];
	    $this->_row = $dpdb->SqlOneRowPS($sql, $args);

        if( ! $this->Exists()) {
	        LogMsg("FetchUser failed for $username");
            die("FetchUser failed for $username");
        }
    }

//    public function Row() {
//        return $this->_row;
//    }

    public function MayWorkInRound($roundid) {
	    if($this->IsSiteManager()) {
		    return true;
	    }

        if($this->HasRole($roundid)) {
            return true;
        }

        switch($roundid) {
            case "P1":
	        case "SR":
				if($this->IsSiteManager()) {
					return true;
				}
                return true;

            case "P2":
            case "F1":
				if($this->IsSiteManager()) {
					return true;
				}
                if($this->DpAge() < 21)
                    return false;
                if($this->PageCount() < 300)
                    return false;
                return true;

            case "P3":
	            if($this->IsSiteManager()) {
		            return true;
	            }
                if($this->PageCount() < 400)
                    return false;
                if($this->RoundPageCount("P2") < 50)
                    return false;
                if($this->RoundPageCount("F1") < 50)
                    return false;
                if($this->DpAge() < 42)
                    return false;
                return $this->HasRole($roundid);

            case "F2":
	            if($this->IsSiteManager()) {
		            return true;
	            }
                if($this->RoundPageCount("F1") < 400)
                    return false;
                if($this->DpAge() < 91)
                    return false;
                return $this->HasRole($roundid);

            case "PP":
            case "PPV":
				if($this->IsSiteManager()) {
					return true;
				}
				return $this->HasRole($roundid);
	        case "POSTED":
            default:
                return false;
        }
    }

	public function NameIs($name) {
		return lower($this->Username()) == lower($name);
	}

    public function MayQC() {
        return $this->HasRole("QC") || $this->IsAdmin();
    }
    public function MayPP() {
	    return $this->MayWorkInRound("PP");
    }

    public function MayPPV() {
	    return $this->MayWorkInRound("PPV");
    }

	public function PMDefault() {
		return 1;
	}

    public function IsTeamMemberOf($id) {
        global $dpdb;
        $username = $this->Username();
        return $dpdb->SqlOneValuePS(
            "SELECT COUNT(1) FROM users_teams
            WHERE username = ? AND team_id = ?",
            [&$username, &$id]);
    }

    public function TeamIDs() {
        global $dpdb;
        $username = $this->Username();
        $args = [&$username];
        return $dpdb->SqlValuesPS(
            "SELECT team_id FROM users_teams
            WHERE username = ?", $args);
    }

    public function QuitTeamId($tid) {
        global $dpdb;
        $username = $this->Username();

        $dpdb->SqlExecutePS("
            DELETE FROM users_teams
            WHERE username = ?
                AND team_id = ?",
            [&$username, &$tid]);
    }

    /**
     * @param $id
     */
    public function AddTeamId($id) {
        global $dpdb;
        $dpdb->SetEcho();
        dump($this->TeamCount());
        if($this->TeamCount() >= 3)
            return;

        dump($id);
        if($this->IsTeamMemberOf($id)) {
            return;
        }
        $username = $this->Username();
        $sql = "
            INSERT INTO users_teams
                ( username, team_id, create_time)
            VALUES
            ( ?, ?, UNIX_TIMESTAMP())";
        $args = [&$username, &$id];
        $dpdb->SqlExecutePS($sql, $args);
    }

    public function ClearTeam($teamnum) {
        $this->QuitTeamId($teamnum);
    }

    public function MayModifyAccess() {
        return $this->MayGrantAccess()
            || $this->MayRevokeAccess();
    }
    public function MayGrantAccess() {
        return $this->IsSiteManager()
        || $this->IsProjectFacilitator();
    }
    public function MayRevokeAccess() {
        return $this->IsSiteManager();
    }

    public function IsSiteNewsEditor() {
	    return $this->HasRole("site_news_editor");
    }

    public function Exists() {
        return count($this->_row) != 0;
    }

    public function Privacy() {
        return $this->_row['u_privacy'];
    }

    public function PrivateUsername() {
        return $this->Privacy() > 0
            ? "anonymous"
            : $this->Username();
    }

    public function HasRole($code) {
        global $dpdb;
        $username = $this->Username();
        $sql = "
            SELECT 1 FROM user_roles
            WHERE username = ?
                AND role_code = ?";
        $args = [&$username, &$code];
        return $dpdb->SqlExistsPS($sql, $args);
    }

    public function RoundNeighborhood($roundid, $radius) {
        global $dpdb;
        $count = $this->RoundPageCount($roundid);
        $rank = $this->RoundRank($roundid);
        $username = $this->Username();
        if($count <= 0) {
            return [];
        }
        $rplus = $radius + 1;
 		$usql = "
            SELECT a.username,
                SUM(a.page_count) page_count,
                u.date_created,
                u.u_privacy,
                u1.date_created created1,
                u.date_created created,
                DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(u.date_created)) age_days
            FROM
            (
                SELECT username, page_count FROM total_user_round_pages
                WHERE phase = ?

                UNION ALL

                SELECT username, COUNT(1) page_count FROM page_versions
                    WHERE phase = ?
						AND state = 'C'
						AND version_time >= UNIX_TIMESTAMP(CURRENT_DATE())
					GROUP BY username
            ) a
            JOIN users u ON a.username = u.username
            JOIN users u1 ON u1.username = ?
            GROUP BY a.username
            HAVING page_count > ?
                OR ( page_count = ?
                    AND u.date_created <= u1.date_created
                )
            ORDER BY page_count, u.date_created DESC
            LIMIT ?";
        echo html_comment($usql);
        $uargs = [&$roundid, &$roundid, &$username, &$count, &$count, &$rplus];
        $urows = $dpdb->SqlRowsPS($usql, $uargs);


		$dsql = "
            SELECT  a.username,
                    SUM(a.page_count) page_count,
                    u.date_created,
                    u.u_privacy,
                    u1.date_created created1,
                    u.date_created created,
                DATEDIFF(CURRENT_DATE(), FROM_UNIXTIME(u.date_created)) age_days
            FROM
            (
                SELECT username, page_count FROM total_user_round_pages
                WHERE phase = ?

                UNION ALL

                SELECT username, COUNT(1) page_count FROM page_versions
                    WHERE phase = ?
						AND state = 'C'
						AND version_time >= UNIX_TIMESTAMP(CURRENT_DATE())
					GROUP BY username
            ) a
            JOIN users u ON a.username = u.username
            JOIN users u1 ON u1.username = ?
            GROUP BY a.username
            HAVING page_count < ?
                OR ( page_count = ?
                    AND u.date_created > u1.date_created
                )
            ORDER BY page_count DESC, u.date_created
            LIMIT ?";
        $dargs = [&$roundid, &$roundid, &$username, &$count, &$count, &$radius];
	    echo html_comment($dsql);
        $drows = $dpdb->SqlRowsPS($dsql, $dargs);

        $rows = [];
        $n = count($urows);
        $irank = $rank - count($urows) ;

        for($i = $n-1; $i >= 0; $i--) {
            $row = $urows[$i];
            $row['rank'] = ++$irank;
            $rows[] = $row;
        }
        foreach($drows as $row) {
            $row['rank'] = ++$irank;
            $rows[] = $row;
        }
        return $rows;
    }

    public function NeighborRadius() {
        return $this->_row['u_neigh'];
    }

    private function DpAge() {
        return $this->_row["dp_age"];
    }

    public function DateCreatedInt() {
        return $this->_row['date_created'];
    }   

    public function DateCreated() {
        return date('m-d-Y', $this->DateCreatedInt());
    }

//    public function CreatedDaysAgo() {
//        return $this->AgeDays() ;
//    }
    public function AgeDays() {
        return round( ( time() - $this->DateCreatedInt() )
            / 24 / 60 / 60 ) ;
    }

    public function LastSeenInt() {
        return $this->_row['t_last_activity'];
    }

    public function LastSeenDays() {
        return $this->_daysBetween($this->LastSeenInt(), time());
    }

    public function RealName() {
        return $this->_row['real_name'];
    }

    public function SetRealName($real_name) {
        global $dpdb;
        $username = $this->Username();
        $sql = "UPDATE users SET real_name = ?
                WHERE username = ?";
        $args = [&$real_name, &$username];
        $dpdb->SqlExecutePS($sql, $args);
    }

    public function EmailAddress() {
        return isset($this->_bb)
            ? $this->_bb->Email()
            : null;
    }

    public function Username() {
        return $this->_row['username'];
    }

    public function InterfaceLanguage() {
        return isset($this->_bb)
            ? $this->_bb->Language()
            : null;
    }

    protected function _daysBetween($earlierdate, $laterdate) {
        return ($laterdate - $earlierdate) / 24 / 60 / 60 ;
    }

    protected function _daysAgo($idate) {
        return $this->_daysBetween($idate, time());
    }

    public function IsSiteManager() {
	    global $site_managers;
	    return in_array(lower($this->Username()), $site_managers);
    }

    public function IsProjectManager() {
        return $this->HasRole("PM");
    }

    public function IsAdmin() {
        return $this->IsSiteManager()
        || $this->IsProjectFacilitator()
        || $this->IsProjectManager();
    }

    public function IsProjectFacilitator() {
        return $this->HasRole("PF");
    }

    public function MayCreateProjects() {
        return $this->IsSiteManager()
            || $this->IsProjectFacilitator()
            || $this->IsProjectManager();
    }

    public function IsImageSourcesManager() {
        return $this->HasRole('image_sources_manager');
    }

    public function RoundRank($roundid) {
        global $dpdb;
        $username = $this->Username();
        $count = $this->RoundPageCount($roundid);

        $sql = "
            SELECT COUNT(1) + 1 FROM
            (
                SELECT a.username,
                       u.date_created create0,
                       u1.date_created create1,
                       SUM(a.page_count) pagecount 
                FROM (
                    SELECT username, page_count FROM total_user_round_pages
                    WHERE phase = ?
                    UNION ALL
                    SELECT username, COUNT(1) FROM page_events_save
                    WHERE phase = ?
                        AND event_time >= UNIX_TIMESTAMP(CURRENT_DATE())
                    GROUP BY username
                ) a
                JOIN users u ON u.username = ?
                JOIN users u1 ON a.username = u1.username
                GROUP BY a.username
                HAVING SUM(a.page_count) > ?
                OR (SUM(a.page_count) = ? AND create1 < create0)
            )  b";
        $args = [&$roundid, &$roundid, &$username, &$count, &$count];
        return $dpdb->SqlOneValuePS($sql, $args);
    }

    public function PageCount() {
        global $dpdb;
        static $_count;
        if(! isset($_count)) {
            $username = $this->Username();
            $sql = "
                SELECT SUM(page_count) FROM (
                    SELECT page_count FROM total_user_round_pages
                    WHERE username = ?
                    UNION ALL
                    SELECT COUNT(1) FROM page_versions
                        WHERE username = ?
                        AND state = 'C'
                        AND version_time >= UNIX_TIMESTAMP(CURRENT_DATE())
                ) a";
            $args = [&$username, &$username];
        $_count = $dpdb->SqlOneValuePS($sql, $args);
        }
        return $_count;
    }

    public function RoundPageCount($phase) {
        global $dpdb;
        $username = $this->Username();
        $sql = "SELECT SUM(page_count) FROM (
                SELECT username, page_count FROM total_user_round_pages
                WHERE username = ? AND phase = ?
                UNION ALL
                SELECT username, COUNT(1) FROM page_versions
                    WHERE username = ?
                    	AND phase = ?
						AND state = 'C'
						AND version_time >= UNIX_TIMESTAMP(CURRENT_DATE())
            ) a";
        $args = [&$username, &$phase, &$username, &$phase];
        return $dpdb->SqlOneValuePS($sql, $args);
    }

    public function RoundTodayCount($phase) {
        global $dpdb;
        $username = $this->Username();
        $sql = "SELECT count(1) FROM page_versions
                WHERE username = ?
                    AND state = 'C'
                    AND phase = ?
                    AND version_time >= UNIX_TIMESTAMP(CURRENT_DATE())";
        $args = [&$username, &$phase];
        return $dpdb->SqlOneValuePS($sql, $args);
    }

    public function MayManageRoles() {
        return $this->IsSiteManager();
    }

/*
    private function IsPostedNotice($projectid) {
        global $dpdb;
        $username = $this->Username();
        return $dpdb->SqlExists("
            SELECT 1 FROM user_posted_notices
            WHERE username = '$username'
                AND projectid = '$projectid'");
    }

    private function SetPostedNotice($projectid) {
        global $dpdb;
        if($this->IsPostedNotice($projectid)) {
            $dpdb->SqlExecute("
                INSERT INTO user_posted_notices
                VALUES ('{$this->Username()}', $projectid)");
        }
    }

    private function ClearPostedNotice($projectid) {
        global $dpdb;
        $username = $this->Username();
        $dpdb->SqlExecute("
            DELETE FROM user_posted_notices
            WHERE username = '$username'
                AND projectid = '$projectid'");
    }

    public function TogglePostedNotice($projectid) {
        if($this->IsPostedNotice($projectid)) {
            $this->ClearPostedNotice($projectid);
        }
        else {
            $this->SetPostedNotice($projectid);
        }
    }
*/

    public function SetCreditName($credit_name) {
        global $dpdb;
        $username = $this->Username();
        $sql = "UPDATE users SET credit_name = ?
                WHERE username = ?";
        $args = [&$credit_name, &$username];
        $dpdb->SqlExecutePS($sql, $args);
    }

//    public function SetUserString($fieldname, $value) {
//        global $dpdb;
//        $username = $this->Username();
//        $sql = "UPDATE users SET {$fieldname} = ?
//                WHERE username = ?";
//        $args = array(&$value, &$username);
//        $dpdb->SqlExecute($sql, $args);
//    }

    public function IsEmailUpdates() {
        return $this->_row['emailupdates'];
    }

    public function FirstRoundDate($phase) {
        global $dpdb;
        $username = $this->Username();
        return $dpdb->SqlOneValuePS(
           "SELECT DATE(FROM_UNIXTIME(MIN(version_time)))
            FROM page_versions
            WHERE username = ? AND phase = ?",
            [&$username, &$phase]);
    }
    public function FirstRoundTime($phase) {
        global $dpdb;
        $username = $this->Username();
        return $dpdb->SqlOneValuePS("
            SELECT FROM_UNIXTIME(MIN(version_time))
            FROM page_versions
            WHERE username = ? AND phase = ?",
            [&$username, &$phase]);
    }

    public function TeamCount() {
        return count($this->Teams());
    }

    public function Teams() {
        $t = [];
        foreach($this->TeamIDs() as $tid) {
            $t[] = new DpTeam($tid);
        }
        return $t;
    }

    public function MayMentor() {
        return $this->HasRole("P2mentor");
    }

    /*
     * The hold_code table maps from roles to the holds
     * which are permitted to be released by users with that role.
     * Four rows: hold_code->role_code
     *   pm::PM
     *   pp::PP
     *   qc::QC
     *   queue::p1queue
     * The column set_or_release is not implemented and currently is always
     * NULL.
     */
    public function MayReleaseHold($holdcode) {
        global $dpdb;

        if($this->IsSiteManager()) {
            return true;
        }
        $username = $this->Username();
        return $dpdb->SqlExists("
            SELECT COUNT(1)
            FROM user_roles ur
            INNER JOIN hold_roles hr
                ON hr.role_code = ur.role_code
            WHERE hr.hold_code ='$holdcode'
                -- AND hr.set_or_release ='R'
                AND ur.username = '$username'");
    }

    public function MaySetHold($holdcode) {
        global $dpdb;

        if($this->IsSiteManager()) {
            return true;
        }
        $username = $this->Username();
        return $dpdb->SqlExists("
            SELECT 1
            FROM user_roles ur
            INNER JOIN hold_roles hr
                ON hr.role_code = ur.role_code
            WHERE hr.hold_code ='$holdcode'
                -- AND hr.set_or_release ='S'
                AND ur.username = '$username'");
    }
    
    public function GrantRole($role) {
        global $dpdb;
        $username = $this->Username();
        $sql = "
            SELECT 1 FROM user_roles
            WHERE username = '$username'
                AND role_code = '$role'";
        if(! $dpdb->SqlExists($sql)) {
             $sql = "
                 INSERT INTO user_roles
                 SET username = '$username',
                     role_code = '$role'";
             $dpdb->SqlExecute($sql);
       }
         LogRoleGrant($username, $role);
    }

    public function RevokeRole($role) {
        global $dpdb;
        $username = $this->Username();
        $dpdb->SqlExecute("
            DELETE FROM user_roles
            WHERE username = '$username'
                AND role_code = '$role'");

        LogRoleRevoke($username, $role);
    }
}

// obtain DpThisUser from phpbb3 session
class DpSessionUser extends DpThisUser
{
    /** @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct() {
        global $Context;
        $this->_bb = new DpPhpbb3();
        if(! $this->_bb->IsLoggedIn()) {
            return;
        }
        $username = $this->_bb->UserName();

        if( ! $Context->UserExists($username)) {
            $Context->CreateUser($username, $this->_bb->Language());
            assert($Context->UserExists($this->Username()));
	        LogMsg("Success - create DP user $username");
        }

        $this->FetchUser($username);
        $this->SetLatestVisit();
    }

    public function SessionId() {
        return $this->_bb->SessionId();
    }
}

class DpThisUser extends DpUser
{
	// if both args are missing, try for a session
    /** @noinspection PhpMissingParentConstructorInspection
     * @param string $username
     * @param string $password
     */
    protected $_bb;

    function __construct($username, $password) {
        global $Context;
        // Might be a session or not,
        // might be logging-in user or not,
        // user might be in DP database or not
        assert($username != "" && $password != "");
		$this->_bb = new DpPhpbb3();

		// Is the user in a session?
		if($this->_bb->IsLoggedIn()) {
			//	If so, phpbb should give us a username
            if( ! $Context->UserExists($username)) {
                $Context->CreateUser($username, $this->_bb->Language());
                LogMsg("Success - create DP
                 user $username");
            }

            parent::__construct($username);
			$this->SetLatestVisit();
			return;
		}

		if($this->_bb->DoLogin($username, $password)) {
			$username = $this->_bb->UserName();
			assert($username != "");
            if( ! $Context->UserExists($username)) {
                $Context->CreateUser($username, $this->_bb->Language());
                assert($Context->UserExists($this->Username()));
                LogMsg("Success - create DP user $username");
            }
            $this->FetchUser($username);
			$this->SetLatestVisit();
			return;
		}
	}

    public function Bb() {
        return $this->_bb;
    }

	public function LogOut() {
		$this->_bb->DoLogout();
	}

    protected function SetLatestVisit() {
        global $dpdb;
        $username = $this->Username();
        $sql = "UPDATE users
                SET t_last_activity = UNIX_TIMESTAMP()
                WHERE username = ?";
        $args = [&$username];
        $dpdb->SqlExecutePS($sql, $args);
    }

	public function IsLoggedIn() {
		if($this->_bb->IsLoggedIn()) {
			assert($this->Username() != "");
			return true;
		}
		return false;
	}

	public function InboxCount() {
		return $this->_bb->InboxCount();
	}
}

class DpTeam
{
    public function __construct($team_id) {
        global $dpdb;

        $sql = "
            SELECT t.team_id,
                   t.teamname,
                   t.team_info,
                   t.createdby,
                   t.created_time,
                   DATE(FROM_UNIXTIME(t.created_time)) created_date,
                   DATEDIFF(CURRENT_DATE(), DATE(FROM_UNIXTIME(t.created_time))) created_days_ago,
                   t.topic_id
            FROM teams t
            LEFT JOIN users ucre ON t.createdby = ucre.username
            LEFT JOIN users_teams ut ON t.team_id = ut.team_id
            WHERE t.team_id = ?";
        $args = [&$team_id];
        $this->_row = $dpdb->SqlOneRowPS($sql, $args);
    }

    public function Exists() {
        return count($this->_row) > 0;
    }

    public function Name() {
        return $this->TeamName();
    }

   public function TeamName() {
        return $this->_row["teamname"];
    }

    public function CreatedDaysAgo() {
        return $this->_row["created_days_ago"];
    }

    public function Id() {
        return $this->_row['team_id'];
    }

    public function MemberCount() {
        global $dpdb;
        $id = $this->Id();
        return
            $dpdb->SqlOneValuePS("
            SELECT COUNT(1) FROM users_teams
            WHERE team_id = ?",
                [&$id]);
    }

    public function CreatedBy() {
        return $this->_row['createdby'];
    }

    public function CreatedDate() {
        return $this->_row['created_date'];
    }

    private function TopicId() {
        return $this->_row['topic_id'];
    }

    private function SetTopicId($id) {
        global $dpdb;
        $tid = $this->Id();
        $sql = "UPDATE teams SET topic_id = ?
                WHERE team_id = ?";
        $args = [&$id, &$tid];
        $dpdb->SqlExecutePS($sql, $args);
    }

    public function Info() {
        return $this->_row['team_info'];
    }

    public function RoundPageCount($roundid) {
        global $dpdb;
        $sql = "
            SELECT SUM(page_count) FROM team_round_pages
            WHERE team_id = {$this->Id()}
              AND phase = '{$roundid}'";
        return $dpdb->SqlOneValue($sql);
    }

    public function RoundRank($roundid) {
        global $dpdb;

        return $dpdb->SqlOneValue("
            SELECT 1 + COUNT(1)
            FROM (
                SELECT team_id, SUM(page_count) pages
                FROM team_round_pages
                WHERE phase = '$roundid'
                GROUP BY team_id
            ) a
            WHERE pages > (
            SELECT SUM(page_count) pages
            FROM team_round_pages
            WHERE phase = 'P2'
                AND team_id = {$this->Id()}
            )");
    }

    public function TopicLink($prompt) {
        global $Context;
        $id = $this->TopicId();
        if(! $id) {
            $id = $Context->CreateTeamTopic($this);
            $this->SetTopicId($id);
        }
        assert($id);
        return link_to_forum_topic($id, $prompt);
    }

    public function StatsLink($phase) {
        return link_to_team_stats($this->Id(), $phase);
    }

    public function CreateTeamTopic() {
        global $Context;
        $Context->CreateTeamTopic($this);
    }
}

function LogRoleGrant($username, $role) {
    global $User;
    global $dpdb;
    $actor = $User->Username();
    $dpdb->SqlExecute("
        INSERT INTO access_log
            (timestamp, subject_username, modifier_username, action, activity)
        VALUES
            (UNIX_TIMESTAMP(), '$username', '$actor', 'grant', '$role')");
}

function LogRoleRevoke($username, $role) {
    global $User;
    global $dpdb;
    $actor = $User->Username();
    $dpdb->SqlExecute("
        INSERT INTO access_log
            (timestamp, subject_username, modifier_username, action, activity)
        VALUES
            (UNIX_TIMESTAMP(), '$username', '$actor', 'revoke', '$role')");
}

// vim: sw=4 ts=4 expandtab
