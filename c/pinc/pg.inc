<?PHP

// Variables, functions, etc. (mostly URL-related) related to Project Gutenberg.

$PG_home_url            = "http://www.fadedpage.com/";
$PG_history_url         = "http://www.fadedpage.com/";
$PG_catalog_url         = "http://www.fadedpage.com/";
$PG_gutindex_url        = "http://www.fadedpage.com/";
$PG_faq_url             = "http://www.fadedpage.com/";
$PG_html_faq_url        = "http://www.fadedpage.com/";
$PG_donation_url        = "http://www.fadedpage.com/";
$PG_copyright_howto_url = "http://www.fadedpage.com/";
$PG_greek_howto_url     = $wiki_url . "/Transliterating_Greek/";
$PG_mailing_lists_url   = "http://www.fadedpage.com/";
$PG_in_progress_url     = "http://www.fadedpage.com/"; 

// -----------------------------------------------------------------------------

function get_pg_catalog_url_for_etext( $etext_number )
// Given a PG etext number, return a URL for the PG catalog page for that text.
{
    return "http://www.fadedpage.com/books/$etext_number";
}

// -----------------------------------------------------------------------------

function get_pg_catalog_link_for_etext( $etext_number, $link_text=null )
// Given a PG etext number, return an HTML <a> element
// that links to the PG catalog page for that text.
{
    $url = get_pg_catalog_url_for_etext( $etext_number );

    if ( is_null($link_text) )
    {
    //MS
        $link_text = sprintf( _('FadedPage etext #%d'), $etext_number );
    }

    return "<a href='$url'>$link_text</a>";
}

// vim: sw=4 ts=4 expandtab
?>
