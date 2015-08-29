<?php
/**
 * module: DpDb.class.php
 * User: don kretz
 * Date: 3/12/2013
 * Time: 8:14 PM
 */

/**
 * Class DpDb
 * Database abstraction layer encapsulating mysqli library.
 * Originally written for the old (deprecated) mysql library functions.
 * Revised for the current mysqli library, adding support for parameterized queries,
 * which are more efficient in many cases and provide a defense or SQL injection security issues.
 * 100% Plug compatible replacement for the original version.
 * Has also been implemented to encapsulate SQL Server.
 */

class DpDb
{
	/** * @var bool */
	private $_is_echo_queries   = false;
	/** * @var bool */
	private $_is_log_queries    = false;
	/** * @var bool */
	private $_is_time_queries   = false;
	/** * @var mysqli */
	private $_mysqli;
	/** @var $_result mysqli_result */
	private $_result;
	/** * @var string */
	private $_error_msg         = "";
	/** * @var string */
	private $_sql;
	/** * @var string */
	private $_error;
	/** * @var int */
	private $_marktime;
	/** * @var int */
	private $_querytime;

	/**
	 * @param bool $islog - log SQL statements
	 * @param bool $istime - capture SQL timing
	 * @param bool $isecho - echo SQL as comments embedded in HTML
	 */

	public function __construct($islog = false, $istime = false, $isecho = false) {
		global $db_server, $db_user, $db_password, $db_name;

		$this->_is_log_queries  = $islog ;
		$this->_is_time_queries = $istime ;
		$this->_is_echo_queries = $isecho;
		$this->_mysqli          = new mysqli("p:".$db_server, $db_user, $db_password, $db_name);

		if($this->_mysqli->connect_errno) {
			$this->_error_msg = $this->dbcerror();
		}
	}

	/**
	 *
	 */
	public function __destruct() {

	}

	/**
	 * @param $sql
	 *
	 * @return bool|mysqli_result
	 * returns false on error, true on empty, else result;
	 */
	private function sql_select($sql) {
		if($this->_is_echo_queries) {
			echo html_comment($sql);
		}
		if($this->_is_time_queries) {
			$this->_marktime = microtime(true);
		}

		$this->_sql = $sql;
		$this->_mysqli->query("SET NAMES 'utf8'");
		$this->_result = $this->_mysqli->query($sql);

		if($this->dberrno() != 0) {
			$this->_error = $this->dberror();
		}

		if($this->_is_log_queries) {
			$this->_querytime = microtime(true) - $this->_marktime;
			$this->Log("Execution time: $this->_querytime");
		}
		return $this->_result;
	}

	// identity value created by insertion
	/**
	 * @return mixed
	 */
	public function InsertId() {
		return $this->_mysqli->insert_id;
	}

	/**
	 * @return string
	 */
	public function ErrorMessage() {
		return $this->_error_msg;
	}

	/**
	 * @param $sql
	 *
	 * @return array
	 */
	public function SqlAssoc($sql) {
		return $this->SqlRows($sql, MYSQLI_ASSOC);
	}

	/**
	 * @param $sql
	 * @param int $flag
	 *
	 * @return array
	 */
	public function SqlRows($sql, $flag = MYSQLI_ASSOC) {
		$rows = array();
		$result = $this->sql_select($sql);
		if($result === false) {
			echo html_comment($sql);
			die($this->ErrorMessage());
		}
		if($result === true) {
			return array();
		}
		while($row = $result->fetch_array($flag)) {
			$rows[] = $row;
		}
		$result->free();
		return $rows;
	}

	/**
	 * @param $sql
	 * @param $args
	 *
	 * @return array
	 */
	public function SqlRowsPS($sql, $args) {
		if($this->_is_log_queries) {
			$this->Log("SqlRowsPS: $sql");
		}
		if($this->_is_time_queries) {
			$this->_marktime = microtime(true);
		}
		$stmt = $this->_mysqli->prepare($sql);
		if(! $stmt) {
			dump($sql); dump($args); dump("prepare failed.");
			assert(false); die();
		}

		// make the type-string
		$typestr = make_typestring($args);
		$params = array($typestr);
		$params = array_merge($params, $args);
		try {
			call_user_func_array(array($stmt, 'bind_param'), $params);
		}
		catch ( Exception $e ) {
			dump($e);
			die();
		}

		// query
		try {
			$stmt->execute();
		}
		catch ( Exception $e ) {
			dump($e);
			die();
		}

		/** @var mysqli_result $md */
		$md = $stmt->result_metadata();
		$parms = $this->bind_meta($md, $row);
		call_user_func_array(array($stmt, 'bind_result'), $parms);

		$rows = array();
		while($stmt->fetch()) {
			$rows[] = unserialize(serialize($row));
		}
		$stmt->close();

		if($this->_is_log_queries) {
			$this->_querytime = microtime(true) - $this->_marktime;
			$this->Log("Execution time: $this->_querytime");
		}
		return $rows;
	}

