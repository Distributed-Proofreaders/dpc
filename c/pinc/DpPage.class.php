<?PHP
/*
 * Redesign - revise all references to textField
 */

ini_set("display_errors", true);
error_reporting(E_ALL);

if(!isset($relPath))
    $relPath = "./";

require_once $relPath . "rounds.php";
require_once $relPath . "DpVersion.class.php";

// PageStatus values

define("PAGE_AVAIL", "page_avail");
define("PAGE_OUT",   "page_out");
define("PAGE_SAVED", "page_saved");
define("PAGE_BAD",   "page_bad");

class DpPage
{
    protected   $_row;
    protected   $_projectid;
    protected   $_pagename;
    protected   $_project;
	protected   $_versions;
    /** @var  $_last_version DpVersion */
	protected   $_last_version;

    function __construct($projectid, $pagename) {
        global $dpdb;
        if(! $projectid) {
            die( "Projectid argument omitted in DpPage." ) ;
        }
        $this->_projectid   = $projectid;
        $this->_pagename = $pagename;
        if(! $this->_pagename) {
            die( "pagename argument omitted in DpPage ($projectid $pagename)" ) ;
        }
		$args = [&$projectid, &$pagename];
	    $this->_row = $dpdb->SqlOneRowPS(sql_project_page(), $args);
    }

    private function _refresh_row() {
        global $dpdb;
        $projectid = $this->_projectid;
        $pagename  = $this->_pagename;

		$args = [&$projectid, &$pagename];
        $this->_row = $dpdb->SqlOneRowPS(sql_project_page(), $args);
    }

    public function ProjectId() {
        return $this->_projectid;
    }

    public function ImageFile() {
        return $this->Exists() 
            ? $this->_row['imagefile']
            : "";
    }
    public function Image() {
        return $this->ImageFile();
    }

    public function LinkToView($prompt = "View Image") {
        $prompt = _($prompt);
		return link_to_view_image($this->ProjectId(), $this->PageName(), $prompt);
    }

    public function ImageFilePath($filename = "") {
        return $filename == ""
            ? build_path($this->ProjectPath(),
                            $this->ImageFileName())
            : build_path($this->ProjectPath(), $filename);
    }

    public function IsImageFile() {
        return $this->ImageFileName() != ""
            && file_exists($this->ImageFilePath());
    }

    public function ProjectUrl() {
        return "/projects/".$this->ProjectId();
    }

    public function ProjectPath() {
        return ProjectPath($this->ProjectId());
    }

    public function PM() {
        return $this->ProjectManager();
    }

    public function LanguageCode() {
        $p = $this->Project();
        return $p->LanguageCode();
    }

    public function RevertToTemp() {
        $this->_refresh_row();
    }

    public function CanBeReverted() {
        return $this->IsSaved();
    }

    public function CheckoutTime() {
        $ret = $this->_row['checkouttime'] ;
        return $ret ;
    }

    public function CompletionTime() {
        $ret = $this->_row['completiontime'] ;
        return $ret ;
    }

    public function PageStatus() {
	    return $this->State();
    }

    public function PageState() {
	    return $this->Version()->State();
    }
    public function RoundState() {
        return $this->PageRoundState();
    }

    public function PageRoundState() {
        return sprintf("%s.%s", 
                $this->RoundId(), $this->PageStatus());
    }

    protected function Project() {
        if(!$this->_project) {
            $this->_project = new DpProject($this->ProjectId());
        }
        return $this->_project;
    }

    public function Phase() {
        $p = $this->Project();
        return $p->Phase();
    }

    public function RoundIndex() {
        return RoundIndexForId($this->RoundId());
    }

    public function RoundId() {
	    return $this->Phase();
    }

    public function PrevPhase() {
        switch($this->Phase()) {
            case "PREP":
                return null;
            case "P1":
                return "PREP";
            case "P2":
                return "P1";
            case "P3":
                return "P2";
            case "F1":
                return "P3";
            case "F2":
                return "F1";
            case "PP":
                return "F2";
            case "PPV":
                return "PP";
            case "POSTED":
                return "PPV";
            default:
                return "POSTED";
        }
    }

