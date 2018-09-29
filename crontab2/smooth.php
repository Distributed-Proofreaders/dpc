<?php
/**
 * Created by PhpStorm.
 * User: don
 * Date: 1/14/2016
 * Time: 1:45 PM
 */

function SmoothNotify() {
    global $dpdb;
    $sql = "
        SELECT  n.projectid,
                n.username to_name,
                n.mode,
                DATE(FROM_UNIXTIME(p.smoothread_deadline)) deadline
        FROM projects p
        JOIN notify n
        ON p.projectid = n.projectid
            AND n.event = 'smooth'
        WHERE DATE(FROM_UNIXTIME(p.smoothread_deadline)) >= CURRENT_DATE()";

    $rows = $dpdb->SqlRows($sql);
    if (count($rows) < 1)
        return 0;

    foreach ($rows as $row) {
        /** @var DpProject $projectid */
        $projectid = $row["projectid"];
        $project = new DpProject($projectid);
        $from = $project->PPer();
        $to = $row["to_name"];
        $title = $project->Title();
        $author = $project->Author();
        $deadline = $project->SmoothReadDateString();
        $surls = ProjectSmoothDownloadUrls($projectid);
        $viewlink = link_to_view_text_and_images($projectid, "view the latest text and images online");
        $links = array();
        foreach ($surls as $fmt => $url) {
            $links[] = link_to_url($url, $fmt);
        }
        $links_str = implode(" · ", $links);
        $subject = "Available for Smooth Reading -- $title ($author)";
        $msg = "

        $title ( by $author.)

        Available for Smooth Reading until $deadline.

        Download your preferred format: 

        $links_str

        If you want to check something closely, you can $viewlink.

        Thank you!";

        DpContext::SendForumMessage($from, $to, $msg, $subject);
    }

    $sqldel = "
        DELETE n
        FROM projects p
        JOIN notify n
        ON p.projectid = n.projectid
            AND n.event = 'smooth'
        WHERE DATE(FROM_UNIXTIME(p.smoothread_deadline)) >= CURRENT_DATE()";

    $n = $dpdb->SqlExecute($sqldel);
    assert($n == count($rows));

    say("Deleted $n rows from notify.");

    return count($rows);
}
