<?PHP
// Variables (constants?) whose values are specific
// to the local installation of the DP code.

// During site configuration, identifiers delimited by double angle-brackets
// are replaced by the corresponding values in SETUP/configuration.sh.

$code_dir = '/home/.sites/143/site2/web/c';
$code_url='http://www.pgdpcanada.net/c';

$site_url = 'http://www.pgdpcanada.net';

$projects_dir = '/home/.sites/143/site2/web/projects';
$projects_url = 'http://www.pgdpcanada.net/projects';

$dyn_dir = '/home/.sites/143/site2/web/d';
$dyn_url = 'http://www.pgdpcanada.net/d';

$dynstats_dir = "$dyn_dir/stats";
$dynstats_url = "$dyn_url/stats";

$dyn_locales_dir = "$dyn_dir/locale";

$xmlfeeds_dir = "$dyn_dir/xmlfeeds";

$jpgraph_dir = '/home/.sites/143/site2/web/jpgraph';

$wiki_url = 'http://www.pgdpcanada.net/wiki';

$wikihiero_dir = '/home/.sites/143/site2/web/wikihiero';
$wikihiero_url = 'http://www.pgdpcanada.net/wikihiero';

$archive_projects_dir = '/home/.sites/143/site2/web/archive';

$forums_dir = '/home/.sites/143/site2/web/phpBB2';
$forums_url = 'http://www.pgdpcanada.net/phpBB2';
$reset_password_url        = "$forums_url/profile.php?mode=sendpassword";


$general_forum_idx                = '5';
$beginners_site_forum_idx         = '2';
$beginners_proofing_forum_idx     = '3';
$waiting_projects_forum_idx       = '15';
$projects_forum_idx               = '16';
$pp_projects_forum_idx            = '17';
$posted_projects_forum_idx        = '19';
$content_providing_forum_idx      = '10';
$post_processing_forum_idx        = '13';
$teams_forum_idx                  = '21';


$general_forum_url                = "$forums_url/viewforum.php?f=$general_forum_idx";
$waiting_projects_forum_url       = "$forums_url/viewforum.php?f=$waiting_projects_forum_idx";
$projects_forum_url               = "$forums_url/viewforum.php?f=$projects_forum_idx";
$pp_projects_forum_url            = "$forums_url/viewforum.php?f=$pp_projects_forum_idx";
$posted_projects_forum_url        = "$forums_url/viewforum.php?f=$posted_projects_forum_idx";
$post_processing_forum_url        = "$forums_url/viewforum.php?f=$post_processing_forum_idx";
$content_providing_forum_url   	  = "$forums_url/viewforum.php?f=$content_providing_forum_idx";
$beginners_site_forum_url         = "$forums_url/viewforum.php?f=$beginners_site_forum_idx";
$beginners_proofing_forum_url     = "$forums_url/viewforum.php?f=$beginners_proofing_forum_idx";
$teams_forum_url                  = "$forums_url/viewforum.php?f=$teams_forum_idx";


$uploads_dir = '/home/.sites/143/site2/.users/146/dpscans';
$uploads_host = 'pgdpcanada.net';
$uploads_account = 'dpscans';
$uploads_password = '2Proof';

// -----------------------------------------------------------------------------

$aspell_executable = '/usr/bin/aspell';
$aspell_prefix = "/usr";
$aspell_temp_dir = '/home/.sites/143/site2/web/d/sp_check';

$xgettext_executable = '/usr/bin/xgettext';
$system_locales_dir = '/usr/share/locale';

// -----------------------------------------------------------------------------

$no_reply_email_addr = 'no-reply@pgdpcanada.net';
$general_help_email_addr = 'dphelp@pgdpcanada.net';
$site_manager_email_addr = $general_help_email_addr;
$auto_email_addr = $general_help_email_addr;
$db_requests_email_addr = 'db-requests@pgdpcanada.net';
$promotion_requests_email_addr = 'dp-promote@pgdpcanada.net';
$ppv_reporting_email_addr = 'ppv-reports@pgdpcanada.net';
$image_sources_manager_addr = 'ism@pgdpcanada.net';

// -----------------------------------------------------------------------------

$testing = FALSE;
$use_php_sessions = TRUE;
$cookie_encryption_key = 'A_LONG_STRING_OF_GIBBERISH2';
$maintenance = 0;
$site_supports_metadata = FALSE;
$site_supports_corrections_after_posting = FALSE;
$auto_post_to_project_topic = FALSE;
$external_catalog_locator = 'z3950.loc.gov:7090/Voyager';
$charset = 'utf-8';

$jpgraph_FF='2';
$jpgraph_FS='9002';

$writeBIGtable = FALSE;
$readBIGtable = FALSE;

// -----------------------------------------------------------------------------

// If the gettext extension is compiled into PHP, then the function named '_'
// (an alias for 'gettext') will be defined.
// If it's not defined (e.g., on dproofreaders.sourceforge.net),
// define it to simply return its argument.
if (! function_exists('_') )
{
    function _($str) { return $str; }
}
?>
