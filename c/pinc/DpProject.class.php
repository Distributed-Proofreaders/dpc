<?php
/** @var DpProject $project */

/** @var $User DpThisUser */
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "words.php";
require_once "DpEnchant.class.php";

define("PJ_EVT_CREATE",     "create");
define("PJ_EVT_CLONE",      "clone");
define("PJ_EVT_SET_PM",     "set_pm");
define("PJ_EVT_ACTIVATE",   "activate");
define("PJ_EVT_DEACTIVATE", "deactivate");
define("PJ_EVT_HOLD",       "set_hold");
define("PJ_EVT_RELEASE",    "release_hold");
define("PJ_EVT_SMOOTH",     "smooth_reading");
define("PJ_EVT_SMOOTH_NOTIFY","smooth_notify");
define("PJ_EVT_MSG",        "send_msg");
define("PJ_EVT_REVERT",     "revert");
define("PJ_EVT_COMPLETE",   "set_complete");
define("PJ_EVT_TRANSITION", "transition");
define("PJ_EVT_CHECKOUT",   "checkout");
define("PJ_EVT_POST",       "post");
define("PJ_EVT_POSTED_NOTIFY","posted_notify");
define("PJ_EVT_PP_NOTIFY",  "pp_notify");
define("PJ_EVT_POSTEDNUM",  "set_postednum");
define("PJ_EVT_DELETE",     "delete");
define("PJ_EVT_PP_UPLOAD",  "pp_upload");
define("PJ_EVT_SMOOTH_UNZIP","smooth_unzip");

class DpProject
{
    protected $_projectid;
    protected $_text;
    protected $_row;
//    protected $_pagerows;
//    protected $_pages;
    protected $_page_objects;
    protected $_error_message = "";
//    protected $_page_char_offset_array = [];
    protected $_page_byte_offset_array = [];
    protected $_enchanted_words = [];
    // phases are PREP, P1, P2, P3, F1, F2, PP, PPV, POSTED
    // now correlates with "rounds" table hence RoundIdForIndex etc.
    // soon to be determined by project_sequence taeble
    protected $_phase;

    public function __construct($projectid = null) {
        $this->_projectid = $projectid;
        $this->query_for_row($projectid);
    }

    protected function query_for_row($projectid) {
	    global $dpdb;

	    if ( ! $projectid ) {
		    StackDump();
		    die( "No project id." );
	    }
	    $sql = "
            SELECT
                p.projectid,
                p.username,
                p.phase,
                p.topic_id,
                nameofwork,
                authorsname,
                language,
                seclanguage,
                comments,
                difficulty,
                createdby,
                createtime,
                cp_comments,
                phase_change_date,
                DATE_FORMAT(FROM_UNIXTIME(phase_change_date), '%b %e %Y %H:%i') phase_date,
                DATE_FORMAT(FROM_UNIXTIME(t_last_edit), '%b %e %Y %H:%i') t_last_edit_str,
                t_last_edit,
                scannercredit,
                postednum,
                clearance,
                genre,
                postproofer,
                ppverifier,
                qc_assign,
                postcomments,
                smoothcomments,
                image_source,
                image_link,
                image_preparer,
                text_preparer,
                extra_credits,
                smoothread_deadline,
                n_pages,
                n_available_pages AS available_count,
                n_complete AS completed_count,
                n_checked_out AS checked_out_count,
                n_bad_pages AS bad_count,
                tags,
                DATE(FROM_UNIXTIME(smoothread_deadline)) smoothread_date_string,
                DATEDIFF(
                    DATE(FROM_UNIXTIME(IFNULL(smoothread_deadline, 0))),
                    CURRENT_DATE()) AS smooth_days_left,
				pv.state version_state,
                DATE_FORMAT(FROM_UNIXTIME(MAX(pv.version_time)), '%b %e %Y %H:%i') last_proof_time,
                project_type

            FROM projects AS p
            LEFT JOIN page_last_versions pv
                ON p.projectid = pv.projectid
                AND p.phase = pv.phase
            WHERE p.projectid = ?
            GROUP BY p.projectid";
	    //	    echo html_comment($sql);
	    $this->_row = $dpdb->SqlOneRowPS($sql, [&$projectid]);
        if (empty($this->_row))
            die("Project does not exist: $projectid");
    }

	/* rewrite */
    public function init_text() {
//        global $dpdb;
        $projectid = $this->ProjectId();

//        $sql = "
//            SELECT pagename, version
//            FROM page_last_versions pv
//            WHERE projectid = ?
//            order by pagename";
//	    $args = array(&$projectid);
//        $rows = $dpdb->SqlRowsPS($sql, $args);

        $rows = $this->PageRows();
        $b_offset = 0;
        foreach($rows as $row) {
            $pagename = $row['pagename'];
            $version = $row['version'];
            $imagefile = $row['imagefile'];
            $this->_page_byte_offset_array[$pagename]["offset"] = $b_offset;
            $this->_page_byte_offset_array[$pagename]["version"] = $version;
            $this->_page_byte_offset_array[$pagename]["imagefile"] = $imagefile;
            $text = PageVersionText($projectid, $pagename, $version);
            $this->_text .= ($text . "\n");
            $b_offset = strlen($this->_text);
        }
    }

    public function TextForWords() {
        if(! isset($this->_text)) {
            $this->init_text();
        }
        return $this->_text;
    }

    public function Exists() {
        return count($this->_row) > 0;
    }

    public function isArchived() {
        return is_file($this->ProjectPath());
    }

    public function Refresh() {
	    if($this->Exists()) {
		    $this->query_for_row( $this->ProjectId() );
	    }
    }

	public function VersionState() {
		return  $this->_row['version_state'];
	}
//	public function LastProofTime() {
//		return  $this->_row['last_proof_time'];
//	}
    public function CurrentVersionTime() {
	    return $this->_row['last_proof_time'];
    }

    public function ProjectType() {
        if (empty($this->_row['project_type']))
            return 'normal';
        return $this->_row['project_type'];
    }

    public function IsNormalProject() {
        return $this->ProjectType() == 'normal';
    }

    public function IsHarvestProject() {
        return $this->ProjectType() == 'harvest';
    }

    public function IsRecoveryProject() {
        return $this->ProjectType() == 'recovery';
    }

    public function MayBeProofedByActiveUser() {
        global $User;

        if($this->IsMentorRound()) {
        }
        else if($this->IsMenteeRound()) {
        }
        else {
	        return $User->IsSiteManager()
			   || $User->IsProjectFacilitator()
			   || $this->UserIsPM()
               || $User->NameIs($this->ProjectManager())
               || $User->MayWorkInRound($this->RoundId());
        }
        return false;
    }

    // site manager, PF, or PM
    public function UserMayManage() {
        global $User;

        return $User->IsSiteManager()
                    || $User->IsProjectFacilitator()
				    || $this->UserIsPPer()
				    || $this->UserIsPPVer()
                    || $this->UserIsPM()
	                || (! $this->UserIsPM() && $this->UserCreatedBy());
    }

    public function UserMayProof() {
        global $User;
        return $User->MayWorkInRound($this->RoundId());
    }


    public function UserMaySeeNames() {
        global $User;
        return $this->UserMayManage()
                || $User->MayMentor()
                || $this->UserIsPPer()
                || $this->UserIsPPVer();
    }

    public function IsAvailableForActiveUser() {
        if(! $this->IsAvailable())
            return false;
        return ($this->NextAvailablePage() != null);
    }

    public function AvailableMessageForActiveUser() {
        if(! $this->IsAvailable())
            return _("Project is not available.");
        return $this->IsAvailableForActiveUser()
                ? _("Pages are available.")
                : _("No more pages are available for you.");
    }

	/*
    public function SetModifiedDate() {
        global $dpdb;
        $dpdb->SqlExecute("
                UPDATE projects 
		SET modifieddate = UNIX_TIMESTAMP()
                WHERE projectid = '{$this->ProjectId()}'");
    }
	*/

