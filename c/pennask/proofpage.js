/*
    version 0.176

    word flags--
    host always returns the text it's sent but tagging may be
    different. Tags are not submitted (they are in prepreview,
    but text from tatext is sent). Tag classes are 
        "wc"  - spell-check fail, 
        "wcb" - bad word list, 
        "wcs" - spell-check fail, accepted, not resolved by PM
        "accepted" - this page this user this position
    (1st three from host, 4th from local list)
    If on accepted list, wc -> accepted, wcb -> accepted, wcs -> wcs.

    traverse spans in new preview -

    -------------------------------------

    wordcheck invoked if turned on and
    a. digraph is applied (eKeyPress())
    b. any text replacement occurs (ReplaceText()) which handles most edit buttons
    c. any keyUp and tatext has changed (eKeyUp())
    d. Blank Page applied (eSetBlankPage())

    this corresponds to any assignment to tatext.value

    -------------------------------------

    what scrolls?
    divfratext is the viewport with scrollbars
      divtext scrolls inside divfratext
          the dimensions of divtext are determined by the bounds of tatext;
              tatext expands its scrollWidth and scrollHeight to fit the contained text
              (but it doesn't shrink)
*/

var AJAX_URL;

var lc_words = ['and','of','the','in','on','de','van','am',
        'pm','bc','ad','a','an','at','by','for','la','le'];

var _keystack = "";
var _accept_tags;

var doc = document;
var boxheight, boxwidth;    // global because used to calculate splitter pct dynamically
var divleft;
var divright;
var divfratext;
var divimage;
var divprevimage;
var divnextimage;
var divsplitter;
var imgpage;
var divcontrols;
var ctlpanel;
var divFandR;
var divPreviewErrors;
var divtweet;
var imgtweet;
var divGear;
var imggear;
var tatext;
var divctlnav;
var prepreview;
var spanpreview;
var divctlimg;
var divctlwc;
var divstatusbar;
var seltodo;
var regex;
var chkIsWC;
var chkIsPunc;
var runAlways;
var tweet;

var formedit;

var _text;

var _is_wordchecking = false;
var _is_previewing = false;
var _active_scroll_element = null;
var _is_tail_space;

var _ajax;
var _ajaxActionFunc;
var _rsp;
var _date = new Date();
var _active_charselector;
var _active_char;
var _wc_wakeup = 0;
var _wc_token = 0;

// for splitter
var _is_resizing = false;

// -- digraphs

var digraphs = {
    "a'" : "á",  "A'" : "À",
    "a!" : "à",  "A!" : "À",
    "a>" : "â",  "A>" : "Â",
    "a:" : "ä",  "A:" : "Ä",
    "a-" : "ā",  "A-" : "Ā",
    "a(" : "ă",  "A(" : "Ă",
    "aa" : "å",  "AA" : "Å",
    "a?" : "ã",  "A?" : "Ã",
    "a3" : "ǣ",  "A3" : "Ǣ",

    "A+" : "א",
    "B+" : "ב", "G+" : "ג", "D+" : "ד",
    "H+" : "ה", "W+" : "ו", "X+" : "ח",
    "Tj" : "ט", "J+" : "י", "K%" : "ך",
    "K+" : "כ", "L+" : "ל", "M%" : "ם",
    "M+" : "מ", "N%" : "ן", "N+" : "נ",
    "S+" : "ס", "E+" : "ע", "P%" : "ף",
    "P+" : "פ", "Zj" : "ץ", "ZJ" : "צ",
    "Q+" : "ק", "R+" : "ר", "Sh" : "ש",
    "T+" : "ת",

    "e'" : "é",  "E'" : "É",
    "e!" : "è",  "E!" : "È",
    "e>" : "ê",  "E>" : "Ê",
    "e:" : "ë",  "E:" : "Ë",
    "e-" : "ē",  "E-" : "Ē",
    "e(" : "ĕ",  "E(" : "Ĕ",
    "e?" : "ẽ",  "E?" : "Ẽ",

    "i'" : "í",  "I'" : "Í",
    "i!" : "ì",  "I!" : "Ì",
    "i>" : "î",  "I>" : "Î",
    "i:" : "ï",  "I:" : "Ï",
    "i-" : "ī",  "I-" : "Ī",
    "i(" : "ĭ",  "I(" : "Ĭ",
    "i?" : "ĩ",  "I?" : "Ĩ",

    "o'" : "ó",  "O'" : "Ó",
    "o!" : "ò",  "O!" : "Ò",
    "o>" : "ô",  "O>" : "Ô",
    "o:" : "ö",  "O:" : "Ö",
    "o-" : "ō",  "O-" : "Ō",
    "o(" : "ŏ",  "O(" : "Ŏ",
    "o?" : "õ",  "O?" : "Õ",
    "o/" : "ø",  "O/" : "Ø",

    "u'" : "ú",  "U'" : "Ú",
    "u!" : "ù",  "U`" : "Ù",
    "u>" : "û",  "U>" : "Û",
    "u:" : "ü",  "U:" : "Ü",
    "u-" : "ū",  "U-" : "Ū",
    "u(" : "ŭ",  "U(" : "Ŭ",
    "u?" : "ũ",  "U?" : "Ũ",

    "y'" : "ý",  "Y'" : "Ý",
    "y!" : "ỳ",  "Y`" : "Ỳ",
    "y>" : "ŷ",  "Y>" : "Ŷ",
    "y:" : "ÿ",  "Y:" : "Ÿ",

    "-o" : "º",  "-a" : "ª",
    "c," : "ç",  "C," : "Ç",
    "n?" : "ñ",  "N?" : "Ñ",
    "SE" : "§",  "DO" : "$",
    "ae" : "æ", "AE" : "Æ",
    "oe" : "œ", "OE" : "Œ",

    "'\"" : "˝", "+=" : "±",
    "''" : "´",  "'!" : "`",
    "d-" : "ð",  "D-" : "Ð",
    "th" : "þ",  "TH" : "Þ",

    ".M" : "·",  "*X" : "×",
    ":-" : "÷",

    "a*" : "α",  "A*" : "Α",
    "a%" : "ά",  "A%" : "Ά",
    ";!" : "ἂ",  ",!" : "ἃ",
    "?;" : "ἄ",  "?," : "ἅ",
    "!:" : "ἆ",  "?:" : "ἇ",
    "b*" : "β",  "B*" : "Β",
    "c*" : "ξ",  "C*" : "Ξ",
    "d*" : "δ",  "D*" : "Δ",
    "e*" : "ε",  "E*" : "Ε",
    "e%" : "έ",  "E%" : "Έ",
    "f*" : "φ",  "F*" : "Φ",
    "g*" : "γ",  "G*" : "Γ",
    "m3" : "ϝ",  "M3" : "Ϝ",
    "h*" : "θ",  "H*" : "Θ",
    "i*" : "ι",  "I*" : "Ι",
    "i%" : "ί",  "I%" : "Ί",
    "i3" : "ΐ",  "u3" : "ΰ",
    "j*" : "ϊ",  "J*" : "Ϊ",
    "j3" : "ϵ",  "J3" : "3",
    "k*" : "κ",  "K*" : "Κ",
    "k3" : "ϟ",  "K3" : "Ϟ",
    "l*" : "λ",  "L*" : "Λ",
    "m*" : "μ",  "M*" : "Μ",
    "n*" : "ν",  "N*" : "Ν",
    "o*" : "ο",  "O*" : "Ο",
    "o%" : "ό",  "O%" : "Ό",
    "p*" : "π",  "P*" : "Π",
    "p3" : "ϡ",  "P3" : "Ϡ",
    "q*" : "ψ",  "Q*" : "Ψ",
    "r*" : "ρ",  "R*" : "Ρ",
    "s*" : "σ",  "S*" : "Σ",
    "*s" : "ς",  "*S" : "Σ",
    "t*" : "τ",  "T*" : "Τ",
    "t3" : "ϛ",  "T3" : "Ϛ",
    "u*" : "υ",  "U*" : "Υ",
    "u%" : "ύ",  "U%" : "Ύ",
    "v*" : "ϋ",  "V*" : "Ϋ",
    "w*" : "ω",  "W*" : "Ω",
    "w%" : "ώ",  "W%" : "Ώ",
    "x*" : "χ",  "X*" : "Χ",
    "y*" : "η",  "Y*" : "Η",
    "y%" : "ή",  "Y%" : "Ύ",
    "z*" : "ζ",  "Z*" : "Ζ",
    "g," : "ģ",  "G," : "Ģ",

    "<<" : "«", ">>" : "»",

    "0S" : "⁰", "1S" : "¹",
    "2S" : "²", "3S" : "³", "4S" : "⁴", "5S" : "⁵",
    "6S" : "⁶", "7S" : "⁷", "8S" : "⁸", "9S" : "⁹",
    "12" : "½", "14" : "¼", "34" : "¾",
    "18" : "⅛", "38" : "⅜", "58" : "⅝", "78" : "⅞",
    "13" : "⅓", "23" : "⅔", "16" : "⅙", "56" : "⅚",
    "15" : "⅕", "25" : "⅖", "35" : "⅗", "45" : "⅘",

    '"6' : "“", '"9' : "”", "'6" : "‘", "'9" : "’",
    '.9' : "‚", ':9' : "„", "<1" : "‹", ">1" : "›",
    "DG" : "°", "--" : "−", "'1" : "′", "'2" : "″"
};

var igraphs = {};

function $(id) {
    var obj = doc.getElementById(id);
    if (!obj)
        console.log("Cannot find " + id);
    return obj;
}

function addEvent(obj, evType, fn) {
    if(obj && obj.addEventListener) {
        obj.addEventListener(evType, fn, false);
    }
    else if(obj && obj.attachEvent) {
        obj.attachEvent("on" + evType, fn);
        obj.setCapture();
    }
}

function removeEvent(obj, evType, fn) {
    if(obj && obj.removeEventListener) {
        obj.removeEventListener(evType, fn);
    }
    else if(obj && obj.detachEvent) {
        obj.detachEvent("on" + evType, fn);
        obj.releaseCapture();
    }
}

