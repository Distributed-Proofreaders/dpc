<?php
?>
<!doctype html>
<html>
<head>
<title>Search/Replace</title>
<style type='text/css'>
#regex_help {display: none;}
#regex_help_title {color: blue; cursor: pointer;}
</style>
<script type='application/javascript'>

function $(s) { return document.getElementById(s); }
function p$(s) { return pdoc.getElementById(s); }

var searchbox;
var replacebox;
var pdoc;
var editform;
var textbox;
var saved_text;
var search;
var undo;
var regex;

function init() {
    searchbox    = $("searchbox");
    replacebox   = $("replacebox");
    pdoc         = opener.document;
    editform     = opener.parent.proofframe.editform;
    textbox      = editform.text_data;
    saved_text   = textbox.value;
    undo         = $("undo");

    // searchbox.value = selectedText();
}

function is_regex() {
    return $("is_regex").checked;
}

/*
function set_regex() {
    var key = searchbox.value;
    regex = new RegExp(key, "g");
}

function efind() {
    var rslt, t, pos;
    var istart, iend, fword;
    if(searchbox.value.length == 0) {
        return;
    }

    set_regex();

    t   = textbox.value;
    pos = SelectionBounds();
    regex.lastIndex = pos.start + 1;
    rslt = regex.exec(t);
    if(! rslt && pos.start > 0) {
        regex.lastIndex = 0;
        rslt = regex.exec(t);
    }
    if(! rslt) {
        return;
    }

    fword = rslt[0];
    istart = rslt.index;
    iend = istart + fword.length;
    setSelection(istart, iend);
    
    textbox.focus();
}
*/

function ereplace() {
    var search = searchbox.value;
    saved_text = textbox.value;
    if (! is_regex()) {
        search = unwild(search);
    }
    textbox.value = textbox.value.replace(new RegExp(search, "g"), replacebox.value);
    undo.disabled = false;
}

/*
function ereplaceall() {
    saved_text = textbox.value;
    if (! is_regex()) {
        search = unwild(search);
    }
   
    textbox.value = textbox.value.replace(new RegExp(search, "g"), replacebox.value);
    undo.disabled = false;
}
*/

function erestore() {
    textbox.value = saved_text;
    undo.disabled = true;
}

/*
function setSelection(start, end) {
    if(typeof textbox.selectionStart == "number" 
            && typeof textbox.selectionEnd == "number") {
        textbox.selectionStart   = start;
        textbox.selectionEnd     = end;
    }
    else if(window.selection && window.selection.createRange) {
        textInputRange = textbox.createTextRange();
        // conflate end to beginning
        textInputRange.collapse(true);
        textInputRange.moveStart('character', start);
        textInputRange.moveEnd('character', start
            - (textbox.value
                .slice(0, start).split(/\r\n/).length - 1));
        textInputRange.select();
    }
}

function SelectionBounds() {
    var start = 0;
    var end = 0;
    var normalizedValue;
    var range;
    var textInputRange;
    var le;
    var endRange;
    var el = textbox;

    if(typeof el.selectionStart == "number" 
                && typeof el.selectionEnd == "number") {
        start = el.selectionStart;
        end   = el.selectionEnd;
    }
    else {
        range = window.selection.createRange();

        if(range && range.parentElement() == el) {
            len = el.value.length;
            normalizedValue = el.value.replace(/\r\n/g, "\n");

            // Create a working TextRange that lives in the input
            textInputRange = el.createTextRange();
            textInputRange.moveToBookmark(range.getBookmark());

            // Check if start and end are at the very end
            // of input -- moveStart/moveEnd don't return 
            // what we want in those cases
            endRange = el.createTextRange();
            endRange.collapse(false);

            if(textInputRange.compareEndPoints(
                            "StartToEnd", endRange) > -1) {
                start = end = len;
            } else {
                start = -textInputRange.moveStart("character", -len);
                start += normalizedValue.slice(0, start)
                                    .split(/\n/).length - 1;

                if(textInputRange.compareEndPoints(
                                "EndToEnd", endRange) > -1) {
                    end = len;
                } else {
                    end = -textInputRange.moveEnd("character", -len);
                    end += normalizedValue
                            .slice(0, end).split(/\n/).length - 1;
                }
            }
        }
    }

    return {
        start: start,
        end: end
    };
}
*/

