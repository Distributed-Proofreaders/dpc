// var imgblock     = null;
var scrollTime   = 0;
// var interface;

var frameRef     = null; // used by dp_proof.js
var scanimage;
var textdata;

function initializeStuff(wFace) {

    // interface = wFace;

    frameRef = top.frames[0].document;
    isLded = 1;
    inProof = 1;
    cRef = top.menuframe.document.markform;


    // if(wFace == 1) {
            // enhanced interface, non-spellcheck
            // docRef = top.frames[0].document;
            // doBU();
    // }
    // else if (wFace == 0) {
        // standard interface, non-spellcheck
        // docRef = top.frames[0].textframe.document;
    // }
    if(proofframe && proofframe.imageframe 
                  && proofframe.imageframe.document
                  && proofframe.imageframe.document.getElementById) {
        scanimage = proofframe.imageframe.document.getElementById("scanimage");
    }
    else {
        scanimage = proofframe.document.getElementById("scanimage");
    }

    initZoom();

    // scanimage.style.width = 10 * ZoomValue() + 'px'

    if (scanimage.addEventListener) {
        scanimage.addEventListener ("mousewheel", eScroll, false);
        scanimage.addEventListener ("DOMMouseScroll", eScroll, false);
    }
    else {
        scanimage.attachEvent ("onmousewheel", eScroll);
    }

    if ( docRef().editform.text_data ) {
        textdata = docRef().editform.text_data;
        if (textdata.addEventListener) {
            textdata.addEventListener ("mousewheel", eScroll, false);
            textdata.addEventListener ("DOMMouseScroll", eScroll, false);
        }
        else {
            textdata.attachEvent ("onmousewheel", eScroll);
        }
    }

    if(! docRef().selection) {
        docRef().editform.text_data.style.whiteSpace = 'pre-line';
    }
    textdata.focus();
}


// ------------------------------------------------
// The following functions are the "exported" ones.

function docRef() {
    return top.frames[0].textframe
            ? top.frames[0].textframe.document
            : top.frames[0].document; 
}

function ZoomCookieValue() {
    return getnamevalue("zoom", 100);
}

function SetZoomCookieValue(value) {
    setnamevalue("zoom", value);
}

function ZoomValue() {
    return parseInt(docRef().editform.imgzoom.value);
}

// the textbox value has changed - deal with the consequences
function ChangeZoomValue() {
    SetZoomCookieValue(ZoomValue());
    ApplyZoomValue();
    return false;
}

function SetZoomValue(val) {
    docRef().editform.imgzoom.value = val.toString();
}

function ApplyZoomValue() {
    scanimage.style.width = (10 * ZoomValue()).toString() +'px';
}

function focusText() {
    if (isLded && inProof) {
        docRef().editform.text_data.focus();
    }
}

function eScroll (event) {
    var t;
    if(! event.altKey)
        return;
    event.cancelBubble = true;
	if (event.stopPropagation) 
        event.stopPropagation();
    
    var rolled = 0;
    if ('wheelDelta' in event) {
        rolled = event.wheelDelta;
    }
    else {  // Firefox - units of detail and wheelDelta properties different.
        rolled = -40 * event.detail;
    }

    if(event.srcElement) {
        t = event.srcElement;
    }
    else if(event.target) {
        t = event.target;
    }

    if(t.id == "scanimage") {
        return scrollImageSize(rolled);
    }
    else if(t.id == "text_data") {
        return textSize(rolled);
    }
}

function textSize(amt) {
    var ts = docRef().getElementById("fntSize");
    if(amt < 0) {
        if(ts.selectedIndex > 0) {
            ts.selectedIndex -= 1;
        }
        chFSize(ts.value);
    }
    if(amt > 0) {
        if(ts.selectedIndex < ts.options.length) {
            ts.selectedIndex += 1;
        }
        chFSize(ts.value);
    }
    return false;
}

function initZoom() {
    var zoom = ZoomCookieValue();
    SetZoomValue(zoom);
    ApplyZoomValue();
}

function ImgBigger() {
    SetZoomValue(ZoomValue() * 1.1);
    ApplyZoomValue();
}

function ImgSmaller() {
    SetZoomValue(ZoomValue() / 1.1);
    ApplyZoomValue();
}

function ResetZoomValue() {
    SetZoomValue(100);
    ApplyZoomValue();
}

function scrollImageSize(delta) {
    var zoom = ZoomValue();
    if(delta > 0) {
        zoom = Math.min(zoom + 5, 150);
    }
    else if(delta < 0) {
        zoom = Math.max(zoom - 5, 20);
    }
    SetZoomValue(zoom);
    ApplyZoomValue();
    return false;
}

    
inProof = 0; // used by dp_proof.js
isLded = 0;
// inFace = 0;
