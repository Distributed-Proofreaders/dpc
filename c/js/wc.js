
/**
 *
 */
var tblcontext;
var _active_context_index = -1;
var _active_ctl;


var _contexts;

function $(id) {
    return document.getElementById(id);
}

function addEvent(obj, evType, fn) {
    if(obj && obj.addEventListener) {
        obj.addEventListener(evType, fn, false);
    }
    if(obj && obj.attachEvent) {
        obj.attachEvent("on" + evType, fn);
    }
}

function set_scroll_pct(ctl, pct) {
    if(pct <= 0) {
        return;
    }

    var torange = ctl.scrollHeight - ctl.clientHeight;
    if(torange <= 0) {
        return;
    }

    ctl.scrollTop = torange * pct / 100;
}

// load event for wordcontext page
// we have projectid, language, and which list type.
// need to pull list and select first word and context.
function eContextInit() {
    tblcontext  = $("tblcontext");

    addEvent(tblcontext,       "change", eTblContextChange);
    addEvent($("btnremove"),   "click", eRemoveWordClick);
    addEvent($("btngood"),     "click", eGoodWordClick);
    addEvent($("btnbad"),      "click", eBadWordClick);
    addEvent($("btnrefresh"),  "click", eRefreshClick);
    addEvent($("btnreplace"),  "click", eShowReplace);
    addEvent($("doreplace"),   "click", eDoReplace);
    addEvent($("doreplaceall"),   "click", eDoReplaceAll);
    addEvent($("doreplnext"),  "click", eDoReplaceNext);
    addEvent($("donereplace"), "click", eDoneReplace);
    addEvent($("btnadhocfind"),"click", eAdHocFind);
    addEvent($("imgcontext"),  "load",  eContextImgLoad);

    $("btnremove").style.display =
        ['good', 'bad', 'suggested']
            .indexOf($("mode").value) >= 0
        ? "inline-block" 
        : "none";
    $("btngood").style.display =
        ['bad', 'suggested', 'adhoc', 'flagged', 'regex']
            .indexOf($("mode").value) >= 0
        ? "inline-block"
        : "none";
    $("btnbad").style.display =
        ['good', 'suggested', 'adhoc', 'flagged', 'regex']
            .indexOf($("mode").value) >= 0
        ? "inline-block"
        : "none";
    //$("btnreplace.style.display = "inline-block";
    /*
    switch($("mode").value) {
        default:
            $('command-section').style.height = "25%";
            $('divtblcontext').style.height = "75%";
            break;

        case "regex":
        case 'adhoc':
            $('command-section').style.height = "50%";
            $('divtblcontext').style.height = "50%";
            break;
    }
    */

    refresh_context();
}

function retext_active_word() {
    var opt = tblcontext.options[tblcontext.selectedIndex];
    opt.text = context_word() + " (" + _contexts.length.toString() + ")";
}

function active_word_index() {
    return tblcontext.selectedIndex;
}

function context_word() {
    return tblcontext.value.substring(2);
}
function eTblContextChange() {
    refresh_context();
}

function eGoodWordClick() {
    var i = active_word_index();
    if(i >= 0) {
        switch($("mode").value) {
            case "good":
                break;
            case "bad":
            case "suggested":
            case "flagged":
            case "adhoc":
                awcAddGoodWord();
                break;
        }
        tblcontext.remove(i);
        refresh_context();
        //tblcontext.selectedIndex = i;
    }
}

function eRefreshClick() {
    refresh_context();
}
function eShowReplace() {
    $("txtreplace").value = context_word();
    $("buttonbox").className = "none";
    $("replacebox").className = "block";
}
function eDoReplace() {
     awcDoReplace();
}
function eDoReplaceAll() {
    awcDoReplaceAll();
}

function eDoReplaceNext(e) {

}

function eAdHocFind() {

}
function eDoneReplace() {
    $("buttonbox").className = "block";
    $("replacebox").className = "none";
}

function eBadWordClick() {
    var i = active_word_index();
    if(i >= 0) {
        switch($("mode").value) {
            case "good":
                awcGoodToBadWord();
                break;
            case "bad":
                break;
            case "suggested":
                awcSuggestedToBadWord();
                break;
            case "flagged":
            case "adhoc":
                awcAddBadWord();
                break;
        }
        tblcontext.remove();
        //tblcontext.selectedIndex = i;
        refresh_context();
    }
}

