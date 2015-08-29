<?php
/**
 * Created by PhpStorm.
 * User: don
 * Date: 3/12/2015
 * Time: 5:48 PM
 */


class DpQualProject {
	private $_row = null;

	public function __construct($projectid) {
		global $dpdb;
		$this->_row = $dpdb->SqlOneRow(
			"SELECT * FROM qual_projects
		WHERE projectid = '$projectid'");
	}

	public function ProjectId() {
		return $this->_row['projectid'];
	}

	public function NameOfWork() {
		return $this->_row['nameofwork'];
	}

	public function AuthorsName() {
		return $this->_row['authorsname'];
	}

	public function UserMayProof() {
		global $dpdb, $User;
		$username = $User->Username();
		$projectid = $this->ProjectId();
		$state = $dpdb->SqlOneValue(
			"SELECT state FROM qual_clones
			WHERE qual_projectid = '$projectid'
				AND username = '$username'");
		switch($state) {
			case 'A':
				return true;
			case 'N':
			case 'R':
			default:
				return false;
		}
	}

	public function Username() {
		return $this->_row['username'];
	}

	public function PageRows() {
		static $_rows;
		global $dpdb;
		if(! isset($_rows)) {
			$projectid = $this->ProjectId();
			$username = $this->Username();
			$_rows = $dpdb->SqlRows("
				SELECT 	pagename AS image,
				 		state,
				 		FROM_UNIXTIME(savedate) savetime,
				 		text
				 FROM qual_page_versions
				WHERE qual_projectid = '$projectid'
					AND username = '$username'");
		}
		return $_rows;
	}
	public function Pages() {
		static $_pages;
		global $dpdb;
		if(! isset($_pages)) {
			$_pages = array();
			$projectid = $this->ProjectId();
			$username = $this->Username();
			$rows = $dpdb->SqlRows("
				SELECT 	pagename AS image,
				 		state,
				 		FROM_UNIXTIME(savedate) savetime,
				 		text
				 FROM qual_page_versions
				WHERE qual_projectid = '$projectid'
					AND username = '$username'");
			foreach($rows as $row) {
				$_pages = new DpQualPage($row);
			}
		}
		return $_pages;
	}
}

	class DpQualPage {
		private $_row;
		public function __construct($row) {
			$this->_row = $row;
		}
		public function State() {
			return $this->_row['state'];
		}
		public function Image() {
			return $this->_row['image'];
		}
		public function Text() {
			return $this->_row['text'];
		}

		public function SaveTime() {
			return $this->_row['savetime'];
		}
	}