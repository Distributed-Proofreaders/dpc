<?PHP
$relPath = "./../../pinc/";
include_once($relPath . 'dpinit.php');
include_once($relPath . 'xml.inc');
include_once('../members/member.inc');

$username = Arg("username");
$username != ""
or die("No username specified");

$usr = new DpUser($username);
$usr->Exists()
or die("Invalid username - $username");

//Try our best to make sure no browser caches the page
header("Content-Type: text/xml");
header("Expires: Sat, 1 Jan 2000 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

echo "<?xml version='1.0' encoding='$charset' ?>
<memberstats xmlns:xsi='http://www.w3.org/2000/10/XMLSchema-instance' xsi:noNamespaceSchemaLocation='memberstats.xsd'>\n";

if ($usr->Privacy() == PRIVACY_PUBLIC) {
    echo "
		<userinfo id='" . $usr->Uid() . "'>
			<username>" . xmlencode($usr->Username()) . "</username>
			<datejoined>" . date("m/d/Y", $usr->DateCreatedInt()) . "</datejoined>
			<lastlogin>" . date("m/d/Y", $usr->LastSeenInt()) . "</lastlogin>\n";

    foreach (array("P1", "P2", "P3", "F1", "F2") as $round_id) {
        $current_page_count = $usr->RoundPageCount($round_id);
        $currentRank        = $usr->RoundRank($round_id);
        $bestDayCount       = $usr->BestRoundDayCount($round_id);
        $bestDayTimestamp   = $usr->BestRoundDay($round_id);
        $bestDayTime        = date("M. jS, Y", ($bestDayTimestamp - 1));

        if ($daysInExistence > 0) {
            $daily_Average = $current_page_tally / $daysInExistence;
        }
        else {
            $daily_Average = 0;
        }

        echo "<roundinfo id='$round_id'>
				<pagescompleted>$current_page_count</pagescompleted>
				<overallrank>$currentRank</overallrank>
				<bestdayever>
					<pages>$bestDayCount</pages>
					<date>$bestDayTime</date>
				</bestdayever>
				<dailyaverage>" . number_format($daily_Average) . "</dailyaverage>
			</roundinfo>\n";
    }

    echo "</userinfo>\n";

//Team info
    $teams = $usr->Teams();

    echo "
		<teaminfo>";
    foreach ($teams as $team) {
        /** @var DpTeam $team */
        echo "
			<team>
			<name>" . xmlencode($team->TeamName()) . "</name>
			<activemembers>" . $team->MemberCount() . "</activemembers>
			</team>";
    }
    echo "
		</teaminfo>";
}

echo "
</memberstats>";

?>
