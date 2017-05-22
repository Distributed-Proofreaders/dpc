<?PHP
require "/sharehome/htdocs/c/pinc/udb_user.php";

class dbConnect
{
    function connect() {
        global $db_server, $db_user, $db_password, $db_name;

        mysql_connect($db_server, $db_user, $db_password);
        mysql_select_db($db_name);
    }
}
?>
