var imgblock     = null;
var scrollTime   = 0;
var interface;

frameRef     = null; // used by dp_proof.js


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

function ZoomInputValue() {
    return parseInt(docRef().editform.imgzoom.value);
}

function SetZoomInputValue(val) {
    docRef().editform.imgzoom.value = val.toString();
}

function SetImageWidth() {
    frameRef.scanimage.style.width = (10 * ZoomInputValue()).toString() +'px';
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
        return imageSize(rolled);
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
    SetZoomInputValue(zoom);
    SetImageWidth(zoom);
}

function initializeStuff(wFace) {
    var textdata;

    initZoom();

    interface = wFace;

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

    scanimage.style.width = 10 * ZoomInputValue() + 'px'

    if (scanimage.addEventListener) {
        scanimage.addEventListener ("mousewheel", eScroll, false);
        scanimage.addEventListener ("DOMMouseScroll", eScroll, false);
    }
    else {
        scanimage.attachEvent ("onmousewheel", eScroll);
    }

    if ( docRef.editform.text_data ) {
        textdata = docRef.editform.text_data;
    }
    if (textdata.addEventListener) {
        textdata.addEventListener ("mousewheel", eScroll, false);
        textdata.addEventListener ("DOMMouseScroll", eScroll, false);
    }
    else {
        textdata.attachEvent ("onmousewheel", eScroll);
    }
    if(! docRef.selection ) {
        docRef.editform.text_data.style.whiteSpace = 'pre-line';
    }
}

function ImgBigger() {
    SetZoomInputValue(ZoomInputValue() * 1.2);
    SetImageWidth();
}

function ImgSmaller() {
    SetZoomInputValue(ZoomInputValue() / 1.2);
    SetImageWidth();
}

function ResetImageSize() {
    SetZoomInputValue(100);
    SetImageWidth();
}

function imageSize(amt) {
    var zoom = ZoomInputValue();
    if(amt > 0) {
        zoom += 5;
        if(zoom > 150) {
            zoom = 150;
        }
    }
    else if(amt < 0) {
        zoom = min(zoom = 5, 20);
    }
    SetZoomInputValue(zoom);
    scanimage.style.width = (zoom * 10).toString() + 'px';
    return false;
}

    
inProof = 0; // used by dp_proof.js
isLded = 0;
// inFace = 0;
