<?
include('dpsql.inc');	
include('connect.inc');  
new dbConnect();
header("Content-Type: text/plain");

// $mtime = microtime();
// $mtime = explode(" ",$mtime);
// $mtime = $mtime[1] + $mtime[0];
// $starttime = $mtime;

// Clear yesterday's birthdays.
mysql_query("
    DELETE FROM usersettings 
    WHERE setting = 'birthday_today'");

echo "Cleared yesterday's ".mysql_affected_rows()." birthdays.\n\n";

// $now = time();
// $today = getdate($now);

// $months = 24;
// $max = $months * 2592000;
$rows = $dpdb->SqlRows("
    SET @d = DAY(CURRENT_DATE());
    SET @m = MONTH(CURRENT_DATE());
    SET @y = YEAR(CURRENT_DATE());
    SET @dt = DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR);

    SELECT username, 
        @y - YEAR(FROM_UNIXTIME(date_created)) yearsago
    FROM users
    WHERE MONTH(FROM_UNIXTIME(date_created)) = @m
        AND DAY(FROM_UNIXTIME(date_created)) = @d
        AND @y - YEAR(FROM_UNIXTIME(date_created)) >= 2
    ORDER BY yearsago DESC, username;");

if (count($rows) == 0) {
    echo "No user birthdays today.";
    return;
}

echo "Today's birthdays:\n";

foreach ($rows as $row) {
    $username = $row["username"];
    $yearsago = $row["yearsago"];
    $dpdb->SqlExecute("
        INSERT INTO usersettings 
        VALUES('$username', 'birthday_today', '$years')");
    echo "  $username ($years)\n";
}

echo "\nBirthdays updated. Mazel tov!";

?>
