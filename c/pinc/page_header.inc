<?PHP
global $relPath;
include_once($relPath.'dpinit.php');

// Display a page-header, either an image (if available) or a textual title.
function dp_page_header( $img_base, $title ) {
    global $dyn_dir, $dyn_url;

    $header_images_dir = "$dyn_dir/header_images";
    $header_images_url = "$dyn_url/header_images";

    if ( file_exists("$header_images_dir/$img_base.jpg") ) {
        echo "<br>
        <div class='center'>
        <img src='$header_images_url/$img_base.jpg' alt='$title'>
        </div>\n";
    }
    else {
        echo "<h1 class='center'>$title</h1>\n";
    }
}

// vim: sw=4 ts=4 expandtab
?>
