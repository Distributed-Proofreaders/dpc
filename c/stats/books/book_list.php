<?php
$relPath = "../../pinc/";
include_once $relPath.'dpinit.php';
include_once($relPath.'showtexts.inc');


$type = Arg("type");
$limit = Arg("limit");
$orderby = Arg("orderby");
$field_name = Arg("field_name");
$search_char = Arg("search_char");
$show_total = Arg("show_total");

$metal_map = array(
    'bronze' => _('Bronze'),
    'silver' => _('Silver'),
    'gold'   => _('Gold'),
);

function echo_other_type( $other_type ) {
    global $code_url, $limit, $orderby, $field_name, $search_char, $show_total, $metal_map;
    $metal_name = $metal_map[$other_type];
    echo "<a href='$code_url/stats/books/book_list.php"
            ."?limit=$limit"
            ."&type=$other_type"
            ."&orderby=$orderby"
            ."&field_name=$field_name"
            ."&search_char=$search_char"
            ."&show_total=$show_total'>$metal_name</a>";
}

function echo_other_order( $other_field_name, $other_direction ) {
    global $code_url, $limit, $type, $search_char, $show_total;
    echo "<a href='$code_url/stats/books/book_list.php"
            ."?limit=$limit"
            ."&type=$type"
            ."&orderby=$other_direction"
            ."&field_name=$other_field_name"
            ."&search_char=$search_char"
            ."&show_total=$show_total'>$other_direction</a>";
}

theme("$type E-Texts", "header");
?>

<h1>E-Texts</h1>

<?

$i = 0;
foreach ( array('gold','silver','bronze') as $metal ) {
    if ( $metal != $type ) {
        $i++;
        if ( $i > 1 )
            echo " | ";
        echo_other_type( $metal );
    }
}


$field_map = array(
    'nameofwork'   => _('Title'),
    'author'       => _('Author'),
    'modifieddate' => _('Submitted Date'),
);

$i = 0;
foreach ( array('nameofwork', 'author', 'modifieddate') as $other_field_name ) {
    $i++;
    if ( $i > 1 )
        echo " | ";

    $field_name_t = $field_map[$other_field_name];
    echo "<i>$field_name_t:</i>\n";

    echo_other_order( $other_field_name, 'asc' );
    echo " or ";
    echo_other_order( $other_field_name, 'desc' );
}

?>
<hr class="w75 center">

<?

showtexts($limit, $type, $orderby, $field_name, $search_char, $show_total);

theme("", "footer");
?>