function eRemoveWordClick() {
    var i = active_word_index();
    if(i < 0) {
        return;
    }
    switch($("mode").value) {
        case "good":
            awcRemoveGoodWord();
            break;
        case "bad":
            awcRemoveBadWord();
            break;
        case "suggested":
            awcRemoveSuggestedWord();
            break;
        case "flagged":     // Remove meaningless
            return;
            break;
        case "adhoc":
            break;
    }
    tblcontext.remove(i);
    //tblcontext.selectedIndex = i;
    refresh_context();
}

function eContextImgLoad() {
    var context_image_scroll_pct = 
        (active_context() && (active_context().lineindex >= 0))
            ? 100 * active_context().lineindex 
                                / active_context().linecount 
            : -1;
    set_scroll_pct($('div_context_image'), context_image_scroll_pct);
}

function divcontext() {
    return _active_context_index >= 0
        ? $('divctxt_' + _active_context_index.toString())
        : null;
}

function check_div_index_context(i) {
    var _cdiv;
    var t1, t2;

    if(! (_cdiv = divcontext())) {
        return;
    }
    if(_cdiv.children.length > 1) {
        t1 = _cdiv.children[1].innerText;
        t2 = _contexts[i].context;
        t2 = t2.replace(/<span[^>]*>(.*?)<\/span>/g, "$1");
        if(t1 != t2) {
            window.alert(_cdiv.innerText);
        }
    }
}
// consequence of word table item onclick
function eSetContextWordIndex(index) {
    // if a current item is active, un-hilite it
    // and deal with any changes
    if(divcontext()) {
        divcontext().style.backgroundColor = "#EEEEEE";
        check_div_index_context(_active_context_index);
    }

    _active_context_index = index;
    divcontext().style.backgroundColor = "white";

    $('imgcontext').src =  active_context().imageurl.replace(/\s/g, "").replace(/&amp;/g, "&");
    eSetImageScroll();
}


function eSetImageScroll() {
    var context_image_scroll_pct =
        (active_context() && (active_context().lineindex > 0))
            ? 100 * active_context().lineindex
        / active_context().linecount
            : -1;
    set_scroll_pct($('div_context_image'), context_image_scroll_pct);
}

// json response handler for wordcontext
// fills top box with contexts from json
// response has array of pagename, lineindex, linecount, context
function ajxDisplayWordContextList(rsp) {
    var i;
    var str = "";
 
    if(! $("div_context_list")) {
        return;
    }
    //noinspection JSUnresolvedVariable
    _contexts = rsp.contextinfo.contexts;
    if(_contexts.length < 1) {
        return;
    }
    for(i = 0; i < _contexts.length; i++) {
        var ctxt = _contexts[i];
        var id = i.toString();
        str = str + "<div class='ctxt' id='divctxt_" + id + "'"
        + " onclick='eSetContextWordIndex(" + id + ")'>"
        + "<div class='ctxt-left'>"
        + "page " + ctxt.pagename
        + "<br>line " + ctxt.lineindex.toString()
        + " of " + ctxt.linecount.toString()
        + "</div>"
        + "<div class='ctxt-right' contenteditable='true'>"
        + ctxt.context + "</div>  <!-- ctxt-right -->"
        + "</div>\n";
    }
    $("div_context_list").innerHTML = str;
    $("div_context_list").scrollTop = 0;

    // now make the first context active
    eSetContextWordIndex(0);
}

// json response handler for wccontext
// builds the word list for context selection
function ajxDisplayContextWords(rsp) {
    var i;
    var str = "<tbody>\n";
    if(_active_ctl) {
        _active_ctl = null;
    }
    for (i = 0; i < rsp.length; i++) {
        var wrd = rsp[i][0];
        var n   = rsp[i][1];
        str = str + "<tr><td id='w_" + wrd + "' class='likealink'"
                + " style='background-color: white;'>"
                + wrd + "</td><td>" + n + "</td></tr>\n";
    }
    str += "</tbody>\n";
    tblcontext.innerHTML = str;
}

// json response handler for regexcontext
// fills top box with contexts from json
// response has array of pagename, lineindex, linecount, context
function ajxDisplayRegexContextList(rsp) {
    var i;
    var str = "";
 
    if(! $("div_context_list")) {
        return;
    }
    //noinspection JSUnresolvedVariable
    _contexts = rsp.contextinfo.contexts;
    if(_contexts.length < 1) {
        $("div_context_list").innerHTML = "";
        return;
    }
    // DisplayRegexList(rsp.word, _contexts.length);
    for(i = 0; i < _contexts.length; i++) {
        var ctxt = _contexts[i];
        var id = i.toString();
        str = str + "<div class='ctxt' id='divctxt_" + id + "'"
        + " onclick='eSetContextWordIndex(" + id + ")'>"
        + "<div class='ctxt-left'>"
        + "page " + ctxt.pagename
        + "<br>line " + ctxt.lineindex.toString()
        + " of " + ctxt.linecount.toString()
        + "</div>"
        + "<div class='ctxt-right'>"
        + ctxt.context + "</div>  <!-- ctxt-right -->"
        + "</div>\n";
    }
    $("div_context_list").innerHTML = str;
    $("div_context_list").scrollTop = 0;
    eSetContextWordIndex(0);
}

