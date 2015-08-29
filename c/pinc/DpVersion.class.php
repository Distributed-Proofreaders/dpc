<?php
/**
 * Created by PhpStorm.
 * User: don
 * Date: 8/8/2015
 * Time: 8:50 PM
 */

function Versions($projectid, $pagename) {
	global $dpdb;
	$ary = array();
	$sql = "
		  SELECT projectid,
				pagename,
				version,
				version_time,
				username,
				phase,
				task,
				state
			FROM page_versions
			WHERE projectid = '$projectid'
				AND pagename = '$pagename'
			ORDER BY version";
	$rows = $dpdb->SqlRows($sql);
	foreach($rows as $row) {
		$ary[$row['version']] = new DpRowVersion($row);
	}
	return $ary;
}

class DpRowVersion extends DpVersion {
	function __construct($row) {
		$this->_row = $row;
	}
}

class DpLastVersion extends DpVersion {
	function __construct($projectid, $pagename) {
		global $dpdb;
		$version = $dpdb->SqlOneValue(
				"SELECT version FROM page_last_versions
				WHERE projectid = '$projectid' AND pagename = '$pagename'");
		parent::__construct($projectid, $pagename, $version);
	}
}

class DpVersion {
	protected $_row;
	protected $_version_number;

	function __construct($projectid, $pagename, $version) {
		global $dpdb;
		if(is_null($version)) {
			dump("proj $projectid  pg $pagename    ver $version");
			StackDump();
			die();
		}
		$this->_version_number = $version;

		$sql = "SELECT projectid,
						pagename,
						version,
						version_time,
						username,
						phase,
						task,
						state
			   FROM page_versions
			   WHERE projectid = ?
			   		AND pagename = ?
			   		AND version = ?";
		$args = array(&$projectid, &$pagename, &$version);
		$row = $dpdb->SqlOneRowPS($sql, $args);

		$this->init($row);
	}

	protected function init(&$row) {
		$this->_row = $row;
	}

	public function VersionNumber() {
		return $this->_version_number;
	}
	private function ProjectId() {
		return $this->_row['projectid'];
	}

	private function PageName() {
		return $this->_row['pagename'];
	}

	private function Version() {
		return $this->_row['version'];
	}

	public function Username() {
		return $this->_row['username'];
	}

	public function State() {
		return $this->_row['state'];
	}

	public function Phase() {
		return $this->_row['phase'];
	}

	private function IsAvailable() {
		return $this->State() == 'A';
	}

	public function CheckOutVersion($username) {
		assert($this->IsAvailable());
		global $dpdb;
		$projectid = $this->ProjectId();
		$pagename = $this->PageName();
		$version = $this->Version();
		$sql = "
			UPDATE page_last_versions
			SET username = ?,
				state = 'O',
				version_time = UNIX_TIMESTAMP()
			WHERE projectid = ?
				AND pagename = ?
				AND version = ?
				";
		$args = array(&$username, &$projectid, &$pagename, &$version);
		return $dpdb->SqlExecutePS($sql, $args);
	}
//	public function SaveVersionTemp($text) {
//		return $this->SaveTextState($text, "O");
//	}
//	public function SaveVersionComplete($text) {
//		return $this->SaveTextState($text, "C");
//	}

//	public  function SaveTextState($text, $state) {
//		global $dpdb;
//		$projectid = $this->ProjectId();
//		$pagename = $this->PageName();
//		$version = $this->Version();
//		$crc32 = crc32($text);
//		$textlen = mb_strlen($text);
//
//		$sql = "UPDATE page_versions
//				SET crc32 = ?,
//					textlen = ?,
//					state = ?,
//					version_time = UNIX_TIMESTAMP()
//				WHERE projectid = ?
//					AND pagename = ?
//					AND version = ?";
//		$args = array(&$crc32, &$textlen, &$state, &$projectid, &$pagename, &$version);
//		$dpdb->SqlExecutePS($sql, $args);
//		$ret = $this->UpdateText($text);
//		return $ret;
//	}

//	public function ReturnToRound() {
//		global $dpdb;
//		$projectid = $this->ProjectId();
//		$pagename = $this->PageName();
//		$version = $this->Version();
//
//		$sql = "UPDATE page_versions
//				SET state = 'A',
//					username = NULL,
//					version_time = UNIX_TIMESTAMP()
//				WHERE projectid = ?
//					AND pagename = ?
//					AND version = ?";
//		$args = array(&$projectid, &$pagename, &$version);
//		return $dpdb->SqlExecutePS($sql, $args);
//
//	}

//	public function UpdateText($text) {
//		$path = PageVersionPath($this->ProjectId(), $this->PageName(), $this->Version());
//		assert(file_exists($path));
//		return file_put_contents($path, norm($text));
//
//	}