	/**
	 * @param $md
	 * @param $row
	 *
	 * @return array
	 */
	private function bind_meta($md, &$row) {
		$parms = array();
		/** @var mysqli_result $md */
		while($field = $md->fetch_field()) {
			$parms[] = &$row[$field->name];
		}
		return $parms;
	}

	/**
	 * @param $sql
	 *
	 * @return array
	 */
	public function SqlObjects($sql) {
		$objects = array();
		$result = $this->sql_select($sql);
		if(false === $result) {
			dump(html_comment($sql));
			die($this->ErrorMessage());
		}
		if(true === $result) {
			return array();
		}
		while($object = $this->_result->fetch_object()) {
			$objects[] = $object;
		}
		$result->free();
		return $objects;
	}

	/**
	 * @param $sql
	 *
	 * @return array|object|stdClass
	 */
	public function SqlOneObject($sql) {
		$result = $this->sql_select($sql);
		if(false === $result) {
			echo html_comment($sql);
			die($this->ErrorMessage());
		}
		if(true === $result) {
			return array();
		}
		$obj = $result->fetch_object();
		$result->free();
		return $obj;
	}

	/**
	 * @param $sql
	 * @param int $flag
	 *
	 * @return array|mixed
	 */
	public function SqlOneRow($sql, $flag = MYSQLI_ASSOC) {
		$result = $this->sql_select($sql);
		if(false === $result) {
			say($this->ErrorMessage());
			echo html_comment($sql);
			die($this->ErrorMessage());
		}
		if(true === $result) {
			return array();
		}
		if($result->num_rows > 0)
			$row = $result->fetch_array($flag);
		else
			$row = array();
		$result->free();
		return $row;
	}

	/**
	 * @param $sql
	 *
	 * @return array
	 */
	public function SqlValues($sql) {
		$ary = array();
		$result = $this->sql_select($sql);
		if(false === $result) {
			echo html_comment($sql);
			die($this->ErrorMessage());
		}
		while($row = $result->fetch_array(MYSQLI_NUM)) {
			$ary[] = $row[0];
		}
		$result->free();
		return $ary;
	}

	/**
	 * @param $sql
	 * @param null $default
	 *
	 * @return null
	 */
	public function SqlOneValue($sql, $default = null) {
		$result = $this->sql_select($sql);
		if(false === $result) {
			echo html_comment($sql);
			die($this->ErrorMessage());
		}
		if(true === $result) {
			return $default;
		}
		$row = $result->fetch_array(MYSQLI_NUM);
		$result->free();
		return $row[0] ;
	}

	public function SqlOneValuePS($sql, $args, $default = null) {
		$rows = $this->SqlRowsPS($sql, $args);
		if(count($rows) < 1) {
			return $default;
		}
		return current($rows[0]);
	}

