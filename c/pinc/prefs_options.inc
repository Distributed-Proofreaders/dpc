<?php
global $relPath;
include_once($relPath.'resolution.inc');
include_once($relPath.'languages.inc');

define( 'BROWSER_DEFAULT_STR', _("Browser") );

$p_l = array(_("No Rounds"),
            _("First Round"),
            _("Second Round"),
            _("Both Rounds"));

$u_l = array(_("English"),
            _("French"),
            _("German"),
            _("Spanish"),
            _("Italian"),
            _("Portuguese"));

$i_r = $i_resolutions;

$f_f = array(BROWSER_DEFAULT_STR,
            'Courier',
            'Times',
            'Arial',
            'Lucida',
            'Monospaced',
            'DPCustomMono2');

$f_s = array( 0 => BROWSER_DEFAULT_STR,
              1 => '8pt', 2 => '9pt', 3 => '10pt', 4 => '11pt', 5 => '12pt', 6 => '13pt',
              7 => '14pt', 8 => '15pt', 9 => '16pt', 10 => '18pt', 11 => '20pt');

$u_radius = array('0', '2', '4', '6', '8', '10', '12', '14', '16', '18', '20');

// $u_intlang_tmp = installed_langs();
// $u_intlang_options[""] = BROWSER_DEFAULT_STR;
// foreach($u_intlang_tmp as $k => $v) {
	// $u_intlang_options[$v] = bilingual_name($v);
// }

$i_pm = array(_("All Projects"), _("Active Projects"), _("Search Page"));

$i_stats = array(_("Public"), _("Anonymous"), _("Private"));
define('PRIVACY_PUBLIC',    0);
define('PRIVACY_ANONYMOUS', 1);
define('PRIVACY_PRIVATE',   2);
// See ../faq/pophelp/prefs/set_privacy.html
// for definitions of these privacy categories.
?>
