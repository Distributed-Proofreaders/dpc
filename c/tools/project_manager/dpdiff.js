function $(s) { return document.getElementById(s); }

var projectid;
var pagename;
var roundid;
var pagelist;
var navform;
var pgimg;
var divimg;
var diffbox;

function init()
{
    projectid   = $('projectid');
    pagename    = $('pagename');
    roundid     = $('roundid');
    pagelist    = $('pagelist');
    projectid   = $('projectid');
    navform     = document.navform;
    pgimg       = $('pgimg');
    divimg      = $('divimg');
    diffbox     = $('diffbox');
    imagepath   = $('imagepath');
}

function eListChange()
{
    pagename.value = pagelist.value;
    navform.submit();
}

function eShowImage()
{
    var w = window.open(imagepath.value, pagename, '', false);
    w.write("<img src'"+imagepath.value+"' style='width: 100%' />");
    w.close();
    w.focus();
}