    public function SetPhaseDate() {
        global $dpdb;
        $projectid = $this->ProjectId();
        $dpdb->SqlExecutePS("
                UPDATE projects SET phase_change_date = UNIX_TIMESTAMP()
                WHERE projectid = ?", [&$projectid]);
    }

//    public function ModifiedDateInt() {
//        return $this->_row['modifieddate'];
//    }

	public function PhaseDate() {
		return $this->_row['phase_date'];
	}
    public function PhaseDateInt() {
        return $this->_row['phase_change_date'];
    }

    public function IsMentorRound() {
        return false;
    }

    public function IsMenteeRound() {
        return false;
    }


    public function UserIsPPVer() {
        global $User;
        return $User->NameIs($this->PPVer());
    }

    public function UserIsPPer() {
        global $User;
	    return $User->NameIs($this->PPer());
    }

    public function ScannerCredit() {
        return $this->_row['scannercredit'];
    }

	public function Tags() {
		return $this->_row['tags'];
	}

    public function IsAvailable() {
        switch($this->Phase()) {
            case "P1":
            case "P2":
            case "P3":
            case "F1":
            case "F2":
                return $this->ActiveHoldCount() == 0;
            default:
                return false;
        }
    }

    public function SetSmoothDeadlineDays($days) {
        global $dpdb;
        $projectid = $this->ProjectId();
        $currentdays = $this->SmoothDaysLeft();
        $v = $this->_row['smooth_days_left'];

        $sql = "
            UPDATE projects
            SET smoothread_deadline = UNIX_TIMESTAMP(
                DATE_ADD(CURRENT_DATE(), INTERVAL $days DAY))
            WHERE projectid = ?";
        $dpdb->SqlExecutePS($sql, [&$projectid]);
        $this->LogProjectEvent(PJ_EVT_SMOOTH, "Set deadline days to $days");
        $this->Refresh();

        // Note smooth_days_left is negative, if smoothread_deadline is NULL
        if ((empty($currentdays) || $currentdays < 0) && !empty($days)) {
            $this->LogProjectEvent(PJ_EVT_SMOOTH_NOTIFY, "Smooth deadline set, notifying waiters");
            list($subject, $email) = $this->ConstructSmoothMsg();
            $this->Notify('smooth', $this->PPer(), $subject, $email);
        }
    }

    public function Notify($event, $from, $subject, $email) {
        global $dpdb;
        $projectid = $this->Projectid();
        if (empty($from)) {
            $from = $this->PM();

            if (empty($from))
                die("PM and PPer is not set!");
        }

        $sql = "
            SELECT username
            FROM notify
            WHERE projectid=? AND event=?
        ";
        $sentTo = array();
        foreach ($dpdb->SqlValuesPS($sql, [&$projectid, &$event]) as $to) {
            $this->SendPM($from, $to, $subject, $email, $event);
            $sentTo[] = $to;
        }
        return $sentTo;
    }

    public function ConstructSmoothMsg()
    {
        $projectid = $this->ProjectId();
        $surls = ProjectSmoothDownloadUrls($projectid);
        $title = $this->Title();
        $author = $this->Author();
        $days = $this->SmoothDaysLeft();
        $viewlink = link_to_view_text_and_images($projectid, "view the latest text and images online");
        $viewlink = str_replace("\n", "", $viewlink);   // remove \n
        $projecturl = $this->ProjectLink('this project');
        $projecturl = str_replace("\n", "", $projecturl);

        $links = array();
        foreach ($surls as $fmt => $url) {
            $links[] = link_to_url($url, $fmt);
        }
        $links_str = " · " . implode(" · ", $links);
        $subject = "Available for Smooth Reading -- $title ($author)";
        $msg = "
        You are receiving this PM because you asked to be informed when $projecturl became available for Smooth Reading.

        $title (by $author)

        Available for Smooth Reading for $days days.

        Download your preferred format:

        $links_str

        If you want to check something closely, you can $viewlink.

        Thank you!";

        return array($subject, $msg);
    }

    public function ConstructPostedMsg()
    {
        $title = $this->Title();
        $author = $this->Author();
        $projecturl = $this->ProjectLink('this project');
        $projecturl = str_replace("\n", "", $projecturl);
        $postednum = $this->PostedNumber();
        $fadedpageurl = link_to_fadedpage_catalog($postednum, "view the posted details on Fadedpage at $postednum");
        $fadedpageurl = str_replace("\n", "", $fadedpageurl);

        $subject = "Now Posted to Fadedpage -- $title ($author)";
        $msg = "
        You are receiving this PM because you asked to be informed when $projecturl was posted to Fadedpage.

        $title (by $author)

        You can $fadedpageurl.

        Thank you!";

        return array($subject, $msg);
    }

    public function ConstructPPMsg()
    {
        $title = $this->Title();
        $author = $this->Author();
        $projecturl = $this->ProjectLink('this project');
        $projecturl = str_replace("\n", "", $projecturl);

        $subject = "Transition to PP -- $title ($author)";
        $msg = "
        You are receiving this message because you are either the PP or PM for this project.

        $title (by $author): $projecturl has transitioned to PP.

        Thank you!";

        return array($subject, $msg);
    }

    public function ConstructP1QueueReleaseMsg()
    {
        $title = $this->Title();
        $author = $this->Author();
        $projecturl = $this->ProjectLink('this project');
        $projecturl = str_replace("\n", "", $projecturl);

        $subject = "Released to P1 -- $title ($author)";
        $msg = "
            You are receiving this message because you asked to be informed when
            $projecturl was released from the P1 Queue, or you are the PM.

            $title (by $author): $projecturl has been released from the P1 Queue and is ready for proofing.

            Thank you!";

        return array($subject, $msg);
    }

    public function SendPM($from, $to, $subject, $msg, $event) {
        $this->LogProjectEvent(PJ_EVT_MSG, "Send PM to $to on event $event from $from");
        DpContext::SendForumMessage($from, $to, $subject, $msg);
    }

    public function SmoothDaysLeft() {
        return $this->_row["smooth_days_left"];
    }

    public function IsAvailableForSmoothReading() {
        return $this->SmoothDaysLeft() >= 0;
    }

	public function SmoothUploadedFiles() {
		$path = build_path($this->ProjectPath(), "*_smooth_done_*");
		$ary = [];
		foreach(glob($path) as $filepath) {
			$ary[] = basename($filepath);
		}
		return $ary;
	}

    public function UserSmoothreadCommit() {
        global $dpdb, $User;
        $username = $User->Username();
        $projectid = $this->ProjectId();
        $dpdb->SqlExecutePS("
        REPLACE INTO smoothread
        SET projectid = ?,
            username = ?",
            [&$projectid, &$username]);
    }

    public function UserSmoothreadWithdraw() {
        global $dpdb, $User;
        $username = $User->Username();
        $projectid = $this->ProjectId();
        $dpdb->SqlExecutePS("
        DELETE FROM smoothread
        WHERE projectid = ?
            AND username = ?",
        [&$projectid, &$username]);
    }

    public function CommittedSmoothReaders() {
        global $dpdb;
        $projectid = $this->ProjectId();
        $args = [&$projectid];
        return $dpdb->SqlValuesPS(
            "SELECT username FROM smoothread
            WHERE projectid = ?",
            $args);
    }
    public function UserIsCommittedToSmoothread() {
        global $dpdb, $User;
        $projectid = $this->ProjectId();
        $username = $User->Username();
        $sql = "
            SELECT COUNT(1) FROM smoothread
            WHERE projectid = ?
                AND username = ?";
        $args = [&$projectid, &$username];
        $n = $dpdb->SqlOneValuePS($sql, $args);
        return $n > 0;
    }

    public function SmoothreadDeadline() {
        return $this->_row['smoothread_deadline'];
    }

    public function SmoothDirectoryPath() {
        return build_path($this->ProjectPath(), "smooth");
    }

//    public function SmoothDirectoryPath() {
//        return build_path(ProjectPath($this->ProjectId()), "smooth");
//    }

    public function SmoothreadDateString() {
        return $this->_row['smoothread_date_string'];
    }

    public function SmoothZipFilePath() {
        return build_path($this->ProjectPath(), $this->SmoothZipFileName());
    }

    public function SmoothZipFileName() {
       return $this->ProjectId() . "_smooth_avail.zip";
    }
    public function IsRoundCompleted() {
	    return $this->UncompletedCount() == 0;
//        return count($this->AdvanceValidateErrors()) == 0;
    }

//    public function IsSmoothDownloadFile() {
//        return file_exists($this->SmoothDownloadPath("zip"));
//    }

    public function IsSmoothDownloadFiles() {
        return count($this->SmoothDownloadFileNames()) > 0;
    }

    public function SmoothDownloadPaths() {
        $dir = build_path($this->ProjectPath(), "smooth");
        $ary = glob($dir);
        $rslt = [];
        foreach($ary as $path) {
            if(is_dir($path)) {
                continue;
            }
            $type = extension($path);
            $rslt[$type] = $path;
        }
        return $rslt;
    }

//	public function SmoothDownloadUrl($filename) {
//		return build_path(ProjectSmoothDownloadUrl($this->ProjectId()), $filename);
//	}

    public function SmoothZipUploadPath() {
        global $User;
        $username = $User->Username();
        $projectid = $this->ProjectId();
//        return ProjectSmoothZipUploadPath($this->ProjectId(), $User->Username());
    return build_path(ProjectPath($projectid), $projectid
                    . "_smooth_done_{$username}.zip");
    }

	public function SmoothUploadFilename() {
		return ProjectSmoothUploadFilename($this->ProjectId());
	}

    public function LogPPUpload($filename) {
        $this->LogProjectEvent(PJ_EVT_PP_UPLOAD, "PP upload ($filename)");
    }
    public function LogSmoothDownload() {
        $this->LogProjectEvent(PJ_EVT_SMOOTH, "smooth reader download");
    }

    public function LogSmoothDone() {
        $this->LogProjectEvent(PJ_EVT_SMOOTH, "smooth reader returned (done)");
    }

    // =====================================================

    public function SmoothTextFilePath() {
        foreach($this->SmoothDownloadFileNames() as $name) {
            if(extension($name) == "txt") {
                return build_path($this->SmoothDirectoryPath(), $name);
            }
        }
        return "";
    }

    public function SmoothDirectoryUrl() {
        return build_path($this->ProjectUrl(), "smooth");
    }

    private function SmoothDownloadFilePaths() {
        $path = build_path($this->SmoothDirectoryPath(), "*");
        $ary = glob($path);
        $key = array_search("images", $ary);
        if($key !== false) {
            unset($ary[$key]);
        }
        return $ary;
    }

    public function SmoothDownloadFileNames() {
        $ary = [];
        foreach($this->SmoothDownloadFilePaths() as $path) {
            if(basename($path) != "images") {
                $ary[] = basename($path);
            }
        }
        return $ary;
    }

    public function SmoothDownloadUrls($projectid) {
        $dir = build_path($this->SmoothDirectoryPath(), "*");
        $ary = glob("$dir");
        $rslt = [];
        $smoothurl = build_path(ProjectUrl($projectid), "smooth");
        foreach($ary as $path) {
            if(is_dir($path)) {
                continue;
            }
            $filename = basename($path);
            $type = extension($path);
            $url = build_path($smoothurl, $filename);
            $rslt[$type] = $url;
        }
        return $rslt;
    }

    public function MaybeUnzipSmoothZipFile() {
//        if(! is_file($this->SmoothZipFilePath()))
//            return;
//        $dest = $this->SmoothDirectoryPath();
//        if(! file_exists($dest)) {
//            mkdir($dest);
//            chmod($dest, 0777);
//        }
        $this->UnzipSmoothZipFile();
    }

    private function UnzipSmoothZipFile() {
        if(! file_exists($this->SmoothZipFilePath())) {
            die("Cannot unzip {$this->SmoothZipFilePath()}");
        }
        $dest = $this->SmoothDirectoryPath();

        if(! file_exists($dest)) {
            mkdir($dest);
            chmod($dest, 0777);
        }
        $zip = new ZipArchive();
        $zip->open($this->SmoothZipFilePath());
        $zip->extractTo($dest);
        $this->fix_smooth_paths();
        $zip->close();

        global $User;
        $username = $User->Username();
        $this->LogProjectEvent(PJ_EVT_SMOOTH_UNZIP, "$username unzip smooth file to $dest");
    }

    private function fix_smooth_paths() {
        $dest = $this->SmoothDirectoryPath();
        $paths = glob("$dest/*");
        foreach($paths as $path) {
            $pre = rootname($path);
            $fixed = nice_filename($pre);
            $ext = extension($path);
            $newpath = build_path($dest, "$fixed.$ext");
            switch($ext) {
                case "txt":
                case "epub":
                case "html":
                case "pdf":
                case "mobi":
                    if($path != $newpath) {
                        rename($path, $newpath);
                        dump("$path $newpath");
                    }
                    break;
                default:
                    break;
            }
        }
    }

    public function SmoothComments() {
        return $this->_row['smoothcomments'];
    }
    public function SetSmoothComments($str) {
        global $dpdb;
        $projectid = $this->ProjectId();
        $sql = "UPDATE projects
                SET smoothcomments = ?
                WHERE projectid = ?";
        $args = [&$str, &$projectid];
        $dpdb->SqlExecutePS($sql, $args);
        $this->Refresh();
    }
    // =====================================================

    public function Credits() {
        global $site_name;

        $credits = [];
        if($this->ProjectManager() != "")
            $credits[$this->ProjectManager()] = true;
        if($this->TextPreparer() != "")
            $credits[$this->TextPreparer()] = true;
        if($this->ExtraCredits() != "")
            $credits[$this->ExtraCredits()] = true;
        if($this->ScannerCredit() != "")
            $credits[$this->ScannerCredit()] = true;
        if($this->ImagePreparer() != "")
            $credits[$this->ImagePreparer()] = true;

        if($this->PPer() != "")
            $credits[$this->PPer()] = true;
        if($this->PPVer() != "")
            $credits[$this->PPVer()] = true;

        if(count($credits) == 0) {
            return _("The team at ") . $site_name;
        }
	    $aret = array_unique(array_keys($credits));
		return implode(", ", $aret)
                    .  _(" and the team at ") . $site_name;
    }

    public function Phase() {
	    if(! isset($this->_row['phase'])) {
		    dump($this->_row);
		    exit;
	    }
        return $this->_row['phase'];
    }

	public function PhaseSequence() {
		global $Context;
		return $Context->PhaseSequence($this->Phase());
	}

    public function PhaseIndex() {
        global $Context;
        return $Context->PhaseIndex($this->Phase());
    }
    public function RoundIndex() {
        return RoundIndexForId($this->RoundId());
    }

    public function RoundId() {
        if(! $this->Exists()) {
            return "";
        }
        switch($this->Phase()) {

        }
	    return $this->Phase();
    }

    public function RoundDescription() {
        return RoundIdDescription($this->Phase());
    }
    public function RoundName() {
        return RoundIdName($this->Phase());
    }

    public function PageStateCounts() {
        static $ary;
        if(! isset($ary)) {
            $ary      = ["A"    => $this->_row["available_count"],
                         "O"   => $this->_row["checked_out_count"],
                         "C"    => $this->_row["completed_count"],
                         "B"          => $this->_row["bad_count"],
                         "total"        => $this->_row["n_pages"]];
        }
        return $ary;
    }

/*
    To advance:

*/
    // move a project from a completed round to the next
    // and set page records appropriately

    public function AdvanceValidateErrors() {
        $msgs = [];

	    // freshen counts in projects row
        $this->RecalcPageCounts();
        // if there is a Hold active, no advance.
        if($this->ActiveHoldCount() > 0) {
            $msgs[] = "On Hold";
        }

	    $phase = $this->Phase();

        switch($phase) {
            // special handling for PREP -
            // must have PM, Clearance, Pages, and no holds (above)
            case "PREP":
                if($this->ProjectManager() == "") {
                    $msgs[] = "No PM assigned";
                }
                if($this->Clearance() == "") {
                    $msgs[] = "No Clearance";
                }
                // Only need pages if we are a normal project
                if($this->IsNormalProject() && $this->PageCount() < 1) {
                    $msgs[] = "No pages";
                }
                break;

            case "P1":
            case "P2":
            case "P3":
            case "F1":
            case "F2":
                if($this->PageCount() == 0 && $this->IsNormalProject()) {
                    $msgs[] = "No page texts in $phase";
                }
                if($this->UncompletedCount() != 0) {
                    $msgs[] = "Pages are uncompleted";
                }
                break;

            case "PP":
                if($this->PPer() == "") {
                    $msgs[] = "No PPer";
                }
                if(! $this->IsPPUploadFile()) {
                    $msgs[] = "No Uploaded zip file.";
                }
                if($this->IsAvailableForSmoothReading()) {
                    $msgs[] = $this->SmoothDaysLeft();
                    $msgs[] = $this->SmoothreadDeadline();
                    $msgs[] = "Still open for smooth reading.";
                }
                break;

            case "PPV":
                if($this->PostedNumber() == "") {
                    $msgs[] = "No Posted Number";
                }
                if(strlen($this->PostedNumber()) != 8) {
                    $msgs[] = "Incorrect Posted Number";
                }
                if($this->PPVer() == "") {
                    $msgs[] =  "No PPVer";
                }
                break;

            case "POSTED":
                break;

            case "DELETED":
                $msgs[] = "Deleted project";
                break;
        }

        return $msgs;
    }

    private function newPhase($to_phase, $task) {
        global $dpdb;

        try {
            $dpdb->beginTransaction();
            $this->SetPhase($to_phase);
            if (!$this->ClonePageVersions( $to_phase, $task, "A"))
                throw new Exception($this->_projectid . ": Cloning pages to $to_phase failed");
            $dpdb->commit();
        } finally {
            $dpdb->rollback();
            // SetPhase probably changed our phase, get our data correct
            $this->refresh();
        }
    }

	/*
	 * Called from
	 *      Project Page
	 *      When a PPV checkes it out (prob. wrong)
	 *      In $this->ReleaseHoldId
	 *          and $this->ReleasePhaseHold
	 *      Whena PM is assigned
	 *      When a Posted # is assigned
	 *      Trace page test button
	 *      in DpPage->SaveText which is used for editing actions
	 */
    public function MaybeAdvanceRound() {
        global $Context;
        global $dpdb;

        if($this->IsPosted()) {
            return null;
        }
        // the following checks that all pages are completed
        $msgs = $this->AdvanceValidateErrors();

        if(count($msgs) > 0) {
            return $msgs;
        }

        /*
         * For all cases:
         * UPDATE project_pages SET status = 'page_avail' WHERE projectid = '$projectid'
         */

        $to_phase   = $Context->PhaseAfter($this->Phase());

        // Recovery and Harvest go directly to PP
        if (!$this->IsNormalProject()) {
            switch ($this->Phase()) {
            case "PREP":
                $this->SetPhase("PP");
                break;
            default:
                $this->SetPhase($to_phase);
                break;
            }
        } else
        switch($this->Phase()) {
            case "PREP":
            case "P1":
            case "P2":
                $this->newPhase($to_phase, "PROOF");
                break;
            case "P3":
            case "F1":
                $this->newPhase($to_phase, "FORMAT");
                break;

            case "F2":
            case "PP":
            case "PPV":
                $this->SetPhase($to_phase);
                break;

            case "PPV":
                $this->SetPhase($to_phase);
                break;

            case "POSTED":
                break;

            default:
                assert(false);
                StackDump();
                die("Advance from unresolved round - {$this->RoundId()}.") ;
        }

        $this->RecalcPageCounts();
        $this->Refresh();

//	$this->CopyPagesToNewTable();
//      $this->validatePageState();
//      $this->validatePageState();
    }

	public function PageNames() {
		static $_names;
		if ( ! $_names ) {
            global $dpdb;
            $pid = $this->ProjectId();
            $sql = "
				SELECT DISTINCT pagename FROM page_last_versions
				WHERE projectid = ?
				ORDER BY pagename";
            $args = [&$pid];
            $_names = $dpdb->SqlValuesPS($sql, $args);
		}
		return $_names;
	}

    public function ImageFileForPageName($pagename) {
        return $this->_page_byte_offset_array[$pagename]["imagefile"];
    }
    public function LastPageVersion($pagename) {
        return $this->_page_byte_offset_array[$pagename]["version"];
    }

    /* Unused (by pager) */
    public function RoundPageVersions($roundid) {
        global $dpdb;
        $projectid = $this->ProjectId();
        return $dpdb->SqlRowsPS("
			SELECT pv.pagename, pv.version, pp.imagefile
			FROM page_versions pv
			JOIN pages pp
            ON pv.projectid = pp.projectid
                AND pv.pagename = pp.pagename
			WHERE pv.projectid = ?
			    AND pv.phase = ?
			    AND pv.task IN ('PROOF', 'FORMAT')
			ORDER BY pv.pagename", [&$projectid, &$roundid]);
    }

	// Given a project, ad a new version for every page with phase, task, version state
	private function ClonePageVersions( $phase, $task, $state, $username = "(auto)") {
		global $dpdb;
		// first add the database rows.
		// We then have a version row without a text file

		$projectid = $this->ProjectId();

		$rows = $dpdb->SqlRowsPS("
			SELECT projectid, pagename, version, crc32, textlen
			FROM page_last_versions
			WHERE projectid = ?", [&$projectid]);

		foreach($rows as $row) {
			$pagename = $row['pagename'];
			$version = $row['version'];
			if($this->ClonePageVersionFile( $projectid, $pagename, $version )) {
				if(! $this->ClonePageVersionRow( $row, $phase, $task, $state, $username )) {
					return false;
				}
			}
			else {
				return false;
			}
		}
		return true;
	}

	private function ClonePageVersionRow( $row, $phase, $task, $state, $username = "(auto)" ) {
		global $dpdb, $User;
		$projectid  = $this->ProjectId();
		$pagename   = $row['pagename'];
		$version    = $row['version'];
		$newversion = $version + 1;
		$crc32      = $row['crc32'];
		$textlen    = $row['textlen'];
		if($username == "") {
			$username = $User->Username();
		}

		$this->ClonePageVersionFile( $projectid, $pagename, $version );

		$sql = "REPLACE INTO page_versions
					(projectid, pagename, version, phase, task, username, state, version_time, dateval, crc32, textlen)
				VALUES
				 	(?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), CURRENT_DATE(), ?, ?)";

		$args = [&$projectid, &$pagename, &$newversion, &$phase, &$task, &$username, &$state, &$crc32, &$textlen];
		return $dpdb->SqlExecutePS($sql, $args);
	}
	private function ClonePageVersionFile( $projectid, $pagename, $version ) {
		$from_path = PageVersionPath($projectid, $pagename, $version);
		$to_path   = PageVersionPath($projectid, $pagename, $version + 1);
        if (copy($from_path, $to_path) === FALSE)
            // Note error_get_last() is returning null, don't know why.
            // Removed @ from before copy(), so that Warning will include
            // the error message
            throw new Exception($this->_projectid . ": Text File copy page $pagename, version $version failed.");
        return TRUE;
	}

    public function History() {
        global $dpdb;
        static $ahistory;
        if(! isset($ahistory)) {
            $rows = $dpdb->SqlRows("
                SELECT projectid,
					DATE_FORMAT(FROM_UNIXTIME(event_time), '%b %e %Y %H:%i') event_time,
                    username,
                    event_type,
                    phase,
                    to_phase,
                    details1,
                    details2
                FROM project_events
                WHERE projectid = '{$this->ProjectId()}'
                ORDER BY project_events.event_time");
            foreach($rows as $row) {
                $ahistory[] = new DpEvent($row);
            }
        }
        return $ahistory;
    }

    function FirstRoundId() {
        return RoundIdForIndex(1);
    }

    function NextRoundDescription() {
        return RoundIdDescription($this->NextRoundId());
    }


    public function ProjectLink($prompt) {
        return link_to_project($this->ProjectId(), $prompt);
    }

    public function ProjectUrl() {
        global $projects_url;
        return "$projects_url/{$this->ProjectId()}";
    }

    public function PPer() {
        return $this->_row['postproofer'];
    }

    public function PPCheckout() {
        global $User;
        $username = $User->Username();
        $this->SetPPer($username);
        $this->LogProjectEvent(PJ_EVT_CHECKOUT, "to post-process");
    }
    public function PPUncheckout() {
        $this->ClearPPer();
        $this->LogProjectEvent(PJ_EVT_REVERT, "to post-process");
    }
    public function PPSetComplete() {
        global $User;
        $msgs = [];
        assert($this->Phase() == "PP");
        if(! $User->MayPP()) {
            $msgs[] = "User not a PPer.";
        }
        if(! $this->IsPPUploadFile()) {
            $msgs[] = "No PP uploaded project file";
        }
        if(count($msgs) > 0) {
            return $msgs;
        }
        $this->ReleasePPHold();
        $this->ClearUserHold("PP");
        $this->SetPhase("PPV");
        return [];
    }

    public function SetPPer($username) {
        global $dpdb;
        $projectid = $this->ProjectId();
        $dpdb->SqlExecutePS("
            UPDATE projects
            SET postproofer = ?
            WHERE projectid = ?", [&$username, &$projectid]);
        $this->_row['postproofer'] = $username;
    }

    public function ClearPPer() {
        global $dpdb;
        $projectid = $this->ProjectId();
        $dpdb->SqlExecutePS("
            UPDATE projects
            SET postproofer = ''
            WHERE projectid = ?", [&$projectid]);
        $this->_row['postproofer'] = "";
    }

    public function SetPPVer($username) {
        global $dpdb;
        $projectid = $this->ProjectId();
        $dpdb->SqlExecutePS("
            UPDATE projects
            SET ppverifier = ?
            WHERE projectid = ?", [&$username, &$projectid]);
        $this->_row['ppverifier'] = $username;
    }

    public function PPVCheckout() {
        global $dpdb, $User;
        $username = $User->Username();
        $projectid = $this->ProjectId();

        $dpdb->SqlExecutePS("
            UPDATE projects
            SET ppverifier = ?
            WHERE projectid = ?", [&$username, &$projectid]);

        $this->MaybeAdvanceRound();

    }
    public function PPVUncheckout() {
        $this->ClearPPVer();
        if($this->Phase() == "PPV") {
	        $this->SetPhase("PP");
        }
    }

    public function PPVSetComplete() {
        global $User;
        $msgs = [];
        assert($this->Phase() == "PPV");
        if(! $User->MayPPV()) {
            $msgs[] = "User not a PPVer.";
        }
        if($this->PostedNumber() == "") {
            $msgs[] = "No Posted Number";
        }
        if(count($msgs) > 0) {
            return $msgs;
        }

        $this->ClearUserHold("PPV");
        return [];
    }

    public function ClearPPVer() {
        global $dpdb;
        assert($this->Phase() == "PPV");
        $projectid = $this->ProjectId();
        $dpdb->SqlExecutePS("
            UPDATE projects
            SET ppverifier = ''
            WHERE projectid = ?", [&$projectid]);
        $this->_row['ppverifier'] = '';
    }

    public function PPVer() {
        return $this->_row['ppverifier'];
    }

    public function QCAssign() {
        return $this->_row['qc_assign'];
    }

    public function SetQCAssign($username) {
        global $dpdb;
        $projectid = $this->ProjectId();
        $dpdb->SqlExecutePS("
            UPDATE projects
            SET qc_assign = ?
            WHERE projectid = ?", [&$username, &$projectid]);
        $this->_row['qc_assign'] = $username;
    }

    public function UploadPath() {
        return ProjectUploadPath($this->ProjectId());
    }

//    public function LoadFilePath() {
//        return $this->UploadPath();
//    }

//    private function RoundsDownloadPath() {
//        return ProjectRoundsDownloadPath($this->ProjectId());
//    }

//     function IsPPDownloadFile() {
//        return file_exists($this->PPDownloadPath());
//    }

    public function LastCompletedText() {
        global $dpdb;
        $projectid = $this->ProjectId();
        $sql = "SELECT pagename, version
                FROM page_last_completed_versions
                WHERE projectid = ?
                ORDER BY pagename";
        $args = [&$projectid];
        $pgs = $dpdb->SqlRowsPS($sql, $args);
        $text = "";
        foreach($pgs as $pg) {
            $name = $pg['pagename'];
            $vsn = $pg['version'];
            $text .= (ExportPageHeader($pg['pagename'], null) . "\n");
            $text .= PageVersionText($this->ProjectId(), $name, $vsn) . "\n";
        }
        return $text;
    }

    /*
     * $include can be nothing, separator, names, or pagetag
     * $exact is true or false
     * $pagetags is true or false
     */
	public function PhaseExportText($phase, $include = "nothing", $exact = false) {
		global $dpdb;
        switch($phase) {
            case "PP":
            case "PPV":
            case "POSTED":
                $phase = "F2";
                break;

            default:
                break;
        }
        $projectid = $this->ProjectId();
		$sql = "SELECT
					pv.pagename,
					pp.imagefile,
					pv.version,
					pv.state,
					pv2.proofernames
			    FROM page_versions pv
			    JOIN pages pp
			    ON pv.projectid = pp.projectid
			        AND pv.pagename = pp.pagename
                JOIN (
                    /* Make sure we get only the largest version of the page */
                    SELECT MAX(version) version, pagename from page_versions
                    WHERE projectid = ? AND phase = ?
                    GROUP BY pagename
                ) pv1 ON pv.version = pv1.version AND pv.pagename = pv1.pagename
			    LEFT JOIN (
                    SELECT  projectid,
                            pagename,
                            GROUP_CONCAT(username) proofernames
                    FROM page_versions
                    WHERE state = 'C'
                        AND projectid = ? /* Redundant, but fixes full scan */
                    GROUP BY projectid, pagename
                    ORDER BY VERSION
                ) pv2
                ON pv.projectid = pv2.projectid
                    AND pv.pagename = pv2.pagename
			    WHERE pv.projectid = ?
			    	AND pv.phase = ?
			    	AND pv.task IN ('PROOF', 'FORMAT', 'LOAD')
		        ORDER BY pv.pagename";
        // Note that we've added an extra projectid=? to the inner left join,
        // so it doesn't full scan the huge page_versions table. 15 seconds
        // before, zero after!
        $args = [&$projectid, &$phase, &$projectid, &$projectid, &$phase];
		$pgs = $dpdb->SqlRowsPS($sql, $args);

		$text = "";
		foreach($pgs as $pg) {
            $name = $pg["pagename"];
            $version  = (integer) $pg["version"];
            if ($exact && $pg['state'] != "C") {
                continue;
            }
            if($include == "nothing") {
                $text .=  PageVersionText($this->ProjectId(), $name, $version) . "\n";
                continue;
            }

            // $include is "separator" or "names"
            $names = explode(",", $pg["proofernames"]);

            $img = $pg['imagefile'];
			$text .= ($include == "separator")
                        ? ExportPageHeader($pg['pagename'], null, $img) . "\n"
                        : ExportPageHeader($pg['pagename'], $names, $img) . "\n";
            if($include == "pagetag") {
                $text .= PageTag($pg['imagefile']);
            }
            $text .= PageVersionText($this->ProjectId(), $name, $version) . "\n";
		}
		return $text;
	}

/*
 * problem - excludes last versions not completed
	public function ExportPPText() {
		global $dpdb;
		$projectid = $this->ProjectId();
		$sql = "
			SELECT pagename, version
			FROM page_last_versions
			WHERE projectid = ?
			    AND state = 'C'";
        $args = array(&$projectid);
		$rows = $dpdb->SqlRowsPS($sql, $args);
		$text = "";
		foreach($rows as $row) {
			$pagename = $row['pagename'];
			$version  = $row['version'];
			$text .= ExportPageHeader($pagename) . "\n";
			$text .= PageVersionText($projectid, $pagename, $version) . "\n";
		}
		return $text;
	}

    public function ExportRoundText( $roundid ) {
	    global $dpdb;

        $projectid = $this->ProjectId();

	    $sql = "SELECT
					pagename,
					version
			    FROM page_versions
			    WHERE projectid = ?
			        AND phase = ?
			        AND task IN ( 'PROOF', 'FORMAT' )
		        ORDER BY pagename";
        $args = array(&$projectid, &$roundid);
	    $pgs = $dpdb->SqlRowsPS($sql, $args);
	    $text = "";
	    foreach($pgs as $pg) {
		    $name = $pg['pagename'];
		    $vsn = $pg['version'];
		    $text .= (ExportPageHeader($pg['pagename']) . "\n");
		    $text .= PageVersionText($this->ProjectId(), $name, $vsn);
	    }
	    return $this->PhaseExportText($roundid);
    }

    public function SavePPDownloadFile() {
        global $Context;
	    $text = $this->PhaseExportText("F2") ;
        if($this->IsPPDownloadFile()) {
            unlink( $this->PPDownloadPath() );
        }
        $Context->ZipSaveString($this->ProjectId() . ".txt", $text);
    }
*/

//    private function PPDownloadPath() {
//        return $this->RoundsDownloadPath();
//    }

    // will need to be modified to return the rounds specifically for this project
    public function Rounds() {
        global $dpdb;
        return $dpdb->SqlValues("
                SELECT roundid FROM rounds
                ORDER BY round_index");
    }

    public function PPUploadPath() {
        return ProjectPPUploadPath($this->ProjectId());
    }
    public function PPVUploadPath() {
        return ProjectPPVUploadPath($this->ProjectId());
    }

    private function IsPPUploadFile() {
        return file_exists($this->PPUploadPath());
    }

    private function ImageFilesInProjectDirectory() {
        $path = $this->ProjectPath();
        return glob("$path/*");
    }

	// delete a file from the project directory
    public function DeleteProjectFile($filename) {
	    if($filename) {
		    return;
	    }
        $path = build_path($this->ProjectPath(), $filename);
        unlink($path);
    }

    // cached as $this->_pages
    /*
     * only used in cleanup.php and guiprep.php
     */
    public function PageObjects() {
        global $dpdb;

	    if(! isset($this->_pages)) {
			$sql = "SELECT
						p.projectid,
						pp.pagename,
						pp.imagefile,
						pv.state,
						p.language,
						p.seclanguage,
						p.comments,
						p.username AS projectmanager,
						p.nameofwork,
						p.authorsname,
						p.phase
					FROM projects p
					JOIN pages pp
						ON p.projectid = pp.projectid
					LEFT JOIN page_last_versions pv
						ON pp.projectid = pv.projectid
						   AND pp.pagename = pv.pagename
					WHERE p.projectid = '{$this->ProjectId()}'";


            $this->_pages = $dpdb->SqlObjects($sql);
        }
        return $this->_pages;
    }

    public function ImagePaths() {
        global $dpdb;
        $projectid = $this->ProjectId();
        $sql = "SELECT imagefile FROM pages
                WHERE projectid = ?
                ORDER BY pagename";
        $args = [&$projectid];
        $names = $dpdb->SqlValuesPS($sql, $args);
        $aret = [];
        foreach($names as $a) {
            $aret[] = build_path($this->ProjectPath(), $a);
        }
        return $aret;
    }

    public function ImagePath($filename) {
        return $filename == ""
            ? ""
            : build_path($this->ProjectPath(), $filename);
    }

    public function ImageUrl($imagefile) {
        return build_path($this->ProjectUrl(), $imagefile);
    }
    // queries single table only (pages, or "project" table)
    // cached as $_pagerows
    // returns old fieldnames (plus "active_text");
    public function PageRows($is_refresh = false) {
        static $_pagerows;
        global $dpdb;
        $projectid = $this->_projectid;

        if(! isset($_pagerows) || $is_refresh) {
	        $_pagerows = $dpdb->SqlRowsPS("
                SELECT
                	pg.projectid,
                	pv.phase,
                	pg.pagename,
                	pg.imagefile,
                	pv.username,
                	pv.version_time,
                	pv.version

                FROM pages pg

                JOIN page_last_versions pv
                	ON pg.projectid = pv.projectid
                	AND pg.pagename = pv.pagename

	            WHERE pg.projectid = ?
                ORDER BY pg.pagename", [&$projectid]);
        }
        return $_pagerows;
    }

    public function ProjectRow() {
        return $this->_row;
    }

	public function OCRText() {
		return $this->RoundText("PREP");
	}

    // Concatentate versions for the round.
    // If the round is not available, uses last version
    // which may not always be what's wanted.
    public function RoundText($phase) {
	    global $dpdb;
	    $projectid = $this->ProjectId();
        switch($phase) {
            case "PREP":
            case "P1":
            case "P2":
            case "P3":
            case "F1":
            case "F2":
                break;

            case "OCR":
                $phase = "PREP";
                break;

            case "PP":
            case "PPV":
            case "POSTED":
                $phase = "F2";
                break;
            default:
                return "";
        }
	    $sql = "SELECT pv.pagename,
						IFNULL(pv.version, plv.version) version
	            FROM page_versions pv
	            JOIN page_last_versions plv
		    	ON pv.projectid = plv.projectid
		    		AND pv.pagename = plv.pagename
	            WHERE pv.projectid = ?
	            	AND pv.phase = ?";
	    $args = [&$projectid, &$phase];
	    $rows = $dpdb->SqlRowsPS($sql, $args);

	    $text = "";
	    foreach($rows as $row) {
		    $pgtext = PageVersionText( $this->ProjectId(), $row['pagename'], $row['version']);
		    $text .= ("\n" . $pgtext);
	    }
	    return $text;
    }

    public function ActiveText() {
        $ary = $this->ActivePageArray();
        $text = "";
        foreach($ary as $pg) {
	        $pgtext = PageVersionText( $this->ProjectId(), $pg['pagename'], $pg['version']);
            $text .= ("\n" . $pgtext);
        }
        return $text;
    }

	public function PrePostText() {
		$rows = $this->PageRows();
		$txt = "";
		foreach($rows as $row) {
			$txt .= ("[pgnum id='{$row['pagename']}']"
					 . PageVersionText( $this->ProjectId(), $row['pagename'], $row['version'])
			         . "\n");
		}
		return $txt;
	}

	public function PPText() {
		$ary = $this->ActivePageArray();
		$text = "";
		foreach($ary as $pg) {
			$sep = PageTag($pg['imagefile']);
			$text .= ($sep . PageVersionText( $this->ProjectId(), $pg['pagename'], $pg['version']));
		}
		return $text;
	}

	private function ActivePageArray() {
		return $this->PagePhaseTextArray($this->Phase());
	}
    // in the following, $roundid is the last text
    // to include if not null
    private function PagePhaseTextArray($phase = "") {
        global $dpdb;

        switch($phase) {
            case "PREP":
            case "P1":
            case "P2":
            case "P3":
            case "F1":
            case "F2":
                break;
            default:
                $phase = "";
                break;
        }

	    if($phase == "") {
			$sql = "
				SELECT pg.pagename,
					   plv.version,
					   GROUP_CONCAT(pv.username ORDER BY pv.version) username
				FROM projects p
				JOIN pages pg
					on p.projectid = pg.projectid
				JOIN page_last_versions plv
					ON pg.projectid = plv.projectid
					AND pg.pagename = plv.pagename
			    JOIN page_versions pv
					ON pg.projectid = pv.projectid
					AND pg.pagename = pv.pagename

				WHERE p.projectid = ?
				GROUP BY pg.pagename
				";
	    }
	    else {
		    $sql = "
				SELECT pg.pagename,
					   ppv.version,
					   GROUP_CONCAT(pv.username ORDER BY pv.version) username
				FROM projects p

				JOIN pages pg
					on p.projectid = pg.projectid

				JOIN page_versions ppv
					ON pg.projectid = ppv.projectid
					AND pg.pagename = ppv.pagename
					AND ppv.phase = '$phase'

				JOIN page_versions pv
					ON pg.projectid = pv.projectid
					AND pg.pagename = pv.pagename

				WHERE p.projectid = ?
					AND pv.phase = '$phase'
				GROUP BY pg.pagename, ppv.phase
				";

	    }
        $projectid = $this->ProjectId();
	    return $dpdb->SqlRowsPS($sql, [&$projectid]);
    }

    public function IsPage($pagename) {
        global $dpdb;
        $projectid = $this->ProjectId();
        return $dpdb->SqlOneValuePS("
            SELECT COUNT(1) FROM pages
            WHERE projectid = ?
            AND pagename = ?", [&$projectid, &$pagename]) > 0;
    }

    public function Page($pagename) {
        if(!empty($pagename))
            return new DpPage($this->ProjectId(), $pagename);
        else {
            assert(false);
            return null;
        }
    }

/*
 *  only used in zip_images.php
 *
    public function ProjectPages() {
        static $pgs ;

        if(! isset($pgs ) ) {
            $projectid = $this->ProjectId();
            $pgs = [];
            foreach($this->PageRows() as $row) {
                $pagename = $row['fileid'];
                $pgs[$pagename] = new DpPage($projectid, $pagename);
            }
        }
        return $pgs;
    }
*/


	public function PageCount() {
		global $dpdb;
		$projectid = $this->ProjectId();
		$sql = "
	        SELECT COUNT(1) FROM page_last_versions
	        WHERE projectid = ?";
		$args = [&$projectid];
		return $dpdb->SqlOneValuePS($sql, $args);
	}

	protected function StateCount($state) {
		global $dpdb;
		$projectid = $this->ProjectId();
	    //$phase = $this->Phase();

        // Note this doesn't take into account the current phase,
        // so if there are any pages in subsequent phases it will be
        // wrong.
		return $dpdb->SqlOneValuePS("
	        SELECT COUNT(1) FROM page_last_versions
	        WHERE projectid = ?
            AND state = ?", [&$projectid, &$state]);
	}
	public function AvailableCount() {
		return $this->StateCount("A");
	}
	public function BadCount() {
		return $this->StateCount("B");
	}
    public function CompletedCount() {
	    return $this->StateCount("C");
    }
	public function CheckedOutCount() {
		return $this->StateCount("O");
	}

    public function UncompletedCount() {
        return $this->PageCount() - $this->CompletedCount();
    }


	public function ReclaimableCount() {
		global $dpdb;
		$projectid = $this->ProjectId();
		$sql = "SELECT COUNT(1) FROM page_last_versions
				WHERE projectid = ?
					AND state = 'O'
					AND version_time < UNIX_TIMESTAMP() - (60 * 60 * 4)";
		return $dpdb->SqlOneValuePS($sql, [&$projectid]);
	}

    public function IsBad() {
        return $this->BadCount() > 0;
    }

	public function IsRoundPhase() {
		switch($this->Phase()) {
			case "P1":
			case "P2":
			case "P3":
			case "F1":
			case "F2":
				return true;
			default:
				return false;
		}
	}

    public function ProjectId() {
        return $this->_projectid;
    }

    public function ProjectPath() {
        return ProjectPath($this->ProjectId());
    }

    public function IsRoundsComplete() {
        switch($this->Phase()) {
            case "PP":
            case "PPV":
            case "POSTED":
                return true;
            default:
                return false;
        }
    }

    public function PostComments() {
        return trim($this->_row['postcomments']);
    }
    
    public function AddPostComment($comment) {
        global $dpdb;
        $projectid = $this->ProjectId();
        $postcomments = "\n----------\n".date("Y-m-d H:i")
            . "\n$comment\n";
//        {$this->PostComments()}\n";

        $sql = "
			UPDATE projects
			SET  postcomments = CONCAT(IFNULL(postcomments, ''), ?)
			WHERE projectid = ?";

        $args = [&$postcomments, &$projectid];
        $dpdb->SqlExecutePS($sql, $args);
		$this->Refresh();
    }

    public function SetPostComments($str) {
        global $dpdb;
        $projectid = $this->ProjectId();
        $sql = "UPDATE projects SET postcomments = ?
                WHERE projectid = ?";
        $args = [&$str, &$projectid];
        $dpdb->SqlExecutePS($sql, $args);
        $this->_row['postcomments'] = $str;
    }

    public function Comments() {
	    $commentfile = $this->ProjectPath() . "/comments.txt";
	    if(file_exists($commentfile)) {
		    return file_get_contents($commentfile);
	    }
        return $this->_row['comments'];
    }

    public function LastEditTime() {
        return $this->_row['t_last_edit'];
    }

	public function LastEditTimeStr() {
		return $this->_row['t_last_edit_str'];
	}

	public function CreatedBy() {
		return $this->_row['createdby'];
	}

	public function CreateTime() {
		return $this->_row['createtime'];
	}

	public function CPComments() {
		return $this->_row['cp_comments'];
	}

	public function SetCPComments($str) {
		global $dpdb;
		$projectid = $this->ProjectId();
		$sql = "UPDATE projects
				SET cp_comments = ?
				WHERE projectid = ?";
		$args = [&$str, &$projectid];
		return $dpdb->SqlExecutePS($sql, $args);
	}

    public function PM() {
        return $this->ProjectManager();
    }

    public function SetPM($pm) {
        global $dpdb;
        $projectid = $this->ProjectId();
        $sql = "UPDATE projects SET username = ?
            WHERE projectid = ?";
        $args = [ &$pm, &$projectid ];
        $dpdb->SqlExecutePS($sql, $args);
        $this->_row['username'] = $pm;
        if($this->Phase() != "PP") {
            return;
        }
        $this->LogProjectEvent(PJ_EVT_SET_PM,  "{$pm} to Project Manager");
        $this->MaybeAdvanceRound();
    }

    public function ProjectManager() {
        return $this->_row['username'];
    }

    public function ImageSource() {
        return $this->_row['image_source'];
    }

    public function SetImageSource($src) {
        $this->_row['image_source'] = $src;
    }

    public function ImageLink() {
        return $this->_row['image_link'];
    }

    public function TitleAuthor() {
        return "
            <div>
                <h1>{$this->Title()}</h1>
                <h2>{$this->Author()}</h2>
            </div>\n";
    }
    public function Title() {
        if(! $this->Exists()) {
            return "";
        }
        if(! isset($this->_row['nameofwork'])) {
            StackDump();
        }
        return $this->_row['nameofwork'];
    } 

    public function NameOfWork() {
        return $this->Title();
    }

    public function Author() {
        return $this->_row['authorsname'];
    }

    public function AuthorsName() {
        return $this->_row['authorsname'];
    }

    public function SetAuthorsName($author) {
        $this->_row['authorsname'] = $author;
    }

    public function Language() {
        global $lang_codes;
        $code = $this->_row['language'];
        if(isset($lang_codes[$code])) {
            return $lang_codes[$code];
        }
        else {
            return $code;
        }
    }

	public function SetLanguage($code) {
		$this->_row['language'] = $code;
	}

	public function SetSecLanguage($code) {
		$this->_row['seclanguage'] = $code;
	}

	public function SecLanguageCode() {
		return $this->_row['seclanguage'];
	}

	public function SecLanguage() {
		global $lang_codes;
		$code = $this->_row['seclanguage'];
		return $code == "" ? "" : $lang_codes[$code];
	}

    /*
     * Note that language codes are supposed to be the two or sometimes
     * slightly more than two letter keys found in the lang_codes array.
     * However, historically, sometimes it has been stored as the full
     * language name; and in the case of two languages it seems to be
     *      lang1 with lang2
     * While editproject appears to have a secondary language, it doesn't
     * actually save a secondary language at this point!
     */
    public function LanguageCode() {
        global $lang_codes;

        $c = $this->_row["language"];
        if (isset($lang_codes[$c]))
            return $c;

        $code = array_search($c, $lang_codes);
        if(! $code) {
            $code = lower(left($c, 2));
        }
        return $code;
    }

    public function Clearance() {
        return empty( $this->_row['clearance'] )
            ? ""
            :  $this->_row['clearance'] ;
    }

    public function SetClearance($clearance) {
        $this->_row['clearance'] = $clearance;
    }

    public function PostedNumber() {
        return $this->_row['postednum'];
    }

    public function SetPostedNumber($postednum) {
        global $dpdb;
        $projectid = $this->ProjectId();
        if($postednum == "0" || $postednum == "") {
            $args = [&$projectid];
            $sql = "
                UPDATE projects SET postednum = NULL
                WHERE projectid = ?";
        } else {
            $args = [&$postednum, &$projectid];
            $sql = "
                UPDATE projects SET postednum = ?
                WHERE projectid = ?";
        }
        $dpdb->SqlExecutePS($sql, $args);
        $this->LogProjectEvent(PJ_EVT_POSTEDNUM, $postednum);
    }

    public function Difficulty() {
        return empty( $this->_row['difficulty'] )
            ? ""
            : $this->_row['difficulty'] ;
    }

    public function CreateForumThread() {
        global $Context;
        $subj = "{$this->Title()} (by {$this->Author()})";
        $title = $this->Title();
        $author = $this->Author();
        $url = full_url_for_project($this->ProjectId());

        $msg = "
        This thread is for discussion of {$title} (by {$author}).

Please review the [url={$url}]project comments[/url] before posting, as well as any posts below; your question may already be answered there.

(This post is automatically generated.)\n";

        $tid = $Context->CreateForumThread($subj, $msg, $this->ProjectManager());
        assert($tid > 0);
        $this->SetForumTopicId($tid);
		$this->MoveTopicToPhase($this->Phase());
        return $tid;
    }

    public function ForumTopicId() {
        return $this->_row['topic_id'];
    }

    public function SetForumTopicId($id) {
        global $dpdb;

        $dpdb->SqlExecute("
            UPDATE projects 
            SET topic_id = $id
            WHERE 
                projectid = '{$this->ProjectId()}'");
        $this->_row['topic_id'] = $id;
    }
    public function ClearForumTopicId() {
        $this->SetForumTopicId(0);
    }

    public function ForumTopicUrl() {
        $id = $this->ForumTopicId();
        return url_for_project_thread($id);
    }

    public function LastForumPostDate() {
        global $Context;
        return $this->ForumTopicId() == 0
            ? "--"
            : $Context->LatestTopicPostTime($this->ForumTopicId());
    }

    public function MoveTopicToPhase($phase) {
        global $Context;
        $topicid = $this->ForumTopicId();
        $Context->MoveTopicToPhaseForum($topicid, $phase);
    }

    public function ForumTopicIsEmpty() {
        global $Context;
        return $this->ForumTopicId() == 0
            || $Context->ForumTopicReplyCount($this->ForumTopicId()) == 0;
    }

    public function Genre() {
        return $this->_row['genre'];
    }

    public function CPer() {
        return $this->TextPreparer();
    }

    public function TextPreparer() {
        return $this->_row['text_preparer'];
    }

    public function ImagePreparer() {
        return $this->_row['image_preparer'];
    }

    public function ExtraCredits() {
        return $this->_row['extra_credits'];
    }

    public function SetDifficulty($val) {
        $this->_row['difficulty'] = $val;
    }

    public function SetGenre($val) {
        $this->_row['genre'] = $val;
    }

    public function SetCPer($val) {
        $this->SetTextPreparer($val);
    }
    public function SetTextPreparer($val) {
        $this->_row['text_preparer'] = $val;
    }

    public function SetImagePreparer($val) {
        $this->_row['image_preparer'] = $val;
    }

    public function SetExtraCredits($val) {
        $this->_row['extra_credits'] = $val;
    }

    public function TogglePostNotify() {
        if($this->IsPublishUserNotify()) {
            $this->ClearNotify('post');
        } else {
            $this->SetNotify('post', 'pm');
        }
    }

    public function ToggleSmoothNotify() {
        if($this->IsSmoothUserNotify()) {
            $this->ClearNotify('smooth');
        } else {
            $this->SetNotify('smooth', 'pm');
        }
    }

    public function ToggleP1QueueReleaseNotify() {
        if ($this->IsP1QueueReleaseUserNotify())
            $this->ClearNotify('p1queue');
        else
            $this->SetNotify('p1queue', 'pm');
    }

	public function IsPublishUserNotify() {
        return $this->IsNotify('post');
    }

    public function IsSmoothUserNotify() {
        return $this->IsNotify('smooth');
    }

    public function IsP1QueueReleaseUserNotify() {
        return $this->IsNotify('p1queue');
    }

    private function SetNotify($event, $mode) {
        global $dpdb, $User;
        $username = $User->Username();
        $projectid = $this->ProjectId();

        $sql =  "REPLACE INTO notify
                 (projectid, username, event, mode)
                 VALUES
                 (?, ?, ?, ?)";
        $args = [&$projectid, &$username, &$event, &$mode];
        $dpdb->SqlExecutePS($sql, $args);
    }

    private function ClearNotify($event) {
        global $dpdb, $User;
        $username = $User->Username();
        $projectid = $this->ProjectId();

        $sql = "DELETE FROM notify
             WHERE projectid = ? AND username = ?
                AND event = ?";
        $args = [&$projectid, &$username, &$event];
        $dpdb->SqlExecutePS($sql, $args);
    }

    public function IsNotify($event) {
		global $User;
		global $dpdb;

		$username = $User->Username();
		$projectid = $this->ProjectId();

        $sql = "SELECT COUNT(1) FROM notify
             WHERE projectid = ?
                   AND username = ?
                   AND event = ?";

        $args = [&$projectid, &$username, &$event];
        return $dpdb->SqlOneValuePS($sql, $args) > 0;
	}

	public function UserCreatedBy() {
		global $User;
		return $User->NameIs($this->CreatedBy());
	}

    public function UserIsPM() {
        global $User;
        return $User->NameIs($this->ProjectManager());
    }

    private function NextAvailablePage() {
        if(! $this->IsAvailable()) {
            return null;
        }

        $page = $this->next_available_page_for_user();
        if(! $page) {
            $page = $this->next_retrievable_page_for_user();
        }
        return $page;
    }

    public function CheckOutNextAvailablePage() {
        global $dpdb;

        try {
            // Need a transaction, so after finding the next page, we
            // it doesn't get taken before we check it out.
            // Note requires the sql in the below function to use FOR UPDATE
            // Subsequent user getting next page will wait until we commit
            // after DpPage.CheckOutPage has updated the row.  The subsequent
            // user will then fetch the next next page.
            $dpdb->beginTransaction();
            $page = $this->NextAvailablePage();
            if($page) {
                //sleep(5); For Testing to actually get a conflict
                $page->CheckOutPage();
                $dpdb->commit();
                return $page;
            }
        } finally {
            $dpdb->rollback();
        }

        return null;
    }

    private function next_available_page_for_user() {
        global $dpdb;
        global $User;

        if(! $this->IsAvailable()) {
            assert(false);
            return null;
        }

//        $phase = $this->Phase();
        $username = $User->Username();

	    $sql =
	    "SELECT pv.pagename, pv.version
		FROM projects p
		JOIN page_last_versions pv
			ON p.projectid = pv.projectid
			AND p.phase = pv.phase
		LEFT JOIN page_versions ppv
			ON pv.projectid = ppv.projectid
			   AND pv.pagename = ppv.pagename
               AND ppv.version = pv.version - 1
		WHERE p.projectid = ?
			AND pv.state = 'A'
		   	AND
		   	(
		   	    IFNULL(ppv.username, '') != ?
		   	    || p.phase = 'F1'       -- allow sequential users P3 -> F1
            )
		ORDER BY pv.pagename
		LIMIT 1
        FOR UPDATE
		";

        $id = $this->ProjectId();
        $args = [ &$id, &$username ];
        $pagename = $dpdb->SqlOneValuePS($sql, $args);

        return (empty($pagename))
            ? null
            : $this->Page($pagename);
    }

    public function next_retrievable_page_for_user() {
        global $dpdb, $User;
	    $projectid = $this->ProjectId();
        $username = $User->Username();
        // See DpPage.Reclaim for comment on page_last_versions view
	    $sql = "
			SELECT pv.pagename, pv.version
			FROM page_last_versions pv
            LEFT JOIN page_versions ppv
                ON pv.projectid = ppv.projectid
                   AND pv.pagename = ppv.pagename
				   AND ppv.version = pv.version - 1
			WHERE pv.projectid = ?
				AND pv.state = 'O'
				AND pv.username != ?
                AND
                (
                    IFNULL(ppv.username, '') != ?
                    || pv.phase = 'F1'       -- allow sequential users P3 -> F1
                )
				AND pv.version_time < UNIX_TIMESTAMP() - 60 * 60 * 4
			ORDER BY pv.version_time
			LIMIT 1
		";
        $args = [ &$projectid, &$username, &$username ];
        $pagename = $dpdb->SqlOneValuePS($sql, $args);

        if ($pagename == "")
            return null;

        $pg = $this->Page($pagename);
        $pg->Reclaim();
        return $pg;
    }

    private function NextRoundId() {
        return NextRoundIdForRoundId($this->RoundId());
    }

    public function SetPosted($postednum = 0) {
        $this->SetPostedNumber($postednum);
        $this->MaybeAdvanceRound();
    }

    public function PhaseBefore() {
	    global $Context;
	    $idx = $Context->PhaseIndex($this->Phase());
	    return $Context->IndexPhase($idx - 1);
    }

    public function IsPosted() {
        return $this->Phase() == "POSTED";
    }

    public function NextPhase() {
        global $Context;
	    $idx = $Context->PhaseIndex($this->Phase());
        return $Context->IndexPhase($idx + 1);
    }

    public function RevertPhase($is_clear = false, $set_user_hold = true) {
        global $Context;
        /** @var DpContext $Context */
        $oldphase = $this->Phase();
        $newphase = $Context->PhaseBefore($oldphase);

        if (!$this->IsNormalProject())
            if ($oldphase == 'PP')
                $newphase = 'PREP';

	    if(! $is_clear) {
            if ($set_user_hold)
                $this->SetUserHold( $newphase, "from reverting project from $oldphase" );
	    }
        $this->SetPhase($newphase);
        $this->LogProjectEvent(PJ_EVT_REVERT, "$oldphase to $newphase");
//        $this->SetModifiedDate();
        $this->SetPhaseDate();
        $this->_row["phase"] = $newphase;
        if(! $this->IsPPHold()) {
            $this->SetPPHold();
        }
        if($is_clear) {
	        $this->ClonePageVersions($newphase, "REVERT", "A");
//	        $dpdb->SqlExecute(
//		        "UPDATE page_last_versions
//		        SET state = 'A'
//		        WHERE projectid = '{$this->ProjectId()}'
//		        	AND phase = '{$this->Phase()}'");
        }
        $this->Refresh();
    }

    public function SetPhase($newphase) {
        global $dpdb;
	    if(! is_string($newphase)) {
		    assert(false);
		    StackDump();
	    }
        $phase = $this->Phase();
	    if(! is_string($phase)) {
		    assert(false);
	    }
	    if($phase == $newphase) {
		    return;
	    }
        $dpdb->SqlExecute("
            UPDATE projects 
            SET phase = '$newphase' 
            WHERE projectid = '{$this->ProjectId()}'");
        $this->_row['phase'] = $newphase;
        $this->LogPhaseTransition($phase, $newphase);
        $this->SetPhaseDate();
        $this->MoveTopicToPhase($newphase);

        // If this is a transition to posted, send notifications
        if ($phase != 'POSTED' && $newphase == 'POSTED')
            $this->PostedNotify();
        // If this is a transition to PP, send notification to PM/PP
        if ($phase != 'PP' && $newphase == 'PP')
            $this->PPNotify();
    }

    public function PostedNotify()
    {
        $this->LogProjectEvent(PJ_EVT_POSTED_NOTIFY, "Project transitioning to POSTED, notifying waiters");
        list($subject, $email) = $this->ConstructPostedMsg();
        $this->Notify('post', $this->PM(), $subject, $email);
    }

    public function PPNotify()
    {
        $this->LogProjectEvent(PJ_EVT_PP_NOTIFY, "Project transitioning to PP, notifying PM&PP");
        list($subject, $email) = $this->ConstructPPMsg();

        $pm = $this->PM();
        $pp = $this->PPer();
        $event = "Transition to PP";

        // Always send to the PM, and also send to the PP if they exist
        // and differ from the PM
        $this->SendPM($pm, $pm, $subject, $email, $event);
        if (!empty($pp) && $pp != $pm)
            $this->SendPM($pm, $pp, $subject, $email, $event);
    }

    public function P1QueueReleaseNotify()
    {
        list($subject, $email) = $this->ConstructP1QueueReleaseMsg();
        $event = 'p1queue';
        $pm = $this->PM();
        $sentTo = $this->Notify($event, $pm, $subject, $email);

        // Send PM to the PM always, unconditionally (well, unless for
        // some strange reason they registered!)
        if (!in_array($pm, $sentTo))
            $this->SendPM($pm, $pm, $subject, $email, $event);
    }

    public function LogPhaseTransition($fromphase, $tophase) {
        global $dpdb;
        global $User;
        $username = $User->Username();
        $sql = "
            INSERT INTO project_events
            SET event_time = UNIX_TIMESTAMP(),
                projectid = '{$this->ProjectId()}',
                phase     = '{$fromphase}',
                to_phase   = '{$tophase}',
                username = '$username',
                event_type = 'transition',
                details1 = '$fromphase',
                details2 = '$tophase'";
        $dpdb->SqlExecute($sql);
    }

    public function DeleteProject() {
        global $dpdb;
        $projectid = $this->ProjectId();

        $this->SetPhase("DELETED");

        $dpdb->SqlExecute("
            DELETE FROM project_holds
            WHERE projectid = '$projectid'");

        $this->_row["phase"] = 'DELETED';
        $this->_row["state"] = 'project_delete';
//        $this->SetModifiedDate();
        $this->SetPhaseDate();
    }

    public function DeletePages($pagenames) {
        // Don't like instantiating in a loop, but ...
        // Need a generator ...
        foreach($pagenames as $pagename) {
            $this->DeletePage($pagename);
        }
    }

    private function DeletePage($pagename) {
        $pg = new DpPage($this->ProjectId(), $pagename);
        $pg->Delete();
    }

    public function ClearPages($pagenames) {
	    if(count($pagenames) == 0) {
		    return;
	    }
	    // if the phase is PP, Clearing a page means reverting to F2
		foreach($pagenames as $pgname) {
			$pg = new DpPage($this->ProjectId(), $pgname);
			$pg->Clear();
		}
    }

    public function PageNameBefore($pagename) {
        global $dpdb;
        $sql = "
			SELECT MAX(pagename) FROM page_last_versions
			WHERE projectid = '{$this->ProjectId()}'
				AND pagename < '$pagename'";
//            SELECT MAX(fileid) FROM $this->_projectid
//            WHERE fileid < '$pagename'";
        return $dpdb->SqlOneValue($sql);
    }

    public function PageNameAfter($pagename) {
        global $dpdb;
        $sql = "
            SELECT MIN(pagename) FROM page_last_versions
            WHERE projectid = '{$this->ProjectId()}'
            	AND pagename > '$pagename'";
        return $dpdb->SqlOneValue($sql);
    }

    private function LogProjectEvent( $event_type, $remark = null, $phase = null) {
        global $dpdb;
        global $User;

        if(! $phase) {
            $phase = $this->Phase();
        }
        $projectid = $this->ProjectId();
	    $sql = "
					INSERT INTO project_events
						SET event_time   = UNIX_TIMESTAMP(),
							projectid   = ?,
							phase       = ?,
							username    = ?,
							event_type  = ?,
							details1    = ?";
        $username = $User->Username();
        $args = [ &$projectid, &$phase, &$username, &$event_type, &$remark ];
	    $n = $dpdb->SqlExecutePS($sql, $args);
        assert($n > 0);
    }

	public function PageBefore($pagename, $phase) {
		global $dpdb;
		$projectid = $this->ProjectId();
		$sql = "
			SELECT MAX(pagename) FROM page_versions
			WHERE projectid = '$projectid'
				AND phase = '$phase'
				AND pagename < '$pagename'
		";
		$pgname = $dpdb->SqlOneValue($sql);

		if(! $pgname) {
			return null;
		}
		return new DpPage($projectid, $pgname);
	}

	public function PageAfter($pagename, $phase) {
		global $dpdb;
		$projectid = $this->ProjectId();
		$pgname = $dpdb->SqlOneValue("
			SELECT MIN(pagename) FROM page_versions
			WHERE projectid = '$projectid'
				AND phase = '$phase'
				AND pagename > '$pagename'
		");
		if(! $pgname) {
			return null;
		}
		return new DpPage($projectid, $pgname);
	}

	public function ProoferPhasePageBefore($pagename, $phase, $proofer) {
		global $dpdb;
		$projectid = $this->ProjectId();
		$pgname = $dpdb->SqlOneValue("
			SELECT MAX(pagename) FROM page_versions
			WHERE projectid = '$projectid'
				AND $phase = '$phase'
				AND username = '$proofer'
				AND pagename < '$pagename'
		");
		if($pgname) {
			return null;
		}
		return new DpPage($projectid, $pgname);
	}

	public function ProoferPhasePageAfter($pagename, $phase, $proofer) {
		global $dpdb;
		$projectid = $this->ProjectId();
		$pgname = $dpdb->SqlOneValue("
			SELECT MIN(pagename) FROM page_versions
			WHERE projectid = '$projectid'
				AND $phase = '$phase'
				AND username = '$proofer'
				AND pagename > '$pagename'
		");
		if($pgname) {
			return null;
		}
		return new DpPage($projectid, $pgname);
	}

    public function RecalcPageCounts() {
        global $dpdb;
        $projectid = $this->ProjectId();
//        $phase = $this->Phase();

	    return $dpdb->SqlExecute("
			UPDATE projects p
			JOIN (
				SELECT projectid,
						PHASE,
						SUM(pv.state = 'A') n_avail,
						SUM(pv.state = 'O') n_out,
						SUM(pv.state = 'C') n_done,
						SUM(pv.state = 'B') n_bad,
						COUNT(1) n_pgs
				FROM page_last_versions pv
				WHERE projectid = '$projectid'
				GROUP BY projectid, PHASE
				) a
				ON p.projectid = a.projectid
				AND p.phase = a.phase
			SET n_available_pages = a.n_avail,
				n_checked_out = a.n_out,
				n_complete = a.n_done,
				n_bad_pages = a.n_bad,
				n_pages = a.n_pgs
			WHERE p.projectid = '$projectid'
	    ");
    }

    public function HoldCount() {
        global $dpdb;
        $projectid = $this->ProjectId();
        return $dpdb->SqlOneValue("
            SELECT COUNT(1) FROM project_holds
            WHERE projectid = '$projectid'");
    }

    private function PhaseHoldCount() {
        global $dpdb;
        $projectid = $this->ProjectId();
        return $dpdb->SqlOneValue("
            SELECT SUM(1) FROM projects p
            JOIN project_holds ph
            	ON p.projectid = ph.projectid
            	AND p.phase = ph.phase
            WHERE p.projectid = '$projectid'");
    }

    public function ActiveHoldCount() {
        return $this->PhaseHoldCount();
    }

    public function HoldRows() {
        global $dpdb;
        $projectid = $this->ProjectId();
        return $dpdb->SqlRows("
            SELECT  ph.hold_code,
                    ph.set_by,
                    DATE_FORMAT(FROM_UNIXTIME(ph.set_time), '%b %e %Y %H:%i') set_time,
                    ph.phase,
                    ph.note,
                    h.description hold_description
            FROM project_holds ph
            JOIN hold_types h
            ON ph.hold_code = h.hold_code
            WHERE ph.projectid = '$projectid'
            ORDER BY set_time");
    }

    public function IsInRounds() {
        switch($this->Phase()) {
            case "P1":
            case "P2":
            case "P3":
            case "F1":
            case "F2":
                return true;
            default:
                return false;
        }
    }
    public function UserPhaseHoldId($phase) {
        global $User, $dpdb;
        $username = $User->Username();
        $id = $this->ProjectId();
        $args = [&$id, &$username, &$phase];
        return $dpdb->SqlOneValuePS("
            SELECT id FROM project_holds
            WHERE projectid = ?
                AND hold_code = 'user'
                AND set_by = ?
                AND phase = ?", $args);
    }

	public function IsQCHold() {
		global $dpdb;
		$projectid = $this->ProjectId();
		return $dpdb->SqlOneValue("
			SELECT COUNT(1) FROM project_holds
			WHERE projectid = '$projectid'
				AND hold_code = 'qc'") > 0;
	}

    public function QCHoldId() {
        global $dpdb;
        return $dpdb->SqlOneValue("
            SELECT id FROM project_holds
            WHERE projectid = '{$this->ProjectId()}'
                AND hold_code = 'qc'");
    }

    private function PhaseHolds($phase) {
        global $dpdb;
        $projectid = $this->ProjectId();
        return $dpdb->SqlRows("
            SELECT hold_code, description
            FROM project_holds
            WHERE projectid = '$projectid'
                AND (phase = '$phase' OR phase IS NULL)");
    }

    public function ActiveHolds() {
        return $this->PhaseHolds($this->Phase());
    }
    public function ValidUserActions() {
        return [];
    }

    private function IsPhaseHold($phase, $holdcode) {
        global $dpdb;
        $projectid = $this->ProjectId();

        $sql = "
            SELECT COUNT(1) FROM project_holds
            WHERE projectid = '$projectid'
                AND phase = '$phase'
                AND hold_code = '$holdcode'";
        $x = $dpdb->SqlOneValue($sql) > 0;
        return $x;
    }

    public function IsPhaseUserHold($phase, $username) {
        global $dpdb;
        $projectid = $this->ProjectId();

        $sql = "
            SELECT COUNT(1) FROM project_holds
            WHERE projectid = ?
                AND phase = ?
                AND hold_code = 'user'
                AND set_by = ?";
        $args = [&$projectid, &$phase, &$username];
        $x = $dpdb->SqlOneValuePS($sql, $args) > 0;
        return $x;
    }
    public function SetUserHold($phase, $note = "") {
        $this->SetPhaseHold($phase, "user", $note);
    }

    public function ClearUserHold($phase) {
        $this->ClearPhaseHold($phase, "user");
    }

	public function SetAutoPMHold($phase, $note) {
		$this->SetAutoHold($phase, "pm", $note);
	}
    public function SetPMHold($phase, $note = "") {
        $this->SetPhaseHold($phase, "pm", $note);
    }

    public function ClearPMHold($phase) {
        $this->ClearPhaseHold($phase, "pm");
    }

	private function SetAutoHold($phase, $holdcode, $note = "") {
		global $dpdb;

		// if it's auto, can't have more than one
		if($this->IsPhaseHold($phase, $holdcode)) {
			return;
		}
		$projectid = $this->ProjectId();
		$username  = "(auto)";

		$sql = "
                INSERT INTO project_holds
                SET hold_code = ?,
                    projectid = ?,
                    phase     = ?,
                    note      = ?,
                    set_by    = ?,
                    set_time  = UNIX_TIMESTAMP()";
		$args = [&$holdcode, &$projectid, &$phase, &$note, &$username];
		$dpdb->SqlExecutePS($sql, $args);
		$this->LogProjectEvent(PJ_EVT_HOLD, "set $holdcode Hold");
	}

    private function SetPhaseHold($phase, $holdcode, $note = "") {
        global $dpdb;
        global $User;

        if($this->IsPhaseHold($phase, $holdcode)) {
            return;
        }
        $projectid = $this->ProjectId();
        $username  = $User->Username();
        // return $dpdb->SqlExecute("
        if($dpdb->SqlOneValue("
            SELECT COUNT(1) FROM project_holds
            WHERE projectid = '{$this->ProjectId()}'
                AND hold_code = '$holdcode'
                AND phase = '$phase'") == 0) {
            $sql = "
                INSERT INTO project_holds
                SET hold_code = ?,
                    projectid = ?,
                    phase     = ?,
                    note      = ?,
                    set_by    = ?,
                    set_time  = UNIX_TIMESTAMP()";
            $args = [ &$holdcode, &$projectid, &$phase, &$note, &$username ];
            $dpdb->SqlExecutePS($sql, $args);
            $this->LogProjectEvent(PJ_EVT_HOLD, "set $holdcode Hold ($note)");
        }
    }

    // -------------- Wordcheck related functions

    public function WriteBadWordsArray($langcode, $words_array) {
        $this->WriteWordsArray("bad", $langcode, $words_array);
    }

	// a[w, w, w...]
    public function WriteBadWordsList($langcode, $words) {
        $wary = list_to_unique_array($words);
        $this->WriteBadWordsArray($langcode, $wary);
    }

    // apply words to current bad words list
    public function SubmitBadWordsArray( $langcode, $w_array) {
        $bwa = $this->BadWordsArray($langcode);
	    // does this do anything?
        foreach($w_array as $w) {
            $bwa[] = $w;
        }
        $bwa = array_unique($bwa);
        $this->WriteBadWordsArray($langcode, $bwa);
        $this->RefreshAcceptedWordsArray($langcode);
    }

    public function BadWordsArray($langcode) {
        return project_bad_words_array($this->ProjectId(), $langcode);
    }

    // no counts, unique list
    public function GoodWordsArray($langcode) {
        return project_good_words_array($this->ProjectId(), $langcode);
    }

    public function GoodWordCount($langcode = "") {
        if($langcode == "") {
            $langcode = $this->LanguageCode();
        }
        return count($this->GoodWordsArray($langcode));
    }

    public function BadWordCount($langcode = "") {
        if($langcode == "") {
            $langcode = $this->LanguageCode();
        }
        return count($this->BadWordsArray($langcode));
    }

    public function AddGoodWord($langcode, $word) {
        $this->SubmitGoodWordsArray($langcode, [$word]);
    }

    public function AddBadWord($langcode, $word) {
        $this->SubmitBadWordsArray($langcode, [$word]);
    }

    public function AcceptedWordsByCountAlpha($langcode) {
        $a = $this->AcceptedWordCountArray($langcode);
        $this->sort_words_by_count_alpha($a);
        return $a;
    }


    public function FlagWordsByCountAlpha($langcode) {
        $sw = $this->FlagWordCountArray($langcode);
        $this->sort_words_by_count_alpha($sw);
        return $sw;
    }


    public function AcceptedWordCountArray($langcode) {
        return $this->WordCountArray(
            $this->AcceptedWordsArray($langcode));
    }

//    public function SuggestedWordCount($langcode = "") {
//        if($langcode == "") {
//            $langcode = $this->LanguageCode();
//        }
//        return count($this->AcceptedWordsArray($langcode));
//    }

	// for an array of words, annotate if theyare flagged, accepted, good, and/or bad
	// by adding a dimension to the array $a[] = [w][c][a]
    private function AnnotateWordCountArray(&$words, $langcode, $notAnnotated = "") {
        $fwa = $swa = $gwa = $bwa = [];

        if ("flagged" != $notAnnotated) {
            $fwa = $this->FlagWordsArray($langcode);
        }
        if ("suggested" != $notAnnotated) {
            $swa = $this->AcceptedWordsArray($langcode);
        }
        if ("good" != $notAnnotated) {
            $gwa = $this->GoodWordsArray($langcode);
        }
        if ("bad" != $notAnnotated) {
            $bwa = $this->BadWordsArray($langcode);
        }

        foreach ($words as &$a) {
            if ("flagged" != $notAnnotated && in_array($a[0], $fwa)) {
                $a[2] = "flagged";
            }
            if ("suggested" != $notAnnotated && in_array($a[0], $swa)) {
                $a[2] = "suggested";
            }
            if ("good" != $notAnnotated && in_array($a[0], $gwa)) {
                $a[2] = "good";
            }
            if ("bad" != $notAnnotated && in_array($a[0], $bwa)) {
                $a[2] = "bad";
            }
        }
    }

    private function WriteWordsArray($code, $langcode, $warray) {
        assert(is_array($warray));
        $warray = array_unique($warray);
        $warray = array_diff($warray, [""]);
        _write_project_words_array(
            $this->ProjectId(), $code, $langcode, $warray);
    }

    public function WriteGoodWordsArray($langcode, $w_array) {
        $this->WriteWordsArray("good", $langcode, $w_array);
    }

    public function WriteAcceptedWordsArray($langcode, $words) {
        $this->WriteWordsArray("suggested", $langcode, $words);
    }
//
    public function AcceptWordsArray($langcode, $w_array) {
        $this->SubmitAcceptedWordsArray($langcode, $w_array);
    }
//
    public function SubmitAcceptedWordsArray( $langcode, $w_array) {
        if(! is_array($w_array)) {
            return;
        }
        $s = $this->AcceptedWordsArray($langcode);
        foreach($w_array as $w) {
            $s[] = $w;
        }
        $s = array_unique($s);
        $this->WriteAcceptedWordsArray($langcode, $s);
        $this->RefreshAcceptedWordsArray($langcode);
    }

    public function DeleteAcceptedWordsArray($langcode, $awords) {
        $ary = $this->AcceptedWordsArray($langcode);
        $out = [];
        foreach($ary as $a) {
            if(in_array($a, $awords))
                continue;
            $out[] = $a;
        }
        $this->WriteAcceptedWordsArray($langcode, $out);
        $this->RefreshAcceptedWordsArray($langcode);
    }

    // apply words to current good words list
    // used to move suggested words to good list
    public function SubmitGoodWordsArray( $langcode, $w_array) {
        $gwa = $this->GoodWordsArray($langcode);
        foreach($w_array as $w) {
            $gwa[] = $w;
        }
        $gwa = array_unique($gwa);
        $this->WriteGoodWordsArray($langcode, $gwa);
        $this->RefreshAcceptedWordsArray($langcode);
    }

    public function WriteGoodWordsList($langcode, $words) {
        $wary = list_to_unique_array($words);
        $this->WriteGoodWordsArray($langcode, $wary);
    }

    public function RefreshAcceptedWordsArray($langcode) {
        $s = $this->AcceptedWordsArray($langcode);
        $g = $this->GoodWordsArray($langcode);
        $b = $this->BadWordsArray($langcode);
//
        $map = $out = [];
        foreach($s as $word)
            $map[$word] = 1;

        foreach($g as $word)
            if(isset($map[$word]))
                $map[$word] = 0;

        foreach($b as $word)
            if(isset($map[$word]))
                $map[$word] = 0;

        foreach($map as $word => $ok)
            if($ok)
                $out[] = $word;

        $this->WriteAcceptedWordsArray( $langcode, $out);
    }

    public function FlagWordCountArray($langcode) {
        return $this->WordCountArray(
            $this->FlagWordsArray($langcode));
    }
//
    public function AdHocWordCountArray($langcode, $strwords) {
        $ary = text_to_words($strwords);
        $ary = $this->WordCountArray($ary);

        $this->AnnotateWordCountArray($ary, $langcode);
        return $ary;
    }
//
	public function PageByteOffsetArray() {
        return $this->_page_byte_offset_array;
	}

    public function PageNameForByteOffset($offset) {
        $ret = null;
        $a = $this->PageByteOffsetArray();
        if(count($a) == 0) {
            assert(false);
            return "";
        }

        $ret = null;
        foreach($a as $pgname => $data) {
            if($offset < $data["offset"]) {
                break;
            }
            $ret = $pgname;
        }
        return $ret;
    }

    public function VersionForPageName($pagename) {
        return $this->_page_byte_offset_array[$pagename]["version"];
    }
    public function ByteOffsetForPageName($pagename) {
        return $this->_page_byte_offset_array[$pagename]["offset"];
    }

    public function PageForByteOffset($offset) {
        $pn = $this->PageNameForByteOffset($offset);
        return ($pn == "")
            ? null
            : new DpPage($this->ProjectId(), $pn);
    }

    /*
     * given a word and its character offset into project text,
     * locate the page and line # on page and return 5 adjacent lines
     */
    function ContextForByteOffset($word, $offset) {
        $projectid = $this->ProjectId();
        $pagename = $this->PageNameForByteOffset($offset);
        $version = $this->LastPageVersion($pagename);
        $imagefile = $this->ImageFileForPageName($pagename);
        if(! $pagename) {
            assert(false);
            dump($projectid);
            dump(" |$word|$offset");
            exit();
        }
        $pgtext = PageVersionText($projectid, $pagename, $version);
        $pgoffset = $this->ByteOffsetForPageName($pagename);
        $pglines = text_lines($pgtext);
        $pgposn = $offset - $pgoffset;
        $lineindex = RegexCount("\n", "u", bleft($pgtext, $pgposn)) + 1;

        $ary = [];
        $ary['offset'] = $offset;
        $ary['pgoffset'] = $pgoffset;
        $ary['pgposn'] = $pgposn;
        $ary['word'] = $word;
        $ary['imageurl'] = $this->ImageUrl($imagefile);
        $ary['projectid'] = $projectid;
        $ary['pagename'] = $pagename;
        $ary['lineindex'] = $lineindex;
        $ary['linecount'] = count($pglines);
        // back up 2 lines to first
        $lstart = max(0, $lineindex - 3);
        $ary['lstart'] = $lstart;
        $context = implode("\n", array_slice($pglines, $lstart, 5));
        $context = ReplaceRegex("(?<!\p{L})".$word."(?!\p{L})",
            "<span class='wcontext'>{$word}</span>", "u", $context);
        $ary['context'] = $context;
        return $ary;
    }
    /*
    public function ContextForByteOffset($word, $offset) {
        $pg = $this->PageForByteOffset($offset);
	    if(! $pg) {
		    assert(false);
		    dump($this->ProjectId());
		    dump(" |$word|$offset");
            exit();
	    }
        $pagename = $pg->PageName();
        $pgtext = $pg->ActiveText();
        $pgoffset = $this->ByteOffsetForPageName($pagename);
        $pglines = text_lines($pgtext);
        $pgposn = $offset - $pgoffset;
        $lineindex = RegexCount("\n", "u", bleft($pgtext, $pgposn)) + 1;

        $ary = [];
        $ary['offset'] = $offset;
        $ary['pgoffset'] = $pgoffset;
        $ary['pgposn'] = $pgposn;
        $ary['word'] = $word;
        $ary['imageurl'] = $pg->ImageUrl();
        $ary['pagename'] = $pagename;
        $ary['lineindex'] = $lineindex;
        $ary['linecount'] = count($pglines);
        // back up 2 lines to first
        $lstart = max(0, $lineindex - 3);
        $ary['lstart'] = $lstart;
        $context = implode("\n", array_slice($pglines, $lstart, 5));
        $context = ReplaceRegex("(?<!\p{L})".$word."(?!\p{L})",
            "<span class='wcontext'>{$word}</span>", "u", $context);
        $ary['context'] = $context;
        return $ary;
    }
    */

    public function StringContexts($str) {
        $stroffsets  = RegexStringByteOffsets($str, $this->TextForWords());
        $rsp         = [];
        $rsp['str']  = $str;
        $rsp['contexts'] = [];
        foreach($stroffsets as $oset) {
            $offset = $oset[1];
            $wctxt = $this->ContextForByteOffset($str, $offset);
            $rsp['contexts'][] = $wctxt;
        }
        return $rsp;
    }

    public function WordContexts($word) {
        // get project positions for the word
	    $tfw = $this->TextForWords();
        $wdoffsets   = WordByteOffsets($word, $tfw);
        $rsp         = [];
        $rsp['word'] = $word;
        $rsp['contexts'] = [];

        foreach($wdoffsets as $oset) {
            $offset = $oset[1];
            $wctxt = $this->ContextForByteOffset($word, $offset);
            $rsp['contexts'][] = $wctxt;
        }
        return $rsp;
    }

    public function RegexMatchArray($strfind, $isic) {
        if($strfind == "") {
            return [];
        }
        $flags = $isic ? "ui" : "u";
        $n = RegexCount($strfind, $flags, $this->TextForWords());
        if($n > 1000) {
            die("More than 1000 matches found - terminated.");
        }

        $ary =  RegexMatch($strfind, $flags, $this->TextForWords());
        return $ary[0];
    }

    private function sort_words_by_count_alpha(&$word_array, $invert_count = false) {
        uasort($word_array,
            function ($w1, $w2) use ($invert_count) {
                if($w1[1] != $w2[1]) {
	                return ! $invert_count
		                    ? $w2[1] - $w1[1]
                            : $w1[1] - $w2[1];
                }
                else {
                	#MS PHP8
                	#return $w1[0] == $w2[0]
                    #    ? 0
                    #	 : strtolower($w1[0]) > strtolower($w2[0])
                    #        ? 1
                    #        : -1 ;
                    return ($w1[0] == $w2[0]
                        ? 0
                        : (strtolower($w1[0]) > strtolower($w2[0])))
                            ? 1
                            : -1 ;
                }
            }
        );
    }

    public function GoodWordCountArray($langcode) {
        return $this->WordCountArray(
            $this->GoodWordsArray($langcode));
    }

    public function BadWordCountArray($langcode) {
        return $this->WordCountArray(
            $this->BadWordsArray($langcode));
    }

    public function GoodWordsByCountAlpha($langcode) {
        $wds = $this->GoodWordCountArray($langcode);
        $this->sort_words_by_count_alpha($wds);
        return $wds;
    }


	public function BadWordCountNotZero($langcode) {
		$ary = $this->BadWordCountArray($langcode);
		$bry = [];
		foreach($ary as $a => $c) {
			if($c > 0) {
				$bry[$a] = $c;
			}
		}
		return $bry;
	}
    public function BadWordsByCountAlpha($langcode) {
        $wds = $this->BadWordCountNotZero($langcode);
        $this->sort_words_by_count_alpha($wds);
	    $b = [];
	    foreach($wds as $a => $c) {
		   if($c > 0) {
			   $b[$a] = $c;
		   }
	    }
        return $b;
    }



	public function ActiveTextLines() {
		static $_lines;
		if(! isset($_lines) ) {
			$_lines = text_lines( $this->ActiveText() );
		}
		return $_lines;
	}

	public function WordsForRound($roundid) {
		return text_to_words($this->RoundText($roundid));
	}
	// duplicates not conflated
	public function ActiveTextWords() {
		static $_ary;
		if(! isset($_ary)) {
			$_ary = text_to_words($this->TextForWords());
		}
		return $_ary;
	}

	// including varieties of case
	// in form a[] = array(w, c);
    public function ActiveWordCounts() {
        $ary = [];
        foreach($this->ActiveTextWords() as $w) {
            $ary[$w] = isset($ary[$w]) ? $ary[$w] + 1 : 1;
        }
	    // restate as array of arrays
//	    $aret = [];
//	    foreach($ary as $w => $c) {
//		    $aret[] = array($w, $c);
//	    }
        return $ary;
    }

	public function ActiveWordsByCount() {
		$a = $this->ActiveWordCounts();
		foreach($a as $w => $c) {
			$ary[] = [$w, $c];
		}
		usort($ary,
			function($a1, $a2) {
				return ($a1[1] == $a2[1])
					?  (mb_strtolower($a1[0]) > mb_strtolower($a2[0]))
					: ($a2[1] - $a1[1]);
			}
		);
		return $ary;
	}

	public function ActiveWordsAlpha() {
		$a = $this->ActiveWordCounts();
		// convert to array type for context page
		$ary = [];
		foreach($a as $w => $c) {
			$ary[] = [$w, $c];
		}

		usort($ary,
			function($a1, $a2) {
				return mb_strtolower($a1[0]) > mb_strtolower($a2[0]);
			}
		);
		return $ary;
	}

	// a[word][count]
//	private function sort_words_by_alpha(&$word_array) {
//		uksort($word_array,
//			function($w1, $w2) {
//				return mb_strtolower($w1[0]) > mb_strtolower($w2[0]);
//			}
//		);
//	}



	// returns an array with $a[] = array(w, c);
    public function WordCountArray($w_array) {
        if(! is_array($w_array)) {
            assert(false);
            dump($w_array);
        }
	    // get counts from array of all words for lookup
        $wds = $this->ActiveWordCounts();
        $a = [];
        // for each queried word
        foreach($w_array as $w) {
            if(isset($wds[$w])) {
                $a[] = [$w, $wds[$w]];
            }
            else {
                $a[] = [$w, 0];
            }
        }
        return $a;
    }

    public function EnchantedWords($langcode) {
        // text is either submitted in a json query, or ActiveText();
        if ( array_key_exists( 'jsonqry', $_REQUEST ) ) {
            $request = json_decode ($_REQUEST['jsonqry'], true);
            $text = $request['text'];
        } else {
            $text = $this->TextForWords();
        }
        // words are stored in a transient array per language
        if(! isset($this->_enchanted_words[$langcode])) {
            $this->_enchanted_words[$langcode] =
                new DpEnchantedWords($langcode,
                    $text, $this->ProjectId());
        }
        $e = $this->_enchanted_words[$langcode];
        /** @var DpEnchantedWords $e */
        return $e;
    }


    // simple array of spell words in no particular order.
    public function SpellWordsArray($langcode) {
        /** @var $ew DpEnchantedWords */
        $ew = $this->EnchantedWords($langcode);
        return array_unique($ew->WordsArray());
    }

    public function AcceptedWordsArray($langcode) {
        return project_accepted_words_array($this->ProjectId(), $langcode);
    }

    public function FlagWordsArray($langcode) {
        $s = $this->SpellWordsArray($langcode);
        $b =  $this->BadWordsArray($langcode);
        $g =  $this->GoodWordsArray($langcode);
        $out = [];

        foreach($s as $a)
            if(! in_array($a, $g))
                $out[] = $a;

        foreach($b as $a)
            if(! in_array($a, $out))
                $out[] = $a;

        return $out;
    }

    private function ClearPhaseHold($phase, $holdcode) {
        $this->ReleasePhaseHold($phase, $holdcode);
    }

    private function ReleasePhaseHold($phase, $holdcode) {
        global $dpdb;
        $projectid = $this->ProjectId();
        $dpdb->SqlExecute("
            DELETE FROM project_holds
            WHERE phase = '$phase'
                AND projectid = '$projectid'
                AND hold_code = '$holdcode'");
        $this->LogProjectEvent(PJ_EVT_RELEASE, "release $holdcode Hold");
        $this->MaybeAdvanceRound();
        if ($phase == "P1" && $holdcode == "queue")
            $this->P1QueueReleaseNotify();
    }

    private function SetQueueHold($phase, $note = "") {
        $this->SetAutoHold($phase, "queue", $note);
    }

    public function ClearQueueHold($phase) {
        $this->ReleasePhaseHold($phase, "queue");
    }


    public function IsPPHold() {
        global $dpdb;
        $projectid = $this->ProjectId();
        return $dpdb->SqlOneValuePS("
            SELECT COUNT(1) FROM project_holds
            WHERE projectid = ?
                AND hold_code = 'pp'
                AND phase = 'PP'", [&$projectid]) > 0;
    }

    public function SetPPHold($note = "") {
        if(! $this->IsPPHold()) {
            $this->SetAutoHold("PP", "pp", $note);
        }
    }

	public function QCHoldNote() {
		global $dpdb;
		$projectid = $this->ProjectId();
		return $dpdb->SqlOneValuePS("
			SELECT note FROM project_holds
			WHERE projectid = ?
			      AND hold_code = 'qc'", [&$projectid]);
	}

	public function SetAutoQCHold() {
		$this->SetAutoHold("PREP", "qc");
	}
    public function SetQCHold($note = "") {
        // no permission required - set by implication
        $this->SetPhaseHold("PREP", "qc", $note);
    }
    public function ClearQCHold() {
        $this->ReleasePhaseHold("PREP", "qc");
    }
    public function ReleasePPHold() {
        $this->ReleasePhaseHold("PP", "pp");
    }

    public function ReleaseQCHold() {
        $this->ReleasePhaseHold("PREP", "qc"); 
    }
    public function ReleaseHoldId($id) {
        global $dpdb;
        $hold = $dpdb->SqlOneObject("
            SELECT phase, hold_code,  id, projectid FROM project_holds
            WHERE id = $id AND projectid = '{$this->ProjectId()}'");
        if(! $hold) {
            return;
        }
        assert($hold->projectid == $this->ProjectId());
        $dpdb->SqlExecute("
            DELETE FROM project_holds
            WHERE id = $hold->id AND projectid = '{$hold->projectid}'");
        $this->LogProjectEvent(PJ_EVT_RELEASE,
                "release {$hold->hold_code} Hold");
        $this->MaybeAdvanceRound();
        if ($hold->phase == "P1" && $hold->hold_code == "queue")
            $this->P1QueueReleaseNotify();
    }

    public function ExtraFilePaths() {
        global $dpdb;
        $projectid = $this->ProjectId();
        $path = build_path($this->ProjectPath(), "*");
        $filepaths = glob($path);

        $images = $dpdb->SqlValuesPS("
            SELECT imagefile FROM pages
            WHERE projectid = ?
            ORDER BY pagename", [&$projectid]);

        $notfiles = [];
        $extrapaths = [];
        foreach($images as $img) {
            $notfiles[] = basename($img);
        }
        $notfiles[] = "wordcheck";
        $notfiles[] = "text";
        $notfiles[] = "holding";

        foreach ($filepaths as $filepath) {
            $filename = basename($filepath);
            if ( !in_array( $filename, $notfiles ) ) {
                if(extension($filename) != "zip") {
                    $extrapaths[] = $filepath;
                }
            }
        }
        return $extrapaths;
    }

    public function SendImageZipFile() {
        global $Context;
        $projectid = $this->ProjectId();
        $zipstub = "{$projectid}_images";
        $files   = $this->ImageFilesInProjectDirectory();
        $Context->ZipSendFileArray($zipstub, $files);
    }

//    public function SendPPZipFile() {
//        global $Context;
//        $projectid = $this->ProjectId();
//        $zipstub = "{$projectid}";
//        $Context->ZipSendString($zipstub, $this->ActiveText());
//    }

    public static function CreateProject($title, $author, $projectmanager = "", $cp="", $type = "normal") {
        global $Context, $dpdb ;

        if (empty($type))
            $type = "normal";

        $projectid = $Context->NewProjectId();
//	    $username = $User->Username();

        assert($projectid != "");
        $sql = "
            INSERT INTO projects
            SET projectid           = ?,
                nameofwork          = ?,
                authorsname         = ?,
                username            = ?,
                createdby           = ?,
                phase               = 'PREP',
                LANGUAGE            = 'en',
                project_type         = ?,
                createtime          = UNIX_TIMESTAMP()";
        $args = [
            &$projectid, &$title, &$author, &$projectmanager, &$cp, &$type
        ];
        $ret = $dpdb->SqlExecutePS($sql, $args);
	    assert($ret == 1);
        
        $project = new DpProject($projectid);
//        $project->CreateProjectTable();
        $project->LogProjectEvent(PJ_EVT_CREATE);
        if ($type != 'normal')
            $project->SetAutoPMHold("PREP", "PM release to PP");
        else {
            $project->SetAutoQCHold();
            $project->SetQueueHold("P1", "P1 Queue Manager release");
            $project->SetAutoPMHold("PREP", "PM release to QC manager");
        }
        return $projectid;
    }
}


class DpEvent
{
    private $_event_type;
    private $_username;
    private $_event_time;
    private $_dtl1;
    private $_dtl2;
    private $_dtl3;

    public  function __construct($ary) {
        $this->_event_type = $ary["event_type"] ;
        $this->_username = $ary["username"];
        $this->_event_time = $ary["event_time"];
        $this->_dtl1 = (string) $ary["details1"];
        $this->_dtl2 = (string) $ary["details2"];
    }

    public function EventType() {
        return $this->_event_type;
    }

    public function Username() {
        return $this->_username;
    }

    public function EventTime() {
        return $this->_event_time;
    }

    public function Note() {
        return $this->_dtl1
            . ($this->_dtl2 ? ", " . $this->_dtl2 : "")
            . ($this->_dtl3 ? ", " . $this->_dtl3 : "");
    }

    public function ToString() {
        return "$this->_event_time  $this->_event_type  $this->_username  {$this->Note()}";
    }

}

// vim: sw=4 ts=4 expandtab
