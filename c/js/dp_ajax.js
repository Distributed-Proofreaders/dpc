/*
    word flags--
    host always returns the text it's sent but tagging may be
    different. Tags are not submitted (they are in divpreview,
    but text from tatext is sent). Tag classes are 
        "wc"  - spell-check fail, 
        "wcb" - bad word list, 
        "wcs" - spell-check fail, suggested, not resolved by PM
        "accepted" - this page this user this position
    (1st three from host, 4th from local list)
    If on accepted list, wc -> accepted, wcb -> accepted, wcs -> wcs.

divproof
  divleft  -- width determined by divimage
    divprevimage
      imgprev
    divimage
      imgpage
    divnextimage
      imgnext
  divright
    divctlbar      
    divfratext
      divtext
        pre divpreview
        textarea tatext
  divcontrols
  divstatusbar

    +--divleft--------------------+-divright-------------+
    |                             |___ctlbar_____________|
    |   divimage                  |                      |
    |    imgpage                  |   divfratext         |
    |                             |    divtext           |
    |                             |     pre              |
    |                             |     textarea         |
    |                             |                      |
    +-----------------------------+----------------------+
    |  controls                                          |
    +--status--------------------------------------------+
    +----------------------------------------------------+

    +----------------------------------------------------+
    |  image                                             |
    |                                                    |
    |                                                    |
    +----------------------------------------------------+
    +----------------ctlbar------------------------------+
    |                                                    |
    |  text                                              |
    |                                                    |
    |                                                    |
    +----------------------------------------------------+
    |  controls                                          |
    +--status--------------------------------------------+
    +----------------------------------------------------+

*/

var _pending_request = "";
var _ajax;
var _ajaxActionFunc;
var _rsp;


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
    _ajaxActionFunc = eWCMonitor;
    _ajax.onreadystatechange = readAjax;
}

function readyToSend() {
    return ( XMLHttpRequest.DONE == _ajax.readyState
    || XMLHttpRequest.UNSENT == _ajax.readyState)
}

function writeAjax(a_args) {
    var discard_pending = false;

    initAjax();
    if (!readyToSend()) {
        //previous request haven-t completed yet
        if (_pending_request == "") {
            //remebering this request for later
            _pending_request = encodeURIComponent(JSON.stringify(a_args));
            writeAjax._pending_request_code = a_args['querycode'];
            return;
        } else {
            //already got a _pending_request...
            discard_pending = true;
            _pending_request = "";
            //it-s ok to silently discard one 'wordcontext' requests out of two
            if ( !(a_args['querycode'] == "wordcontext"
                && writeAjax._pending_request_code == "wordcontext") ) {
                alert( "Sorry! Cannot handle all these fast requests!\n"
                + "-- Discarded one pending '"
                + writeAjax._pending_request_code
                + "' request --");
            }
            writeAjax._pending_request_code = "";
        }
    }
    if (readyToSend() || discard_pending) {
        // php end will rawurldecode this to recover it
        var jq = 'jsonqry=' + encodeURIComponent(JSON.stringify(a_args));
        var ret1 = _ajax.open("POST", AJAX_URL, true);
        var ret2 = _ajax.setRequestHeader("Content-type",
            "application/x-www-form-urlencoded");
        var ret3 = _ajax.send(jq);
    }
}

function readAjax() {
    var msg;
    var errstr;
    if(readyToSend()) {
        if (_ajax.status == 200) {
            msg = _ajax.responseText;
            errstr = "";
            try {
                errstr = "err decodeURI";
                msg = decodeURIComponent(msg);
                errstr = "err parse";
                JSON.parse(msg);
                errstr = "";
                //dbg("received response " + msg)
            }
            catch(err) {
                alert(errstr + " (msg:" + msg + ")");
                return;
            }
            if(_ajaxActionFunc) {
                _ajaxActionFunc(msg);
            }
        }
        if (_pending_request.length) {
            var tmp_pending_request = _pending_request;
            _pending_request = "";
            writeAjax(JSON.parse(decodeURIComponent(tmp_pending_request)));
        }
    }
}

function addEvent(obj, evType, fn) {
    if(obj && obj.addEventListener) {
        obj.addEventListener(evType, fn, false);
    }
    else if(obj && obj.attachEvent) {
        var r = obj.attachEvent("on" + evType, fn);
    }
}




function eBodyLoad() {
    //formedit        = $("formedit");

    //addEvent(tatext,              "focus",    eTextFocus);

    eCtlInit();
}

function px(val) {
    return val ? val.toString() + "px" : "";
}

function eCtlInit() {
}

// pickers

function dbg(arg) {
    if(console && console.debug) {
        console.debug(arg);
    }
}

function point_in_obj(obj, ptX, ptY) {
    var x1 = 0;
    var y1 = 0;
    var o;
    for(o = obj; o ; o = o.offsetParent) {
        x1 += (o.offsetLeft - o.scrollLeft);
        y1 += (o.offsetTop - o.scrollTop);
    }
    x2 = x1 + obj.offsetWidth;
    y2 = y1 + obj.offsetHeight;
    return ptX >= x1 && ptX <= x2
        && ptY >= y1 && ptY <= y2;
}


/*
function awcAccept() {
    accepts_to_form();
    var qry = {};

    qry['querycode']    = "wcaccept";
    qry['projectid']    = formedit.projectid.value;
    qry['pagename']     = formedit.pagename.value;
    qry['langcode']     = formedit.langcode.value;
    qry['acceptwords']  = formedit.acceptwords.value;
    writeAjax(qry);
}

// initiate wordcheck transaction
function awcWordcheck() {
    var qry = {};
    _text = tatext.value;

    // save accepted words
    qry['querycode']    = "wctext";
    qry['token']        = ++_wc_token;
    qry['projectid']    = formedit.projectid.value;
    qry['pagename']     = formedit.pagename.value;
    qry['langcode']     = formedit.langcode.value;
    qry['text']         = _text;
    set_wordchecking();
    writeAjax(qry);
}
*/

function eWCMonitor(msg) {
    _rsp = JSON.parse(msg);
    switch (_rsp.querycode) {

        // page editing functions

    //case 'dosavetemp':
    //    show_wordcheck(_rsp);
    //    alert(_rsp.alert);
    //    break;
    //

    //case 'setfontsize':
    //    break;

    default:
        window.alert("unknown querycode: " + _rsp.querycode);
        break;
    }
}

function setnamevalue(name, value) {
    var date = new Date();
    date.setTime(date.getTime() + 365*24*60*60*1000);
    document.cookie = name + '='
            + value + ';' 
            + ' expires=' + date.toUTCString()
            + '; path=/';
}

function getnamevalue(name, dflt) {
    var v, i, c = document.cookie.split(/[\s;]+/);
    for(i = 0; i < c.length; i++) {
        v = c[i].split(/=/);
        if(v[0] && v[0] == name) {
            return v[1];
        }
    }
    return dflt;
}