	/*
	private function AddVersionRow($phase, $task, $state) {
		global $dpdb, $User;
		$projectid  = $this->ProjectId();
		$pagename   = $this->PageName();
		$version    = $this->Version();
		$username   = $User->Username();

		$sql = "INSERT INTO page_versions
                    SET projectid = ?,
                    	pagename = ?,
                    	version = ?,
                    	phase = ?,
                    	task = ?,
                    	username = ?,
                    	version_time = UNIX_TIMESTAMP(),
                    	state = ?";

		$args = array( &$projectid, &$pagename, &$version, &$phase, &$task, &$username, &$state );
		$ret  = $dpdb->SqlExecutePS( $sql, $args );
		assert( $ret == 1 );
		return $ret;
	}
	*/

//	private function NextVersionNumber() {
//		return $this->LastVersionNumber() + 1;
//	}

//	static public function LastVersionRow($projectid, $pagename) {
//		global $dpdb;
//		$version = Versions::LastVersionNumber($projectid, $pagename);
//		if($version < 0) {
//			return array();
//		}
//		$row = $dpdb->SqlOneRow( "SELECT projectid, pagename,
//									IFNULL($version, -1) version,
//									 state, username, phase, task,
//									 version_time, FROM_UNIXTIME(version_time) strtime
//								 FROM page_last_versions
//								 WHERE projectid = '$projectid' AND pagename = '$pagename'" );
//
//		return $row;
//	}

	/**
	 * @param string $phase
	 * @param string $task
	 * @param string $state
	 * @param string/null $text
	 *
	 * @return DpVersion
	 */
/*
	public function AddPageVersion($phase, $task, $state = "A", $text = null) {

		$nextversion = $this->NextVersionNumber();
		$this->AddVersionRow( $phase, $task, $state, $nextversion );
		if($text === null) {
			$text = $this->Text();
		}
		$n = $this->SetVersionText( $nextversion, $text );
		return $n;

	}
*/

//	static public function CopyLastVersionAhead($projectid, $pagename) {
//		$text = Versions::LastVersionText($projectid, $pagename);
//		Versions::AddVersionText($projectid, $pagename, $text);
//	}

	/*
	private function SetVersionText($version, $text) {
		$projectid = $this->ProjectId();
		$pagename  = $this->PageName();
		$vpath = PageVersionPath($projectid, $pagename, $version);
		EnsureWriteableDirectory(dirname($vpath));
		return file_put_contents($vpath, $text);
	}
	*/

//	static public function VersionPageTextPath($projectid, $pagename) {
//		$path = build_path(ProjectPath($projectid), "text");
//		return build_path($path, $pagename);
//	}

//	static public function VersionPath($projectid, $pagename, $version) {
//		$path = Versions::VersionPageTextPath($projectid, $pagename);
//		return build_path($path, "$pagename,$version");
//	}

	// don't update if there's no change in state
//	static public function UpdateLastPageVersion($projectid, $pagename, $state, $text = null) {
//		global $dpdb;
//		$version = Versions::LastVersionNumber($projectid, $pagename);
//		$sql = "UPDATE page_versions
//				SET state = ?
//				WHERE projectid = ?
//					AND pagename = ?
//					AND version = ?
//					AND state != ?";
//		$args = array(&$state, &$projectid, &$pagename, &$version, &$state);
//		$ret = $dpdb->SqlExecutePS($sql, $args);
//		if($ret && $text !== null) {
//			$ret = Versions::UpdateVersionText($projectid, $pagename, $version, $text);
//		}
//		return $ret;
//	}

	/*
	public function Text() {
		return $this->VersionText($this->Version());
//		$projectid = $this->ProjectId();
//		$pagename  = $this->PageName();
//		$version   = $this->Version();
//		$path = PageVersionPath($projectid, $pagename, $version);
//		assert(file_exists($path));
//		return file_get_contents($path);
	}
	*/

//	public function PreviousText() {
//		return $this->VersionText($this->Version()-1);
//	}

	public function VersionText() {
//		global $Context;
		$projectid = $this->ProjectId();
		$pagename  = $this->PageName();
		$version   = $this->VersionNumber();
		return PageVersionText($projectid, $pagename, $version);
	}
}

/*
function AddPageVersionRows($projectid, $phase, $task, $state) {
	global $dpdb, $User;
	$username = $User->Username();
	$sql = "INSERT INTO page_versions
				(projectid, pagename, version, phase, task, username, state, version_time)
			SELECT projectid, pagename, version+1, ?, ?, ?, ?, UNIX_TIMESTAMP()
			FROM page_last_versions
			WHERE projectid = ?";
	$args = array(&$phase, &$task, &$username, &$state, &$projectid);
	$dpdb->SqlExecutePS($sql, $args);

}

function AddPhasePageVersionTexts($projectid, $phase) {
	global $dpdb;
	$sql = "SELECT projectid,
				   pagename,
		           version,
		           state
			FROM page_versions
			WHERE projectid = '$projectid'
				AND phase = '$phase'";
	$objs = $dpdb->SqlObjects($sql);
	foreach($objs as $pv) {
		$path = PageVersionPath($pv->projectid, $pv->pagename, $pv->version);
		$nextpath = PageVersionPath($pv->projectid, $pv->pagename, $pv->version+1);
		assert(is_file($path));
		assert( copy($path, $nextpath));
	}
}

function AddPageVersionTexts($projectid) {
	global $dpdb;
	$sql = "SELECT projectid,
				   pagename,
		           version,
		           state
			FROM page_last_versions
			WHERE projectid = '$projectid'";
	$objs = $dpdb->SqlObjects($sql);

	foreach($objs as $pv) {
		$path = PageVersionPath($pv->projectid, $pv->pagename, $pv->version);
		$nextpath = PageVersionPath($pv->projectid, $pv->pagename, $pv->version+1);
		assert(is_file($path));
		assert( copy($path, $nextpath));
	}
}
*/