function show_regex_help() {
    var regex_help_para = $("regex_help");
    regex_help_para.style.display = 'block';
    var arrow_span = $('regex_arrow');
    arrow_span.innerHTML = '&#9660;';
}

function hide_regex_help() {
    var regex_help_para = $('regex_help');
    regex_help_para.style.display = 'none';
    var arrow_span = $('regex_arrow');
    arrow_span.innerHTML = '&#9654;';
}

function ehelp() {
    var regex_help_para = $('regex_help');
    if (regex_help_para.style.display == 'none') {
        show_regex_help();
    }
    else {
        hide_regex_help();
    }
}

/*
function selectedText() {
    var sel;
    var seltext;
    var ierange;

    if(textbox.value.length === 0) {
        return "";
    }

    if(textbox.selectionEnd) {
        seltext = textbox.value.substring(
                textbox.selectionStart, textbox.selectionEnd);
    }

    else if(window.getSelection) {
        sel = window.getSelection();
    }

    else if(pdoc.selection) {
        sel = pdoc.selection;
    }

    else {
        return "";
    }

    if(!seltext || !seltext.length) {
        return "";
    }

    _is_tail_space = (seltext.charAt(seltext.length-1) == ' ');
    if(_is_tail_space) {
        seltext = seltext.substring(0, seltext.length-1);
    }
    return seltext;
}
*/

function unwild(str) {
    str = str.replace(/([\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|])/g, "\\" + "$1");
    return str;
    // var escapees = /([\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|])/g;
    // str = str.replace(escapees, "\\" + "$1");
   // var i = 0;
   // var subs = "";
   // var repl = ""
   // for (i=0;i<escapees.length; i++) {
       // repl = new RegExp("\\" + escapees[i], "g")
       // subs = "\\" + escapees[i];
// 
       // str = str.replace(repl, subs);
   // }
   // return(str);
}
</script>
</head>
<body onload="init();">
<form name='srchform'>
<table id="tbl">
    <tr><td class="right"> <? echo _("Search:"); ?> </td>
        <td> <input type="text" name="searchbox" id='searchbox' /> </td></tr>
    <tr><td class="right"> <? echo _("Replace:"); ?> </td>
        <td> <input type="text" name="replacebox" id='replacebox' /> </td></tr>
    <tr><td class="right"> <label for='is_regex'><? echo _("Regular Expression?"); ?></label>
</td>
        <td> <input type="checkbox" name="is_regex" id='is_regex' checked /> </td></tr>
</table>
<!--
<input type="button" value="<? echo _("Find"); ?>" onClick="efind()">
-->
<input type="button" value="<? echo _("Replace."); ?>" onClick="ereplace()">
<!--
<input type="button" value="<? echo _("Replace all."); ?>" onClick="ereplaceall()">
-->
<input type="button" id='undo' value="<? echo _("Undo."); ?>" onClick="erestore()" disabled />
</form>
<p><? echo _("Warning: Undo is only possible for the most recent replace!"); ?></p>
<p id='regex_help_title' onclick='ehelp();'><span id='regex_arrow'>&#9654;</span>
<?= _('Regular expression?')?></p>
<pre id='regex_help'>
. &mdash; any character<br />
[a-z0-9] &mdash; lowercase letters and numbers<br />
a{4} &mdash; four lowercase As<br />
[Aa]{6} &mdash; six As of either case<br />
A{2,8} &mdash; between 2 and 8 capital As<br />
[hb]e &mdash; 'he' or 'be'<br />
</pre>
</body>
</html>

