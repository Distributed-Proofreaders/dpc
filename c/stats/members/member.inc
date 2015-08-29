<?PHP
//A file full of member statistic functions

global $relPath;
// $relPath = "../../pinc/";
include_once $relPath . 'access_log.inc';
require_once $relPath . 'RoundsInfo.php';
require_once $relPath . 'DpPhpbb3.class.php';

$pm_icon_path = "$code_url/graphics/icon_contact_pm.gif";

function EchoMemberDpProfile( $muser ) {
    global $User;
    /** @var DpUser $muser */

    $m = new ForumUser($muser->Username());
	$age_days = number_format($muser->AgeDays())._(" days ago"); // '1,234 days'
	$daysSinceLastSeen = ($muser->LastSeenDays() >= 1
                            ? number_format($muser->LastSeenDays()) . _(" days ago")
                            : _("Today!"));

    $rows = array();
    $rows[] = array(_("Date Joined"), $muser->DateCreated() . " ($age_days)");
    $rows[] = array(_("Last Seen"),  date("m/d/Y", $muser->LastSeenInt()) . "&nbsp;($daysSinceLastSeen)");
    $rows[] = array(_("Location"), $m->Location());
    $rows[] = array(_("Occupation"), $m->Occupation());
    $rows[] = array(_("Interests"), $m->Interests());
    $rows[] = array(_("Website"), $m->WebSiteUrl());
    $rows[] = array(_('Roles'), MemberRoles( $muser ));
    $rows[] = array(_("Send PM"),  link_to_pm($muser->Username()), true);
    if($muser->Username() == $User->Username() || $User->IsAdmin()) {
        $rows[] = array(_('Page Count (All Rounds)'), $muser->PageCount());
    }
    $tbl = new DpTable("tblmember", "dptable w75 minitab");
    $tbl->NoColumnHeadings();
    $tbl->SetTitle("User Profile");
    $tbl->AddColumn("<", 0);
    $tbl->AddColumn("^", 1);
    $tbl->SetRows($rows);
    $tbl->EchoTable();
}

function MemberRoles($muser) {
    /** @var DpUser $muser */
    global $code_url;
	$muserStatus = "";

	if ($muser->PageCount() < 50) {
        $muserStatus .= "
            <img class='middle' src='$code_url/graphics/icon_newbie.png' alt='Newbie'
            title='"._("Newbie")."'>&nbsp;";
    }

	if ($muser->PageCount() >= 50) {
        $muserStatus .= "
            <img class='middle'  src='$code_url/graphics/icon_proofer.png' alt='Proofer'
            title='"._("Proofer")."'>&nbsp;";
    }

	if ($muser->IsProjectManager()) {
        $muserStatus .= "
            <img class='middle' src='$code_url/graphics/icon_pm.png' alt='Project Manager'
            title='"._("Project Manager")."'>&nbsp;";

    }
	if ($muser->HasRole("PP")) {
        $muserStatus .= "
            <img class='middle'  src='$code_url/graphics/icon_pp.png' alt='"
                ._("Post-Processor")."' title='"._("Post-Processor")."'>&nbsp;";
    }

	if ($muser->HasRole("PPV")) {
        $muserStatus .= "
            <img class='middle' src='$code_url/graphics/icon_ppv.png' 
                alt='Post-Processing Verifier' title='"
                ._("Post-Processing Verifier")."'>&nbsp;";
    }

	if ($muser->IsProjectFacilitator()) {
        $muserStatus .= "
            <img class='middle' src='$code_url/graphics/icon_pf.png' alt='"
            ._("Project Facilitator")."' title='"._("Project Facilitator")."'>&nbsp;";
    }

	if ($muser->IsSiteManager()) {
        $muserStatus .= "
            <img class='middle' src='$code_url/graphics/icon_sa.png' alt='"
                ._("Site Administrator")."' title='"._("Site Administrator")."'>&nbsp;";
    }

	$muserStatus .= "&nbsp;";

	return $muserStatus;
}

// XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

function EchoMemberRoundSelector( $muser, $roundid ) {
    global $Context;
    /** @var DpUser $muser */

    $opts = array();
    foreach($Context->Rounds() as $rid) {
        if($rid == $roundid) {
            continue;
        }
        $opts[] = link_to_member_stats($muser->Username(), $rid, $rid);
	}
    $choices = implode(" | ", $opts);
	echo "<p class='center'>"
	     . _("Display user stats for round: ") . $choices . "</p>\n";

	if ( ! $roundid) {
        return;
    }

}


