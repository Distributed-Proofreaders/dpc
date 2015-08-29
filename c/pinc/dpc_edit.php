<?php
/*
Plugin Name: DPC Edit
Plugin URI: http://www.pgdpcanada.net
Description: DPC Editor controls
Author: d kretz
Version: 0.1
Author URI: http://www.pgdpcanada.net

Copyright (C) 2014 don kretz
dkretz@www.pgdpcanada.net
http://www.pgdpcanada.net
*/
/*
    functions
    dpc_edit_add_metaboxes() {
        dpc_find_replace_box($post, $args) {
        dpc_preprocess_box($post, $args) {
        dpc_edit_box($post, $args) {
    add_action('add_meta_boxes', 'dpc_edit_add_metaboxes');

    dpc_preproc_save($post_id) {

    dpc_edit_preprocess( $content, $post_id ) {
    add_filter( 'content_edit_pre', 'dpc_edit_preprocess', 10, 2 );
    add_filter( 'preview_post_link', 'dpc_edit_preprocess', 10, 2 );

    add_action('save_post',  'dpc_preproc_save', 10, 2);
    add_action('admin_init', 'dpc_preproc_save', 10, 2);
*/

// ----------------------------------------------------------------
// queue up the js with functions called by the metaboxes
// ----------------------------------------------------------------

wp_enqueue_script('dpc_edit', plugins_url('/dpc_edit/dpc_edit.js'));


// ----------------------------------------------------------------
// Three metaboxes - Find/Replace, Preprocess, EditBox
// First manages local js, second sets options for PHP, third executes js.
// ----------------------------------------------------------------

// the callback function to draw the metabox
// called when the metabox queue is invoked

function dpc_find_replace_box($post, $args) {
    echo "
    Find        <input type='text' id='txtFind' name='txtFind'/>
    Replace     <input type='text' id='txtReplace' name='txtReplace'/>
    Ignore case <input type='checkbox' id='chki' name='chki'/>
    Multiline   <input type='checkbox' id='chkm' name='chkm'/>
   <br>
    <input type='button' class='dpc-button' name='btnFind'
        id='btnFind' value='Find' onclick='eFind()'/>
    <input type='button' class='dpc-button' name='btnRepl'
        id='btnRepl' value='Repl' onclick='eReplace()'/>
    <input type='button' class='dpc-button' name='btnFindRepl'
        id='btnFindRepl' value='Repl-&gt;Find'
        onclick='eReplaceNext()'/>
    <input type='button' class='dpc-button' name='btnReplaceAll'
        id='btnReplaceAll' value='Repl All' 
        onclick='eReplaceAll()'/>
    <br>\n";
}

function dpc_preprocess_box($post, $args) {
    wp_nonce_field( basename( __FILE__ ), 'dpc_preprocess_meta_nonce' );
    $meta = get_post_meta( get_the_ID(), 'dpc_preproc_meta' );

    echo "
    <form name='frm_preprocess' method='POST'>
    <ul>
    <li><input type='checkbox' class='dpc-checkbox' name='preproc_chk[pages]'
        id='chk_preproc_meta' value='Pages' />Pages</li>
    <li><input type='checkbox' class='dpc-checkbox' name='preproc_chk[sidenotes]'
        id='chk_preproc_meta' value='Sidenotes' />Sidenotes</li>
    <li><input type='checkbox' class='dpc-checkbox' name='preproc_chk[footnotes]'
        id='chk_preproc_meta' value='Footnotes' />Footnotes</li>
    <li><input type='checkbox' class='dpc-checkbox' name='preproc_chk[paragraphs]'
        id='chk_preproc_meta' value='Paragraphs' />Paragraphs</li>
    <li><input type='checkbox' class='dpc-checkbox' name='preproc_chk[linenums]'
        id='chk_preproc_meta' value='LineNums' />LineNums</li>
    </ul>
    <input type='submit' name='submit_preproc' value='Save' />
    </form>\n";
}

function dpc_edit_box($post, $args) {

    echo "
    <input type='button' class='dpc-button' name='btnUnmark'
        id='btnUnmark' value='&lt;(X)&gt;' onclick='eUnmark()'/>

    <input type='button' class='dpc-button' name='btnPara'
        id='btnPara' value='[p]' onclick='eParas()'/>\n";

}



// ----------------------------------------------------------------
// Attach to "add_meta_boxes" hook to install metaboxes
// ----------------------------------------------------------------

function dpc_edit_add_metaboxes() {
/*                 object id               title               callback fn             where   where   priority */
    add_meta_box( 'dpc_edit_preprocess',   'Preprocess',       'dpc_preprocess_box',   'post', 'side', 'high');
    add_meta_box( 'dpc_edit_edit',         'js editor code',   'dpc_edit_box',         'post', 'side', 'high');
    add_meta_box( 'dpc_edit_find_replace', 'Find and Replace', 'dpc_find_replace_box', 'post', 'side', 'high');
}

add_action('add_meta_boxes', 'dpc_edit_add_metaboxes');




// -------------------------------------------------------------------------------
// Attach to "save_post" action (how about getting options to populate form?)
// Theory B: Attach to 'admin_init' because we always come back to some admin page
// -------------------------------------------------------------------------------



function dpc_preproc_save($post_id) {
    global $post;
    // referencing $post below - is it supposed to be an argument?

    // nothing to do if no nonce.

    // if ( ! isset( $_POST['dpc_preproc_nonce'] ) || 
         // ! wp_verify_nonce( $_POST['dpc_preproc_nonce'], basename( __FILE__ ) ) ) {
        // return $post_id;
    // }

    /* Get the meta key. */
    $meta_key = 'dpc-preproc-meta';

    // $post_type = get_post_type_object( $post->post_type );

    /* Check if the current user has permission to edit the post. */
    // if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) )
        // return $post_id;

    var_dump($_POST);
    /* Get the posted data and sanitize it for use as an HTML class. */
    $new_meta_value = ( 
        isset( $_POST['dpc-preproc-meta'] ) 
            ? $_POST['dpc-preproc-meta']
            : '' );


    /* Get the meta value of the custom field key. */
    $meta_value = get_post_meta( $post_id, $meta_key, true );

    /* If a new meta value was added and there was no previous value, add it. */
    if ( $new_meta_value && '' == $meta_value )
        add_post_meta( $post_id, $meta_key, $new_meta_value, true );

    /* If the new meta value does not match the old value, update it. */
    else if ( $new_meta_value && $new_meta_value != $meta_value )
        update_post_meta( $post_id, $meta_key, $new_meta_value );

    /* If there is no new meta value but an old value exists, delete it. */
    else if ( '' == $new_meta_value && $meta_value )
        delete_post_meta( $post_id, $meta_key, $meta_value );
    
}

add_action('admin_init', 'dpc_preproc_save', 10, 2);
// add_action('save_post',  'dpc_preproc_save', 10, 2);

function dpc_edit_preprocess( $content, $post_id ) {
  // Process content here
  return $content;
}

add_filter( 'content_edit_pre', 'dpc_edit_preprocess', 10, 2 );
add_filter( 'preview_post_link', 'dpc_edit_preprocess', 10, 2 );


?>

