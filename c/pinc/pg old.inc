<?PHP

// Variables, functions, etc. (mostly URL-related) related to Project Gutenberg.

$PG_home_url            = "http://gutenberg.ca/";
$PG_history_url         = "http://gutenberg.ca/";
$PG_catalog_url         = "http://gutenberg.ca/";
$PG_gutindex_url        = "http://gutenberg.ca/";
$PG_faq_url             = "http://gutenberg.ca/";
$PG_html_faq_url        = "http://en.wikipedia.org/wiki/Project_Gutenberg_Canada";
$PG_donation_url        = "http://gutenberg.ca/links/donations.html";
$PG_copyright_howto_url = "http://gutenberg.ca/";
$PG_greek_howto_url     = "http://gutenberg.ca/";
$PG_mailing_lists_url   = "http://gutenberg.ca/";
$PG_in_progress_url     = "http://gutenberg.ca/"; 
$PG_paypal_business_id  = "donate@pgdpcanada.net";

// -----------------------------------------------------------------------------

function get_pg_catalog_url_for_etext( $etext_number )
// Given a PG etext number, return a URL for the PG catalog page for that text.
{
    return "http://www.gutenberg.ca/etext/$etext_number";
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
        $link_text = sprintf( _('PGC etext #%d'), $etext_number );
    }

    return "<a href='$url'>$link_text</a>";
}

// vim: sw=4 ts=4 expandtab
?>