    public function PrevRoundId() {
        $idx = RoundIndexForId($this->RoundId());
        return RoundIdForIndex($idx - 1);
    }

//    public function RoundIndexText($index) {
//        if($index == 0) {
//            $colname = "master_text";
//        }
//        else {
//            $colname = sprintf("round%d_text", $index);
//        }
//        return $this->_row[$colname];
//    }

    protected function PrevUser() {
	    $index = $this->LastVersionNumber();
	    return $this->Version($index-1)->Username();
    }

	public function RoundText($roundid) {
		return $this->PhaseText($roundid);
    }

	public function PhaseText($phase) {

	    $projectid = $this->ProjectId();
	    $pagename = $this->PageName();
		$vnum = $this->PhaseVersionNumber($phase);
		return PageVersionText($projectid, $pagename, $vnum);
    }

	public function PhasePreviousText($phase) {

		$projectid = $this->ProjectId();
		$pagename = $this->PageName();
		$version = $this->PhaseVersionNumber($phase) - 1;
		if($version < 0) {
			return "";
		}
		return PageVersionText($projectid, $pagename, $version);
	}

    public function Text() {
        return $this->ActiveText();
    }

    public function ActiveText() {
	    return $this->LastVersionText();
    }

    public function ActiveHtmlText() {
        return h($this->ActiveText());
    }

    public function NextRoundText() {
        return $this->RoundText($this->ActiveRoundIndex() + 1);
    }

    private function ActiveRoundIndex() {
        return RoundIndexForId($this->RoundId());
    }

    public function NameOfWork() {
        $p = $this->Project();
        return $p->NameOfWork();
    }

    public function AuthorsName() {
        $p = $this->Project();
        return $p->AuthorsName();
    }

    public function InitImageFromPath($path) {
        $topath = $this->ImageFilePath();
        copy($path, $topath);
    }

	/*
	 * Needs to be rewritten entirely
	 */
	public function ReplaceImage($path) {
		if(! file_exists($path)) {
			return;
		}
		$imgpath = $this->ImageFilePath();
		if(file_exists($imgpath)) {
			unlink($imgpath);
		}
        echo "copy $path->$imgpath";
		copy($path, $imgpath);
        $this->LogReplaceImage();
	}

    public function ImageFileName() {
        if(count($this->_row) == 0) {
            return "";
        }
        return $this->_row['imagefile'];
    }

    public function ImageFileSize() {
        return 
        $this->IsImageFile()
            ? filesize($this->ImageFilePath())
            : 0 ;
    }

    public function ImageUrl() {
        return build_path($this->ProjectUrl(), $this->ImageFileName());
    }

    public function PrevImageUrl() {
        global $dpdb;

        $projectid  = $this->ProjectId();
        $pagename   = $this->PageName();

        $sql = "SELECT MAX(pagename) FROM pages
                WHERE projectid = ? AND pagename < ?";
        $args = [ &$projectid, &$pagename ];
        $pagename = $dpdb->SqlOneValuePS($sql, $args);
        if ($pagename == null)
            return null;
        $p = $this->Project();
        $prev = new DpPage($projectid, $pagename);
        return $prev->ImageUrl();
    }
    public function NextImageUrl() {
        global $dpdb;

        $projectid  = $this->ProjectId();
        $pagename   = $this->PageName();

        $sql = "SELECT MIN(pagename) FROM pages
                WHERE projectid = ? AND pagename > ?";
        $args = [ &$projectid, &$pagename ];
        $pagename = $dpdb->SqlOneValuePS($sql, $args);
        if ($pagename == null)
            return null;
        $p = $this->Project();
        $next = new DpPage($projectid, $pagename);
        return $next->ImageUrl();
    }
    public function Exists() {
        return count($this->_row) > 0;
    }

    public function PageName() {
        return $this->_pagename;
    }

    public function ProjectComments() {
        $p = $this->Project();
        return $p->Comments();
    }
    
    public function ProjectManager() {
        $p = $this->Project();
        return $p->ProjectManager();
    }

    public function Title() {
        $p = $this->Project();
        return $p->NameOfWork();
    }

    public function Author() {
        $p = $this->Project();
        return $p->AuthorsName();
    }

//    public function MasterText() {
//        if(! $this->Exists() || $this->MaxVersionNumber() < 0) {
//            return null;
//        }
//	    return $this->VersionText(0);
//    }

