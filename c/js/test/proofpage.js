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

    traverse spans in new preview -
*/

var lc_words = ['and','of','the','in','on','de','van','am',
        'pm','bc','ad','a','an','at','by','for','la','le'];

var boxheight, boxwidth;

var _keystack = "";
var _accept_tags;

var doc = document;
var divleft;
var divright;
var divfratext;
var divimage;
var divsplitter;
var imgpage;
var divcontrols;
var ctlpanel;
var divFandR;
var tatext;
var divctlnav;
var divtext;
var divpreview;
var divctlimg;
var divctlwc;
var divstatusbar;
var regex;

var formedit;
var formcontext;

var _text;
var _account, _wccount, _bwcount;

var _is_wordchecking = false;
var _active_scroll_id = null;
var _sync_x, _sync_y;
var _img_top, _img_bottom;
var _is_tail_space;

var _ajax;
var _ajaxActionFunc;
var _rsp;
var _charpicker;
var _contexts;
var _date = new Date();
var _active_charselector;
var _active_char;
var _active_context_index = -1;
var _wc_wakeup = 0;
var _wc_token = 0;
// var _ajax_log = [];

// for splitter
var _is_resizing = false;
// var _startX, _startY;
// var _startTop, _startLeft;

function $(id) {
    return doc.getElementById(id);
}