//noinspection FunctionTooLongJS
function eInit() {
    formedit        = $("formedit");
    divleft         = $("divleft");
    divright        = $("divright");
    divfratext      = $("divfratext");
    divimage        = $("divimage");
    divprevimage    = $("divprevimage");
    divnextimage    = $("divnextimage");
    divsplitter     = $("divsplitter");
    divcontrols     = $("divcontrols");
    ctlpanel        = $("ctlpanel");
    divtweet        = $("divtweet");
    imgtweet        = $("imgtweet");
    tweet           = $("tweet");
    divFandR        = $("divFandR");
    divPreviewErrors = $("divPreviewErrors");
    tatext          = $("tatext");
    imgpage         = $("imgpage");

    prepreview      = $("prepreview");
    spanpreview     = $("spanpreview");
    divctlimg       = $("divctlimg");
    divctlnav       = $("divctlnav");
    divctlwc        = $("divctlwc");
    divstatusbar    = $("divstatusbar");
    seltodo         = $("seltodo");

    imggear         = $("imggear");
    divGear         = $("divGear");
    chkIsWC         = $("chkIsWC");
    chkIsPunc       = $("chkIsPunc");
    runAlways       = $("runAlways");

    addEvent(doc,           "keydown",   eKeyDown);
    addEvent(doc,           "keyup",     eKeyUp);
    addEvent(doc,           "keypress",  eKeyPress);
    addEvent(tatext,        "click",     eTextClick);
    addEvent(tatext,        "input",     eTextInput);
    addEvent(divfratext,    "scroll",    eScroll);
    addEvent(divimage,      "scroll",    eScroll);
    addEvent(divimage,      "click",     eClickImage);
    addEvent(divprevimage,  "click",     eTogglePrevImage);
    addEvent(divnextimage,  "click",     eToggleNextImage);
    //addEvent(seltodo,       "select",    eTodo);
    addEvent(divGear,       "click",     eGearClick);   // click on any gear control
    addEvent(imgtweet,      "click",     eToggleTweet);
    addEvent(imggear,       "click",     eToggleGear);
    addEvent($('btnCloseGear'), "click",     eToggleGear);

    addEvent(divsplitter,   "mousedown", eSplitterDown);

    addEvent(doc,               "mousemove", eMouseMove);
    // event for "hide ontrols" link
    addEvent($('hidectls'),     "click",     eToggleCtls);
    addEvent($('linksync'),     "click",     eToggleSync);
    addEvent($('linkzoomin'),   "click",     eZoomIn);
    addEvent($('linkzoomout'),  "click",     eZoomOut);
    addEvent($('linklayout'),   "click",     eSwitchLayout);
    addEvent($('imgpvw'),       "click",     ePreviewFormat);
    addEvent($('btnFandR'),     "click",     eToggleFandR);
    addEvent($('btnfind'),      "click",     eFind);
    addEvent($('btnrepl'),      "click",     eReplace);
    addEvent($('btnreplnext'),  "click",     eReplaceNext);
    addEvent($('btnreplall'),   "click",     eReplaceAll);
    addEvent($('btnclose'),     "click",     eCloseFandR);
    addEvent($('linkwc'),       "click",     eLinkWC);
    addEvent($('seltodo'),      "change",    eSelToDo);
    addEvent($('showdigraphs'), "click",     eShowDigraphs);
    addEvent($('digraph-close'),"click",     eHideDigraphs);
    addEvent($('selfontface'),  "change",    eSetFontFace);
    addEvent($('selfontsize'),  "change",    eSetFontSize);
    addEvent($('opt_mark_bad'), "click",     eToggleBad);
    addEvent($('opt_submit_continue'),"click",     eOptToDo);
    addEvent($('opt_submit_quit'),"click",     eOptToDo);
    addEvent($('opt_draft_quit'),"click",     eOptToDo);
    // addEvent($('opt_return_quit'),"click",     eOptToDo);
    addEvent($('opt_draft_quit'),"click",     eOptToDo);


    addEvent($('divcharpicker'), "mouseover", ePickerOver);
    addEvent($('divcharpicker'), "click",     eCharClick);

    applylayout();
    // copy text to detect changes
    _text = tatext.value;
    setSyncButton();
    eCtlInit();
}

function eCtlInit() {
    if($('divcharpicker')) {
        $('selectors').innerHTML = char_selectors();
        $('pickers').innerHTML   = char_pickers(' ');
    }

    invert_digraphs();

    tatext.className = prepreview.className = getLineHeight();

    if(getEditorStyle() == "menu") {
        $("rdomenu").checked = "checked";
        $("divmenu").className = "block";
        $("divicons").className = "hide";
    }
    else {
        $("rdoicons").checked = "checked";
        $("divmenu").className = "hide";
        $("divicons").className = "block";
    }

    var lh = getLineHeight();
    switch(lh) {
        case "lh10":
        case "lh15":
        case "lh20":
            $("rdo" + lh).checked = "checked";
            break;
        default:
            $("rdolh10").checked = "checked";
            saveLineHeight("lh10");
            break;
    }

    chkIsWC.checked = (getIsWC() ? "checked" : "");
    chkIsPunc.checked = (getIsPunc() ? "checked" : "");
    runAlways.checked = (getRunAlways() ? "checked" : "");
    if($("divctlwc")) {
        $("divctlwc").className = (getIsWC() ? "block" : "hide");
    }

    $('txtfind').value = getFind();
    $('txtrepl').value = getRepl();
    getAndSetFandRFlags();

    // Run wordcheck if set to run always, and we are proofing
    if (runAlways.checked && $("imgpvw") == null) {
        console.log("Run Always checked; initiating wordcheck");
        chkIsWC.checked = "checked";
        setIsWC();
        requestWordcheck();
    }
}

function px(val) {
    return val ? val.toString() + "px" : "";
}

function applylayout() {
    requestAnimationFrame(_applylayout);
}

//noinspection FunctionTooLongJS
function _applylayout() {
    var barpct = GetBarPct();   // get value stored in cookie

    divleft.style.visibility        = "hidden";
    divright.style.visibility       = "hidden";
    divsplitter.style.visibility    = "hidden";
    ctlpanel.style.visibility       = "hidden";

    divcontrols.className           = IsCtls() ? "block" : "hide";
    $("imghidectls").style.display  = IsCtls() ? "block" : "none";
    $("imgshowctls").style.display  = IsCtls() ? "none" : "block";

    SetFontSizeSelector(GetFontSize());
    SetFontFaceSelector(GetFontFace());
    prepreview.style.fontFamily     =
    tatext.style.fontFamily         = GetFontFace();
    prepreview.style.fontSize       =
    tatext.style.fontSize           = GetFontSize();
    applyLineHeight(getLineHeight());
    imgpage.style.width             = (GetZoom() * 10).toString() + 'px';

    boxheight                       = doc.body.offsetHeight
                                        - divstatusbar.offsetHeight
                                        - (IsCtls() ? divcontrols.offsetHeight : 0)
                                        - ctlpanel.offsetHeight;
    boxwidth                        = doc.body.offsetWidth
                                        - (Layout() == "vertical" ? 4 : 0);
    ctlpanel.style.top              = px(boxheight + 1);

    if(Layout() == 'horizontal') {
        // splitter height already accounted for
        var topheight               = boxheight * barpct / 100;
        var bottomheight            = boxheight - topheight - 4;


        divleft.style.height        = px(topheight);
        divleft.style.width         = "100%";

        divsplitter.style.top       = px(topheight+1);
        divsplitter.style.left      = "0";
        divsplitter.style.width     = "100%";
        divsplitter.style.height    = "4px";
        divsplitter.style.cursor    = "n-resize";

        //divright.style.top          = px(boxheight - (100 - barpct) * boxheight/100 + divsplitter.offsetHeight);
        divright.style.top          = px(topheight + 4);
        //divright.style.top          = px(boxheight + bottomheight + 4);
        divright.style.left         = "0";
        divright.style.height       = px(bottomheight);
        divright.style.width        = "100%";

        divtweet.style.top          =
        divPreviewErrors.style.top  =
        divFandR.style.top          = divright.offsetTop;
    }

    else {
        var leftwidth               = boxwidth * barpct / 100;
        var rightwidth              = boxwidth - leftwidth - 4;

        divleft.style.height        = px(boxheight);
        divleft.style.width         = px(leftwidth);

        divsplitter.style.top       = "0";
        divsplitter.style.left      = px(leftwidth);
        divsplitter.style.width     = "4px";
        divsplitter.style.height    = px(boxheight);
        divsplitter.style.cursor    = "w-resize";

        divright.style.top          = "0";
        divright.style.left         = px(leftwidth + 4);
        divright.style.height       = px(boxheight);
        divright.style.width        = px(rightwidth);

        divtweet.style.top          =
        divPreviewErrors.style.top  =
        divFandR.style.top          = "0";
    }
    divtext_match_tatext();
    divleft.style.visibility        = "visible";
    divcontrols.style.visibility    = "visible";
    divright.style.visibility       = "visible";
    divsplitter.style.visibility    = "visible";
    ctlpanel.style.visibility       = "visible";

    divimage.scrollLeft             = parseInt(getnamevalue("imgscroll"), 10);

    setLayoutIcon();
}

// still leaves a problem if the font is resized
function set_text_size() {
    divtext.style.height = px(tatext.scrollHeight);
    divtext.style.width  = px(tatext.scrollWidth);
}

function divtext_match_tatext() {
    // divtext contains tatext and prepreview/spanpreview at 100% w x h
    set_text_size();
}

// pickers

function char_selector_row(str) {
    var i;
    var s = '<tr>\n';
    var a = str.split(/ /g);
    var alen = a.length;
    for (i = 0; i < alen; i++) {
        s += ('<td class="selector">' + a[i] + '</td>\n');
    }
    s += '</tr>\n';
    return s;
}

function char_row(str) {
    var i;
    var s = '<tr>\n';
    var imax = str.length;
    for (i = 0; i < imax; i++) {
        s += ('<td class="picker">' + str[i] + '</td>\n');
    }
    s += '</tr>\n';
    return s;
}

function char_selectors() {
    var s = "<table class='tblpicker'>\n";
    s += char_selector_row("A E I O UY CD LN R-Z αβγ ἄ ἒ ἠ ΐ ό ύ ώ ῤ+ ћ Ѫ + ❦");
    s += "\n</table>\n";
    return s;
}

