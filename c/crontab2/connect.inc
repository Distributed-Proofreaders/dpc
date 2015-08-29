<?PHP
include_once('/home/pgdpcanada/htdocs/c/pinc/udb_user.php'); //MS

class dbConnect
{
  var $error='';
  function dbConnect() {
    global $db_server, $db_user, $db_password, $db_name;
    $this->db_lk=mysql_connect($db_server,$db_user,$db_password);
    if (!$this->db_lk) {
      $this->error= _("Unable to connect to database server."); return 0;
    }

    if (!mysql_select_db($db_name,$this->db_lk)) {
      $this->error= _("Unable to locate database."); return 0;
    }
    else {
      return 1;
    }
  }

  function close() {
    if (isset($this->db_lk)) {
      mysql_close($this->db_lk);
    }
  }
}
?>