function addEvent(obj, evType, fn) {
    if(obj && obj.addEventListener) {
        obj.addEventListener(evType, fn, false);
    }
    else if(obj && obj.attachEvent) {
        var r = obj.attachEvent("on" + evType, fn);
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

function eBodyLoad() {
    formedit        = $("formedit");
    divleft         = $("divleft");
    divright        = $("divright");
    divfratext      = $("divfratext");
    divimage        = $("divimage");
    divsplitter     = $("divsplitter");
    divcontrols     = $("divcontrols");
    ctlpanel        = $("ctlpanel");
    divFandR        = $("divFandR");
    tatext          = $("tatext");
    imgpage         = $("imgpage");
                   
    divtext         = $("divtext");
    divpreview      = $("divpreview");
    divctlimg       = $("divctlimg");
    divctlnav       = $("divctlnav");
    divctlwc        = $("divctlwc");
    divstatusbar    = $("divstatusbar");
                    
    addEvent(doc,      "keydown",   eKeyDown);
    addEvent(doc,      "keyup",     eKeyUp);
    addEvent(doc,      "keypress",  eKeyPress);
    addEvent(tatext,        "click",     eTextClick);
    addEvent(tatext,        "change",    eTextChange);
    addEvent(divfratext,    "scroll",    eScroll);
    addEvent(divimage,      "scroll",    eScroll);
    addEvent(divsplitter,   "mousedown", eSplitterDown);

    // scroll img controls to zoom
    if(imgpage.onmousewheel) {
        addEvent(imgpage,       "mousewheel", eImgCtlWheel);
    } else {
        addEvent(imgpage,       "DOMMouseScroll", eImgCtlWheel);
    }
    addEvent(doc,            "mousemove", eMouseMove);
    addEvent($('linksync'),     "click",     eToggleSync);
    addEvent($('linkzoomin'),   "click",     eZoomIn);
    addEvent($('linkzoomout'),  "click",     eZoomOut);
    addEvent($('linklayout'),   "click",     eSwitchLayout);
    addEvent($('linkfind'),     "click",     eOpenFandR);
    addEvent($('btnfind'),      "click",     eFind);
    addEvent($('btnrepl'),      "click",     eReplace);
    addEvent($('btnreplnext'),  "click",     eReplaceNext);
    addEvent($('btnclose'),     "click",     eCloseFandR);
    addEvent($('linkwc'),       "click",     eLinkWC);
    addEvent($('returnpage'),   "click",     eReturnPage);
    addEvent($('savequit'),     "click",     eSaveQuit);
    addEvent($('savenext'),     "click",     eSaveNext);
    addEvent($('quit'),         "click",     eQuit);
    addEvent($('linkupload'),   "click",     eShowUpload);
    addEvent($('selfontface'),  "change",    eSetFontFace);
    addEvent($('selfontsize'),  "change",    eSetFontSize);
    addEvent($('badbutton'),    "click",     eToggleBad);

    applylayout();
    // copy text to detect changes
    _text = tatext.value;
    setSyncButton();

    eCtlInit();
}

function Layout() {
    return GetLayout();
}

function eVerifyUnload(e) {
    var n;
    if(!e) { e = window.event; }
    var prompt = "";
    if(tatext && _text && (tatext.value != _text)) {
        prompt = "The text has changed.\n";
    }
    if((n = accept_count()) > 0) {
        prompt = n.toString() + " words have been accepted.\n";
    }
    return true;
}

function px(val) {
    return val ? val.toString() + "px" : "";
}

function applylayout() {
    var barpct = GetBarPct();
//    var zoom   = GetZoom();
//    var fontface = GetFontFace();
//    var fontsize = GetFontSize();
    var barpos;

    divleft.style.visibility        = "hidden";
    divcontrols.style.visibility    = "hidden";
    divright.style.visibility       = "hidden";
    divsplitter.style.visibility    = "hidden";

    SetFontSizeSelector(GetFontSize());
    SetFontFaceSelector(GetFontFace());
/*
    if(rsp != null) {
        SetFontSizeSelector(rsp.fontsize);
        SetFontFaceSelector(rsp.fontface);
        imgpage.style.width         = rsp.zoom + "%";
        divpreview.style.fontFamily =
        tatext.style.fontFamily     = rsp.fontface;
        divpreview.style.fontSize   =
        tatext.style.fontSize       = rsp.fontsize;
    }
    else {
*/
        // noinspection JSUnresolvedVariable
        divpreview.style.fontFamily =
        tatext.style.fontFamily     = GetFontFace();
        divpreview.style.fontSize   =
        tatext.style.fontSize       = GetFontSize();
//   }
    imgpage.style.width             = (GetZoom() * 10).toString() + 'px';

    boxheight                       = doc.body.offsetHeight - divstatusbar.offsetHeight;
    boxwidth                        = doc.body.offsetWidth;


    if(Layout() == 'horizontal') {
        barpos                      = boxheight * barpct / 100;

//        divleft.style.left          = "0";
//        divleft.style.top           = "0";
        divleft.style.height        = px(barpos);
        divleft.style.width         = "100%";

        divsplitter.style.top       = px(barpos);
        divsplitter.style.left      = "0";
        divsplitter.style.width     = "100%";
        divsplitter.style.height    = "4px";
        divsplitter.style.cursor    = "n-resize";

        divright.style.top          = px(barpos + divsplitter.offsetHeight);
        divright.style.left         = "0";
        divright.style.height       = px(divcontrols.offsetTop - divright.offsetTop);

        divFandR.style.top          = divright.offsetTop;
    }
    else {
        barpos                      = boxwidth * barpct / 100;
        // set width first so scrollbar doesn't disappear after setting height
        divleft.style.width         = px(barpos);
        divleft.style.height        = px(divcontrols.offsetTop);

        divsplitter.style.top       = "0";
        divsplitter.style.left      = px(barpos);
        divsplitter.style.width     = "4px";
        divsplitter.style.height    = px(divleft.offsetHeight);
        divsplitter.style.cursor    = "w-resize";

        divright.style.top          = "0";
        divright.style.left         = px(divleft.offsetWidth + divsplitter.offsetWidth);
        divright.style.height       = px(divleft.offsetHeight);

        divFandR.style.top          = ctlpanel.style.top   = "0";
    }
    tatext_match_divpreview();
    divleft.style.visibility        = "visible";
    divcontrols.style.visibility  = "visible";
    divright.style.visibility       = "visible";
    divsplitter.style.visibility    = "visible";

    setLayoutIcon();
}

function tatext_match_divpreview() {
    divtext.style.height   = px(divpreview.scrollHeight);
    tatext.style.height    = px(divpreview.scrollHeight);
    divtext.style.width    = px(divpreview.scrollWidth);
    // tatext.style.width     = px(divpreview.scrollWidth-1);
}

function eCtlInit() {
    if($('divcharpicker')) {
        $('selectors').innerHTML = char_selectors();
        $('pickers').innerHTML   = char_pickers(' ');
    }
    invert_digraphs();
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

function char_pickers(cgroup) {
    // var s = "<table class='tblpicker'>\n";
    // s += char_selector_row("A E I O UY CD LN RS TZ αβγ ἄ ἒ ἠ ΐ ό ύ ώ ῤ Ћћ Ѡѡ");

    // s += "</table><table class='tblpicker'>\n";
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

        //    case 'Y':
        //        s += char_row('Ýý&#255;');	// (ATB) NOW SUBSUMED ABOVE
        //        break;

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

function InsertChar(c) {
    var sel = SelectedText();
    ReplaceText(c);
    if(_is_wordchecking) {
        requestWordcheck();
    }
    return false;
}


function eCharClick(e) {
    var c;
    var t;
    if(!e) { e = window.event; }
    if(e.originalTarget) {
        t = e.originalTarget;
    }
    else {
        t = e.srcElement;
    }
    if(t.value) {
        c = t.value;
    }
    else {
        c = t.innerHTML;
    }
    if(t.className == "selector") {
        if(_active_charselector) {
            _active_charselector.style.border = "0";
        }
        _active_charselector = t;
        _active_charselector.style.border = "2px solid red";
        // _charpicker.innerHTML = char_pickers(c);
        // pickers.innerHTML = char_pickers(c);
        $('pickers').innerHTML = char_pickers(c);
        return true;
    }

    if(t.className == "picker") {
        if(_active_char) {
            _active_char.style.border = "0";
        }
        _active_char = t;
        t.style.border = "2px solid red";
        InsertChar(c);
    }
    return true;
}

function ePickerOut(e) {
    if(!e) { e = window.event; }
    $('divcharshow').style.display = "none";
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
    if(! tgt.innerHTML) {
        $('divcharshow').style.display = "none";
    }
    else {
        ctgt = tgt.innerHTML;
        ditgt = ( igraphs[ctgt] ? igraphs[ctgt] : '' );
        $('divcharshow').style.display = "block";
        $('divchar').innerHTML = tgt.innerHTML;
        $('divdigraph').innerHTML = ditgt;
    }
}

function ReplaceText(str) {
    if(_is_tail_space) {
        str += ' ';
    }

    // IE?
    if(doc.selection) {
        var sel = doc.selection;
        if(!sel || !sel.createRange) {
            return;
        }
        var ierange = sel.createRange();
        ierange.text = str;
        sel.empty();
    } else {
        var itop   = tatext.scrollTop;
        var istart = tatext.selectionStart;

        tatext.value = 
            tatext.value.substring(0, tatext.selectionStart) 
            + str + tatext.value.substring(tatext.selectionEnd);
        tatext.selectionEnd = 
            tatext.selectionStart = istart + str.length;
        tatext.scrollTop = itop;
    }
    consider_wordchecking();
    tatext.focus();
}



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
     "DG" : "°", "--" : "−"
};

var igraphs = {};

function digraph(c) {
    return digraphs[c] ? digraphs[c] : null;
}

function invert_digraphs() {
    for(var d in digraphs) {
        igraphs[digraphs[d]] = d;
    }
}

function eKeyUp(e) {
    consider_wordchecking();
}

function dbg(arg) {
    if(console && console.debug) {
        console.debug(arg);
    }
}

function eKeyDown(e) {
    var kCode;
    if(!e) { e = window.event; }

    set_text_id();

    kCode = (e.which && typeof e.which == "number") 
        ? e.which 
        : e.keyCode;

    // console.debug("keydown " + kCode);

    switch(kCode) {
        case  8:  // backspace
            if(_keystack.length == 0) {
                // backspace over nothing
                // dbg("backspace over nothing");
            }
            else if(_keystack.length == 1) {
                // dbg("keydown appending backspace to"
                             // + _keystack);
                _keystack += "\b";
            }
            else if(_keystack.length == 2 && _keystack[1] == "\b") {
                // dbg("keydown 2nd backspace - clear stack");
                _keystack = "";
            }
            else {
                // dbg("keydown error "
                //       + "keycode = " + String.fromCharCode(kCode)
                //       + "stack length" + _keystack.length);
            }
            break;
    }

    return true;
}

function eKeyPress(e) {
    if(!e) { e = window.event; }
    var kCode = (e.which && typeof e.which == "number") 
                ? e.which : e.keyCode;
//    var kChar = e.charCode;

    // dbg("keyPress" + kChar);
    switch (kCode) {
    case 8:  // backspace - needs to be trapped by keydown
    case 16: // IE passes the shift key, ignore it
        return true;

    // remember - \b is filtered out
    default:
        switch (_keystack.length) {
            case 0:
            case 1:
                // legit character, start the stack
                // dbg("zero/one push " +kCode);
                _keystack = String.fromCharCode(kCode);
                return true;

            case 2:
                // sb a character and a backspace
                // dbg("two push " +kCode);
                if(_keystack[1] == "\b") {
                    // complete the digraph and stop bubbling
                    _keystack = _keystack[0] 
                                + String.fromCharCode(kCode);
                    // dbg("detecting digraph" + _keystack);
                }
                else {
                    // dbg("bogus digraph" + _keystack);
                    // restart the stack
                    _keystack = String.fromCharCode(kCode);
                    return true;
                }
                break;

            default:    // error
                // dbg("error keystack=" + _keystack,
                  //       "length", _keystack.length);
                _keystack = String.fromCharCode(kCode);
                return true;
        }
    }

    // should only get here if collected char 2
    // time to fire digraph
    // var char1 = String.fromCharCode(_keystack[0]);
    // var char2 = String.fromCharCode(_keystack[1]);
    // var key = char1 + char2;

    var mappedChar = digraph(_keystack);

    if(! mappedChar) {
        return true;
    }

    e.preventDefault();
    e.returnValue = false;

    var val = tatext.value;
    if(typeof tatext.selectionStart == "number" 
        && typeof tatext.selectionEnd == "number") {
        // Non-IE browsers and IE 9+
        var itop     = tatext.scrollTop;
        var start    = tatext.selectionStart;
        var end      = tatext.selectionEnd;
        tatext.value = val.slice(0, start) + mappedChar + val.slice(end);

        // Move the cursor
        tatext.selectionStart = start + 1;
        tatext.selectionEnd = start + 1;
        tatext.scrollTop    = itop;
    }
    else if(doc.selection 
            && doc.selection.createRange) {
        // For IE up to version 8
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
        // var itop     = tatext.scrollTop;
        // var start    = tatext.selectionStart;
        // var end      = tatext.selectionEnd;
        // tatext.value = val.slice(0, start) + mappedChar + val.slice(end);

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

function eSetImageUrl(url) {
    doc.getElementById("scanimage")
        .src = url;
}

function eReplacedImageFile() {
    imgpage.src = imgpage.src;   
    eHideUpload();
}

function eHideUpload() {
    $("uploadframe").style.display = 'none';
}

function eShowUpload() {
    $("uploadframe").style.display = 'block';
}

// file has been selected
function eUpFile() {
    //noinspection JSUnresolvedVariable
    $("uploadframe").contentDocument.upform
        .upbutton.style.display='inline-block';
}

function eQuitUpload() {
    eHideUpload();
}

function eUp() {
    //noinspection JSUnresolvedVariable
    $("uploadframe").contentDocument.upform
        .upbutton.style.display='none';
    $("uploadframe").contentDocument
        .getElementById('uploading').style.display='inline';
}

function translate(str) {
    return str;
}

// needs to ajax for translation
function AreYouSure(question) {
    return(confirm(translate(question)));
}

function Ask(question) {
    return(prompt(translate(question)));
}

function ReplaceUrl(url) {
    window.location.replace(url);
}

function eDrop(e) {
    if(!e) { e = window.event; }
    var tgt = e.target ? e.target : e.srcElement;
    if(tgt.options[tgt.selectedIndex].value == 0) {
        return true;
    }
    var c = tgt.options[tgt.selectedIndex].text;
    InsertChar(c);
    tgt.selectedIndex = 0;
    return false;
}

function IsSelectedText() {
    return (tatext ? tatext.value.length > 0 : false);
}

/* return whatever text is selected in textarea*/
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
    } else if(tatext.selectionEnd) {
        seltext = tatext.value.substring(
                tatext.selectionStart, tatext.selectionEnd);
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

function eSetFootnote() {
    var sel = SelectedText();
    var re = /\s/; 
    var txt = '[Footnote ' + sel.replace(re, ': ') + ']';
    ReplaceText(txt);
    return false;
}

function eSetGreek() {
    var sel = SelectedText();
    var txt = '[Greek: ' + sel + ']';
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
    return false;
}

function eSetBlankPage() {
    tatext.value = "[Blank Page]";
    consider_wordchecking();
    return false;
}

function eSetNote() {
    var sel = SelectedText();
    var txt = sel + '[** ' + sel + ']';
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

function eSetParens() {
    var sel = SelectedText();
    var txt = '(' + sel + ')';
    ReplaceText(txt);
    return false;
}

function eSetSmallCaps() {
    var sel = SelectedText();
    ReplaceText('<sc>' + sel + '</sc>');
    return false;
}

function eSetTitleCase() {
    var i, c;
    var j, d;
//    var re;
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

        // assert word.length != 0
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
    
    // sel = sel.toLowerCase();
    // sel = sel.charAt(0).toUpperCase() + sel.substr(1);
    // if(sel.length <= 1) {
        // ReplaceText(sel);
        // return false;
    // }

    // sel = sel.replace(/\b\w/g, function(str) {
        // return str.toUpperCase();
    // });

    // for(i = 0; i < acommon.length; i++) {
        // aword = acommon[i];
        // expr = '\\\\b' + aword + '\\\\b';
        // re = new RegExp(expr, 'gi');
        // sel = sel.replace(re,
            // function(str) { return str.toLowerCase(); });
    // }
    // ReplaceText(sel);

    return false;
}

function eCurlyQuotes() {
    var sel = SelectedText();
    sel = sel.replace(/"?([^"]*)"?/, '“$1”');
    ReplaceText(sel);
    return false;
}

function eSetAntiqua() {
    var sel = SelectedText();
    ReplaceText('<f>' + sel + '</f>');
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

function eSetGesperrt() {
    var sel = SelectedText();
    ReplaceText('<g>' + sel + '</g>');
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
        ReplaceText(s.replace(/<\/?[^>]+(>|$)/g,''));
    }
    return false;
}

function NewBlankWindow() {
    return window.open();
}

function NewWindow(url, name, height, width) {
    return window.open(url, name,
                    "height=" + height + "," +
                    "width=" + width + "," +
                    "directories=no," +
                    "location=no," +
                    "menubar=no," +
                    "resizable=yes," +
                    "scrollbars=no," +
                    "status=no," +
                    "titlebar=no," +
                    "toolbar=no");
}

function eSetBold() {
    var sel = SelectedText();
    ReplaceText('<b>' + sel + '</b>');
    return false;
}

function eSetItalics() {
    var sel = SelectedText();
    ReplaceText('<i>' + sel + '</i>');
    return false;
}

function eSetNoWrap() {
    var sel = SelectedText();
    ReplaceText('/*\n' + sel + '\n*/');
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
    ReplaceText('\n/#\n' + sel + '\n#/\n');
    return false;
}

function eOpenFandR() {
    var _div = $('divFandR');
    if(_div.style.display == "none" || _div.style.display == "") {
        _div.style.display = "block";
        _div.style.top = px(divcontrols.offsetTop);
        if(SelectedText() != "") {
            $("txtfind").value = SelectedText();
            $("txtrepl").value = "";
        }
        // imgpage.style.top = _div.clientHeight+2);
        // if(Layout() == "vertical") {
            // divright.style.top = px(_div.clientHeight+2);
        // }
    }
}

function eCloseFandR() {
    var _div = $('divFandR');
    if(_div.style.display == "block") {
        _div.style.display = "none";
        // imgpage.style.top = "0";
        // if(Layout() == 'vertical') {
            // divright.style.top = 0;
        // }
        // applylayout();
    }
}

function set_regex() {
    var key = $('txtfind').value;
    if($('chkm')) {
        key = key.replace(/[^\\]\./, "[\s\S]");
    }
    var flags = 'g' + ($('chki').checked ? 'i' : '');
    regex = new RegExp(key, flags);
}

function eFind() {
    var rslt, t, pos;
    var istart, iend, fword;
    if($('txtfind').value.length == 0) {
        if(SelectedText().length > 0) {
            $('txtfind').value = SelectedText();
        }
        else {
            return;
        }
    }
    set_regex();
    t   = tatext.value;
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
    SetSelection(istart, iend);
    
    tatext.focus();
}

function eReplace() {
    if($('txtrepl').value.length == 0)
        return;
    var istart = SelectionBounds().start;
    var rstr = SelectedText().replace(regex, $('txtrepl').value);
    ReplaceText(rstr);
    SetCursor(istart + 1);
}

function eReplaceNext() {
    eReplace();
    eFind();   
}

function eTextLeft() {
    var lm = tatext.style.paddingLeft 
        ? parseInt(tatext.style.paddingLeft, 10)
        : 0;
    if(lm > 0) {
        divpreview.style.paddingLeft =
        tatext.style.paddingLeft     = (lm - 1).toString() + "%";
    }
}

function eTextRight() {
    var lm = tatext.style.paddingLeft 
        ? parseInt(tatext.style.paddingLeft, 10)
        : 0;
    if(lm < 20) {
        divpreview.style.paddingLeft =
        tatext.style.paddingLeft     = (lm + 1).toString() + "%";
    }
}

function FontFace() {
    return $('selfontface') 
        .item($('selfontface').selectedIndex) 
            .value;
}

function eSetFontFace() {
    divpreview.style.fontFamily =
    tatext.style.fontFamily     = FontFace();
    tatext_match_divpreview();
    SaveFontFace();
    return false;
}

function FontSize() {
    return $('selfontsize') 
        .item($('selfontsize').selectedIndex) 
            .value;
}

function ApplyFontSize() {
    divpreview.style.fontSize = tatext.style.font;
    tatext.style.fontSize     = FontSize();
    tatext_match_divpreview();
    return false;
}

function eSetFontSize() {
    divpreview.style.fontSize =
    tatext.style.fontSize     = FontSize();
    tatext_match_divpreview();
    SaveFontSize();
    return false;
}

function PreviewFormat() {
    var d = NewBlankWindow().document;
        d.write(""
            + tatext.value
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/&lt;(\/?)(i|b|hr)&gt;/ig, '<$1$2>')
                .replace(/&lt;sc&gt;/ig,
                    '<span style="font-variant: small-caps;">')
                .replace(/&lt;\/sc&gt;/ig, '</span>')
            + "");
    d.close();
    return false;
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

function eTextPlus() {
    var sz = parseInt($('fntSize').value, 10);
    if(sz >= font_sizes.length) {
        return true;
    }
    sz++;
    divpreview.style.fontSize =
    tatext.style.fontSize     = font_sizes[sz];
    tatext_match_divpreview();
    $('fntSize').value        = sz;
    return true;
}

function eTextMinus() {
    var sz = parseInt($('fntSize').value, 10);
    if(sz <= 0) {
        return true;
    }
    sz--;
    divpreview.style.fontSize =
    tatext.style.fontSize     = font_sizes[sz];
    tatext_match_divpreview();
    $('fntSize').value        = sz;
    return true;
}

// save accepted words and text. Response will provide new wc tags.
function eSaveNext() {
    accepts_to_form();
    formedit.todo.value = 'savenext';
    formedit.submit();
}

function eSaveQuit() {
    accepts_to_form();
    formedit.todo.value = 'savequit';
    formedit.submit();
}

function eQuit() {
    formedit.todo.value = 'quit';
    formedit.submit();
}

function eReturnPage() {
    if(AreYouSure("Return page and lose your changes?")) {
        formedit.todo.value = "returnpage";
        formedit.submit();
    }
}

function eToggleBad() {
    var answer;
    if($("badbutton").alt == "notbad") {
        answer = 
            Ask("Page will be unavailable until fixed by PM.\nReason?");
        if(answer == null || answer == "")
            return;
        formedit.badreason.value = answer;
        formedit.todo.value = "badpage";
        formedit.submit();
    }
    else {
        formedit.todo.value = "fixpage";
        formedit.submit();
    }
}

function point_in_obj(obj, ptX, ptY) {
    var x1 = 0;
    var x2 = 0;
    var y1 = 0;
    var y2 = 0;
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

function eTextClick(e) {
    var i, ispan, sp;
    if(!e) { e = window.event; }
    var spans = divpreview.getElementsByTagName("SPAN");
    var eX = e.clientX;
    var eY = e.clientY;
    for(i = 0; i < spans.length; i++) {
        sp = spans[i];
        if(point_in_obj(sp, eX, eY)) {
            ispan = sp;
            break;
        }
    }

    if(! ispan) {
        return true;
    }

    // var id = ispan.id;
    switch(ispan.className) {
        case "wc":
        case "wcb":
            ispan.className = "accepted";
            decr_wc_count();
            break;

        case "wcs":
            // ispan.className = "wc";
            // incr_wc_count();
            break;

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

function set_image_id() {
    set_scroll_id("divimage");
}

function set_text_id() {
    set_scroll_id("divfratext");
}

function set_null_id() {
    if(_active_scroll_id) {
        _active_scroll_id = null;
    }
}

function set_scroll_id(id) {
    if(_active_scroll_id != id) {
        _active_scroll_id = id;
    }
}

function eMouseMove(e) {
    if(!e) { e = window.event; }
    if(_is_resizing) {
        eSplitterMove(e);
        return;
    }
    // var _sync_x = e.clientX;
    // var _sync_y = e.clientY;
    if(is_in(divimage, e.clientX, e.clientY)) {
        set_image_id();
    }
    else if(is_in(divfratext, e.clientX, e.clientY)) {
        set_text_id();
    }
    else {
        set_null_id();
    }
    // if(xy_in_divfratext(_sync_x, _sync_y)) {
        // set_text_id();
    // }
    // else if(xy_in_divimage(_sync_x, _sync_y)) {
        // set_image_id();
    // }
    // else {
        // set_null_id();
    // }
}

function is_in(elem, x, y) {
    var bnds = elem.getBoundingClientRect();
    return x >= bnds.left && x <= bnds.left + bnds.width
        && y >= bnds.top  && y <= bnds.top  + bnds.height;
}

/*
function xy_in_divfratext(x, y) {
    var top = divfratext.offsetTop;
    var bottom = top + divfratext.offsetHeight;
    var left = divfratext.offsetLeft;
    var right = divfratext.offsetLeft + divfratext.offsetWidth;
    return x >= left && x <= right && y >= top && y <= bottom;
}

function xy_in_divimage(x, y) {
    var top = divimage.offsetTop;
    var bottom = top + divimage.offsetHeight;
    var left = divimage.offsetLeft;
    var right = divimage.offsetLeft + divimage.offsetWidth;
    return x >= left && x <= right && y >= top && y <= bottom;
}
*/

function eScroll(e) {
    if(! e) {e = window.event;}
    if(! issync()) { return true; }
    var tgt = e.currentTarget ? e.currentTarget : e.srcElement;
    if(tgt.id == "divfratext") {
        if(_active_scroll_id == "divfratext") {
            // source is divfratext, also scroller, apply to divimage
            apply_scroll_pct();
        }
    }
    else if(tgt.id == "divimage") {
        if(_active_scroll_id == "divimage") {
            apply_scroll_pct();
        }
    }
    return true;
}

function apply_scroll_pct() {
    if(! issync() || ! _active_scroll_id) {
        return;
    }
    var fromctl = $(_active_scroll_id);
    var toctl   = (_active_scroll_id == "divfratext"
                    ? divimage
                    : divfratext);
    var fromrange = fromctl.scrollHeight - fromctl.offsetHeight;
    if(fromrange <= 0) {
        return;
    }
    var pct = 100 * fromctl.scrollTop / fromrange;
    var torange = toctl.scrollHeight - toctl.clientHeight;
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
    // _startY = e.pageY;
    // _startX = e.pageX;
    // _startTop = parseInt(divsplitter.clientTop);
    // _startLeft = parseInt(divsplitter.clientLeft);

    // if(Layout() == "horizontal") {
        // divsplitter.style.top = px(divcontrols.clientTop + divcontrols.offsetTop);
        // divsplitter.style.left = "0";
        // divsplitter.style.width = "100%";
        // divsplitter.style.height = "4px";
    // }
    // else {
        // divsplitter.style.top = "0";
        // divsplitter.style.left = px(divcontrols.clientLeft + divcontrols.offsetLeft);
        // divsplitter.style.width = "4px";
        // divsplitter.style.height = "100%";
    // }
    // divsplitter.style.visibility = "visible";
    // divsplitter.style.backgroundColor = "red";

    addEvent(doc, "mouseup",   eSplitterUp);
}

function eSplitterUp(e) {
    var pct;
    _is_resizing = false;
    removeEvent(doc, "mouseup", eSplitterUp);
    // adjust panels to match splitter
    if(Layout() == "horizontal") {
        divleft.style.height        = px(e.clientY - 1);
        divFandR.style.top          =
        divright.style.top          = px(e.clientY + divsplitter.offsetHeight)
        pct = 100 * e.clientY / doc.body.offsetHeight;
    }
    else {
        divleft.style.width         = px(e.clientX-1);
        // divcontrols.style.left   = px(e.clientX);
        divright.style.left         = px(e.clientX) + divsplitter.offsetWidth;
        pct = 100 * e.clientX / doc.body.offsetWidth;
    }
    SaveBarPct(pct);
    applylayout();
    // divsplitter.style.visibility = "hidden";
    // divsplitter.style.backgroundColor = "green";
}

function eSplitterMove(e) {
    // e.preventDefault();
    // var newTop = _startTop + (e.pageY - _startY);
    // var newLeft = _startLeft + (e.pageX - _startX);
    if(Layout() == "horizontal") {
        divsplitter.style.top = px(e.clientY);
    }
    else {
        divsplitter.style.left = px(e.clientX);
    }
}

function eResize() {
    applylayout();
    // dbg("resize");
}

function set_wordchecking() {
    divctlwc.style.display          = "inline";
    $("imgwordcheck").src         = "gfx/wchk-on.png";
    _is_wordchecking = true;
    _wc_wakeup = 0;
}

function text_has_changed() {
    return (tatext.value !== _text);
}

function consider_wordchecking() {
    if(! _is_wordchecking) { return; }
    // dbg("considering...changed..." + text_has_changed());
    if(! text_has_changed()) { return; }
    divpreview.style.visibility     = "hidden";
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

function clear_wordchecking() {
    if(_is_wordchecking) {
        _is_wordchecking                = false;
        _wc_wakeup                      = 0;
        divpreview.style.visibility     = "hidden";
        divctlwc.style.display          = "none";
        $("imgwordcheck").src         = "gfx/wchk-off.png";
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

/*
function eImgClick(e) {
    if(!e) { e = window.event; }
    var t = e.target ? e.target : e.srcElement;
    if(t.nodeName.toLowerCase() != "img") {
        return;
    }
}
*/

// when hovering over the image +/- buttons,
// the mouse wheel zooms the image
// up (> 0) zoom in, down (< 0) zoom out

/*
function eWheel(e) {
    var t;
    if(!e) { e = window.event; }

    var tgt = e.currentTarget ? e.currentTarget : e.srcElement;

    return true;
}
*/

function eImgCtlWheel(e) {
    if(!e) { e = window.event; }
    if(e.altKey) {
        if(e.wheelDelta) {
            if(e.wheelDelta > 0) {
                eZoomIn();
            } 
            else {
                eZoomOut();
            }
        }
        else if(e.detail) {
            if(e.detail > 0) {
                eZoomOut();
            }
            else {
                eZoomIn();
            }
        }
        if(e.stopPropagation) {
            e.stopPropagation();
        }
        else {
            window.event.cancelBubble = true;
        }
        return false;
    }
}

// user clicks a list word to choose a context set
// the option element for the word has value w_ + the word
function eTblContextChange(e) {
    _active_context_index = -1;
    sync_context();
    return true;
}

function eAdHocFocus() {
    var ctl = $("adhoclist");
    if(ctl.className == "grayadhocbox") {
        ctl.style.color = "blackadhocbox";
        ctl.value = "";
    }
}

function eAdHocChange() {
}

function sync_context() {
    var t = $("tblcontext");
    if(! t) {
        return;
    }
    if(t.value.length <= 2) {
        return;
    }

    var w = t.value.substr(2);
    var qry = {};
    qry['querycode'] = 'wordcontext';
    qry['projectid'] = $("projectid").value;
    qry['word']      = w;
    writeAjax(qry);
}

// json response handler for wccontext
// builds the word list for context selection
function ajxDisplayContextWords(rsp) {
    var i;
    var str = "<tbody>\n";
    for (i = 0; i < rsp.length; i++) {
        var wrd = rsp[i][0];
        var n   = rsp[i][1];
        str = str + "<tr><td id='w_" + wrd + "' class='likealink'"
                + " style='background-color: white;'>"
                + wrd + "</td><td>" + n + "</td></tr>\n";
    }
    str += "</tbody>\n";
    formcontext.tblcontext.innerHTML = str;
}

/*
function DisplayRegexList(rgx, n) {
    var str = "<tbody>\n"
            +"<tr><td id='w_" + rgx + "' class='likealink'"
                + " style='background-color: white;'>"
                + rgx + "</td><td>(" + n + ")</td></tr>\n"
                + "</tbody\n";
    formcontext.tblcontext.innerHTML = str;
}
*/

// json response handler for wordcontext
// fills top box with contexts from json
// response has array of pagename, lineindex, linecount, context
//function ajxDisplayWordContextList(rsp) {
//    var i = 0;
//    var str = "";
//
//    if(! $("div_context_list")) {
//        return;
//    }
//    _contexts = rsp.contextinfo.contexts;
//    if(_contexts.length < 1) {
//        return;
//    }
//    for(i = 0; i < _contexts.length; i++) {
//        var ctxt = _contexts[i];
//        var id = i.toString();
//        str = str + "<div class='ctxt' id='divctxt_" + id + "'"
//                  + " onclick='eSetContextWordIndex(" + id + ")'>"
//                  + "<div class='ctxt-left'>"
//                  + "page " + ctxt.pagename
//                  + "<br>line " + ctxt.lineindex.toString()
//                  + " of " + ctxt.linecount.toString()
//                  + "</div><div class='ctxt-right'>"
//                  + ctxt.context + "</div></div></div>\n";
//    }
//    $("div_context_list").innerHTML = str;
//    $("div_context_list").scrollTop = 0;
//    eSetContextWordIndex(0);
//}

// json response handler for regexcontext
// fills top box with contexts from json
// response has array of pagename, lineindex, linecount, context
//function ajxDisplayRegexContextList(rsp) {
//    var i = 0;
//    var str = "";
//
//    if(! $("div_context_list")) {
//        return;
//    }
//    _contexts = rsp.contextinfo.contexts;
//    if(_contexts.length < 1) {
//        return;
//    }
//    // DisplayRegexList(_rsp.word, _contexts.length);
//    for(i = 0; i < _contexts.length; i++) {
//        var ctxt = _contexts[i];
//        var id = i.toString();
//        str = str + "<div class='ctxt' id='divctxt_" + id + "'"
//                  + " onclick='eSetContextWordIndex(" + id + ")'>"
//                  + "<div class='ctxt-left'>"
//                  + "page " + ctxt.pagename
//                  + "<br>line " + ctxt.lineindex.toString()
//                  + " of " + ctxt.linecount.toString()
//                  + "</div><div class='ctxt-right'>"
//                  + ctxt.context + "</div></div></div>\n";
//    }
//    $("div_context_list").innerHTML = str;
//    $("div_context_list").scrollTop = 0;
//    eSetContextWordIndex(0);
//}

function eLangcode(e) {
    if(!e) { e = window.event; }
    var tgt = e.target ? e.target : e.srcElement;
    formedit.langcode.value = tgt.value;
    requestWordcheck();
}

function eWordList(e) {
    requestWordList();
}

function SetAllCheck(val) {
    var i, row, tbl, c;
    tbl = $('tblcontext');
    for (i = 0; i < tbl.rows.length; i++) {
        row = tbl.rows[i];
        c = row.getElementsByTagName('input')[0];
        if(c) {
            c.checked = val;
        }
    }
}

function eCheckAll() {
    SetAllCheck(true);
}

function eUncheckAll() {
    SetAllCheck(false);
}

function requestWordcheck() {
    _accept_tags = accept_tags();
    awcWordcheck();
}

function requestWordList() {
    awcWordList();
}

function accept_tags() {
    var i, a = [];
    var tags = divpreview.getElementsByTagName("SPAN");
    for(i = 0; i < tags.length; i++) {
        if(tags[i].className == "accepted") {
            a[a.length] = tags[i];
        }
    }
    return a;
}

function accept_count() {
    return accept_tags().length;
}

function wc_class_count(code) {
    var tags = divpreview.getElementsByTagName("SPAN");
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

function awcWordList() {
    var qry = {};
    qry['querycode']    = "wcwordlist";
    qry['projectid']    = formedit.projectid.value;
    qry['pagename']     = formedit.pagename.value;
    qry['langcode']     = formedit.langcode.value;
    qry['text']         = tatext.value;
    writeAjax(qry);
}

function awcAddGoodWord(i) {
    var qry = {};
    qry['querycode']    = "addgoodword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substr(2);
    writeAjax(qry);
}

function awcAddBadWord(i) {
    var qry = {};
    qry['querycode']    = "addbadword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substr(2);
    writeAjax(qry);
}

function awcRemoveGoodWord(i) {
    var qry = {};
    qry['querycode']    = "removegoodword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substr(2);
    writeAjax(qry);
}

function awcRemoveBadWord(i) {
    var qry = {};
    qry['querycode']    = "removebadword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substr(2);
    writeAjax(qry);
}

function awcRemoveSuggestedWord(i) {
    var qry = {};
    qry['querycode']    = "removesuggestedword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substr(2);
    writeAjax(qry);
}

function awcGoodToBadWord(i) {
    var qry = {};
    qry['querycode']    = "goodtobadword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substr(2);
    writeAjax(qry);
}

function awcBadToGoodWord(i) {
    var qry = {};
    qry['querycode']    = "badtogoodword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substr(2);
    writeAjax(qry);
}

function awcSuggestedToBadWord(i) {
    var qry = {};
    qry['querycode']    = "suggestedtobadword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substr(2);
    writeAjax(qry);
}

function awcSuggestedToGoodWord(i) {
    var qry = {};
    qry['querycode']    = "suggestedtogoodword";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['word']         = formcontext.tblcontext.options[i]
                            .value.substr(2);
    writeAjax(qry);
}

function SaveBarPct(pct) {
    var hv = Layout()[0];
    setnamevalue(hv + "_barpct", pct.toString()); 
}

function GetBarPct() {
    var hv = Layout()[0];
    var val = getnamevalue(hv + "_barpct");
    var pct;
    if(val == "" || isNaN(val.toString())) {
        SaveBarPct(50);
        pct = 50;
    }
    pct = parseInt(val);
    if(pct == 0) {
        SaveBarPct(pct);
        pct = 50;
    }
    else if(pct < 20) {
        SaveBarPct(pct);
        pct = 20;
    }
    else if(pct > 80) {
        SaveBarPct(pct);
        pct = 80;
    }
    return pct;
}

function GetFontFace() {
    // if cookie value is set, use it else use list selection
    var hv = Layout()[0];
    var val = getnamevalue(hv + "_fontface");
    return val == "" ? $('selfontface').value : val;
}

function GetFontSize() {
    var hv = Layout()[0];
    var val = getnamevalue(hv + "_fontsize");
    return val == "" ? $('selfontsize').value : val;
}

function SaveFontFace() {
    var hv = Layout()[0];
    setnamevalue(hv + "_fontface", $('selfontface').value);
}

function SaveFontSize() {
    var hv = Layout()[0];
    setnamevalue(hv + "_fontsize", $('selfontsize').value);
}

function GetZoom() {
    var hv = Layout()[0];
    var strval = getnamevalue(hv + "_zoom").toString();
    if(strval == "" || isNaN(strval)) {
        setnamevalue(hv + "_zoom", "100");
        return 100;
    }
    var val = parseInt(strval, 10)
    if(val < 20) {
        val = 20;
    }
    else if(val > 1000) {
        val = 1000;
    }
    setnamevalue(hv + "_zoom", val.toString());
    return val;
}

function SaveZoom(val) {
    var hv = Layout()[0];
    setnamevalue(hv + "_zoom", val.toString());
}

function GetLayout() {
    var strval = getnamevalue("layout").toString();
    switch(strval) {
        case "vertical":
            return "vertical";
        default:
            return "horizontal";
    }
}

function SaveLayout(val) {
    switch(val.toString()) {
        case "vertical":
            setnamevalue("layout", "vertical");
            break;
        default:
            setnamevalue("layout", "horizontal");
            break;
    }
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

function eLinkWC(e) {
    if(! e) { e = window.event; }
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
function show_wordcheck(rsp) {
    var wclass;
    var i, id, spans, span, accepts;
    // wordchecking cancelled?
    if(! _is_wordchecking) {
        return;
    }
    // token match?
    if(_wc_token != rsp.token) {
        return;
    }

    // var accepts = divpreview.getElementsByTagName("accepted");
    divpreview.innerHTML = rsp.pvwtext;
    divtext.style.height = px(Math.max(tatext.scrollHeight, 
                                       divpreview.clientHeight));

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

    _wccount = wc_count();
    _bwcount = bw_count();

    $("span_wccount").innerHTML = (_wccount + _bwcount).toString();

    if(divpreview.innerHTML.substr(-1) != "\n") {
        divpreview.innerHTML += "\n";
    }

    divpreview.style.visibility = "visible";
}

function eWCMonitor(msg) {
    _rsp = JSON.parse(msg);
    switch (_rsp.querycode) {
    case 'wctext':
        show_wordcheck(_rsp);
        break;

    // response from submitting accepted words leaving wc mode
    case 'wcaccept':
        clear_wordchecking();
        break;

//    case 'wccontext':
//        ajxDisplayContextWords(_rsp.wordarray);
//        break;
//
//    case 'wordcontext':
//        ajxDisplayWordContextList(_rsp);
//        break;
//
//    case 'regexcontext':
//        ajxDisplayRegexContextList(_rsp);
//        break;

    // case 'setfontsize':
    // case 'setfontface':
    // case 'setzoom':
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

/*
    case 'switchlayout':
        formedit.layout.value = _rsp.layout;
        formedit.imgpct = _rsp.imgpct;
        $('switchlayout').src = 
            (_rsp.layout == "horizontal")
                ? "gfx/horiz.png"
                : "gfx/vert.png";
        applylayout();
        break;
*/
    default:
        window.alert("unknown querycode/response: " 
                    + _rsp.querycode + "/" + _rsp.response);
        break;
    }
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

/*
http://af-design.com/blog/2008/03/14/rfc-3986-compliant-uri-encoding-in-javascript/
String.prototype.to_rfc3986 = function (){
   var tmp =  encodeURIComponent(this);
   tmp = tmp.replace('!','%21');
   tmp = tmp.replace('*','%2A');
   tmp = tmp.replace('(','%28');
   tmp = tmp.replace(')','%29');
   tmp = tmp.replace("'",'%27');
   return tmp;
*/

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
    // var jq = 'jsonqry=' + encodeURIComponent(JSON.stringify(a_args));
    var jq = 'jsonqry=' + JSON.stringify(a_args);
    // _ajax_log.push(jq);
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
        // _ajax_log.push(msg);
        if(_ajax.status != 200) {
            alert("ajax status: " + _ajax.statusText);
            return;
        }
        errstr = "";
        try {
            // errstr = "err decodeURI";
            // msg = decodeURIComponent(msg);
            errstr = "err parse";
            jsonrsp = JSON.parse(msg);
            // erase err msg if JSON.parse succeeded
            errstr = "";
        }
        catch(err) {
            alert(errstr + " (readAjax msg:" + msg + ")");
            return;
        }

        if(_ajaxActionFunc) {
            _ajaxActionFunc(msg);
        }
    }
}

// only called when textarea loses focus
function eTextChange(e) {
    consider_wordchecking();
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
    } else {
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

function eSetSort(e) {
    if(!e) { e = window.event; }

    var tgt = e.target ? e.target : e.srcElement;
    var key = tgt.id;
    var vsort, vdesc;
    var sf;

    switch(key)
    {
        case "lktitle":
        case "lkauthor":
        case "lklang":
        case "lkprojid":
        case "lkgenre":
        case "lkpm":
        case "lkdiff":
        case "lkround":
            break;
        default:
            return;
    }
    vsort = $("sort");
    vdesc = $("desc");

    if( vsort.value === key ) {
        if(vdesc.value == '0') {
            vdesc.value = '1';
        }
        else {
            vdesc.value = '0';
        }
    }
    else {
        vsort.value = key ;
        vdesc.value = '0' ;
    }
    sf = $('searchform');
    sf.submit();
}

function issync() {
    return getsync();
}

function setSyncButton() {
    $('icosync').src = issync()
        ? "/graphics/blusync.png"
        : "/graphics/brnsync.png";
}

// event for linksync click
function eToggleSync(e) {
    if(! e) {e = window.event;}
    if(e.shiftKey) {
        // if(false) {  // text scrolled to top
            // setImageTop();
        // }
        // else if(false) { // text scrolled to bottom
            // setImageBottom();
        // }
        return;
    }
    else {
        setsync(! issync());
        setSyncButton();
    }
}

function setImageTop() {
    // _img_top = divimage.scrollTop;
}

function setImageBottom() {
    // _img_bottom = divimage.scrollTop;
}

function setsync(val) {
    setnamevalue("sync", val ? "1" : "0");
}

function getsync() {
    return (getnamevalue("sync") == "1");
}

function setnamevalue(name, value) {
    var date = new Date();
    date.setTime(date.getTime() + 365*24*60*60*1000);
    document.cookie = name + '=' 
            + value + ';' 
            + ' expires=' + date.toGMTString() 
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

function correctDivPreviewHeight() {
    var new_item = divpreview.cloneNode(true);
    document.body.appendChild(new_item); 
    var h = new_item.clientHeight;
    document.body.removeChild(new_item); 
    return h;
}