//noinspection OverlyComplexFunctionJS,FunctionTooLongJS
function char_pickers(cgroup) {
    var s = "<table class='tblpicker'>\n";

    switch (cgroup) {
    
    case 'A':
        s += char_row('ÀÁÂÄÅÃÆĀĂĄ');
        s += char_row('àáâäåãæāăą');
        break;

    case 'E':
        s += char_row('ÈÉÊËĖĘĚĔĒ');
        s += char_row('èéêëėęěĕē');
        break;

    case 'I':
        s += char_row('ÌÍÎÏĨĪĬǏĮĜĤĴ');
        s += char_row('ìíîïĩīĭǐįĝĥĵ');
        break;

    case 'O':
        s += char_row('ÒÓÔÖÕØŌŎǑŒ');
        s += char_row('òóôöõøōŏǒœ');
        break;

    case 'UY':
        s += char_row('ÙÚÛÜŨŪŬ ÝŸ');	// (ATB) ýÿ -> ÝŸ
        s += char_row('ùúûüũūŭ ýÿ');
        break;

    case '+':
        s += char_row('$¢£‰¤¥¡¿©® «»„“” ÐðÞþßǶƕÑñĝĥĵ†‡™•⁂');
        s += char_row('′″‴¦§¨ªº¯° ‹›‚‘’ ±¹²³´¶·¸¼½¾×÷ȣƺ‿−');
        break;

    case 'CD':
        s += char_row('ÇĆĈĊČƆƇÐĎĐƉƊĜĤĴ');
        s += char_row('çćĉċčɔƈðďđɖɗĝĥĵ');
        break;

    case 'LN':
        s += char_row('ĹĻĽĿŁ_ÑŃŅŇ Ŋ');
        s += char_row('ĺļľŀł_ñńņňŉŋ');
        break;

    case 'R-Z':
        s += char_row('ŔŖŘ  ŚŜŞŠŢŤŦŹŻŽ');
        s += char_row('ŕŗřßſśŝşšţťŧźżž');
        break;

    case 'αβγ':
        s += char_row('ΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟΠΡΣ ΤΥΦΧΨΩ');	// (ATB) 
        s += char_row('αβγδεζηθικλμνξοπρσςτυφχψω');	// (ATB)
        break;

    case 'ἄ':
        s += char_row('Ά ᾺἈἌἎἊἉἍἏἋᾼ   ᾈᾌᾏᾊᾉᾍᾎᾋᾸᾹ');	// (ATB) Ά ->, Ά ->, Ἀ <-
        s += char_row('άᾶὰἀἄἆἂἁἅἇἃᾳᾴᾷᾲᾀᾄᾆᾂᾁᾅᾇᾃᾰᾱ');	// (ATB) ά -> ά, ἀ <-
        break;

    case 'ἒ':
        s += char_row('Έ ῈἘἜ ἚἙἝ Ἓ');			// (ATB) Έ(1) ->, Ἒ(2) ->
        s += char_row('έ ὲἐἔ ἒἑἕ ἓ');			// (ATB) έ(1) ->, ἒ ->
        break;

    case 'ἠ':
        s += char_row('Ή ῊἨἬἮἪἩἭἯἫῌ   ᾘᾜᾞᾚᾙᾝᾟᾛ');	// (ATB) Ή -> Ή, Ὴ <-
        s += char_row('ήῆὴἠἤἦἢἡἥἧἣῃῄῇῂᾐᾔᾖᾒᾑᾕᾗᾓ');	// (ATB) ή ->, ῆ <-, ὴ <-, ῇ <-
        break;

    case 'ΐ':
        s += char_row('Ί ῚἸἼἾἺἹἽἿἻΪ   ῘῙ');		// (ATB) Ί(1) ->, ΐ ->
        s += char_row('ίῖὶἰἴἶἲἱἵἷἳϊΐῗῒῐῑ');		// (ATB) ῖ <-, ΐ(1) ->, ῗ <-
        break;

	case 'ό':
        s += char_row('Ό ῸὈὌ ὊὉὍ Ὃ');			// (ATB) Ό(1) ->
        s += char_row('ό ὸὀὄ ὂὁὅ ὃ');			// (ATB) ό(1) ->
        break;

    case 'ύ':
        s += char_row('Ύ Ὺ    ὙὝὟὛΫ   ῨῩ');		// (ATB) Ύ ->
        s += char_row('ύῦὺὐὔὖὒὑὕὗὓϋΰῧῢῠῡ');		// (ATB) ῦ <-, ύ(1) ->, ΰ <-, ῧ <-, ῢ <-
        break;

    case 'ώ':
        s += char_row('Ώ ῺὨὬὮὪὩὭὯὫῼ   ᾨᾬᾮᾪᾩᾭᾯᾫ'); 	// (ATB) Ὑ -> Ὡ, Ώ(1) ->
        s += char_row('ώῶὼὠὤὦὢὡὥὧὣῳῴῷῲᾠᾤᾦᾢᾡᾥᾧᾣ'); 	// (ATB) ὑ -> ὡ, ώ(1) ->
        break;

    case 'ῤ+':
        s += char_row('ϚϜϞϠ Ῥ͵ ·'); 			// (ATB) ʹ ->, ͵ <-, · -> ·
        s += char_row('ϛϝϟϡῤῥʹ ;'); 			// (ATB) ʹ <-
        break;

    case 'ћ':
        s += char_row('ЂЃЀЁЄЅЍІЇЙЈЉЊЋЌЎЏЩЪЫЬЭЮЯ');
        s += char_row('ђѓѐёєѕѝіїйјљњћќўџщъыьэюя');
        break;

    case 'Ѫ':
        s += char_row('ѢѠѡѢѣѤѥѦѧѨѩѪѫѬѭѮѯѰѱ');
        s += char_row('ѲѳѴѵѶѷѸѹѺѻѼѽѾѿҀҁ҂Ğğ');
        break;

    default:
        break;

    }


    s += '</table>\n';
    return s;
}

/**
 * @return {boolean}
 */
function InsertChar(c) {
    ReplaceText(c);
    if(_is_wordchecking) {
        requestWordcheck();
    }
    return false;
}


function eCharClick(e) {
    if(!e) { e = window.event; }
    var t = e.target || e.srcElement;
    var val = t.value || t.textContent;

    if(t.className == "selector") {
        if(_active_charselector) {
            _active_charselector.style.border = "0";
        }
        _active_charselector = t;
        //noinspection JSPrimitiveTypeWrapperUsage
        _active_charselector.style.border = "2px solid red";
        $('pickers').innerHTML = char_pickers(val);
        $('divcharshow').style.visibility = (val == "❦" ? "hidden" : "visible");
        return true;
    }

    if(t.className == "picker") {
        if(_active_char) {
            _active_char.style.border = "0";
        }
        _active_char = t;
        //noinspection JSPrimitiveTypeWrapperUsage
        t.style.border = "2px solid red";
        InsertChar(val);
    }
    return true;
}

function ePickerOver(e) {
    var tgt;
    var ctgt;
    var ditgt;
    if(!e) { e = window.event; }

    tgt = e.target ? e.target : e.srcElement;
    if(tgt.nodeName.toLowerCase() !== "td") {
        return;
    }
    if(tgt.className != 'picker') {
        return;
    }
    if(tgt.innerHTML) {
        ctgt = tgt.innerHTML;
        ditgt = ( igraphs[ctgt] ? igraphs[ctgt] : '' );
        $('divchar').innerHTML = tgt.innerHTML;
        $('divdigraph').innerHTML = ditgt;
    }
}

/*
    Replace SelectedText with argument
 */
function ReplaceText(str) {
    if(_is_tail_space) {
        str += ' ';
    }

    // IE
    if(doc.selection  && doc.selection.createRange) {
        var sel = doc.selection;
        var ierange = sel.createRange();
        ierange.text = str;
        sel.empty();
    }
    else {
        var itop   = divfratext.scrollTop;
        var istart = tatext.selectionStart;

        tatext.value = 
            tatext.value.substring(0, tatext.selectionStart) 
            + str + tatext.value.substring(tatext.selectionEnd);
        tatext.selectionEnd = 
            tatext.selectionStart = istart + str.length;
    }
    consider_wordchecking();
    tatext.focus();
    divfratext.scrollTop = itop;
}



function digraph(c) {
    return digraphs[c] ? digraphs[c] : null;
}

function invert_digraphs() {
    var d;
    for(d in digraphs) {
        if(digraphs.hasOwnProperty(d)) {
            igraphs[digraphs[d]] = d;
        }
    }
}


function eKeyUp() {
    // to compensate when number of lines changes in tatext (changing tatext.offsetHeight)
    // need to reset prepreview.innerHTML and derive divtext dimensions
    // (or else tatext bottom line becomes invisible).
    // Textarea change won't work because it only fires when textarea loses focus.

    if(tatext.value != _text) {
        set_text_size();
        consider_wordchecking();
        _text = tatext.value;
    }
}

function eKeyDown(e) {
    var kCode;

    set_text_element();

    kCode = (e.which && typeof e.which == "number") 
        ? e.which 
        : e.keyCode;

    switch(kCode) {
        case  8:  // backspace
            //noinspection IfStatementWithTooManyBranchesJS,IfStatementWithTooManyBranchesJS
            if(_keystack.length == 0) {
            }
            else if(_keystack.length == 1) {
                _keystack += "\b";
            }
            else if(_keystack.length == 2 && _keystack[1] == "\b") {
                _keystack = "";
            }
            else {
            }
            break;
    }

    return true;
}

function eAltKeyPress(kCode) {
    var k = String.fromCharCode(kCode);
    switch (k) {
    case "q":
    case "#":
        eSetBlockQuote();
        break;
    case "w":
    case "*":
        eSetNoWrap();
        break;
    case "b":
        eSetBold();
        break;
    case "u":
        eSetUpperCase();
        break;
    case "l":
        eSetLowerCase();
        break;
    case "t":
        eSetTitleCase();
        break;
    case "i":
        eSetItalics();
        break;
    case "s":
        eSetSmallCaps();
        break;
    case "1":
        eLineHeight("lh10");
        break;
    case "5":
        eLineHeight("lh15");
        break;
    case "2":
        eLineHeight("lh20");
        break;
    case "j":
        eDeHyphen();
        break;
    default:
        break;
    }
}

//noinspection FunctionWithMoreThanThreeNegationsJS,OverlyComplexFunctionJS,FunctionTooLongJS,FunctionTooLongJS
function eKeyPress(e) {
    if(!e) { e = window.event; }
    var kCode = (e.which && typeof e.which == "number")
                ? e.which : e.keyCode;

    // handle keyboard shortcuts
    if(e.altKey) {
        if(!e.ctrlKey && !e.metaKey) {
            eAltKeyPress(kCode);
        }
        _keystack = "";
        return true;
    }

    // inspect and manipulate for digraph

    switch (kCode) {
    case 8:  // backspace - needs to be trapped by keydown
    case 16: // IE passes the shift key, ignore it
        return true;

    // remember - \b is filtered out
    default:
        //noinspection NestedSwitchStatementJS
        switch (_keystack.length) {
            case 0:
            case 1:
                // legit character, start the stack
                _keystack = String.fromCharCode(kCode);
                return true;

            case 2:
                // sb a character and a backspace
                if(_keystack[1] == "\b") {
                    // complete the digraph and stop bubbling
                    _keystack = _keystack[0] 
                                + String.fromCharCode(kCode);
                }
                else {
                    // restart the stack
                    _keystack = String.fromCharCode(kCode);
                    return true;
                }
                break;

            default:    // error
                _keystack = String.fromCharCode(kCode);
                return true;
        }
    }

    // should only get here if collected char 2
    // time to fire digraph

    var mappedChar = digraph(_keystack);

    if(! mappedChar) {
        return true;
    }

    // apply digraph (and filter out keykstroke default)
    e.preventDefault();
    e.returnValue = false;

    var val = tatext.value;

    // Non-IE browsers and IE 9+
    if(typeof tatext.selectionStart == "number"
        && typeof tatext.selectionEnd == "number") {
        var itop     = divfratext.scrollTop;
        var start    = tatext.selectionStart;
        var end      = tatext.selectionEnd;
        // insert mapped character
        tatext.value = val.slice(0, start) + mappedChar + val.slice(end);

        // Move the cursor past inserted character
        tatext.selectionStart = start + 1;
        tatext.selectionEnd = start + 1;
        // reapply scroll position
        divfratext.scrollTop    = itop;
    }
    // For IE up to version 8
    else if(doc.selection  && doc.selection.createRange) {
        var selectionRange  = doc.selection.createRange();
        var textInputRange  = tatext.createTextRange();
        var precedingRange  = tatext.createTextRange();
        var bookmark        = selectionRange.getBookmark();
        textInputRange.moveToBookmark(bookmark);
        precedingRange.setEndPoint("EndToStart", textInputRange);
        start               = precedingRange.text.length;
        end                 = start + selectionRange.text.length;
        tatext.value        = val.slice(0, start) + mappedChar + val.slice(end);
        start++;

        // Move the cursor
        //noinspection ReuseOfLocalVariableJS,ReuseOfLocalVariableJS
        textInputRange      = tatext.createTextRange();
        textInputRange.collapse(true);
        textInputRange.move("character", start - (tatext.value.slice(0, start).split(/\r\n/).length - 1));
        textInputRange.select();
    }

    consider_wordchecking();

    return false;
}

