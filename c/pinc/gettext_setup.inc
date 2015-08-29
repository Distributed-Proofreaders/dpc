<?PHP
// Localization setup
//include_once('languages.inc');

/* Explanation of logic:

If $_GET['lang'] is set (a page is linked as f. e. default.php?lang=sr) and
requested language is installed, then use that language and set language cookie
so that after clicking on links user would remain in the language. This is
useful both for external linking to a localised version and for users to test
it, so it has precedence over user preferences.

Else, if user is logged in, and has set language in user preferences, use that
language.

If user is not logged in, or has not set language in the preferences, use
language cookie, if there is one.

If there is no cookie try to guess an appropriate language from browser's
settings. There is no need to set the cookie because browser remains the same.

If everything else fails, default to English.

*/


/*
$intlang = $User->Language();
$locale = setlocale(LC_ALL, $intlang);
// Specify location of translation tables and choose domain
$gettext_domain = 'messages';
if ( function_exists('bindtextdomain') ) {
    bindtextdomain($gettext_domain, $dyn_locales_dir);
    bindtextdomain("iso_639", $system_locales_dir);
    if(function_exists("bind_textdomain_codeset")) {
        bind_textdomain_codeset ($gettext_domain, $charset);
        bind_textdomain_codeset ("iso_639", $charset);
    }
    textdomain($gettext_domain);
}
*/