    private function DeleteVersions() {
        global $dpdb;
        $projectid = $this->ProjectId();
        $pagename = $this->PageName();
        $dpdb->SqlExecute( "
				DELETE FROM page_versions
				WHERE projectid = '$projectid' AND pagename = '$pagename'");

    }
    private function DeletePage() {
        global $dpdb;
        $projectid = $this->ProjectId();
        $pagename = $this->PageName();
        $dpdb->SqlExecute( "
				DELETE FROM pages
				WHERE projectid = '$projectid' AND pagename = '$pagename'");
    }
	public function Delete() {
        $this->DeleteVersions();
        $this->DeletePage();
        $this->DeleteImageFile();
		$this->LogDelete();
		$this->_refresh_row();
	}

    
    public function CheckOutPage() {
        global $User;
        global $dpdb;

        $projectid  = $this->ProjectId();
        $pagename   = $this->PageName();
        $username   = $User->Username();
        $phase      = $this->Phase();

        // did the user save the most recent version?
        if($this->ActiveUserIsEditor() || $this->IsAvailable() ) {
            $sql = "
                UPDATE page_versions
                SET username = ?,
                    version_time = UNIX_TIMESTAMP(),
                    state = 'O'
                WHERE projectid = ?
                    AND pagename = ?
                    AND phase = ?";

            $args = [&$username, &$projectid, &$pagename, &$phase];
            $dpdb->SqlExecutePS($sql, $args);
            $this->RecalcProjectPageCounts();
            $this->_refresh_row();
            assert($this->State() == "O");
            $this->LogCheckOut();
        }
    }

    protected function TimeField() {
        return TimeFieldForRoundId($this->RoundId());
    }
    protected function UserField() {
        return UserFieldForRoundId($this->RoundId());
    }
    protected function TextField() {
        return TextFieldForRoundId($this->RoundId());
    }

    public function SaveText($text) {
        global $Context;
        return $Context->UpdateLastVersion($this->ProjectId(), $this->PageName(), $this->LastVersionNumber(), $text);
    }

    public function SaveOpenText($text) {
	    global $Context;
	    return $Context->UpdateOpenVersion($this->ProjectId(), $this->PageName(), $this->LastVersionNumber(), "O", $text);
    }

    public function SaveTemp($text) {
	    return $this->SaveOpenText($text);
	}

	public function LastVersion() {
        if(! $this->_last_version) {
            $this->_last_version = new DpVersion( $this->ProjectId(), $this->PageName(), $this->LastVersionNumber() );
        }
		assert($this->_last_version);
		return $this->_last_version;
	}

	public function Version($vnum = -1) {
		if($vnum != $this->LastVersionNumber() && $vnum >= 0) {
			return new DpVersion($this->ProjectId(), $this->PageName(), $vnum);
		}
		return $this->LastVersion();
	}

	public function VersionText($vnum) {
		assert($vnum != "");
		return PageVersionText($this->ProjectId(), $this->PageName(), $vnum);
	}

    public function SaveAsDone($text) {
        global $User, $Context;

        if (!$this->ActiveUserIsEditor()) {
            // If this isn't the person who we think is editing it,
            // then the PM or a PF can still edit it.
            if(!$this->UserIsPM()
            && !$User->IsSiteManager()
            && !$User->IsProjectFacilitator()) {
                $owner = $this->Owner();
                $user = $User->Username();
                $status = $this->PageStatus();
                $phase = $this->Phase();
                $pagename = $this->PageName();
                $ver = $this->LastVersionNumber();
                $projectid = $this->ProjectId();
                return "This page is no longer checked out to you.<br><br>
                    Additional information:
                    Owner=$owner, user=$user, status=$status, phase=$phase,
                    pagename=$pagename, version=$ver, projectid=$projectid";
            }
        }

	    $Context->UpdateOpenVersion($this->ProjectId(), $this->PageName(), $this->LastVersionNumber(), "C", $text);

        $this->LogSaveAsDone();
        //
        // Don't advance here
        // 
        // make a new project - old one may be out of date
        // $this->_project = new DpProject($this->ProjectId());
        // $this->_project->MaybeAdvanceRound();
        return null;
    }

    public function ReturnToRound() {
        global $dpdb;
        $projectid  = $this->ProjectId();
        $pagename   = $this->PageName();
	    $phase      = $this->Phase();

        $sql = "
                UPDATE page_versions
                SET state = 'A',
                	version_time = UNIX_TIMESTAMP(),
                	username = NULL
                WHERE projectid = ?
                	AND pagename = ?
                	AND phase = ?";
	    $args = [&$projectid, &$pagename, &$phase];
        $dpdb->SqlExecutePS($sql, $args);

        $this->LogReturnToRound();
        $this->RecalcProjectPageCounts();
    }

    private function RecalcProjectPageCounts() {
        $proj = $this->Project();
        $proj->RecalcPageCounts();
    }

	public function State() {
		return $this->_row['state'];
	}

    public function ActiveRoundUser() {
        global $User;
        $roundid = $this->RoundId();
        if($roundid == "PREP") {
            return $User->Username();
        }
	    switch($this->State()) {
		    case "O":
		    case "C":
				return $this->_row['username'];
//			    return $this->Version()->Username();
		    default:
			    return "";
	    }
    }

	public function Versions() {
		if(! $this->_versions) {
			$this->_versions = Versions($this->ProjectId(), $this->PageName());
		}
		return $this->_versions;
	}

    public function RoundUser($roundid = "") {
	    global $dpdb;
        if($roundid == "") {
            $roundid = $this->RoundId();
        }
	    $sql = "SELECT username FROM page_versions WHERE phase = '$roundid'";
	    return $dpdb->SqlOneValue($sql);
    }

	public function RoundVersion($roundid) {
		return $this->PhaseVersion($roundid);
	}

	public function PhaseVersion($phase) {
		global $dpdb;
		$projectid = $this->ProjectId();
		$pagename = $this->PageName();

		$sql = "
			SELECT MAX(version) FROM page_versions
			WHERE projectid = ?
				AND pagename = ?
				AND phase = ?";
        $args = [&$projectid, &$pagename, &$phase];
		$vnum = $dpdb->SqlOneValuePS($sql, $args);
		if(is_null($vnum)) {
            $sql = "
                SELECT version FROM page_last_versions
				WHERE projectid = ?
					AND pagename = ?";
            $args = [&$projectid, &$pagename];

			$vnum = $dpdb->SqlOneValuePS($sql, $args);
		}
		return $this->Version($vnum);
	}

	public function PenultimateVersion() {
		static $_obj;
		if(! $_obj) {
			$_obj = new DpVersion($this->ProjectId(), $this->PageName(), $this->PenultimateVersionNumber());
		}
		return $_obj;
	}

	public function PenultimatePhase() {
		return $this->_row['penultimate_phase'];
	}
	public function PenultimateUsername() {
		return $this->_row['penultimate_username'];
	}
	public function PenultimateVersionNumber() {
		return $this->_row['penultimate_version'];
	}
	public function PhaseVersionNumber($phase) {
		$vsn = $this->PhaseVersion($phase);
		return $vsn->VersionNumber();
	}
    public function UserIsOwner() {
        global $User;
        return lower($User->Username()) == lower($this->Owner());
    }
    
    public function Owner() {
	    return $this->LastUsername();
    }

    public function UserIsPM() {
        global $User;
        return lower( $User->Username()) == lower($this->PM() ) ;
    }

	protected function MaxVersionNumber() {
		if(! isset($this->_row['maxversion'])) {
			return - 1;
		}
		return $this->_row['maxversion'];
	}

    public function LastUsername() {
        return $this->LastVersion()->Username();
    }

	public function LastVersionNumber() {
        if( ! isset($this->_row['last_version'])) {
            dump($this->_row);
            die();
        };
        return $this->_row['last_version'];
	}

    // the final text, regardless of current state
    public function LastVersionText() {
	    return PageVersionText($this->ProjectId(), $this->PageName(), $this->LastVersionNumber());
//	    return $this->LastVersion()->Text();
    }

	public function Proofers() {
		return implode(", ", $this->ProofersArray());
	}
    public function ProofersArray() {
        $ary = [];
	    /** @var DpVersion $version */
	    foreach($this->Versions() as $version) {
		    $ary[] = $version->Username();
	    }
	    array_shift($ary);
        return $ary;
    }

    // May the current user select this page for editing?
    // Yes, checked out by them or saved by them in the current round.
    public function MayBeSelectedByActiveUser() {
        global $User;
	    $msgs = [];
	    if(! $this->ActiveUserIsEditor()) {
		    $msgs[] = "?Page is owned by " . $this->Owner() . " and you are " . $User->Username();
		    return $msgs;
	    }
	    return $this->ActiveUserIsEditor() || $this->UserIsPM() || $User->IsSiteManager() ;
    }

    public function UserMayManage() {
        $p = $this->Project();
        return $p->UserMayManage();
    }

    public function UserMayProof() {
        if($this->UserIsOwner()) {
            return true;
        }
        $p = $this->Project();
        return $p->UserMayProof();
    }

    public function MayBeMarkedBadByActiveUser() {
	    return true;
    }

    public function ActiveUserIsEditor() {
        global $User;
	    return lower($this->Owner()) == lower($User->Username())
            && ($this->IsSaved() || $this->IsCheckedOut());
    }

    public function BadReporter() {
        return $this->_row['b_user'];
    }

    // the current user may be assigned this page - ie
    // the page is available, and the user is not the prior user,
    // and the user is qualified,
    protected function IsAvailableForActiveUser() {
        global $User;
        switch($this->Phase()) {
            case "P1":
            case "P2":
            case "P3":
            case "F1":
            case "F2":
                break;
            default:
                return false;
        }

        if($this->IsOnHold()) {
            return false;
        }
        // shouldn't need this - shouldn't be available
        if(preg_match("/^PREP/", $this->Phase()))
            return false;
        if(lower($User->Username()) == lower($this->PrevUser())) {
            return false;
        }
        return true;
    }

    // Page Holds not implemented yet
    public function PageHoldCount() {
        return 0;
    }

    public function ProjectHoldCount() {
        $proj = $this->Project();
        return $proj->ActiveHoldCount();
    }

    public function HoldCount() {
        return $this->PageHoldCount()
            + $this->ProjectHoldCount();
    }

    public function IsOnHold() {
        return $this->HoldCount() > 0;
    }

    // the page phase is P1 - F2, and it's not on hold,
    // but it might be checked out or saved as proofed

    public function IsAvailable() {
        return $this->State() == "A"
        && (! $this->IsOnHold());
    }

    protected function IsCheckedOut() {
        return $this->PageStatus() == "O";
    }

    public function IsSaved() {
        return $this->PageStatus() == "C";
    }
    public function IsBad() {
        return $this->PageStatus() == "B";
    }

    public function MarkBad($reason = "none") {
        global $User;
        global $dpdb;


        $projectid = $this->ProjectId();
        $pagename  = $this->PageName();
	    $phase     = $this->Phase();
	    $username  = $User->Username();

        $dpdb->SqlExecute( "
                UPDATE page_versions
                SET state = 'B'
                WHERE projectid = '$projectid'
                	AND pagename = '$pagename'
                	AND phase = '$phase'");


        $this->LogMarkAsBad();
         $this->SendBadPageEmail($username, $reason);
    }

    private function SendBadPageEmail($username, $reason) {
        global $Context;
        $Context->SendUserEmail($this->PM(),
            "DP Canada "
            ._("notification {$this->Title()} - page marked bad"),
            _("
To the project manager for ".$this->Title().",

User ".$username." has marked page " .$this->PageName()
." as a 'bad page' in this project.

Thanks!

The Administration"));
    }

    public function Clear() {
	    switch ( $this->Phase() ) {
		    case "P1":
		    case "P2":
		    case "P3":
		    case "F1":
		    case "F2":
			    $this->ClearPhase( $this->Phase() );
			    break;
		    case "PP":
			    $this->ClearPP();
			    break;
		    default:
			    // can't clear
			    return;
	    }
    }

	// Unselected pages keep their round and status; which if the project is reverted
    //      means they hold in place until it's this round again.
    // example: p3.available will still be p3 available while the project is backed up in p2.
    // If the page is available in the current round and is cleared,
    //      we need to set it back to checked out to the proofer in the previous round.
    //     example: p3.available beomes p2.checked-out.
    //      and revert the project to the previous round.
    // If the page is checked out or saved in the current round,
    //      we need to set it back to checked out in the previous round.
	// Example: P2 can be cleared while the project is in P2 or P3.
	//      Or I suppose P1 if we've backed it up that far. But we can't consequentially
	//      clear subsequent rounds, so it doesn't make sense to clear P2 if the project is in P3.
    private function ClearPhase($phase) {
        global $dpdb;
	    global $Context;

	    $proj = $this->Project();
	    $projphase = $proj->Phase();
	    if($Context->PhaseSequence($projphase) < $Context->PhaseSequence($phase)){
		    $proj->RevertPhase();
	    }

        $projectid = $this->ProjectId();
	    $pagename  = $this->PageName();
	    $phase     = $this->Phase();
        $sql = "
            UPDATE page_versions
            SET state = 'A',
            	username = NULL,
            	version_time = NULL
            WHERE projectid = ?
            	AND pagename = ?
            	AND phase = ?
            	AND state != 'A'";
	    $args = [&$projectid, &$pagename, &$phase];
        $dpdb->SqlExecutePS($sql, $args);
        $this->LogClearRound($phase);
        $this->RecalcProjectPageCounts();
    }

    /* Note page_last_versions is a view on page_versions:
     *
     * SELECT id, projectid, pagename, version, phase, task,
     *     username, state, version_time, crc32, textlen
     * FROM (
     *     page_versions LEFT JOIN page_versions pv0
     *     ON
     *         projectid = pv0.projectid and
     *         pagename = pv0.pagename and
     *         version < pv0.version
     * ) WHERE ISNULL(pv0.id) 
     *
     * Called from DpProject.next_retrievable_page_for_user()
     * when it determines that there are no more pages available in this
     * round; but one has been checked out for more than four hours.
     * Must set the information back, or our own checkout won't work.
     */
    public function Reclaim() {
        global $dpdb;
        global $User;

        $projectid = $this->ProjectId();
	    $pagename  = $this->PageName();
	    $phase     = $this->Phase();
        $sql = "
            UPDATE page_versions
            SET state = 'A',
                username = NULL,
                version_time = NULL
            WHERE projectid = ?
                and pagename = ?
                and phase = ?
                and state != 'A'";
	    $args = [&$projectid, &$pagename, &$phase];
        $dpdb->SqlExecutePS($sql, $args);
        $this->LogReclaim($phase);
        $this->RecalcProjectPageCounts();
        $this->_refresh_row();
    }

	// special case to return to pre-PP Phase
	// making the final Version available again.
    // Note this doesn't make the page available again, but just marks
    // it checked out again.  Is this correct?  In other phases,
    // ClearPhase marks the page as available, and clears the user.
	private function ClearPP() {
		global $dpdb;

		$projectid  = $this->ProjectId();
		$pagename   = $this->PageName();
		$phase      = "F2"; // NOT current phase!
		$sql = "
            UPDATE page_versions
            SET state = 'A',
            	username = NULL,
            	version_time = NULL
            WHERE projectid = ?
            	AND pagename = ?
            	AND phase = ?
            	AND state = 'C'";
		$args = [&$projectid, &$pagename, &$phase];
		$dpdb->SqlExecutePS($sql, $args);
		$this->LogClearRound("PP");
		$p = $this->Project();
        // true means do not add the F2 user hold. Shouldn't be needed, since
        // the page is cleared, so the project shouldn't move back
        // automatically.
		$p->RevertPhase(false, false);
		$this->RecalcProjectPageCounts();
	}

    private function log_page_event($event_code, $phase) {
	    global $dpdb, $User;

	    $projectid = $this->ProjectId();
	    $pagename = $this->PageName();
	    $version  = $this->LastVersionNumber();
	    $username = $User->Username();

	    $sql = "
        INSERT INTO page_events (
         	event_time,
            projectid,
            pagename,
            version,
            event_type,
            username,
            phase
		)
		values(UNIX_TIMESTAMP(), ?, ?, ?, ?, ?, ?)";

	    $args = [&$projectid, &$pagename, &$version, &$event_code, &$username, &$phase];
	    return $dpdb->SqlExecutePS($sql, $args);
    }

	public static function dp_log_page_event($projectid, $pagename, $event_type,
        $username, $phase, $version = null, $remark = null ) {
		global $dpdb;

		$sql = "
        INSERT INTO page_events (
         	event_time,
            projectid,
            pagename,
            version,
            event_type,
            username,
            phase,
            remark
		)
		values(UNIX_TIMESTAMP(), ?, ?, ?, ?, ?, ?, ?)";
		$args = [
			&$projectid,
			&$pagename,
			&$version,
			&$event_type,
			&$username,
			&$phase,
			&$remark];
		$dpdb->SqlExecutePS($sql, $args);
	}
    protected function LogEraseBadMark() {
        $this->log_page_event( "eraseBadMark", $this->RoundId());
    }
    protected function LogMarkAsBad() {
        $this->log_page_event( "markAsBad", $this->RoundId());
    }
    protected function LogClearRound($roundid) {
        $this->log_page_event( "clearRound", $roundid );
    }
    protected function LogReclaim() {
        $this->log_page_event( "reclaim", $this->RoundId());
    }
    protected function LogReturnToRound() {
        $this->log_page_event( "returnToRound", $this->RoundId());
    }
    protected function LogReOpen() {
        $this->log_page_event( "reopen", $this->RoundId());
    }
    protected function LogSaveAsDone() {
        $this->log_page_event( "saveAsDone", $this->RoundId());
    }
    protected function LogSaveAsInProgress() {
        $this->log_page_event( "saveAsInProgress", $this->RoundId());
    }
    protected function LogCheckOut() {
        $this->log_page_event( "checkout", $this->RoundId());
    }
    protected function LogModifyText() {
        $this->log_page_event( "modifyText", null);
    }
    protected function LogReplaceText() {
        $this->log_page_event( "replaceText", null);
    }
    protected function LogReplaceImage() {
        $this->log_page_event( "replaceImage", null);
    }
    protected function LogDelete() {
        $this->log_page_event( "delete", null);
    }
    protected function LogAdd() {
        $this->log_page_event( "add", null);
    }

    public function ActiveLines() {
        return text_lines($this->ActiveText());
    }
    // accept a new text version from UI, save temp, and check it.
    public function WordCheckText($langcode, $text) {
        // spellcheck the submitted text
//        $this->SaveOpenText($text);
        // break page text into lines

        $ptn = ["/</", "/>/"];
        $rpl = ["&lt;", "&gt;"];
        $text = preg_replace($ptn, $rpl, $text);

        $tlines = text_lines($text);

        // collect lists
	    $fwa = $this->FlagWordsArray($langcode);
        $swa = $this->AcceptedWordsArray($langcode);
        $bwa = $this->BadWordsArray($langcode);

        // for each line
        $nwc = $nwcs = $nwcb = 0;
        //$Log = new DpLog($site_log_path, true);
        global $Log;
        for($i = 0; $i < count($tlines); $i++) {
            $tline = $tlines[$i];
            //
            $rwo = RegexStringsOffsets($tline);
            if(count($rwo) == 0) {
                continue;
            }
            for($j = count($rwo)-1; $j >= 0; $j--) {
                list($wd, $locn) = $rwo[$j];
                if(! in_array($wd, $fwa)) {
                    continue;
                }
                if(in_array($wd, ["sc", "hr", "b", "f", "g"])) {
                    continue;
                }
                if(in_array($wd, $swa)) {
                    $nwcs++;
                    $class = "wcs";
                }
                else if(in_array($wd, $bwa)) {
                    $nwcb++;
                    $class = "wcb";
                }
                else {
                    $nwc++;
                    $class = "wc";
                }
                $tline = bleft($tline, $locn)
                    ."<span id='wc_{$i}_{$j}'"
                    ." class='{$class}'>" .$wd."</span>"
                    .bmid($tline, $locn + strlen($wd));
            }
            $tlines[$i] = $tline;
        }
        return [$nwc, $nwcs, $nwcb, implode("\n", $tlines)];
    }


    public function AcceptWordsArray($langcode, $acceptwords) {
        $p = $this->Project();
        $p->AcceptWordsArray($langcode, $acceptwords);
    }
    public function FlagWordsArray($langcode) {
        /** @var $p DpProject */
        $p = $this->Project();
        return $p->FlagWordsArray($langcode);
    }

    public function AcceptedWordsArray($langcode) {
        /** @var $proj DpProject */
        $proj = $this->Project();
        return $proj->AcceptedWordsArray($langcode);
    }

    public function BadWordsArray($langcode) {
        /** @var $proj DpProject */
        $proj = $this->Project();
        return $proj->BadWordsArray($langcode);
    }

    private function DeleteImageFile() {
        if(! $this->IsImageFile()) {
            return;
        }
        unlink($this->ImageFilePath());
    }


    /* Need to figure out versioning for non-Round changes.
       If Phase == PREP, need at least one Version for edits.
       If Phase is a Round, then
            if state == 'A',
                if preceding task is Admin, update it, resave it, and copy a new A version
                add a Version state A with changes and prev. Task
            if state == 'O', can't make a change
            if state == 'C',
                if last version is PROOF or FORMAT,
                else an ADMIN version state 'C'
    */
    public function ReplaceLineWord($lineindex, $word, $repl) {
        $lines = $this->ActiveLines();
        if(! isset($lines[$lineindex-1])) {
            return;
        }
        $line = $lines[$lineindex-1];
        $lines[$lineindex-1] = preg_replace("/{$word}/", $repl, $line);
        $this->SaveText(implode("\n", $lines));
    }

    public function Tweet() {
        return $this->_row['tweet'];
    }

    public function SetTweet($str) {
        global $dpdb;
        $projectid = $this->ProjectId();
        $pagename  = $this->PageName();
        $this->_row["tweet"] = $str;
        $sql = "UPDATE pages SET tweet = ?
                WHERE projectid = ?
                    AND pagename = ?";
        $args = [&$str, &$projectid, &$pagename];
        $dpdb->SqlExecutePS($sql, $args);
    }
}

function sql_project_page() {
    return "
		    SELECT pg.projectid,
 					pg.pagename,
 					pg.imagefile,
 					pg.tweet,
 					pv.version last_version,
 					pv.phase,
 					pv.version_time,
 					pv.state,
 					pv.username,
					puv.version penultimate_version,
 					puv.phase penultimate_phase,
 					puv.version_time penultimate_version_time,
 					puv.state penultimate_state,
 					puv.username penultimate_username
		    FROM pages pg
		    JOIN projects p
		    	ON pg.projectid = p.projectid
		    JOIN page_last_versions pv
		    	ON pg.projectid = pv.projectid
                    AND pg.pagename = pv.pagename
		    LEFT JOIN page_versions puv
		    	ON pv.projectid = puv.projectid
                    AND pv.pagename = puv.pagename
                    AND pv.version > puv.version
		    LEFT JOIN page_versions pv0
		    	ON pv0.projectid = puv.projectid
                    AND pv0.pagename = puv.pagename
                    AND pv.version > pv0.version
                    AND puv.version < pv0.version
			WHERE pg.projectid = ?
                AND pg.pagename = ?
                AND pv0.id IS NULL";
}

function dp_log_page_event( $projectid, $pagename, $event_type, $phase, $version = null, $remark = null ) {
    global $dpdb, $User;

	$username = $User->Username();

	$sql = "
        INSERT INTO page_events (                     k
         	event_time,
            projectid,
            pagename,
            version,
            event_type,
            username,
            phase,
            remark
		)
		values(UNIX_TIMESTAMP(), ?, ?, ?, ?, ?, ?, ?)";

	$args = [&$projectid, &$pagename, &$version, &$event_type, &$username, &$phase, &$remark];
	$dpdb->SqlExecutePS($sql, $args);
}


function imagefile_to_pagename($str) {
    return preg_replace("/\.[^\.]*$/", "", $str);
}

function RegexStringsOffsets(&$text ) {
    $ptn = "~".UWR."~iu";
    preg_match_all($ptn, $text, $m, PREG_OFFSET_CAPTURE);
    return $m[0];
}

function RegexStringByteOffsets($str, &$text ) {
    preg_match_all($str, $text, $m, PREG_OFFSET_CAPTURE);
    return $m[0];
}


// Class Versions - nothing but statics - virtual namespace

// vim: sw=4 ts=4 expandtab
