<?php

function metal_where($metal) {
    $bronze_where = 'WHERE phase IN ("P1", "P2", "P3", "F1", "F2")';
    $silver_where = 'WHERE phase IN ("PP", "PPV")';
    $gold_where   = 'WHERE phase = "POSTED"';
    switch($metal) {
        case "gold":
            return $gold_where;
            
        case "silver":
            return $silver_where;
           
        case "bronze":
            return $bronze_where;
          
        default:
            return null;
    }
}

function metal_count($metal) {
    global $dpdb;
    $where = metal_where($metal);
    return $dpdb->SqlOneValue("
        SELECT COUNT(1) FROM projects
        $where");
}

// List the specified projects,
// giving brief information about each.
function list_projects( $metal, $limit = "20", $sort = "title", $dir = "A") {
    global $dpdb;

    $dir = $dir == "A" ? "ASC" : "DESC";
    $where = metal_where($metal);
    $rows = $dpdb->SqlRows("
        SELECT *,
            nameofwork title,
            authorsname author,
            DATE(FROM_UNIXTIME(phase_change_date)) moddate
        FROM projects
        $where
        ORDER BY $sort $dir
        LIMIT $limit");

    echo "<br>";
    echo "\n";

    $counter = 0;
    foreach($rows as $project) {
        $counter++;
        $title = maybe_convert($project['nameofwork']);
        $author = maybe_convert($project['authorsname']);
        $language = $project['language'];
        $n_pages = $project["n_pages"];
        $moddate = $project["moddate"];
        $postednum = $project['postednum'];

        echo "$counter) \"$title\" $author ($language)<br>
            " . _("$n_pages pages; ") . "$moddate<br>\n";
        // Download info
        if ( !$postednum ) {
            echo link_to_fadedpage_catalog($postednum) . "<br><br>\n";
        }
    }
}
// vim: sw=4 ts=4 expandtab
