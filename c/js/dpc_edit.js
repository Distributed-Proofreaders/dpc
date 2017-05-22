
// Version 1.1

function $(str) {
    return document.getElementById(str);
}

function eFootnotes() {
    var anchors = [];
    var footnotes = [];
    var t = content().value;
    var re1 = /\[.+?\]/;
    var re2 = /\[Footnote\s*([^:]*):\s*([\S\s]*?)\s*]/i; // in trouble if "[" within footnote

    var loc = re1.exec(t);
    while(loc) {
        anchors[loc[0]] = loc;
        re1.exec();
    }
    loc = re2.exec(t);
    while(loc) {
        footnotes[loc[1]] = loc[2];
        re2.exec();
    }
        // in trouble if footnote referenced more than once.
    for(var i = 0; i < anchors.length; i++) {
        // replace anchor n with footnote n, with proper dressing
        // delete footnote n
    }
}

function seek_regex(target) {
    var istart, iend;
    var t = content().value;
    var pos = selectionBounds();
    var _re = new RegExp(target);
    _re.lastIndex = pos.end;
    rslt = _re.exec(t);
    if(! rslt && pos.start > 0) {
        _re.lastIndex = 0;
        rslt = _re.exec(t);
    }
    if(! rslt) {
        return;
    }
    istart = rslt.index;
    iend = istart + rslt[0].length;
    setSelection(istart, iend);
    showSelection();
    content().focus();
}

// event handler for eb_button controls.
var _regex;
var _is_tail_space;

function set_regex() {
    var key = $("txtFind").value;
    var flags = 'g' + ($("chki").checked ? 'i' : '')
                    + ($("chkm").checked ? 'm' : '');
    _regex = new RegExp(key, flags);
}

function eFind() {
    var rslt, t, pos;
    var istart, iend;
    if($("txtFind").value.length === 0) {
        if(selectedText().length === 0) {
            return;
        }
        $("txtFind").value = selectedText();
    }
    set_regex();
    t   = content().value;
    pos = selectionBounds();
    _regex.lastIndex = pos.end;
    rslt = _regex.exec(t);
    if(! rslt && pos.start > 0) {
        _regex.lastIndex = 0;
        rslt = _regex.exec(t);
    }
    if(! rslt) {
        return;
    }

    istart = rslt.index;
    iend = istart + rslt[0].length;
    setSelection(istart, iend);
    showSelection();
    rslt.index += rslt[0].length;

    content().focus();
}

function eReplace() {
    if($("txtReplace").value.length === 0) {
        if(! confirm('Replacement text is empty. Remove selection?')) {
            return;
        }
    }
    var iend = selectionBounds().end;
    var rstr = selectedText().replace(_regex, $("txtReplace").value);
    set_text(rstr);
    setCursor(iend + 1);
}

function eReplaceAll() {
    if($("txtReplace").value.length === 0) {
        if(! confirm('Replacement text is empty. Delete matches?')) {
            return;
        }
    }
    var istart = selectionBounds().start;
    var irepl  = $("txtReplace").value;
    set_regex();
    var rslt = text().replace(_regex, irepl);
    set_text(rslt);
    setCursor(istart + 1);
}

function replaceSelection(str) {
    if(_is_tail_space) {
        str += ' ';
    }

    // IE?
    if(document.selection) {
        var sel = document.selection;
        if(!sel || !sel.createRange) {
            return;
        }
        var ierange = sel.createRange();
        ierange.text = str;
        sel.empty();
    }
    else {
        var itop   = content().scrollTop;
        var istart = content().selectionStart;

        content().value =
            content().value.substring(0, content().selectionStart)
            + str + content().value.substring(content().selectionEnd);
        content().selectionEnd =
            content().selectionStart = istart + str.length;
        content().scrollTop = itop;
    }
    content().focus();
}

