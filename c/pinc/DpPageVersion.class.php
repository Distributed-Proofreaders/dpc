<?PHP
error_reporting(E_ALL);

class DpPageVersion {
	protected $_projectid;
	protected $_pagename;
	protected $_version;

	protected $_page;

	function __construct( $projectid, $pagename, $version ) {
		global $dpdb;

		! empty( $projectid )
			or die( "Project id argument omitted in DpPageVersion." );
		! empty( $pagename )
			or die( "Page name argument omitted in DpPageVersion." );
		$this->_projectid = $projectid;
		$this->_pagename = $pagename;
		$this->_version = $version;

		$sql = "SELECT pv.username,
                       pv.version_time,
                       pv.phase,
                       pv.task_code
                FROM page_versions pv
                WHERE projectid = ?
                    AND pagename = ?
                    AND version = ?";
		$args = array(&$projectid, &$pagename, &$version);
		$this->_row = $dpdb->SqlOneRowPS($sql, $args);
	}

	public function ProjectId() {
		return $this->_projectid;
	}

	public function PageName() {
		return $this->_pagename;
	}

	public function SaveNewVersion( $phase, $taskcode, $text, $username = "" ) {
		global $dpdb;
		global $User;

		if ( $username == "" ) {
			$username = $User->UserName();
		}
		$projectid = $this->_page->projectid;
		$pagename  = $this->_page->pagename;
		$version   = $this->_page->maxversionnumber + 1;

		// otherwise add a new pageversion
		$sql  = "INSERT INTO pageversions
                       ( projectid, pagename, phase, taskcode, version,
                        username, version_time) 
                   VALUES 
                           ( ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP() )";
		$args = array( &$projectid, &$pagename, &$phase, &$taskcode, &$version, &$username );
		$dpdb->SqlExecutePS( $sql, $args );

		$this->WriteTextFile( $text );
	}

	public function TaskCode() {
		return $this->_page->taskcode;
	}

	public function Version() {
		return $this->_page->version;
	}

	private function WriteTextFile( $text ) {
		$path = $this->FilePath();
		file_put_contents( $path, $text );
	}

	private function ProjectPagePath() {
		$p = ProjectPath( $this->ProjectId() );

		return build_path( $p, $this->PageName() );
	}

	private function FilePath() {
		$path = build_path( $this->ProjectPagePath(), $this->PageName() );
		$path .= ( ',' . $this->Version() );

		return $path;
	}

	public function Text() {
		$path = $this->FilePath();
		if ( ! $path || ( ! file_exists( $path ) ) ) {
			return null;
		}

		return file_get_contents( $path );
	}

	private function IsFile() {
		return file_exists( $this->FilePath() );
	}

	public function Delete() {
		if ( $this->IsFile() ) {
			unlink( $this->FilePath() );
		}

		global $dpdb;
		$dpdb->SqlExecute( "
                DELETE FROM pageversions
                WHERE projectid = '{$this->ProjectId()}'
                    AND pagename = '{$this->PageName()}'
                    AND version = {$this->Version()}" );
	}

	public function FileSize() {
		return $this->IsFile()
			? filesize( $this->FilePath() )
			: 0;
	}


	public static function IsPage( $projectid, $pagename ) {
		global $dpdb;

		return $dpdb->SqlExists( "
		SELECT 1 FROM pages
		WHERE projectid = '$projectid'
		AND pagename = '$pagename'" );
	}

	/*
	function ConvertProject( $projectid ) {
		global $dpdb;
		$project = new DpProject( $projectid );
		$pgs     = $project->PageRows();
		if ( count( $pgs ) < 1 ) {
			return;
		}
		foreach ( $pgs as $pg ) {
			$pagename = $pg['fileid'];
			$imgpath  = $project->ImagePath( $pagename );
			$imgtime  = filemtime( $$imgpath );
			if ( ! $this->IsPage($projectid, $pagename ) ) {
				$project->AddPage( $pagename, $imgpath, $imgtime );
			}
			$dpdb->SqlExecute( "
			REPLACE INTO " );
			$fname = $pg['image'];
			// get the date of the image file
			$imgpath = $project->ImagePath( $fname );
			$crtime  = filemtime( $imgpath );
		}
	}
	*/
}