function SetCursor(pos) {
    SetSelection(pos, pos);
}

function SetSelection(start, end) {
    var textInputRange;
    if(typeof tatext.selectionStart == "number" 
        && typeof tatext.selectionEnd == "number") {
        // Non-IE browsers and IE 9

        // Move the cursor
        tatext.selectionStart   = start;
        tatext.selectionEnd     = end;
    }
    else if(doc.selection && doc.selection.createRange) {
        textInputRange = tatext.createTextRange();
        // conflate end to beginning
        textInputRange.collapse(true);
        textInputRange.moveStart('character', start);
        textInputRange.moveEnd('character', start
            - (tatext.value
                .slice(0, start).split(/\r\n/).length - 1));
        textInputRange.select();
    }
}

// std

function eShowDigraphs() {
    $("digraphframe").style.display = 'block';
    createDigraphHTML();
}

function eHideDigraphs() {
    $("digraphframe").style.display = 'none';
}

function createDigraphHTML()
{
    var t = $("digraph");
    if (t.rows.length > 0)
        return;

    var col = 0;
    var row;
    for (k in digraphs) {
        var v = digraphs[k];

        if ((col % 10) == 0)
            row = t.insertRow(-1);
        var c = row.insertCell(-1);
        c.innerHTML = "<pre>" + k + "</pre>";
        c = row.insertCell(-1);
        c.innerHTML = v;
        col++;
    }
}

function translate(str) {
    return str;
}

// needs to ajax for translation
function AreYouSure(question) {
    return(confirm(translate(question)));
}

function Confirm(question) {
    return(confirm(translate(question)));
}

/**
 * @return {boolean}
 * @return {boolean}
 */
function IsSelectedText() {
    return tatext.selectionEnd > tatext.selectionStart;
}
//noinspection FunctionWithMoreThanThreeNegationsJS
/**
 * @return {string}
 */
function SelectedText() {
    var sel;
    var ierange;
    var seltext;

    if(!tatext || tatext.value.length == 0) {
        return '';
    }

    if(doc.selection) {
        sel = doc.selection;
        if (!sel || !sel.createRange) {
            return '';
        }

        ierange = sel.createRange();
        seltext = ierange.text;
    }
    else if(tatext.selectionEnd) {
        if(tatext.selectionStart == tatext.selectionEnd) {
            return '';
        }
        seltext = tatext.value.substring(
                tatext.selectionStart, tatext.selectionEnd);
    }
    else {
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

function eSetFootnote() {
    var sel = SelectedText();
    var re = /\s/; 
    var txt = '[Footnote ' + sel.replace(re, ': ') + ']';
    ReplaceText(txt);
    return false;
}

function eSetIllustration() {
    var sel = SelectedText();
    var txt = '[Illustration: ' + sel + ']';
    ReplaceText(txt);
    return false;
}

function eInsertThoughtBreak() {
    ReplaceText("\n<tb>\n");
    divtext_match_tatext();
    return false;
}

function eSetBlankPage() {
    tatext.value = "[Blank Page]";
    divtext_match_tatext();
    consider_wordchecking();
    return false;
}

function eSetNote() {
    var sel = SelectedText();
    var txt = '[** ' + sel + ']';
    ReplaceText(txt);
    return false;
}

function eSetBraces() {
    var sel = SelectedText();
    var txt = '{' + sel + '}';
    ReplaceText(txt);
    return false;
}

function eSetBrackets() {
    var sel = SelectedText();
    var txt = '[' + sel + ']';
    ReplaceText(txt);
    return false;
}

function applyIsPunc(str) {
    var re = /[;:\.,!?]/g;
    return str
	.replace(re, "<span class='punc'>$&</span>")
	.replace(/&amp<span class='punc'>;<\/span>/g, "&")
	.replace(/&lt<span class='punc'>;<\/span>/g, "&lt;")
	.replace(/&gt<span class='punc'>;<\/span>/g, "&gt;");
}

//noinspection FunctionWithMoreThanThreeNegationsJS,FunctionWithMoreThanThreeNegationsJS,OverlyComplexFunctionJS,FunctionWithMultipleLoopsJS,FunctionTooLongJS,FunctionTooLongJS
function eSetTitleCase() {
    var i, c;
    var j, d;
    var newstr = '', word = '';

    var sel = SelectedText();
    if(!sel || !sel.length) {
        return false;
    }

    for(i = 0; i < sel.length; i++) {
        c = sel.charAt(i);
        // if starting new word
        if(word.length == 0) {
            // is it alpha? word = lc
            if(c.toUpperCase() != c.toLowerCase()) {
                word = c.toLowerCase();
                continue;
            }
            // is it apostrophe or digit? start word with it
            if(c == "'" || (c >= "0" && c <= "9")) {
                word = c;
                continue;
            }
            // don't start word, just add to newstr
            newstr += c;
            continue;
        }

        // append any alpha or apostrophe or digit to word
        // and continue
        if(c.toUpperCase() != c.toLowerCase()
                || c == "'"
                || (c >= "0" && c <= "9")) {
            word += c.toLowerCase();
            continue;
        }

        // there is a word and it's not continuing
        // uppercase 1st letter if newstr empty 
        //     or word not in lc words
        if(newstr.length == 0 || lc_words.indexOf(word) < 0) {
            for(j = 0; j < word.length; j++) {
                d = word.charAt(j);
                if(d.toUpperCase() != d.toLowerCase()) {
                    word = word.substr(0, j-1)
                            + d.toUpperCase()
                            + word.substr(j+1);
                    break;
                }
            }
        }

        newstr += word;
        word = "";

        // is it alpha? word = lc
        if(c.toUpperCase() != c.toLowerCase()) {
            word = c.toLowerCase();
            continue;
        }
        // is it apostrophe or digit? start word with it
        if(c == "'" || (c >= "0" && c <= "9")) {
            word = c;
            continue;
        }
        // don't start word, just add to newstr
        newstr += c;
    }

    // done processing input - may have an unappended word

    if(word.length > 0) {
        if(newstr.length == 0 || lc_words.indexOf(word) < 0) {
            for(j = 0; j < word.length; j++) {
                d = word.charAt(j);
                if(d.toUpperCase() != d.toLowerCase()) {
                    word = word.substr(0, j-1)
                            + d.toUpperCase()
                            + word.substr(j+1);
                    break;
                }
            }
        }

        newstr += word;
    }

    ReplaceText(newstr);
    
    return false;
}

function eCurlyQuotes() {
    var sel = SelectedText();
    ReplaceText(sel);
    return false;
}

function eSetGuillemets() {
    var sel = SelectedText();
    sel = sel.replace(/"?([^"]*)"?/, '«$1»');
    ReplaceText(sel);
    return false;
}

function eSetGuillemetsR() {
    var sel = SelectedText();
    sel = sel.replace(/"?([^"]*)"?/, '»$1«');
    ReplaceText(sel);
    return false;
}

function eSetItQuotes() {
    var sel = SelectedText();
    sel = sel.replace(/"?([^"]*)"?/, '"$1„');
    ReplaceText(sel);
    return false;
}

function eSetDeQuotes() {
    var sel = SelectedText();
    sel = sel.replace(/"?([^"]*)"?/, '„$1“');
    ReplaceText(sel);
    return false;
}

function eSetUpperCase() {
    var sel = SelectedText();
    if(sel.length) {
        ReplaceText(sel.toUpperCase());
    }
    return false;
}

function eSetLowerCase() {
    var sel = SelectedText();
    if(sel.length) {
        ReplaceText(sel.toLowerCase());
    }
    return false;
}

