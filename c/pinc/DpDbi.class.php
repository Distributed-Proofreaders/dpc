<?php
error_reporting(E_ALL);

// NOTE file is *NOT* in the public_html root!
require_once __DIR__ . "/../../../Database.php";

/** @noinspection PhpUndefinedClassInspection */
class DpDb
{
    private $_is_echo_queries   = false;
    private $_is_log_queries    = false;
    private $_is_time_queries   = false;
    private $_mysqli;
    /** @var $_result mysqli_result */
    private $_result;
    private $_sql;
    private $_error             = 0;
	private $_error_msg         = "";
    private $_marktime;
	private $_querytime;

    public function __construct($islog = false, $istime = false, $isecho = false) {
        $this->_is_log_queries  = $islog ;
        $this->_is_time_queries = $istime ;
        $this->_is_echo_queries = $isecho;
        mysqli_report(MYSQLI_REPORT_OFF);
    	$this->_mysqli = getConnection();

        if($this->_mysqli->connect_errno) {
            $this->_error_msg = $this->dbcerror();
        }
    }

    public function __destruct() {
    }

    /**
     * Transaction Management.
     */
    public function beginTransaction() {
        $this->_mysqli->autocommit(FALSE);
    }

    public function commit() {
        $this->_mysqli->commit();
    }

    public function rollback() {
        $this->_mysqli->rollback();
    }