	/**
	 * @param $sql
	 *
	 * @return int
	 */
	public function SqlExecute($sql) {
		if($this->_is_log_queries) {
			$this->Log("SqlExecute:
                $sql");
		}
		if($this->_is_time_queries) {
			$this->_marktime = microtime(true);
		}
		if($this->IsEcho()) {
			echo html_comment($sql);
		}
		$this->_sql = $sql;
		$this->_result = $this->_mysqli->query($sql);
		if($this->dberrno() != 0) {
			$this->_error = $this->dberror();
		}
		$ret = $this->affected_rows();
		if($this->_is_log_queries) {
			$this->_querytime = microtime(true) - $this->_marktime;
			$this->Log("Execution time: $this->_querytime");
		}
		return $ret;
	}

	/**
	 * @return int
	 */
	private function affected_rows() {
		return $this->_mysqli->affected_rows;
	}

	/**
	 * @return string
	 */
	private function dbcerror() {
		return $this->_mysqli->connect_error;
	}

	/**
	 * @return string
	 */
	private function dberror() {
		return $this->_mysqli->error;
	}
	//    private function dbcerrno() {
	//        return $this->_mysqli->connect_errno;
	//    }
	/**
	 * @return int
	 */
	private function dberrno() {
		return $this->_mysqli->errno;
	}

	/**
	 * @return null
	 */
	public function SqlTime() {
		return $this->_is_time_queries ? $this->_querytime : null;
	}

	// arguments are sql and an array of
	// argument references (not values).
	/**
	 * @param $sql
	 * @param $args
	 *
	 * @return int
	 */
	public function SqlExecutePS($sql, $args) {
		if($this->_is_log_queries) {
			$this->Log("SqlExecutePS: $sql");
		}
		if($this->_is_time_queries) {
			$this->_marktime = microtime(true);
		}
		$stmt = $this->_mysqli->prepare($sql);
		if(! $stmt) {
			dump($sql);
			dump($args);
			dump("prepare failed.");
			dump($this->_mysqli->error);
			assert(false);
			die($this->_mysqli->error);
		}

		// make the type-string
		$typestr = make_typestring($args);
		$params = array($typestr);
		$params = array_merge($params, $args);

		call_user_func_array(array($stmt, 'bind_param'), $params);
		$b = $stmt->execute();
		if(! $b) {
			dump($stmt->error);
		}

		$ret = $this->affected_rows();
		$stmt->close();
		if($this->_is_log_queries) {
			$this->_querytime = microtime(true) - $this->_marktime;
			$this->Log("Execution time: $this->_querytime");
		}
		return $ret;
	}

	/**
	 * @param $sql
	 *
	 * @return bool
	 */
	public function SqlExists($sql) {
		$result = $this->SqlOneRow($sql);
		return (boolean) $result;
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function IsTable($name) {
		$sql = "SHOW TABLES LIKE '$name'";
		$tblname = $this->SqlOneValue($sql);
		return ($tblname != "");
	}

	/**
	 * @param $table
	 * @param $col
	 *
	 * @return bool
	 */
	public function IsTableColumn($table, $col) {
		$sql = "SHOW COLUMNS FRM {$table} LIKE '$col'";
		$colname = $this->SqlOneValue($sql);
		return ($colname != "");
	}

	/**
	 * @param $text
	 */
	public function Log($text) {
		global $User;
		if(! $this->_mysqli) {
			assert(false);
			return;
		}
		$username = $User->IsLoggedIn()
			? $User->Username()
			: "(unavailable)";

		$errormsg = $this->ErrorMessage();

		$sql = "INSERT INTO log (
                    username,
                    eventtime,
                    logtext,
                    errormsg)
                VALUES (
                    '$username',
                    UNIX_TIMESTAMP(),
                    '$text',
                    '$errormsg')";
		$this->_sql = $sql;
		$this->_mysqli->query($sql);
		if($this->_mysqli->error) {
			say($this->_mysqli->error);
		}
	}

	/**
	 * @return bool
	 */
	public function Ping() {
		return $this->_mysqli->ping();
	}

	/**
	 * @return string
	 */
	public function Info() {
		return $this->_mysqli->info;
	}

	/**
	 *
	 */
	public function SetEcho() {
		$this->_is_echo_queries = true;
	}

	/**
	 * @return bool
	 */
	public function IsEcho() {
		return $this->_is_echo_queries;
	}

	/**
	 *
	 */
	public function ClearEcho() {
		$this->_is_echo_queries = false;
	}

	/**
	 *
	 */
	public function SetTiming() {
		$this->_is_time_queries = true;
	}

	/**
	 *
	 */
	public function ClearTiming() {
		$this->_is_time_queries = false;
	}

	/**
	 *
	 */
	public function SetLogging() {
		$this->_is_log_queries = true;
	}

	/**
	 *
	 */
	public function ClearLogging() {
		$this->_is_log_queries = false;
	}

	/**
	 * @param $tablename
	 *
	 * @return array
	 */
	public function FieldList($tablename) {
		$sql = "SELECT *
                FROM information_schema.columns
                WHERE table_name = '$tablename'";
		$objs = $this->SqlObjects($sql);
		$ary = array();

		foreach($objs as $value) {
			$ary[$value->COLUMN_NAME] = $value;
		}
		return $ary;
	}
}

/**
 * Required for parameterized queries.
 * Provides a string of characters describing data types of parameters.
 * @param $args
 *
 * @return string
 */
function make_typestring($args) {
	assert(is_array($args));
	$ret = "";
	foreach($args as $arg) {
		switch(gettype($arg)) {
			case "boolean":
			case "integer":
				$ret .= "i";
				break;
			case "double":
				$ret .= "d";
				break;
			case "string":
				$ret .= "s";
				break;
			case "array":
			case "object":
			case "resource":
			case "NULL":
			default:
				// call it a blob and hope
				// they know what they're doing.
				$ret .= "b";
				break;
		}
	}
	return $ret;
}