function eRemoveMarkup() {
    var s = SelectedText();
    if(s.length) {
        ReplaceText(s.replace(/<\/?\w\w?>/g, '').replace(/(\/#|\/\*|#\/|\*\/)\n?/g, ''));
        divtext_match_tatext();
    }
    return false;
}

function eSetBold() {
    setFont("b");
    return false;
}

function eSetItalics() {
    setFont("i");
    return false;
}

function eSetAntiqua() {
    setFont("f");
    return false;
}

function eSetGesperrt() {
    setFont("g");
    return false;
}

function eSetSmallCaps() {
    setFont("sc");
    return false;
}

function setFont(tag) {
    var startTag = "<" + tag + ">";
    var endTag = "</" + tag + ">";
    var sel = SelectedText();
    if (sel == "")
        return false;
    if (selectionInNowrap())
        sel = fontPerLine(sel, startTag, endTag);
    else
        sel = startTag + sel + endTag;
    ReplaceText(sel);
    return false;
}

/*
 * In a no-wrap block, add font tags to each line.
 */
function fontPerLine(str, startTag, endTag) {
    var lines = str.split('\n');
    var result = "";

    for (var i = 0; i < lines.length; i++) {
        var l = lines[i];
        if (l != "") {
            var lead = "";
            while (l.startsWith(" ")) {
                l = l.substr(1);
                lead += " ";
            }

            if (l.startsWith('"')) {
                // Leading quote: result is: "<i>text
                lead += '"';
                l = l.substring(1);
            }
            var trailingQuote = l.endsWith('"');
            if (trailingQuote)
                // Trailing quote: result is: text</i>"
                l = l.substr(0, l.length-1);
            result += lead + startTag + l + endTag;
            if (trailingQuote)
                result += '"';
        }
        if (i != lines.length-1)
            result += "\n";
    }
    return result;
}

/*
 * Is the current selection in a no-wrap block?
 */
function selectionInNowrap() {
    var lead = tatext.value.substring(0, tatext.selectionStart) 
    var lines = lead.split('\n');
    var inNowrap = false;
    for (var i = 0; i < lines.length; i++) {
        var l = lines[i];

        if (l == "/*")
            inNowrap = true;
        else if (l == "*/")
            inNowrap = false;
    }
    return inNowrap;
}

function eSetNoWrap() {
    var sel = SelectedText();
    if(sel.length == 0) {
        return;
    }
    // newlines, anything ending in not-white-space, whitespace to end
    // to newlines, / * newline, middle text, newline * /, trailing whitespace
    var rpl = sel.replace(/^(\n*)([\s\S]*\S)(\s*)$/g, "$1/*\n$2\n*/$3");
    ReplaceText(rpl);
    divtext_match_tatext();
    return false;
}

function eSetSidenote() {
    var sel = SelectedText();
    var txt = '[Sidenote: ' + sel + ']';
    ReplaceText(txt);
    return false;
}

function eSetBlockQuote() {
    var sel = SelectedText();
    if(sel.length == 0 ) {
        return;
    }
    var rpl = sel.replace(/^(\n*)([\s\S]*\S)(\s*)$/g, "$1/#\n$2\n#/$3");
    //ReplaceText('/#\n' + sel + '\n#/');
    ReplaceText(rpl);
    divtext_match_tatext();
    return false;
}

function eToggleTweet() {
    var _div = $("divtweet");
    if(_div.style.display == "block") {
        _div.style.display = "none";
        putTweet();
    }
    else {
        getTweet();
        _div.style.display = "block";
        _div.style.top = px(divcontrols.offsetTop);
        _div.style.height = px(divcontrols.offsetHeight);
    }
}

function eCloseTweet() {
    var _div = $('divtweet');
    if(_div.style.display == "block") {
        _div.style.display = "none";
    }
}

function eToggleFandR() {
    var _div = $('divFandR');
    if(_div.style.display == "block") {
        _div.style.display = "none";
    }
    else {
        _div.style.display = "block";
        _div.style.top = px(divcontrols.offsetTop);
        _div.style.height = px(divcontrols.offsetHeight);
        if(SelectedText() != "") {
            $("txtfind").value = SelectedText();
            $("txtrepl").value = "";
        }
    }
}

function eCloseFandR() {
    var _div = $('divFandR');
    if(_div.style.display == "block") {
        _div.style.display = "none";
    }
}

function eShowPreviewErrors() {
    divPreviewErrors.style.display = "block";
    divPreviewErrors.style.top = px(divcontrols.offsetTop);
    divPreviewErrors.style.height = px(divcontrols.offsetHeight);
}

function eHidePreviewErrors() {
    divPreviewErrors.style.display = "none";
}

function eToggleGear() {
    if(divGear.className == "hide") {
        divGear.style.bottom = px(divstatusbar.offsetHeight);
        divGear.className = "block";
    }
    else {
        divGear.className = "hide";
    }
}

function eGearClick(e) {
    if(!e) { e = window.event; }
    switch(e.target.name) {
        case "rdoEditor":
            setEditorStyle(e.target.value);
            if(e.target.value == "icons") {
                $("divicons").className = "block";
                $("divmenu").className = "hide";
            }
            else if(e.target.value == "menu") {
                $("divicons").className = "hide";
                $("divmenu").className = "block";
            }
            break;
        case "rdoLineHeight":
            tatext.className = e.target.value;
            prepreview.className = e.target.value;
            saveLineHeight(e.target.value);
            break;
        case "chkIsWC":
            setIsWC();
            break;
        case "chkIsPunc":
            SaveIsPunc();
            break;
	case "runAlways":
	    SaveRunAlways();
	    break;
        default:
            break;
    }
}

function set_regex(key) {
    // if it's not regex, escape and act as if it were
    if(! $('chkr')) {
        key = key.replace('([[.\+*?[^]$(){}=!<>|:-])', '\\\1');
    }
    if($('chkm')) {
        key = key.replace(/\./, "[\s\S]");
    }
    var flags = 'g' + ($('chki').checked ? 'i' : '');
    return new RegExp(key, flags);
}

function scroll_to_selection() {
    var p1 = tatext.value;
    var p2 = p1;

    // span the found string in prepreview and scroll to it
    var ipos = SelectionBounds().start;
    p1 = p1.slice(0, ipos) + "<span id='mark_'></span>" + p1.slice(ipos);
    spanpreview.innerHTML = p1;
    // the following works even if prepreview display is "none"
    $('mark_').scrollIntoView();

    // set prepreview back without the span
    spanpreview.innerHTML = p2;
}

function eDeHyphen() {
    /*

    /(\B)-\n(\B+\S*)\b/
    The target is EOL hyphen preceded by a word character (\B)
        followed by \B after EOL
    if there is no selection
        search the whole text past cursor
        highlight the first eol hypoen
        return
    there is a selection
    is there  no eol hyphen preceded by \B
        return
    locate l1 = "\B-\n" (between selectionStart and selectionEnd)
    locate first l2 = whitespace following EOL
    no whitespace?
        return
    whitespace is newline?
        replace $0 with $1 + $2
    else
        replace whitespace with newline
     */
    // var re = /(\B)-\n(\B\S*)/g;
    var re = /(\w+)-\n(\w+)\b(\S*)(\s?)/;
    var rslt, repl = "$1$2$3\n";
    var s1, s2;
    if(IsSelectedText()) {
        s1 = tatext.value.substring(0, tatext.selectionStart);
        s2 = tatext.value.substring(tatext.selectionStart);
        s2 = s2.replace(re, repl);
        tatext.value = s1 + s2;
        //str = SelectedText().replace(re, repl);
        //ReplaceText(str);
        //SetSelection(re.lastIndex, re.lastIndex);

        if(_is_wordchecking) {
            requestWordcheck();
        }
    }

    re.lastIndex = tatext.selectionStart;
    rslt = re.exec(tatext.value);
    if(! rslt) {
        rslt = re.exec(tatext.value);
    }
    if(! rslt) {
        return false;
    }

    SetSelection(rslt.index, rslt.index + rslt[0].length - 1);
    scroll_to_selection();
    tatext.focus();

    return false;
}

//function eHiliteQuotes() {
    //var pvw = $("spanpreview").innerHTML;
    //var re = /([\s\S]*?)"(?!\n\n)([\s\S]*?)"/g;
    //var pvw2 = pvw.replace(re, '$1<span class="blue">"$2"</span>)');
//}

function eFind() {

    // if target null, return
    if ($('txtfind').value.length == 0) {
        // if something currently selected, use that for the target
        if (SelectedText().length > 0) {
            $('txtfind').value = SelectedText();
        } else {
            return false;
        }
    }

    // Save current text&repl
    setFandR();

    var regex = getFandRRegex();
    var pos = SelectionBounds();
    regex.lastIndex = pos.start + 1;

    return find_regex(regex, pos);
}

function getFandRRegex() {
    var key = $('txtfind').value;
    // If regex isn't checked, then escape all regex-special characters
    if (!$('chkr').checked) {
        key = key.replace(/([[.\+*?^\]$(){}=!<>|:-])/g, '\\$1');
    }
    // If multi-line, replace spaces with any space including newline
    if ($('chkm').checked) {
        key = key.replace(/ /g, "[\\s]");
    }
    var flags = 'g' + ($('chki').checked ? 'i' : '');
    console.log("Regex: " + key + ", flags: " + flags);
    return new RegExp(key, flags);
}

function find_regex(regex, bounds) {
    var istart;
    var t       = tatext.value;
    var rslt    = regex.exec(t);

    // if searching from interior and nothing found, search from the beginning
    if(! rslt && bounds.start > 0) {
        regex.lastIndex = 0;
        rslt = regex.exec(t);
    }
    if(! rslt) {
        return false;
    }

    var fword = rslt[0];
    istart = rslt.index;
    var iend = istart + fword.length;
    SetSelection(istart, iend);
    
    scroll_to_selection();
    tatext.focus();
    return true;
}

function eReplace() {
    var sb = SelectionBounds();
    var start = sb.start;
    if (start == sb.end)
        // Nothing selected
        return;

    // Note repl may have $1 in it, so make sure we do the match again
    var repl = $('txtrepl').value;
    var rstr = SelectedText().replace(getFandRRegex(), repl);
    ReplaceText(rstr);
    SetCursor(start + rstr.length);
    return rstr.length;
}

function eReplaceNext() {
    eReplace();
    eFind();   
}

function eReplaceAll() {
    // Always start at the top.
    var last = 0;
    SetCursor(0);
    while (true) {
        if (!eFind()) {
            return;
        }

        // In the case of a substitute which doesn't change something,
        // e.g. XXX -> <i>XXX</i>
        // make sure we don't go back to the beginning!
        var start = SelectionBounds().start;
        if (start <= last)
            return;
        last = start;

        var len = eReplace();
        last += len;
    }
}

function FontFace() {
    return $('selfontface') 
        .item($('selfontface').selectedIndex) 
            .value;
}

function eSetFontFace() {
    prepreview.style.fontFamily =
    tatext.style.fontFamily     = FontFace();
    divtext_match_tatext();
    SaveFontFace();
    return false;
}

function FontSize() {
    return $('selfontsize') 
        .item($('selfontsize').selectedIndex) 
            .value;
}

function eSetFontSize() {
    prepreview.style.fontSize =
    tatext.style.fontSize     = FontSize();
    divtext_match_tatext();
    SaveFontSize();
    return false;
}

// when the preview button is clicked
function ePreviewFormat() {
    // if preview visible, display text (wc should be hidden)
    if(_is_previewing) {
        hide_preview();
        show_text();
        _is_previewing = false;
    }
    else {
        // preview is hidden, it was clicked, show it, hide others
        var result = tatext.value;
        result = formattedTextAnalysis(result);
        result = result.replace('<tb>', '<hr>');
        setPreviewText(result);
        hide_wordchecking();
        hide_text();
        show_preview();
        _is_previewing = true;
    }
}

function formattedTextAnalysis(str)
{
    var analysis = new TextAnalysis(str);
    analysis.block();
    analysis.inline();
    analysis.displayErrors();
    return analysis.text;
}

class TextAnalysis {
    constructor(str) {
        this.text = str;
        this.msgs = [];
        this.references = [];
    }

    displayErrors()
    {
        console.log("MSGS: " + this.msgs);
        $('divPreviewErrors').innerHTML = this.msgs.join('<br>\n');
        $("span_fmtcount").innerHTML = (this.msgs.length).toString();
    }

    /*
     * Analyse block-quotes and no-wrap blocks.
     */
    block()
    {
        var blocks = [];
        var lines = this.text.split('\n');
        for (var i = 0; i < lines.length; i++) {
            var l = lines[i];
            var last = (i > 0 ? lines[i-1] : "");
            var next = (i+1 == lines.length ? "" : lines[i+1]);

            if (l == "/*") {
                if (blocks.indexOf("*") != -1)
                    lines[i] = this.err(l, "/* (no-wrap) may not be nested");
                blocks.push("*");
                if (last != "" && last != "/#")
                    lines[i] = this.err(l, "/* (no-wrap) must be preceeded by a blank line, start of page, or start of block-quote");
            } else if (l == "/#") {
                if (blocks.indexOf("*") != -1)
                    lines[i] = this.err(l, "/# (block quote) inside a /* (no-wrap)");
                blocks.push("#");
                if (last != "")
                    lines[i] = this.err(l, "/# (block quote) must be preceeded by a blank line or start of page");
            } else if (l == "*/") {
                if (blocks.pop() != "*")
                    lines[i] = this.err(l, "*/ (end no-wrap) never opened");
                if (next != "" && next != "#/")
                    lines[i] = this.err(l, "*/ (end no-wrap) must be followed by a blank line, end of page, or end of block-quote");
            } else if (l == '#/') {
                if (blocks.pop() != "#")
                    lines[i] = this.err(l, "#/ (end block quote) never opened");
                if (next != "")
                    lines[i] = this.err(l, "#/ (end no-wrap) must be followed by a blank line or end of page");
            } else {
                var marker;
                for (marker in { "/*":0, "*/":0, "/#":0, "#/":0 }) {
                    if (l.indexOf(marker) != -1)
                        lines[i] = this.err(l, marker + " embedded in line");
                }
            }
        }
        if (blocks.length != 0) {
            this.msgs.push("Open /" + blocks.pop() + " at end of page");
        }
        this.text = lines.join("\n");
    }

    /*
     * Check a page for any font issues: split to paragraphs, and
     * check each paragraph
     */
    inline()
    {
        var blocks = this.parseToParas();
        for (var i = 0; i < blocks.length; i++)
            this.balancedFonts(blocks[i]);

        // Check if any footnote references remain
        if (this.references.length > 0)
            this.err('', "This page references footnote(s) " + this.references +
                " but definition(s) were not found.");
    }

    /*
     *  Split a page into paragraphs based on blank lines.
     *  Note no-wrap and block-quote lines are still in.
     */
    parseToParas()
    {
        var lines = this.text.split('\n');
        var block = [];
        var blocks = [];
        for (var i = 0; i < lines.length; i++) {
            var l = lines[i];

            if (l == '') {
                // TODO: validate 1, 2, or 4 blank lines only
                if (block.length > 0) {
                    blocks.push(block);
                    block = [];
                }
                continue;
            }
            block.push(l);
        }
        if (block.length > 0)
            blocks.push(block);
        return blocks;
    }

    /*
     * Check a single paragraph for font errors.
     * There are still no-wrap and block-quote markers in the paragraphs,
     * so we accumulate a line until we hit a no-wrap marker; then
     * we check individual lines within the no-wrap markers.
     */
    balancedFonts(block)
    {
        var inNoWrap = false;
        var accumulated = "";
        for (var i = 0; i < block.length; i++) {
            var l = block[i];

            if (l == '/*') {
                this.oneUnit(accumulated, false);
                accumulated = "";
                inNoWrap = true;
                continue;
            } else if (l == '*/') {
                inNoWrap = false;
                continue;
            }
            if (inNoWrap)
                this.oneUnit(l, true);
            else
                if (accumulated == "")
                    accumulated = l;
                else
                    accumulated += " " + l;
        }

        if (accumulated == "<tb>")
            return;

        this.oneUnit(accumulated, false);
    }

    /*
     * Perform checks for a single-unit, i.e. a paragraph or a no-wrap line.
     */
    oneUnit(str, inNoWrap)
    {
        //console.log("oneUnit: " + inNoWrap + ", " + str);
        this.footnoteReferences(str, inNoWrap);
        this.balance(str, inNoWrap);
        this.squareTags(str, inNoWrap);
    }

    footnoteReferences(str, inNoWrap)
    {
        var re = /\[.\]/g;
        var results = str.match(re);
        if (results != null) {
            for (var i = 0; i < results.length; i++) {
                var c = results[i].substr(1, 1);
                if (this.references.indexOf(c) == -1)
                    this.references.push(c);
                else
                    this.err(str, "Multiple references to footnote " + c);
            }
        }
    }

    /*
     * Validate the [XXXX form of markup.
     */
    squareTags(str, inNoWrap)
    {
        var tags = {
            "[Illustration" : this.illustration,
            // TODO: Sidenotes don't need to be isolated so need different code
            //"[Sidenode" : this.sidenote,
            "*[Footnote" : this.footnote,
            "[Footnote" : this.footnote,
        };
        for (var tag in tags) {
            if (str.startsWith(tag)) {
                if (inNoWrap)
                    this.err(str, "Markup " + tag + " may not be inside a no-wrap block");
                else
                    tags[tag].call(this, str);

                // Don't look for [Footnote!
                if (tag == "*[Footnote")
                    break;
            }
            if (str.indexOf(tag, 1) != -1)
                // Even if a valid tag, make sure another markup not embedded
                this.err(str, "Markup " + tag + " must start the line, not be embedded");
        }
    }

    illustration(str)
    {
        // Illustration doesn't need contents
        if (str == "[Illustration]")
            return;
        if (!str.startsWith("[Illustration: ")
        ||  !str.endsWith("]")) {
            this.err(str, "Malformed Illustration markup");
            return;
        }
    }

    sidenote(str)
    {
        if (!str.startsWith("[Sidenote: ")
        ||  !str.endsWith("]")) {
            this.err(str, "Malformed Sidenote markup");
            return;
        }
    }

    footnote(str)
    {
        // Continued footnote, no letter, and no reference check
        if (str.startsWith("*[Footnote")) {
            if (!str.startsWith("*[Footnote: "))
                this.err(str, "Incorrectly formatted footnote continuation");
            if (!str.endsWith("]") && !str.endsWith("]*"))
                this.err(str, "Incorrectly ended footnote continuation");
            return;
        }

        var re = /^\[Footnote .: .*\]\*?$/;
        var results = str.match(re);
        if (results == null) {
            this.err(str, "Malformed footnote markup");
            return 1;
        }
        var footnote = str.substr(10, 1);
        var off = this.references.indexOf(footnote);
        if (off == -1)
            this.err(str, "Footnote " + footnote + " not referenced");
        else
            // Remove? Or handle multiple references?
            this.references.splice(off, 1);
    }

    /*
     * Either a single line if in no-wrap; or an accumulated line for
     * a paragraph.  In both cases, we require fonts correct within this
     * entity.
     */
    balance(line, inNoWrap)
    {
        //console.log("balance: " + line);
        var off = 0;
        var fonts = [];
        var offsets = [];
        var st;
        var errAppend = "";

        if (inNoWrap)
            errAppend = ". In a no-wrap block, each line must individually balance all fonts.";
        while ((st = line.indexOf('<', off)) != -1) {
            var end = line.indexOf('>', off);
            off = st + 1;
            if (end == -1)
                continue;
            var token = line.substring(off, end);
            //console.log("token: " + token);
            if (token == '')
                // <>
                continue;
            if (token.indexOf('<') != -1)
                // <xxx<xxx>
                continue;
            off = end+1;
            if (token.startsWith('/')) {
                token = token.substring(1);
                if (fonts.length == 0) {
                    this.err(line, "End tag &lt;/" + token + "> has no open tag");
                    continue;
                }
                var startTag = fonts.pop();
                var startOff = offsets.pop();
                if (startTag != token && !startTag.startsWith("span class='errline'"))
                    this.err(line, "End tag &lt;/" + token + "> does not match open tag &lt;" + startTag + ">");

                // Do any validation on the text between the tags
                var text = line.substring(startOff, st);
                //console.log(startTag + ": >>>" + text + "<<<");
                if (text == "")
                    this.err(line, "Empty font tag: &lt;" + token + ">&lt;" + token + "/> embedded in line.");
            } else {
                if (token == "tb") {
                    this.err(line, "Non-isolated &lt;tb> tag: must be both preceeded and followed by a blank line.");
                    continue;
                }
                if (fonts.length > 0)
                    if (fonts.includes(token))
                        this.err(line, "Open tag &lt;" + token + ">: this tag already open: " + fonts);
                fonts.push(token);
                offsets.push(off);
            }
        }
        if (fonts.length > 0)
            this.err(line, "Open tag &lt;" + fonts.pop() + "> not closed" + errAppend);
    }

    err(line, msg)
    {
        this.msgs.push(msg);
        return "<span class='errline' title='" + msg + "'>" + line + "</span>";
    }
}

function h(str) {
return String(str)
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/&lt;(\/?)(i|b|hr)&gt;/ig, '<$1$2>')
    .replace(/&lt;sc&gt;/ig,
        '<span style="font-variant: small-caps;">')
    .replace(/&lt;\/sc&gt;/ig, '</span>')
}

function eZoomIn() {
    var w = GetZoom();
    w = w ? w * 1.05 : 100;
    if(w > 400) {
        w = 400;
    }
    imgpage.style.width = (w * 10).toString() + "px";
    SaveZoom(w);
    return false;
}

function eZoomOut() {
    var w = GetZoom();
    w = w ? w * 0.95 : 90;
    if(w < 25) {
        w = 25;
    }
    imgpage.style.width = (w * 10).toString() + "px";
    SaveZoom(w);
    return false;
}

function eLineHeight(lhclass) {
    saveLineHeight(lhclass);
    applyLineHeight(lhclass);
    eResize();
}

function applyLineHeight(lhclass) {
    prepreview.className    =
    tatext.className        = lhclass;
}

// seltodo.select()
//function eTodo() {
//    switch(seltodo.value) {
//        case "opt_test":
//            break;
//        default:
//            this.form.submit();
//    }
//}

// any opt image.click()
// input type=image implicitly submits
function eOptToDo() {
    accepts_to_form();
}

// seltodo.change()
function eSelToDo() {
    accepts_to_form();
    formedit.submit();
}

function eToggleBad(e) {
    var answer;
    //if($bad_state == "notbad") {
    if (true) {
        answer = Confirm("Page will be unavailable until fixed by PM.");
        if (!answer) {
	    console.log("Toggle bad: cancelled");
	    e.stopPropagation();
	    e.preventDefault();
	    return false;
	}
        $("badreason").value = answer;
        $("todo").value = "badpage";
	console.log("Toggle bad: submitting");
        formedit.submit();
    } else {
        $("todo").value = "fixpage";
        formedit.submit();
    }
}

function point_in_obj(obj, ptX, ptY) {
    var x1 = 0;
    var x2;
    var y1 = 0;
    var y2;
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

function eClickImage(e) {
    if(is_in(divnextimage, e.clientX, e.clientY)) {
        eToggleNextImage();
    }
    else if(is_in(divprevimage, e.clientX, e.clientY)) {
        eTogglePrevImage();
    }
}

/*
    if prev is open
        close it
    else (closed, need to open)
        close next if open
        open it
    end if
*/
function eToggleNextImage() {
    if(divnextimage.style.visibility == "visible") {
        hideNextImage();
    }
    else {
        hidePrevImage();
        showNextImage();
    }
}
function showNextImage() {
    divimage.style.height = "75%";
    divnextimage.style.visibility = "visible";
}
function hideNextImage() {
    divimage.style.height = "100%";
    divnextimage.style.visibility = "hidden";
}

function eTogglePrevImage() {
    if(divprevimage.style.visibility == "visible") {
        hidePrevImage();
    }
    else {
        hideNextImage();
        showPrevImage();
    }
}
function showPrevImage() {
    divimage.style.top = "25%";
    divimage.style.height = "75%";
    divprevimage.style.visibility = "visible";
    divprevimage.scrollTop = divprevimage.scrollHeight;
}
function hidePrevImage() {
    divimage.style.top = "0";
    divimage.style.height = "100%";
    divprevimage.style.visibility="hidden";
}

function eTextClick(e) {
    var i, ispan, sp;
    if(!e) { e = window.event; }
    // build a list of spans
    var spans = spanpreview.getElementsByTagName("SPAN");
    // where in the window was clicked
    var eX = e.clientX;
    var eY = e.clientY;
    // find the span that was clicked, if any
    for(i = 0; i < spans.length; i++) {
        sp = spans[i];
        if(point_in_obj(sp, eX, eY)) {
            ispan = sp;
            //noinspection BreakStatementJS
            break;
        }
    }

    if(! ispan) {
        return true;
    }

    switch(ispan.className) {
        // suspicious or bad - change to accepted and decr wc counter
        case "wc":
        case "wcb":
            ispan.className = "accepted";
            decr_wc_count();
            break;

        // accepted - noop
        case "wcs":
            break;

        // accecpted - toggle back to wc and incr wc counter
        case "accepted":
            ispan.className = "wc";
            incr_wc_count();
            break;

        default:
            break;
    }

    return true;
}

function decr_wc_count() {
    adjust_wc_count(-1);
}

function incr_wc_count() {
    adjust_wc_count(1);
}

function adjust_wc_count(adj) {
    var n = parseInt($("span_wccount").innerHTML, 10);
    $("span_wccount").innerHTML = (n + adj).toString();
}

function set_image_element() {
    set_scroll_element("divimage");
}

function set_text_element() {
    set_scroll_element("divfratext");
}

function set_null_element() {
    if(_active_scroll_element) {
        _active_scroll_element = null;
    }
}

function set_scroll_element(id) {
    if(_active_scroll_element != id) {
        _active_scroll_element = id;
    }
}

function eMouseMove(e) {
    if(!e) { e = window.event; }
    if(_is_resizing) {
        eSplitterMove(e);
        return;
    }
    if(is_in(divimage, e.clientX, e.clientY)) {
        set_image_element();
    }
    else if(is_in(divfratext, e.clientX, e.clientY)) {
        set_text_element();
    }
    else {
        set_null_element();
    }
}

function is_in(elem, x, y) {
    var bnds = elem.getBoundingClientRect();
    return x >= bnds.left && x <= bnds.left + bnds.width
        && y >= bnds.top  && y <= bnds.top  + bnds.height;
}

function eScroll(e) {
    if(! e) {e = window.event;}
    if(! issync()) { return true; }
    var tgt = e.currentTarget ? e.currentTarget : e.srcElement;
    if(tgt.id == "divfratext") {
        if(_active_scroll_element == "divfratext") {
            // source is divfratext, also scroller, apply to divimage
            apply_scroll_pct();
        }
    }
    else if(tgt.id == "divimage") {
        setlocalvalue("imgscroll", divimage.scrollLeft.toString());
        setnamevalue("imgscroll", divimage.scrollLeft.toString());
        if(_active_scroll_element == "divimage") {
            apply_scroll_pct();
        }
    }
    return true;
}

function apply_scroll_pct() {
    if(! issync() || ! _active_scroll_element) {
        return;
    }
    var fromctl = $(_active_scroll_element);
    var toctl   = (_active_scroll_element == "divfratext"
                    ? divimage
                    : divfratext);
    var fromrange = fromctl.scrollHeight - fromctl.offsetHeight;
    if(fromrange <= 0) {
        return;
    }
    var pct = 100 * fromctl.scrollTop / fromrange;
    var torange = toctl.scrollHeight - toctl.offsetHeight;
    if(torange <= 0) {
        return;
    }
    if(toctl.scrollTop == torange * pct / 100) {
        return;
    }
    toctl.scrollTop = torange * pct / 100;
}

function eSplitterDown(e) {
    e.preventDefault();
    _is_resizing = true;
    addEvent(doc, "mouseup",   eSplitterUp);
}

// mouse button released after dragging splitter
function eSplitterUp(e) {
    var pct;
    _is_resizing = false;
    removeEvent(doc, "mouseup", eSplitterUp);
    pct = (Layout() == "horizontal"
            ? 100 * e.clientY / boxheight
            : 100 * e.clientX / boxwidth
    );
    SaveBarPct(pct);
    // adjust panels to match splitter
    if(Layout() == "horizontal") {
        divleft.style.height        = px(e.clientY - 1);
        divtweet.style.top          =
        divFandR.style.top          =
        divGear.style.top           =
        divPreviewErrors.style.top  =
        divright.style.top          = px(e.clientY + divsplitter.offsetHeight);
    }
    else {
        divleft.style.width         = px(e.clientX-1);
        divright.style.left         = px(e.clientX) + divsplitter.offsetWidth;
    }
    applylayout();
}

// mouse is moving and is_resizing
function eSplitterMove(e) {
    if(Layout() == "horizontal") {
        divsplitter.style.top = px(e.clientY);
    }
    else {
        divsplitter.style.left = px(e.clientX);
    }
}

// resize body
function eResize() {
    applylayout();
}

function show_text() {
    tatext.style.visibility = "visible";
}

function hide_text() {
    tatext.style.visibility = "hidden";
}

function show_wordchecking() {
    show_text();
    // wordcheck uses prepreview to overlay the text
    //prepreview.style.visibility     = "visible";
    $("span_wccount").style.visibility = "visible";
    $("imgwordcheck").src            = "gfx/wchk-on.png";
}

function hide_wordchecking(){
    prepreview.style.visibility     = "hidden";
    $("span_wccount").style.visibility = "hidden";
    $("imgwordcheck").src            = "gfx/wchk-off.png";
}

function show_preview() {
    if(! $("imgpvw")) {
        return;
    }
    $("imgpvw").src                 = "/graphics/preview_on.png";
    prepreview.style.visibility     = "visible";
    $("span_fmtcount").style.visibility = "visible";
    eShowPreviewErrors();
}

function hide_preview(){
    if(! $("imgpvw")) {
        return;
    }
    $("imgpvw").src                 = "/graphics/preview_off.png";
    prepreview.style.visibility     = "hidden";
    $("span_fmtcount").style.visibility = "hidden";
    eHidePreviewErrors();
}

function set_wordchecking() {
    hide_preview();
    show_text();        // it overlays, show both
    show_wordchecking();
    _is_wordchecking = true;
    _wc_wakeup = 0;
}

function consider_wordchecking() {
    if (tatext.value === _text) {
        return;
    }
    _text = tatext.value;
    if(! _is_wordchecking) {
        return;
    }
    hide_preview();
    hide_wordchecking();
    // advance the token in case a wordcheck is running--
    // response will be ignored if the token has advanced.
    _wc_token++;
    // if there is no pause in place
    if(_wc_wakeup == 0) {
        // init pause and timer
        _wc_wakeup = _date.getTime() + 2000;
        window.setTimeout(resume_wordchecking, 2000);
    }
    else {
        // there is a pause; push it out
        _wc_wakeup = _date.getTime() + 2000;
    }
}

function resume_wordchecking() {
    // is there a pause? No? just return
    if(_wc_wakeup == 0) {
        return;
    }
    // is there time left on the pause?
    var delta = _wc_wakeup - _date.getTime();
    if(delta < 0) {
        // not expired - has been extended - leave for another
        return;
    }

    // it's timed out - cancel pause and submit a wordcheck
    // (which will handle token)
    _wc_wakeup = 0;
    requestWordcheck();
}

function ePing() {
    ajxPing();
}

function ajxPing() {
    var qry = {};
    qry['querycode']    = "ping";
    qry['token']        = ++_wc_token;
    qry['pvwtext']      = "";
    writeAjax(qry);
}

function display_ping() {
    window.alert("ping");
}
function clear_wordchecking() {
    if(_is_wordchecking) {
        _is_wordchecking                = false;
        _wc_wakeup                      = 0;
        hide_wordchecking();
        // don't mess with preview or text - hopefully one is set
    }
}

function eLangcode(e) {
    if(!e) { e = window.event; }
    var tgt = e.target ? e.target : e.srcElement;
    formedit.langcode.value = tgt.value;
    requestWordcheck();
}

function requestWordcheck() {
    // construct an array to transmit of words accepted
    _accept_tags = accept_tags();
    ajxWordcheck();
}

function accept_tags() {
    var i, a = [];
    var tags = spanpreview.getElementsByTagName("SPAN");
    for(i = 0; i < tags.length; i++) {
        if(tags[i].className == "accepted") {
            a[a.length] = tags[i];
        }
    }
    return a;
}

function wc_class_count(code) {
    var tags = spanpreview.getElementsByTagName("SPAN");
    var n = 0;
    var i;
    for(i = 0; i < tags.length; i++) {
        if(tags[i].className == code) {
            n++;
        }
    }
    return n;
}

function bw_count() {
    return wc_class_count("bw");
}

function wc_count() {
    return wc_class_count("wc");
}

// simple array of words to submit
function acceptwordarray() {
    var oks = accept_tags();
    var accepts = [];
    var i;
    for(i = 0; i < oks.length; i++) {
        accepts[accepts.length] = oks[i].innerHTML;
    }
    return accepts;
}

function accepts_to_form() {
    formedit.acceptwords.value = acceptwordarray().join("\t");
}

// initiate wordcheck transaction
function ajxWordcheck() {
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

function SaveBarPct(pct) {
    setlocalvalue(hv() + "_barpct", pct.toString());
    setnamevalue(hv() + "_barpct", pct.toString());
}

function hv() {
    return GetLayout()[0];
}

function GetBarPct() {
    var pct;
    var val = getnamevalue(hv() + "_barpct");
    if(val == "" || isNaN(val.toString())) {
        pct = 50;
        SaveBarPct(pct);
    }
    else {
        pct = parseInt(val);
    }
    if(pct == 0) {
        pct = 50;
        SaveBarPct(pct);
    }
    else if(pct < 20) {
        pct = 20;
        SaveBarPct(pct);
    }
    else if(pct > 80) {
        pct = 80;
        SaveBarPct(pct);
    }
    return pct;
}

/**
 * @return {boolean}
 */
function IsCtls() {
    return GetIsCtls() ;
}

/**
 * @return {boolean}
 */
function GetIsCtls() {
    var val = getnamevalue("isctls");
    if(! val) {
        SaveIsCtls("1");
        return true;
    }
    return getnamevalue("isctls") == "1";
}

function SaveIsCtls(val) {
    setlocalvalue("isctls", val ? "1" : "0");
    setnamevalue("isctls", val ? "1" : "0");
}

function GetFontFace() {
    // if cookie value is set, use it else use list selection
    var val = getnamevalue(hv() + "_fontface");
    return val == "" ? $('selfontface').value : val;
}

function GetFontSize() {
    var val = getnamevalue(hv() + "_fontsize");
    return val == "" ? $('selfontsize').value : val;
}

function SaveFontFace() {
    setlocalvalue(hv() + "_fontface", $('selfontface').value);
    setnamevalue(hv() + "_fontface", $('selfontface').value);
}

function SaveFontSize() {
    setlocalvalue(hv() + "_fontsize", $('selfontsize').value);
    setnamevalue(hv() + "_fontsize", $('selfontsize').value);
}

function GetZoom() {
    var strval = getnamevalue(hv() + "_zoom").toString();
    var val;
    if(strval == "" || isNaN(strval)) {
        setlocalvalue(hv() + "_zoom", "100");
        setnamevalue(hv() + "_zoom", "100");
        val = 100;
    }
    else {
        val = parseInt(strval, 10);
        if(val < 20) {
            val = 20;
        }
        else if(val > 1000) {
            val = 1000;
        }
        setlocalvalue(hv() + "_zoom", val.toString());
        setnamevalue(hv() + "_zoom", val.toString());
    }
    return val;
}

function SaveZoom(val) {
    setlocalvalue(hv() + "_zoom", val.toString());
    setnamevalue(hv() + "_zoom", val.toString());
}

/**
 * @return {string}
 */
function GetLayout() {
    var strval = getnamevalue("layout");
    switch(strval) {
        case "vertical":
        case "horizontal":
            return strval;
        default:
            SaveLayout("horizontal");
            return "horizontal";
    }
}

function SaveLayout(val) {
    switch(val.toString()) {
        case "vertical":
            setlocalvalue("layout", "vertical");
            setnamevalue("layout", "vertical");
            break;
        default:
            setlocalvalue("layout", "horizontal");
            setnamevalue("layout", "horizontal");
            break;
    }
}

/**
 * @return {string}
 */
function Layout() {
    return GetLayout();
}

function setLayoutIcon() {
    $('switchlayout').src = ( Layout() == "horizontal"
            ? "gfx/horiz.png"
            : "gfx/vert.png" );
}

function eSwitchLayout() {
    if(Layout() == "horizontal") {
        SaveLayout("vertical");
    }
    else {
        SaveLayout("horizontal");
    }
    applylayout();
    setLayoutIcon();
    return false;
}

// user clicks wc button
// 1. initial wordcheck,
// 2. wordchecking, and wants to edit
// 3. editing, and wants to (resume) wordcheck.

function eLinkWC() {
    // turn it off? Send accepted list
    if(_is_wordchecking) {
        clear_wordchecking();
    }
    else {
        requestWordcheck();
    }
    return false;
}

// process wordcheck response
function show_wordcheck() {
    var i, id;
    // wordchecking cancelled?
    if(! _is_wordchecking) {
        return;
    }
    // token match?
    if(_wc_token != _rsp.token) {
        return;
    }

    // var accepts = spanpreview.getElementsByTagName("accepted");
    var str = _rsp.pvwtext.replace(/~~/g, '&')
        .replace(/\s"\s/g, "<span class='spacey'>$&</span>")
        .replace(/\s'\s/g, "<span class='spacey'>$&</span>")
        .replace(/\s[,;:?!]/g, "<span class='spacey'>$&</span>");

    if(getIsPunc()) {
        str = applyIsPunc(str);
    }

    setPreviewText(str);

    // DAK
    // maybe here is the place to obtain a fresh correct text height
    // and apply it to tatext
    // (perhaps after prepreview visibility turned on?)
    // (shouldn't matter - as long as display = block)

    if(_accept_tags.length) {
        for(i = 0; i < _accept_tags.length; i++) {
            id = _accept_tags[i].id;
            if($(id)) {
                // opera - style.className fails but this works
                $(id).className = "accepted";
                $(id).style.className = "accepted";
            }
        }
    }

    $("span_wccount").innerHTML         = (wc_count() + bw_count());
    $("span_wccount").style.visibility  = "visible";
    prepreview.style.visibility         = "visible";

    show_preview();
}

function putTweet() {
    var qry = {};

    // save accepted words
    qry['querycode']    = "puttweet";
    qry['token']        = ++_wc_token;
    qry['projectid']    = formedit.projectid.value;
    qry['pagename']     = formedit.pagename.value;
    qry['tweet']        = tweet.value;
    writeAjax(qry);
}

function getTweet() {
    var qry = {};

    qry['querycode']    = "gettweet";
    qry['token']        = ++_wc_token;
    qry['projectid']    = formedit.projectid.value;
    qry['pagename']     = formedit.pagename.value;
    writeAjax(qry);
}

function eWCMonitor(msg) {
    // end of php object => encode JSON => decode JSON => js object
    try {
        _rsp = JSON.parse(msg);
    }
    catch(err) {
        alert(" (readAjax msg error:" + msg + ")");
        return;
    }
    switch (_rsp.querycode) {
    case 'wctext':
        show_wordcheck(_rsp);
        break;

    // response from submitting accepted words leaving wc mode
    case 'wcaccept':
        clear_wordchecking();
        break;

    case 'ping':
        display_ping();
        break;

    case 'gettweet':
        display_tweet(msg);
        break;

    case 'puttweet':
    case 'acceptwords':
    case 'addgoodword':
    case 'addbadword':
    case 'removegoodword':
    case 'removebadword':
    case 'badtogoodword':
    case 'goodtobadword':
    case 'suggestedtogoodword':
    case 'suggestedtobadword':
        break;

    default:
        window.alert("unknown querycode/response: " 
                    + _rsp.querycode + "/" + _rsp.response);
        break;
    }
}

function display_tweet(msg) {
   tweet.value = msg.tweet;
}

function SetFontSizeSelector(size) {
    var i, o;
    var sel = $('selfontsize');
    for (i = 0; i < sel.options.length; i++) {
        o = sel.options[i];
        if(o.value == size) {
            sel.selectedIndex = o.index;
            return;
        }
    }
    sel.selectedIndex = 0;
}

function SetFontFaceSelector(size) {
    var i, o;
    var sel = $('selfontface');
    for (i = 0; i < sel.options.length; i++) {
        o = sel.options[i];
        if(o.value == size) {
            sel.selectedIndex = o.index;
            return;
        }
    }
    sel.selectedIndex = 0;
}

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
    _ajaxActionFunc = eWCMonitor;
    _ajax.onreadystatechange = readAjax;
}

function writeAjax(a_args) {
    // php end will rawurldecode this to recover it
    initAjax();
    var jq = 'jsonqry=' + encodeURIComponent(JSON.stringify(a_args));
    //console.debug(jq);
    _ajax.open("POST", AJAX_URL, true);     // no return value
    _ajax.setRequestHeader("Content-type",
        "application/x-www-form-urlencoded");     // no return value
    _ajax.send(jq);     // no return value
}

// callback function for onreadystatechange
function readAjax() {
    var msg;
    var errstr;
    if(_ajax.readyState == 4) {
        msg = _ajax.responseText;
        if(_ajax.status != 200) {
            alert("ajax status: " + _ajax.statusText);
            return;
        }
        errstr = "";
        try {
            errstr = "err decodeURI";
            msg = decodeURIComponent(msg);
            if(errstr)
                {
                    errstr = "err parse";
                }
            // exec parse to exercise try
            JSON.parse(msg);
            // erase err msg if JSON.parse succeeded
            if(errstr != "") {
                errstr = "";
            }
        }
        catch(err) {
            alert(errstr + " (readAjax msg:" + msg + ")");
            return;
        }

        if(_ajaxActionFunc) {
            _ajaxActionFunc(msg);
        }
        console.debug(errstr);
    }
}

function setPreviewText(str) {
    spanpreview.innerHTML = str;
}

function eTextInput() {
    if(_is_wordchecking) {
        // spanpreview will come from wordcheck response
        consider_wordchecking();
    }
    else {
        setPreviewText(tatext.value);
    }
}

// rules
// from http://stackoverflow.com/questions/263743/how-to-get-cursor-position-in-textarea/3373056#3373056

function SelectionBounds() {
    var start = 0;
    var end = 0;
    var normalizedValue;
    var range;
    var textInputRange;
    var len;
    var endRange;

    if(typeof tatext.selectionStart == "number" 
                && typeof tatext.selectionEnd == "number") {
        start = tatext.selectionStart;
        end   = tatext.selectionEnd;
    }
    else {
        range = doc.selection.createRange();

        if(range && range.parentElement() == tatext) {
            len = tatext.value.length;
            normalizedValue = tatext.value.replace(/\r\n/g, "\n");

            // Create a working TextRange that lives in the input
            textInputRange = tatext.createTextRange();
            textInputRange.moveToBookmark(range.getBookmark());

            // Check if start and end are at the very end
            // of input -- moveStart/moveEnd don't return 
            // what we want in those cases
            endRange = tatext.createTextRange();
            endRange.collapse(false);

            if(textInputRange.compareEndPoints(
                            "StartToEnd", endRange) > -1) {
                start = end = len;
            }
            else {
                start = -textInputRange.moveStart("character", -len);
                start += normalizedValue.slice(0, start)
                                    .split(/\n/).length - 1;

                if(textInputRange.compareEndPoints(
                                "EndToEnd", endRange) > -1) {
                    end = len;
                }
                else {
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

function issync() {
    return getsync();
}

function setSyncButton() {
    $('icosync').src = issync()
        ? "/graphics/blusync.png"
        : "/graphics/brnsync.png";
}

function eToggleCtls() {
    SaveIsCtls(! IsCtls());
    applylayout();
}

// event for linksync click
function eToggleSync() {
    setsync(! issync());
    setSyncButton();
}

function setsync(val) {
    setlocalvalue("sync", val ? "1" : "0");
    setnamevalue("sync", val ? "1" : "0");
}

function getsync() {
    return (getnamevalue("sync") == "1");
}

function getIsWC() {
    var iswc = getnamevalue("iswc");
    if(! iswc) {
        setIsWC();
    }
    return (getnamevalue("iswc") == "1") ;
}

function getFind() {
    return getnamevalue("fandrFind");
}

function getRepl() {
    return getnamevalue("fandrRepl");
}

function getAndSetFandRFlags() {
    var flags = getnamevalue('fandrFlags');
    if (!flags)
        return;
    $('chkm').checked = flags.includes('m');
    $('chki').checked = flags.includes('i');
    $('chkr').checked = flags.includes('r');
}

function setFandR() {
    setnamevalue("fandrFind", $('txtfind').value);
    setnamevalue("fandrRepl", $('txtrepl').value);
    var m = $('chkm').checked;
    var i = $('chki').checked;
    var r = $('chkr').checked;
    var flags = (m ? 'm' : ' ') + (i ? 'i' : ' ') + (r ? 'r' : ' ');
    setnamevalue("fandrFlags", flags);
}

function setIsWC() {
    setlocalvalue("iswc", chkIsWC.checked ? "1" : "0");
    setnamevalue("iswc", chkIsWC.checked ? "1" : "0");
    if($("divctlwc")) {
        $("divctlwc").className = (getIsWC() ? "block" : "hide");
    }

}

function getIsPunc() {
    var ispunc = getnamevalue("ispunc");
    if(! ispunc) {
        setlocalvalue("ispunc", chkIsPunc.checked ? "1" : "0");
        setnamevalue("ispunc", chkIsPunc.checked ? "1" : "0");
        return true;
    }
    return ispunc == "1";
}

function SaveIsPunc() {
    setlocalvalue("ispunc", chkIsPunc.checked ? "1" : "0");
    setnamevalue("ispunc", chkIsPunc.checked ? "1" : "0");
}

function getRunAlways() {
    var runalways = getnamevalue("runalways");
    if(! runalways) {
        setlocalvalue("runalways", runAlways.checked ? "1" : "0");
        setnamevalue("runalways", runAlways.checked ? "1" : "0");
        return true;
    }
    return runalways == "1";
}

function SaveRunAlways() {
    setlocalvalue("runalways", runAlways.checked ? "1" : "0");
    setnamevalue("runalways", runAlways.checked ? "1" : "0");
}

function getEditorStyle() {
    var cstyle = getnamevalue("editorstyle");
    if(! cstyle) {
        setEditorStyle("menu");
        return "menu";
    }
    return cstyle;
}

function setEditorStyle(value) {
    setlocalvalue("editorstyle", value);
    setnamevalue("editorstyle", value);
}

function getLineHeight() {
    var lh = getnamevalue("lineheight");
    switch(lh) {
        case "lh10":
        case "lh15":
        case "lh20":
            return lh;
        default:
            saveLineHeight("lh10");
            return "lh10";
    }
}

function saveLineHeight(val) {
    setlocalvalue("lineheight", val.toString());
    setnamevalue("lineheight", val.toString());
}

function setlocalvalue(name, value) {
    localStorage.setItem(name, value);
}
function getlocalvalue(name) {
    return localStorage.getItem(name);
}

function setnamevalue(name, value) {
    var date = new Date();
    date.setDate(date.getDate() + 365 * 5);
    document.cookie = name + '='
            + value + ';'
            + ' expires=' + date.toUTCString()
            + '; path=/';
}

function getnamevalue(name) {
    var v, i, c = document.cookie.split(/;[\s]+/);
    for(i = 0; i < c.length; i++) {
        v = c[i].split(/=/);
        if(v[0] && v[0] == name) {
            return v[1];
        }
    }
    return "";
}

// vim: ts=4 sw=4 expandtab