function refresh_context() {
    if(! tblcontext) {
        return;
    }
    if($('mode').value == "regex") {

    }
    else {
        if (tblcontext.value.length <= 2) {
            return;
        }

        var w = context_word();
    }
    var qry = {};
    qry['querycode'] = 'wordcontext';
    qry['projectid'] = $("projectid").value;
    qry['word']      = w;
    writeAjax(qry);
}

function active_context() {
    return _contexts.length > 0
        ? _contexts[_active_context_index]
        : null;
}

// select another language
function eLangPick() {
    requestContext();
}

// select another wordlist type
function eListPick() {
    requestContext();
}

function requestContext() {
    awcContext();
}

// send target word and replacement string
// host will make all replacements
// when response comes back, refresh
function eReplaceWordClick() {
    awcReplaceAll();
}

// -----------------------------------------------------------------
// ajax calls
// -----------------------------------------------------------------

function awcDoReplace() {
    var qry = {};
    if(! active_context()) {
        return;
    }
    qry['querycode'] = "doreplace";
    qry['projectid'] = active_context().projectid;
    qry['pagename'] = active_context().pagename;
    qry['lineindex'] = active_context().lineindex;
    qry['word'] = active_context().word;
    qry['repl'] = $("txtwith").value;
    if(qry['repl'] == "") {
        return;
    }
    writeAjax(qry);
}

function awcDoReplaceAll() {
    var qry = {};
    if(! active_context()) {
        return;
    }
    qry['querycode'] = "doreplaceall";
    qry['projectid'] = active_context().projectid;
    qry['word'] = active_context().word;
    qry['repl'] = $("txtwith").value;
    if(qry['repl'] == "") {
        return;
    }
    writeAjax(qry);
}

function awcAddGoodWord() {
    var qry = {};

    // hide now-defunct contexts
    $("div_context_list").style.visibility = "hidden";

    qry['querycode']    = "addgoodword";
    qry['projectid']    = $("projectid").value;
    qry['langcode']     = $("langcode").value;
    qry['word']         = context_word();
    //qry['word']         = tblcontext.options[i]
    //                        .value.substring(2);
    writeAjax(qry);
    //myAjax(qry);
}

function awcAddBadWord() {
    var qry = {};
    qry['querycode']    = "addbadword";
    qry['projectid']    = $("projectid").value;
    qry['langcode']     = $("langcode").value;
    qry['word']         = context_word();
    //qry['word']         = tblcontext.options[i]
    //                        .value.substring(2);
    writeAjax(qry);
}

function awcRemoveGoodWord() {
    var qry = {};
    qry['querycode']    = "removegoodword";
    qry['projectid']    = $("projectid").value;
    qry['langcode']     = $("langcode").value;
    qry['word']         = context_word();
    //qry['word']         = tblcontext.options[i]
    //                        .value.substring(2);
    writeAjax(qry);
}

function awcRemoveBadWord() {
    var qry = {};
    qry['querycode']    = "removebadword";
    qry['projectid']    = $("projectid").value;
    qry['langcode']     = $("langcode").value;
    qry['word']         = context_word();
    //qry['word']         = tblcontext.options[i]
    //                        .value.substring(2);
    writeAjax(qry);
}
function awcRemoveSuggestedWord() {
    var qry = {};
    qry['querycode']    = "removesuggestedword";
    qry['projectid']    = $("projectid").value;
    qry['langcode']     = $("langcode").value;
    qry['word']         = context_word();
    //qry['word']         = tblcontext.options[i]
    //                        .value.substring(2);
    writeAjax(qry);
}

function awcGoodToBadWord() {
    var qry = {};
    qry['querycode']    = "goodtobadword";
    qry['projectid']    = $("projectid").value;
    qry['langcode']     = $("langcode").value;
    qry['word']         = context_word();
    //qry['word']         = tblcontext.options[i]
    //                        .value.substring(2);
    writeAjax(qry);
}

