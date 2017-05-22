<?php
/*
 *  wpinsert.php - test adding a nwe post (type project)
*/

ini_set('display_errors', 1);
error_reporting(E_ALL);

$relPath = "../c/pinc/";
require $relPath . "dpinit.php";

$projectid = 'projectID51870bf940cce';
//$project = new DpProject($projectid);
$url = "http://www.pgdpcanada.net/wordpress/wp-admin/post-new.php?post_type=dpc_project?projectid=$projectid";
//$content = $project->PrePostText();
//$content = "Content will go here.";
//$post = array(
//	'ID'             => [ <post id> ] // Are you updating an existing post?
//  'post_content'   => $project->PrePostText(), // The full text of the post.
//  'post_content'   => "content will go here", // The full text of the post.
//  'post_name'      => $project->NameOfWork(), // The name (slug) for your post
//  'post_title'     => $projectid, // The title of your post.
//  'post_status'    => [ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] // Default 'draft'.
//  'post_type'      => 'project',
//  'post_author'    => [ <user ID> ] // The user ID number of the author. Default is the current user ID.
//  'ping_status'    => [ 'closed' | 'open' ] // Pingbacks or trackbacks allowed. Default is the option 'default_ping_status'.
//  'post_parent'    => [ <post ID> ] // Sets the parent of the new post, if any. Default 0.
//  'menu_order'     => [ <order> ] // If new post is a page, sets the order in which it should appear in supported menus. Default 0.
//  'to_ping'        => // Space or carriage return-separated list of URLs to ping. Default empty string.
//  'pinged'         => // Space or carriage return-separated list of URLs that have been pinged. Default empty string.
//  'post_password'  => [ <string> ] // Password for post, if any. Default empty string.
//  'guid'           => // Skip this and let Wordpress handle it, usually.
//  'post_content_filtered' => // Skip this and let Wordpress handle it, usually.
//  'post_excerpt'   => [ <string> ] // For all your post excerpt needs.
//  'post_date'      => [ Y-m-d H:i:s ] // The time post was made.
//  'post_date_gmt'  => [ Y-m-d H:i:s ] // The time post was made, in GMT.
//  'comment_status' => [ 'closed' | 'open' ] // Default is the option 'default_comment_status', or 'closed'.
//  'post_category'  => [ array(<category id>, ...) ] // Default empty.
//  'tags_input'     => [ '<tag>, <tag>, ...' | array ] // Default empty.
//  'tax_input'      => [ array( <taxonomy> => <array | string>, <taxonomy_other> => <array | string> ) ] // For custom taxonomies. Default empty.
//  'page_template'  => [ <string> ] // Requires name of template file, eg template.php. Default empty.
//);
//);

//$ret = wp_insert_post( $post, $wp_error );
//
//dump($wp_error);
//dump($ret);
//exit;


echo "<!DOCTYPE HTML>
<html>
<head>
<meta charset='utf-8'>
<title>{$projectid}</title>
</head>\n";

echo "<a href='$url'>create project</a>\n";

say("</div></body></html>");



