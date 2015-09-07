var AJAX_URL = "http://www.pgdpcanada.net/c/loadsrv.php";

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
    else {  // ie6 and older
        _ajax = new ActiveXObject("Microsoft.XMLHTTP");
    }
    _ajaxActionFunc = eMonitor;
    _ajax.onreadystatechange = readAjax;
}

function writeAjax(a_args) {
    // php end will rawurldecode this to recover it
    initAjax();
    var jq = 'jsonqry=' + encodeURIComponent(JSON.stringify(a_args));
    console.debug(jq);
    _ajax.open("POST", AJAX_URL, true);     // no return value
    _ajax.setRequestHeader("Content-type",
        "application/x-www-form-urlencoded");     // no return value
    _ajax.send(jq);     // no return value
}

// callback function for onreadystatechange
function readAjax() {
    var msg;
    var errstr, jsonrsp;
    if(_ajax.readyState == 4) {
        msg = _ajax.responseText;
        if(_ajax.status != 200) {
            alert("ajax status: " + _ajax.status.toString());
            return;
        }
        errstr = "";
        try {
            errstr = "err decodeURI";
            msg = decodeURIComponent(msg);
            if(errstr)
                errstr = "err parse";
            // exec parse to exercise try
            //jsonrsp = JSON.parse(msg);
            JSON.parse(msg);
            // erase err msg if JSON.parse succeeded
            if(errstr != "") {
                errstr = "";
            }
        }
        catch(err) {
            alert(errstr + " (readAjax msg:" + msg + ")");
            console.debug(errstr);
            return;
        }

        if(_ajaxActionFunc) {
            _ajaxActionFunc(msg);
        }
        console.debug(msg);
    }
}

function eMonitor(msg) {
    // end of php object => encode JSON => decode JSON => js object
    try {
        _rsp = JSON.parse(msg);
    }
    catch(err) {
        alert(" (readAjax msg error:" + msg + ")");
        return;
    }
    switch (_rsp.querycode) {
        case 'addpage':
            rspaddpage(_rsp);
            break;

        default:
            window.alert("unknown querycode/response: "
            + _rsp.querycode + "/" + _rsp.response);
            break;
    }
}
function init() {

}

function $(id) {
    return document.getElementById(id);
}

function addEvent(obj, evType, fn) {
    if(obj && obj.addEventListener) {
        obj.addEventListener(evType, fn, false);
    }
    else if(obj && obj.attachEvent) {
        var r = obj.attachEvent("on" + evType, fn);
    }
}

function esubmit(e) {
    if(! e ) var e = window.event;

    switch(e.target.name) {
        case "submit_load":
        case "submit_load2":
            eload();
            break;
        case "submit_delete":
        case "submit_delete2":
            edelete();
            break;
        case "submit_load_others":
        case "submit_load_others2":
            eload_others();
            break;
        case "submit_delete_others":
        case "submit_delete_others2j":
            edelete_others();
            break;
    }
}

function eload() {
    var tbl, nrows, i, row, chk, val;
    tbl = $("page_table");
    nrows = tbl.rows.length;
    for(i = 0; i < nrows; i++) {
        row = tbl.rows[i];
        chk = row.getElementsByTagName("input");
        if(chk.length > 0) {
            val = chk[0].checked;
        }
        if(val) {
            eAddPage(row);
        }
    }
}

/*
    cell 0 -
    cell 0 -
    cell 0 -
    cell 0 -
    cell 0 -
    cell 0 -
 */
function eAddPage(row) {
    var qry = {};
    qry['querycode'] = "addpage";
    qry['projectid'] = row.cells[6].innerHTML;
    qry['pagename'] = row.cells[0].innerHTML;
    qry['imagepath'] = row.cells[7].innerHTML;
    qry['textpath'] = row.cells[8].innerHTML;
    writeAjax(qry);
}

function rspAddPage(rsp) {

}

function eCheckAll() {
    var is_ck, chk;

    is_ck = $("chkall").checked;

    var chks = $("page_table").getElementsByTagName("input");
    for(var i = 0; i < chks.length; i++) {
        chk = chks[i];
        chk.checked = is_ck;
    }
}

function eCheckAll2() {
    var is_ck, chk;

    is_ck = $("chkall2").checked;

    var chks = $("tblother").getElementsByTagName("input");
    for(var i = 0; i < chks.length; i++) {
        chk = chks[i];
        chk.checked = is_ck;
    }
}

function econfirm() {
    return window.confirm('Confirm you wish to delete files and/or pages');
}

function parent(path) {
    return path.replace(/\/+[^\/]+$/, "");
}

// get value of hidden form variable
function showpath() {
    return $("showpath").value;
}

function dpscans_path() {
    return $("dpscans_path").value;
}

// if changing path, set new path and submit form
// argument is always a bare directory name to be appended to showpath
function eSetPath(path) {
    if(path == '..') {
        if(showpath() == "/dpscans") {
            return;
        }
        $("showpath").value = parent($("showpath").value);
    }
    else {
        $("showpath").value = $('showpath').value + "/" + path;
    }
    $("workform").submit();
}

function eFileSelect() {
    $("uploading").style.visibility = "hidden";
    $("upbutton").style.visibility = "visible";
}

function eUpClick() {
    $("uploading").style.visibility = "visible";
    $("upform").submit();
}

function zipfile() {
    return $("subjectfilename");
}