function awcBadToGoodWord() {
    var qry = {};
    qry['querycode']    = "badtogoodword";
    qry['projectid']    = $("projectid").value;
    qry['langcode']     = $("langcode").value;
    qry['word']         = context_word();
    //qry['word']         = tblcontext.options[i]
    //                        .value.substring(2);
    writeAjax(qry);
}

function awcSuggestedToBadWord() {
    var qry = {};
    qry['querycode']    = "suggestedtobadword";
    qry['projectid']    = $("projectid").value;
    qry['langcode']     = $("langcode").value;
    qry['word']         = context_word();
    //qry['word']         = tblcontext.options[i]
    //                        .value.substring(2);
    writeAjax(qry);
}

function awcContext() {
    var qry = {};
    qry['querycode']    = "wccontext";
    qry['projectid']    = $("projectid").value;
    qry['langcode']     = $("langcode").value;
    qry['mode']         = $("mode").value;
    writeAjax(qry);
}

function awcReplaceAll() {
    var qry = {};
    qry['querycode']    = "wcreplace";
    qry['projectid']    = $('projectid').value;
    qry['word']         = context_word();
    //qry['word']         = tblcontext.options[i]
    //                        .value.substring(2);
    qry['replace']         = $("txtreplace").value;
    writeAjax(qry);
}

// -----------------------------------------------------------------
// ajax callback
// -----------------------------------------------------------------

// handler for responses returning from web service
function eWCMonitor(msg) {
    var rsp = JSON.parse(msg);
    console.log('response arrived for ' + rsp.querycode );
    switch (rsp.querycode) {

        case 'wccontext':
        case 'wcreplace':
            //noinspection JSUnresolvedVariable
            ajxDisplayContextWords(rsp.wordarray);
            break;

        case 'wordcontext':
            ajxDisplayWordContextList(rsp);
            retext_active_word();
            break;

        case 'regexcontext':
            ajxDisplayRegexContextList(rsp);
            break;

        case 'doreplace':
            // needs to decrement count, reestablish context list
            console.log("confirmed " + rsp.querycode);
            refresh_context();
            break;

        case 'addgoodword':
        case 'addbadword':
        case 'removegoodword':
        case 'removebadword':
        case 'removesuggestedword':
        case 'badtogoodword':
        case 'goodtobadword':
        case 'suggestedtobadword':
        case 'doreplaceall':
            // all these send stuff off to wc.php to be disposed of and only get ACK back
            console.log("confirmed " + rsp.querycode);
            refresh_context();
            $("div_context_list").style.visibility = "visible";
            break;

        default:
            window.alert("unknown querycode: " + msg);
            break;
    }
}

// -----------------------------------------------------------------
// ajax functions
// -----------------------------------------------------------------

var _ajax;

function initAjax() {
    if(_ajax) {
        return;
    }
    if(window.XMLHttpRequest) {
        _ajax = new XMLHttpRequest();
    }
    else {
        _ajax = new ActiveXObject("Microsoft.XMLHTTP");
    }
    _ajax.onreadystatechange = readAjax;
}

// NOTE: DP-IT has different code, apparently to deal with queue overload
function writeAjax(a_args) {
    // php end will rawurldecode this to recover it
    var jq = JSON.stringify(a_args);
    console.log("writeAjax: " + jq.substring( 0, 200));
    jq = "jsonqry=" + encodeURIComponent(jq);
    initAjax();
    _ajax.open("POST", AJAX_URL, true);
    _ajax.setRequestHeader("Content-type",
                        "application/x-www-form-urlencoded");
    _ajax.send(jq);
}

// NOTE: DP-IT has different code, apparently to deal with queue overload
function readAjax() {
    var msg;
    var errstr;
    if(_ajax.responseText == "") {
        return;
    }
    if(_ajax.readyState == 4 && _ajax.status == 200) {
        msg = _ajax.responseText;
        //noinspection JSUnusedAssignment
        errstr = "";
        try {
            //noinspection JSUnusedAssignment
            errstr = "err decodeURI msg: ";
            msg = decodeURIComponent(msg);
            //noinspection JSUnusedAssignment
            errstr = "err parse msg: ";
            JSON.parse(msg);
            //noinspection JSUnusedAssignment
            errstr = "";
            //console.log("readAjax success: " + msg.substring(0, 200));
        }
        catch(err) {
            //console.log("readAjax FAILED: " + errstr + msg);
            window.alert(errstr + msg);
            return;
        }

        console.log("readAjax: " + msg.substring( 0, 400));
        eWCMonitor(msg);
    }
}