    private function sql_select($sql) {
        if($this->_is_echo_queries) {
            echo html_comment($sql);
        }
        if($this->_is_time_queries) {
            $this->_marktime = microtime(true);
        }

        $this->_sql = $sql;
        // query return false on error, true on empty, else result;
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

    public function EscapeString($str) {
        return $this->_mysqli->real_escape_string($str);
    }

    // identity value created by insertion
    public function InsertId() {
        return $this->_mysqli->insert_id;
    }

    /*
    private function sql_close() {
        $this->_mysqli->close();
    }
    */

	public function IsError() {
		return $this->_error != 0;
	}

	public function SQL() {
		return $this->_sql;
	}

    public function ErrorMessage() {
        return $this->_error_msg;
    }

    public function SqlAssoc($sql) {
        return $this->SqlRows($sql, MYSQLI_ASSOC);
    }

    public function SqlRows($sql, $flag = MYSQLI_ASSOC) {
        $rows = [];
        $result = $this->sql_select($sql);
        if($result === false) {
            echo html_comment($sql);
	        return null;
//            die($this->ErrorMessage());
        }
        if($result === true) {
            return [];
        }
        /** @noinspection PhpAssignmentInConditionInspection */
        while($row = $result->fetch_array($flag)) {
            $rows[] = $row;
        }
        $result->free();                     
        $this->DrainResults();
        return $rows;
    }

	public function SqlOneRowPS($sql, $args) {
		$rows = $this->SqlRowsPS($sql, $args);
		if($this->_is_log_queries) {
			$this->Log("SqlOneRowPS: $sql");
		}
		return count($rows) > 0 ? $rows[0] : [];
	}

    public function SqlRowsPS($sql, $args) {
        if($this->_is_log_queries) {
            $this->Log("SqlRowsPS: $sql");
        }
        if($this->_is_time_queries) {
            $this->_marktime = microtime(true);
        }
        $stmt = $this->_mysqli->prepare($sql);
        if(! $stmt) {
            dump("Prepare failed: (" . $this->_mysqli->errno . ") " .
                $this->_mysqli->error);
            dump($sql); dump($args);
            assert(false);
//	        die();
	        return null;
        }

        // make the type-string
        $typestr = make_typestring($args);
        $params = [$typestr];
        $params = array_merge($params, $args);
        try {
            call_user_func_array([$stmt, 'bind_param'], $params);
        }
        catch ( Exception $e ) {
            dump($e);
        }

        // query
        try {
            $stmt->execute();
        }
        catch ( Exception $e ) {
            dump($e);
        }

        /** @vaⅹ mysqli_result $md */
        $md = $stmt->result_metadata();
        $parms = $this->bind_meta($md, $row);
        call_user_func_array([$stmt, 'bind_result'], $parms);

        $rows = [];
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

    private function bind_meta($md, &$row) {
        $parms = [];
        /** @var mysqli_result $md */
        while($field = $md->fetch_field()) {
            $parms[] = &$row[$field->name];
        }
        return $parms;
    }

    public function SqlObjects($sql) {
        $objects = [];
        $result = $this->sql_select($sql);
        if($result === false) {
            sqldump($sql);
//            die($this->ErrorMessage());
	        return null;
        }
        if($result === true) {
            return [];
        }
        while($object = $this->_result->fetch_object()) {
            $objects[] = $object;
        }
        $result->free();  
        $this->DrainResults();
        return $objects;
    }

    private function DrainResults() {
//        while ($this->_mysqli->next_result()) {
//            $result = $this->_mysqli->use_result();
//            if ($result instanceof mysqli_result) {
//                $result->free();
//            }
//        }
    }

    public function SqlOneObject($sql) {
        $result = $this->sql_select($sql);
        if($result === false) {
            echo html_comment($sql);
	        return null;
//            die($this->ErrorMessage());
        }
        if($result === true) {
            return [];
        }
        $obj = $result->fetch_object();
        $result->free();
        $this->DrainResults();
        return $obj;
    }
    
    public function SqlOneRow($sql, $flag = MYSQLI_ASSOC) {
        $result = $this->sql_select($sql); 
        if($result === false) {
            say($this->ErrorMessage());
            echo html_comment($sql);
//            die($this->ErrorMessage());
	        return null;
        }
        if($result === true) {
            return [];
        }
        if($result->num_rows > 0)
            $row = $result->fetch_array($flag); 
        else
            $row = [];
        $result->free();                  
        $this->DrainResults();
        return $row;
    }

    public function SqlValuesPS($sql, $args) {
        $rows = $this->SqlRowsPS($sql, $args);
        if(! $rows || count($rows) < 1) {
            return [];
        }
        $ary = [];
        foreach($rows as $row) {
            $ary[] = current($row);
        }
        return $ary;
    }

    public function SqlValues($sql) {
        $objects = [];
        $result = $this->sql_select($sql);      
        if($result === false) {
            echo html_comment($sql);
	        return null;
//            die($this->ErrorMessage());
        }
        while($row = $result->fetch_array(MYSQLI_NUM)) {
            $objects[] = $row[0];
        }
        $result->free();                      
        $this->DrainResults();
        return $objects;
    }

    public function SqlOneValue($sql, $default = null) {
        $result = $this->sql_select($sql); 
        if($result === false) {
            echo html_comment($sql);
            die($this->ErrorMessage());
        }
        if($result === true) {
            return $default;
        }
        $row = $result->fetch_array(MYSQLI_NUM);
        $result->free();                 
        $this->DrainResults();
        if (is_null($row))
            return null;
        return $row[0] ;
    }

	public function SqlOneValuePS($sql, $args, $default = null) {
		$rows = $this->SqlRowsPS($sql, $args);
		if(count($rows) < 1) {
			return $default;
		}
		return(current($rows[0]));
	}


    public function SqlExecute($sql) {
        if($this->_is_log_queries) {
            $this->Log("SqlExecute: 
                $sql");
        }
        if($this->_is_time_queries) {
            $this->_marktime = microtime(true);
        }
        if($this->_is_echo_queries) {
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

    private function affected_rows() {
        return $this->_mysqli->affected_rows;
    }

    private function dbcerror() {
        return $this->_mysqli->connect_error;
    }

    private function dberror() {
        return $this->_mysqli->error;
    }
//    private function dbcerrno() {
//        return $this->_mysqli->connect_errno;
//    }
    private function dberrno() {
        return $this->_mysqli->errno;
    }

	public function SqlTime() {
		return $this->_is_time_queries ? $this->_querytime : null;
	}

    /*
    private function IsError() {
        if(empty($this->_mysqli))
            return false;
        return !empty($this->_mysqli->error);
    }
    */

    // arguments are sql and an array of 
    // argument references (not values).
    public function SqlExecutePS($sql, $args) {
        if($this->_is_log_queries) {
            $this->Log("SqlExecutePS: $sql");
        }
        if($this->_is_time_queries) {
            $this->_marktime = microtime(true);
        }
        if($this->_is_echo_queries) {
            echo html_comment($sql);
            echo html_comment(pdump($args));
        }
        $stmt = $this->_mysqli->prepare($sql);
        if(! $stmt) {
            dump($sql);
            dump($args);
            dump("prepare failed.");
            dump($this->_mysqli->error);
            assert(false);
	        return null;
//            die($this->_mysqli->error);
        }

        // make the type-string
        $typestr = make_typestring($args);
        $params = [$typestr];
        $params = array_merge($params, $args);
        
        call_user_func_array([$stmt, 'bind_param'], $params);
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

    public function SqlExistsPS($sql, $args) {
        $result = $this->SqlOneRowPS($sql, $args);
        return count($result) > 0;
    }

    public function SqlExists($sql) {
        $result = $this->SqlOneRow($sql);
        return count($result) > 0;
    }

    public function IsTable($name) {
        $sql = "SHOW TABLES LIKE '$name'";
        $tblname = $this->SqlOneValue($sql);
        return ($tblname != "");
    }

    public function IsTableColumn($table, $col) {
        $sql = "SHOW COLUMNS FRM {$table} LIKE '$col'";
        $colname = $this->SqlOneValue($sql);
        return ($colname != "");
    }

    private function Log($text) {
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
                VALUES (?, UNIX_TIMESTAMP(), ?, ?)";
        $args = [ &$username, &$text, &$errormsg ];
        $typestring = "sss";
        $this->_sql = $sql;
        $stmt = $this->_mysqli->prepare($sql);
        $stmt->bind_param("sss", $username, $text, $errormsg);
        $stmt->execute();
        if($this->_mysqli->error) {
            say($this->_mysqli->error);
        }
    }

    public function Ping() {
        return $this->_mysqli->ping();
    }

    public function Info() {
        return $this->_mysqli->info;
    }

    public function SetEcho() {
        $this->_is_echo_queries = true;
    }

    public function IsEcho() {
        return $this->_is_echo_queries;
    }

    public function ClearEcho() {
        $this->_is_echo_queries = false;
    }

    public function SetTiming() {
        $this->_is_time_queries = true;
    }

    public function ClearTiming() {
        $this->_is_time_queries = false;
    }

    public function SetLogging() {
        $this->_is_log_queries = true;
    }

    public function ClearLogging() {
        $this->_is_log_queries = false;
    }
    public function FieldList($tablename) {
        $sql = "SELECT * 
                FROM information_schema.columns
                WHERE table_name = '$tablename'";
        $objs = $this->SqlObjects($sql);
        $ary = [];

        foreach($objs as $value) {
            $ary[$value->COLUMN_NAME] = $value;
        }
        return $ary;
    }

    public function SetProfiling() {
        $this->_mysqli->query("set profiling_history_size = 100");
        $this->_mysqli->query("set profiling = 1");
    }

    public function ClearProfiling() {
        $this->_mysqli->query("set profiling = 0");
    }

    public function EchoProfiling() {
        $objs = $this->SqlObjects("SHOW PROFILES");
        foreach($objs as $obj) {
            say( $obj->Query_ID . ' - ' . $obj->Duration . "  " .  $obj->Query);
        }
    }
}

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
                // you know what you're doing.
                $ret .= "b";
                break;
        }
    }
    return $ret;
}

// vim: sw=4 ts=4 expandtab