function selectedText() {
    var sel;
    var seltext;
    var ierange;
    if(!content() || content().value.length === 0) {
        return "";
    }

    if(document.selection) {
        sel = document.selection;
        if(!sel ||  !sel.createRange) {
            return '';
        }
        ierange = sel.createRange();
        seltext = ierange.text;
    } else if(content().selectionEnd) {
        seltext = content().value.substring(
                content().selectionStart, content().selectionEnd);
    } else {
        return '';
    }

    if(!seltext || !seltext.length) {
        return '';
    }

    _is_tail_space = (seltext.charAt(seltext.length-1) == ' ');
    if(_is_tail_space) {
        seltext = seltext.substring(0, seltext.length-1);
    }
    return seltext;
}

function content() {
    return $('content');
}

function text() {
    if(isSelection()) {
        return selectedText();
    }
    else {
        return content().value;
    }
}

function set_text(txt) {
    if(isSelection()) {
        replaceSelection(txt);
    }
    else {
        content().value = txt;
    }
}

function edit() {
    set_text(text().replace(/<\/?[^>]*?>/ig, "")
            .replace(/\[\/?[^\]]*\]/ig, ""));
}

function fixmisc() {
    set_text(text()
        .replace(/<span\s+class=.sidenote.>([\s\S]*?)<\/span>/ig, "[sn]$1[/sn]")
    .replace(/<span\s+class=["']sc["']>([\s\S]*?)<\/span>/ig, "[sc]$1[/sc]")
	.replace(/<sc>([\s\S]*?)<\/sc>/ig, "[sc]$1[/sc]")
    .replace(/<span class=.bold.>([^<]*)<a [^>]*><\/a><\/span>/im,
            "[arttitle]$1[/arttitle]")
     .replace(/<span class=.arttitle.>([\s\S]*?)<\/span>/ig, "[arttitle]$1[/arttitle]")
    .replace(/<div [^>]*?class=.article.[^>]*?>([\s\S]*)(<\/div>)?\s*$/im, 
                            "[article]$1[/article]")
    .replace(/⁄/, "/")     // bogus slash used for math
    .replace(/<div class="author">([\s\S]*?)<\/div>/, 
                '[author]$1[/author]'));
}

function fixparas() {
    var txt;
    var pos = selectionBounds();

    if(isSelection()) {
        txt = selectedText().replace(
                /<div\sclass="p">([\S\s]*)<\/div>/ig, "[p]$1[/p]");
        replaceSelection(txt);
        replaceSelection(selectedText()
            .replace(/<p([^>]*?)>/ig, "[p$1]")
            .replace(/<\/p>/ig, "[/p]"));
    }
    else {
        set_text(
            text()
            .replace(/<p([^>]*?)>/ig, "[p$1]")
            .replace(/<\/p>/ig, "[/p]"));
    }
    setSelection(pos.start, pos.start);
}

// ----------------------------------------------------------------

function fix_illus_table() {
    var otbl;
    var c;
    var rpltxt;

    c = selectedText();
    if(! c || ! c.length) {
        // find the next table/illo
        seek_regex(/<table\s[^>]*illustration[\s\S]*?<\/table>/i);
        return;
    }
    otbl = new Table(c);
    rpltxt = otbl.html();
    if(rpltxt.length > 0) {
        replaceSelection(rpltxt);
    }
}

function Table(text) {

    this.elements = [];

    var re_table = /^([\s\S]*?)<table\s+([^>]*)>([\s\S]*?)<\/table>([\S\s]*)$/i;
    var tbl = text.match(re_table);

    if(! tbl || ! tbl.length) {
        return;
    }

    this.prefix  = tbl[1];
    this.atts    = tbl[2];
    this.inside  = tbl[3];
    this.suffix  = tbl[4];


    var match;
    var re_row = /<tr[^>]*>([\S\s]*?)<\/tr>/ig;
    this.rows = this.inside.match(re_row)

    for(var ir = 0; ir < this.rows.length; ir++) {
        // construct row object with array of cells objects
        var r = new Trow(this.rows[ir]);
        if(!r || ! r.cells || r.cells.length != 1) {
            return;
        }

        // for each cell object maybe attach it as
        // a table object
        for(var ic = 0; ic < r.cells.length; ic++) {
            var c = r.cells[ic];
            if(! c || ! c.type) {
                return;     // return if not a recognizable cell type
            }

            //noinspection FallthroughInSwitchStatementJS
            switch(c.type) {
                // if it's an image, designate it the table image
                // and also put it in the cell array
                case "image":
                    this.image = c;
                    this.elements[this.elements.length] = c;
                    break;
                case "caption":
                case "credit":
                case "key":
                    this.elements[this.elements.length] = c;
                    break;

                default:
                    return;
            }
        }
    }
    
}

Table.prototype.html = function () {
    var elem;

    if(! this.image) {
        return "";
    }
    // construct [illo] tag
    var rtn = "\r[illo "
        + this.image.class()
        + " " + this.image.source()
        + "]";

    // construct the internal elements
    for(var ie = 0; ie < this.elements.length; ie++) {
        elem = this.elements[ie];
        if(elem.type != "image") {
            rtn = rtn + "\n" + elem.html();
        }
    }

    rtn = this.prefix + rtn + "\n[/illo]" + this.suffix;
    return rtn;
};

// decompose a table row
function Trow(text) {
    // extract <td> from <tr>
    var re_tcell = /<td\s*([^>]*)>([\S\s]*?)<\/td>/ig;
    // add property cells as empty array
    this.cells = [];
    // locate <td>s, convert to objects, and add to cells array
    var match;
    while((match = re_tcell.exec(text))) {
        var cel = get_cell(match[0]);
        if( cel ) {
            this.cells.push(cel);
        }
    }
}

function get_cell(text) {
    var obj = new Img(text);
    if(obj.type == "image") {
        return obj;
    }
    obj = new Credit(text);
    if(obj.type == "credit") {
        return obj;
    }

    obj = new Caption(text);
    if(obj.type == "caption") {
        return obj;
    }
    obj = new Key(text);
    if(obj.type == "key") {
        return obj;
    }
    return null;
}

function Img(text) {
    var re = /<td[^>]*fig(left|right|center)[\s\S]*?<img[\s\S]*?width:\s*(\d+)px[\s\S]*?src="([\s\S]*?)"/i;
    var obj = text.match(re);

    if(! obj || obj.length < 4) {
        return;
    }

    this.type = "image";
    this.lrc = obj[1];
    this.width = obj[2];
    this.src = obj[3];
}

Img.prototype.class = function () {
    var w;
    if(this.width) {
        w = parseInt(this.width, 10);
    }
        
    var ret = "";
    if(this.lrc) {
        switch(this.lrc) {
                
            case "left":
                ret = "lfloat ";
                break;

            case "right":
                ret = "rfloat ";
                break;

            default:
                if(w >= 600) {
                    return "class='w100'";
                }
                ret = "rfloat";
        }
    }

    if(! w || w >= 600) {
        return "class='" + ret + "'";
    }

    if(w < 100) {
        ret = "class='w10 " + ret + "'";
    }
    else if(w < 125) {
        ret = "class='w15 " + ret + "'";
    }
    else if(w < 150) {
        ret = "class='w20 " + ret + "'";
    }
    else if(w < 200) {
        ret = "class='w25 " + ret + "'";
    }
    else if(w < 300) {
        ret = "class='w35 " + ret + "'";
    }
    else if(w < 500) {
        ret = "class='w50 " + ret + "'";
    }
    else if(w < 600) {
        ret = "class='w65 " + ret + "'";
    }
    else {
        ret = "class='w100" + ret + "'";
    }
    
    return ret;
};

Img.prototype.source = function() {
    if(this.src) {
        return "src='" + this.src + "'";   
    }
    return "";
};

// captions have something with class "caption" in the cell
function Caption(text) {
    var re = /<td\s*([^>]*caption[^>]*)>([\s\S]*)<\/td>/i;
    var obj = text.match(re);
    if(! obj || obj.length < 3) {
        return;
    }
    this.type = "caption";
    this.attr = obj[1];
    this.value = obj[2];

}

Caption.prototype.html = function () {
    return "[caption]" + this.value + "[/caption]";
};

// it's a credit if the font is f80
function Credit(text) {
    var re = /<td\s*([^>]*f80[^>]*?)>([\s\S]*)<\/td>/i;
    var obj = text.match(re);
    if(! obj || obj.length < 3) {
        this.type = "";
        return;
    }
    this.type = "credit";
    this.attr = obj[1];
    this.value = obj[2];
}

Credit.prototype.html = function () {
    return "[credit]" + this.value + "[/credit]";
};

// try saying it's a key if the class if f90.
function Key(text) {
    var re = /<td\s*([^>]*f90[^>]*?)>([\s\S]*)<\/td>/i;
    var obj = text.match(re);
    if(! obj || obj.length < 3) {
        this.type = "";
        return;
    }
    this.type = "key";
    this.attr = obj[1];
    this.value = obj[2];
}

Key.prototype.html = function () {
    return "[key]\n" + this.value + "\n[/key]";
};


// -------------------------------------------------------------------

function eOpenImage() {
    var match;
    var pgnum = null;
    var b = selectionBounds();
    if(! b) {
        return;
    }
    var re = /\[pgnum\]\s*(\S*)\s*\[\/pgnum\]/ig;
    while((match = re.exec(content().value))) {
        if(re.lastIndex > b.start) {
            break;
        }
        pgnum = match[1];
    }
    if(! pgnum) {
        return;
    }

    pgnum = "0314";
    
    var url = '/vol04/images/' + pgnum + ".png";
    window.open(url, "img_wondow");
}

function setSelection(start, end) {
    var textInputRange;
    if(typeof content().selectionStart == "number"
        && typeof content().selectionEnd == "number") {
        // Move the cursor
        content().selectionStart   = start;
        content().selectionEnd     = end;
    }
    else if(document.selection
                    && document.selection.createRange) {
        textInputRange = content().createTextRange();
        textInputRange.collapse(true);
        textInputRange.moveStart('character', start);
        textInputRange.moveEnd('character', start
                - (content().value
                    .slice(0, start).split(/\r\n/).length - 1));
        textInputRange.select();

        // For IE up to version 8
    }
}

function setCursor(pos) {
    setSelection(pos, pos);
}

function showSelection() {
    var text = content().value;
    var istart = selectionBounds().start;
    var iend = selectionBounds().end;
    content().value = text.substring(0,iend);
    content().scrollTop = 
        content().scrollHeight - content().clientHeight;
    content().value = text;
    setSelection(istart, iend);
}

function eReplaceNext() {
    eReplace();
    eFind();
}

function ePgNum() {
    $('txtFind').value = "^.*?File:\s*(.*?).png.*$";
    $('txtReplace').value = "[pgnum]$1[/pgnum]";
    $('chki').checked = true;
    $('chkm').checked = true;
}

function isSelection() {
    var bounds = selectionBounds();
    return bounds.start < bounds.end;
}

function selectionBounds() {
    var start = 0;
    var end = 0;
    var normalizedValue;
    var range;
    var textInputRange;
    var len;
    var endRange;

    if(typeof content().selectionStart == "number"
                && typeof content().selectionEnd == "number") {
        start = content().selectionStart;
        end   = content().selectionEnd;
    } else {
        range = document.selection.createRange();

        if(range && range.parentElement() == content()) {
            len = content().value.length;
            normalizedValue = content().value.replace(/\r\n/g, "\n");

            // Create a working TextRange that lives in the input
            textInputRange = content().createTextRange();
            textInputRange.moveToBookmark(range.getBookmark());

            // Check if start and end are at the very end
            // of input -- moveStart/moveEnd don't return
            // what we want in those cases
            endRange = content().createTextRange();
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
    
    function eFootnotes() {
        var t = content();
        var re = /\[.+?]\]/;
        var bkmk = re.exec(t);
        var bkmks = {};
        while(bkmk.index > 0) {
            bkmks.push(bkmk);
            bkmk = re.exec();
        }
        
    

    }

    return {start: start, end: end};
}

