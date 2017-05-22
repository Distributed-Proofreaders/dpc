<?php
// This file holds functions needed for various language issues.
// It is designed as if it is a class - at first I wanted to make
// a class, but it needs no instantiation and PHP doesn't have static 
// variables, I am counting on programmers' discipline. 

// DO NOT USE THE ARRAYS DIRECTLY! Their design WILL change!
// Use the functions, or write your own functions if neither the
// existing ones are inappropriate.


function lang_html_header($langcode = FALSE) {
    global $Context;

    return "";

    if(! $langcode)
        $langcode = $Context->DefaultLocale();

    $direction = @lang_direction($langcode);
    return empty($direction)
        ? " lang='".short_lang_code($langcode)."'"
        : " lang='".short_lang_code($langcode)." dir=$dir'";
}

?>
