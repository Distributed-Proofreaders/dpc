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

var lc_words = ['and','of','the','in','on','de','van','am',
        'pm','bc','ad','a','an','at','by','for','la','le'];

var _keystack = "";
var _accept_tags;

var divimage;
var imgpage;
var divcontrolbar;
var divfratext;
var divtext;
var tatext;
var divctlnav;
var divpreview;
var divctlimg;
var divctlwc;
var divcontrols;
var divstatusbar;
var regex;

var formedit;
var formcontext;

var _text;

var _is_wordchecking = false;
var _active_scroll_id = null;
var _sync_x, _sync_y;
var _is_tail_space;

var _pending_request = "";

var _ajax;
var _ajaxActionFunc;
var _rsp;
var _charpicker;
var _contexts;
var _date = new Date();
var _active_ctl;
var _active_charselector;
var _active_char;
var _active_context_index = -1;
var _wc_wakeup = 0;
var _wc_token = 0;

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




function eBodyLoad() {
    formedit        = $("formedit");
    divimage        = $("divimage");
    divfratext      = $("divfratext");
    divcontrolbar   = $("divcontrolbar");
    tatext          = $("tatext");
    imgpage         = $("imgpage");
                   
    divtext         = $("divtext");
    divpreview      = $("divpreview");
    divctlimg       = $("divctlimg");
    divctlnav       = $("divctlnav");
    divctlwc        = $("divctlwc");
    divcontrols     = $("divcontrols");
    divstatusbar    = $("divstatusbar");
                    
    addEvent(tatext,              "focus",    eTextFocus);
    addEvent(tatext,              "blur",     eTextBlur);
    addEvent(divimage,            "focus",    eImgFocus);
    addEvent(divimage,            "blur",     eImgBlur);
    addEvent(divimage,            "click",    eImgClick);
    addEvent(tatext,              "click",    eTextClick);
    addEvent(tatext,              "change",   eTextChange);
    addEvent(divfratext,          "scroll",   eScroll);
    addEvent(divimage,            "scroll",   eScroll);

    // scroll img controls to zoom
    if(divctlimg.onmousewheel) {
        addEvent(divctlimg,       "mousewheel", eImgCtlWheel);
    } else {
        addEvent(divctlimg,       "DOMMouseScroll", eImgCtlWheel);
    }

    addEvent($('linksync'),     "click",     eToggleSync);
    addEvent($('linkzoomin'),   "click",     eZoomIn);
    addEvent($('linkzoomout'),  "click",     eZoomOut);
    addEvent($('linklayout'),   "click",     eLinkLayout);
    addEvent($('linkfind'),     "click",     eLinkFind);
    addEvent($('btnfind'),      "click",     eFind);
    addEvent($('btnrepl'),      "click",     eReplace);
    addEvent($('btnreplnext'),  "click",     eReplaceNext);
    addEvent($('btnclose'),     "click",     eCloseFandR);
    addEvent($('linkwc'),       "click",     eWordCheckClick);
    addEvent($('returnpage'),   "click",     eReturnPage);
    addEvent($('savetemp'),     "click",     eSaveTemp);
    addEvent($('savequit'),     "click",     eSaveQuit);
    addEvent($('savenext'),     "click",     eSaveNext);
    addEvent($('quit'),         "click",     eQuit);
    addEvent($('linkupload'),   "click",     eShowUpload);
    addEvent($('selfontface'),  "change",    eSetFontFace);
    addEvent($('selfontsize'),  "change",    eSetFontSize);
    addEvent($('badbutton'),    "click",     eToggleBad);

    // copy text to detect changes
    _text = tatext.value;
    setSyncButton();

    eCtlInit();

}

function eVerifyUnload(e) {
    var evt = e ? e : window.event  ;
    if(tatext && _text && (tatext.value != _text)) {
        prompt = "The text has changed.\n";
    }
    if((n = accept_count()) > 0) {
        prompt = n.toString() + " words have been accepted.\n";
    }
    return false;
}

function px(val) {
    return val ? val.toString() + "px" : "";
}

function applylayout(rsp) {
    var f         = $('formedit');
    var layout    = f.layout.value;     // vert or horiz
    var barpct    = 50;
    var barpos;

    // "innerHeight" in window

    if(rsp != null) {
        SetFontSizeSelector(rsp.fontsize);
        SetFontFaceSelector(rsp.fontface);
        // set zoom of image within divimage
        imgpage.style.width         = rsp.zoom + "%";
        divpreview.style.fontFamily =
        tatext.style.fontFamily     = rsp.fontface;
        divpreview.style.fontSize   =
        tatext.style.fontSize       = rsp.fontsize;
    }
    else {
        //noinspection JSUnresolvedVariable
        imgpage.style.width         = formedit.zoom.value + "%";
        divpreview.style.fontFamily =
        tatext.style.fontFamily     = $('selfontface').value;
        divpreview.style.fontSize   =
        tatext.style.fontSize       = $('selfontsize').value;
    }

    // box is the area to split up between image and (text + controlbar)
    var boxheight =
            (window.innerHeight ? window.innerHeight : document.documentElement.offsetHeight)
                - divstatusbar.offsetHeight - divcontrolbar.offsetHeight;
    var boxwidth =
        (window.innerWidth ? window.innerWidth : document.documentElement.offsetWidth)
            - divstatusbar.offsetWidth - divcontrolbar.offsetWidth;

    if(layout == 'horizontal') {
        // bar is horizontal so width is entire box, positioned at barpct
        barpos                      = boxheight * barpct / 100;

        divleft.style.width         = "100%";
        divleft.style.height =      px(barpos);

        divright.style.width         = "100%";
        divright.style.height =     px(boxheight - barpos);

        divsplitter.style.width    = "100%";
        divsplitter.style.height    = "3px";
        divsplitter.style.left    = "0";
        divsplitter.style.top    = px(barpos);

        // control bar is below bar, above text, all the way across
        divctlbar.style.top     = px(barpos);
        divctlbar.style.left    = "0";

        // text is below controlbar
        divfratext.style.top        = px(divcontrolbar.offsetTop
                                       + divcontrolbar.offsetHeight);
        divfratext.style.left       = "0";
        divfratext.style.height           = px(boxheight - divfratext.offsetTop);
        divfratext.style.width      = px(boxwidth);
    }                               
    else {                          
        barpos                      = boxwidth * barpct / 100;
        divleft.style.height       = px(boxheight);
        divleft.style.width        = px(barpos);

        divright.style.height       = px(boxheight); 
        divright.style.width        = px(boxwidth - barpos);

        divsplitter.style.width    = "3px";
        divsplitter.style.height    = "100%";
        divsplitter.style.left    = px(barpos);
        divsplitter.style.top    =  "0";
                                    
        divcontrolbar.style.top     = "0px";
        divcontrolbar.style.left    = px(barpos);
        divcontrolbar.style.width   = px(boxwidth - barpos);
                                    
        divfratext.style.top        = px(divcontrolbar.offsetHeight);
        divfratext.style.left       = px(barpos);
        divfratext.style.width      = px(boxwidth - divimage.offsetWidth);
        divfratext.style.height     = px(boxheight - divcontrolbar.offsetHeight);
    }

    // The textarea and preview sizing all takes place in this:
    tatext_match_divpreview();
}


function tatext_match_divpreview() {
    var txt = divpreview.innerHTML;
    divpreview.innerHTML = "";
    divpreview.style.height = 0;
    divpreview.innerHTML = txt;
    divtext.style.height   =
    tatext.style.height    = px(divpreview.scrollHeight);
    divtext.style.width    = px(divpreview.scrollWidth);
    tatext.style.width     = px(divpreview.scrollWidth-1);
}

function eCtlInit() {
    if($('charpicker')) {
        $('selectors').innerHTML = char_selectors();
        $('pickers').innerHTML   = char_pickers(' ');
    }
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

/*
function c_row(selkey) {
    var sel = c_select[selkey];
    var charstr = sel.chars;
    var i;
    var s = '<tr>\n';
    var imax = charstr.length;
    for (i = 0; i < imax; i++) {
        s += ('<td class="picker"');
        if(sel.prompt) {
            s += (' title="' + sel.prompt + '"');
        }
        s += ('>' + sel.chars + '</td>\n');
    }
    s += '</tr>\n';
    return s;
}
*/

function char_selectors() {
    var s = "<table class='tblpicker'>\n";
    s += char_selector_row("A E I O UY CD LN R-Z αβγ ἄἒ ἠό ίύ ώῤ ћ Ѫ א + ❦");
    s += "\n</table>\n";
    return s;
}

function char_pickers(cgroup) {
    var s = "<table class='tblpicker'>\n";

    switch (cgroup) {
    case 'A':
        s += char_row('àáâäåãæāăą');
        s += char_row('ÀÁÂÄÅÃÆĀĂĄ');

        break;
    case 'E':
        s += char_row('èéêëėęěĕē');
        s += char_row('ÈÉÊËĖĘĚĔĒ');
        break;

    case 'I':
        s += char_row('ìíîïĩīĭǐįĝĥĵ');
        s += char_row('ÌÍÎÏĨĪĬǏĮĜĤĴ');
        break;
    case 'O':
        s += char_row('òóôöõøōŏǒœ');
        s += char_row('ÒÓÔÖÕØŌŎǑŒ');
        break;
    case 'UY':
        s += char_row('ùúûüũūŭ ýÿ');
        s += char_row('ÙÚÛÜŨŪŬ ÝŸ');
        break;

    case '+':
        s += char_row('$¢£‰¤¥¡¿©® «»„“” ÐðÞþßǶƕÑñĝĥĵ†‡™•⁂');
        s += char_row('′″‴¦§¨ªº¯° ‹›‚‘’ ±¹²³´¶·¸¼½¾×÷ȣƺ‿−');
        break;
    case 'CD':
        s += char_row('çćĉċčɔƈðďđɖɗĝĥĵ');
        s += char_row('ÇĆĈĊČƆƇÐĎĐƉƊĜĤĴ');
        break;
    case 'LN':
        s += char_row('ĺļľŀł_ñńņňŉŋ');
        s += char_row('ĹĻĽĿŁ_ÑŃŅŇ Ŋ');
        break;

    case 'R-Z':
        s += char_row('ŕŗřßſśŝşšţťŧźżž');
        s += char_row('ŔŖŘ  ŚŜŞŠŢŤŦŹŻŽ');
        break;

    case 'αβγ':
        s += char_row('αβγδεζηθϝικλμνξοπρσςτυφχψω ·;');
        s += char_row('ΑΒΓΔΕΖΗΘϜΙΚΛΜΝΞΟΠΡΣ ΤΥΦΧΨΩ ’͵');
        break;

    case 'ἄἒ':
        s += char_row('ἀἁἂἃἄἅἆἇ ὰά ᾀᾁᾂᾃᾄᾅᾆᾇ ᾰᾱᾲᾳᾴᾶᾷ ἐἑἒἓἔἕ ὲέ');
        s += char_row('ἈἉἊἋἌἍἎἏ ᾺΆ ᾈᾉᾊᾋᾌᾍᾎᾏ ᾸᾹ ᾼ    ἘἙἚἛἜἝ ῈΈ');
        break;

    case 'ἠό':
        s += char_row('ἠἡἢἣἤἥἦἧ ὴή ᾐᾑᾒᾓᾔᾕᾖᾗ ῂῃῄ ῆῇ όὸὀὁὂὃὄὅό');
        s += char_row('ἨἩἪἫἬἭἮἯ ῊΉ ᾘᾙᾚᾛᾜᾝᾞᾟ  ῌ     ΌῸὈὉὊὋὌὍΌ');
	break;

    case 'ίύ':
        s += char_row('ἰἱἲἳἴἵἶἷ ὶί ῐῑϊῒΐῖῗ ϋ ὐὑὒὓὔὕὖὗ ὺύῠῡ');
        s += char_row('ἸἹἺἻἼἽἾἿ ῚΊ ῘῙΪ     Ϋ  Ὑ Ὓ Ὕ   ῪΎῨῩ');
	break;

    case 'ώῤ':
        s += char_row('ὠὡὢὣὤὥὦὧ ὼώ ᾠᾡᾢᾣᾤᾥᾦᾧ ῲῳῴῶῷϟϡῤῥ');
        s += char_row('ὨὩὪὫὬὭὮὯ ῺΏ ᾨᾩᾪᾫᾬᾭᾮᾯ  ῼ   ϞϠ Ῥ');
        break;

    case 'ћ':
        s += char_row('ђѓѐёєѕѝіїйјљњћќўџщъыьэюя');
        s += char_row('ЂЃЀЁЄЅЍІЇЙЈЉЊЋЌЎЏЩЪЫЬЭЮЯ');
        break;
    case 'Ѫ':
        s += char_row('ѢѠѡѢѣѤѥѦѧѨѩѪѫѬѭѮѯѰѱ');
        s += char_row('ѲѳѴѵѶѷѸѹѺѻѼѽѾѿҀҁ҂Ğğ');
        break;

    case 'א':
        s += char_row('אבגדהוזחטיךכלם');
        s += char_row('מןנסעףפץצקרשת׳״');
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
    //var sel = SelectedText();
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
    var evt = e ? e : window.event;
    //if(!e) { var e = window.event; }
    $('charshow').style.display = "none";
}

function ePickerOver(e) {
    var tgt;
    if(!e) { e = window.event; }

    tgt = e.target ? e.target : e.srcElement;
    if(tgt.nodeName.toLowerCase() !== "td") {
        return;
    }
    if(tgt.className != 'picker') {
        return;
    }
    if(! tgt.innerHTML) {
        $('charshow').style.display = "none";
    }
    else {
        $('charshow').style.display = "block";
        $('charshow').innerHTML = tgt.innerHTML;
    }
}

/**'
 *
 * @param str
 * @return void
 */
function ReplaceText(str) {
    if(_is_tail_space) {
        str += ' ';
    }

    // IE?
    if(this.selection) {
        var sel = this.selection;
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

    /*
     "A+" : "א",
     "B+" : "ב",
     "G+" : "ג",
     "D+" : "ד",
     "H+" : "ה",
     "W+" : "ו",
     "Z+" : "ז",
     "X+" : "ח",
     "Tj" : "ט",
     "J+" : "י",
     "K%" : "ך",
     "K+" : "כ",
     "L+" : "ל",
     "M%" : "ם",
     "M+" : "מ",
     "N%" : "ן",
     "N+" : "נ",
     "S+" : "ס",
     "E+" : "ע",
     "P%" : "ף",
     "P+" : "פ",
     "Zj" : "ץ",
     "ZJ" : "צ",
     "Q+" : "ק",
     "R+" : "ר",
     "Sh" : "ש",
     "T+" : "ת",
     */

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

function digraph(c) {
    return digraphs[c] ? digraphs[c] : null;
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

    kCode = (e.which && typeof e.which == "number") 
        ? e.which 
        : e.keyCode;

    switch(kCode) {
        case  8:  // backspace
            if(_keystack.length == 0) {
                // backspace over nothing
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

function eKeyPress(e) {
    if(!e) { e = window.event; }
    var kCode = (e.which && typeof e.which == "number") 
                ? e.which : e.keyCode;

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

    e.preventDefault();
    e.returnValue = false;

    var val = tatext.value;
    if(typeof tatext.selectionStart == "number" 
        && typeof tatext.selectionEnd == "number") {
        // Non-IE browsers and IE 9
        var itop     = tatext.scrollTop;
        var start    = tatext.selectionStart;
        var end      = tatext.selectionEnd;
        tatext.value = val.slice(0, start) + mappedChar + val.slice(end);

        // Move the cursor
        tatext.selectionStart = start + 1;
        tatext.selectionEnd = start + 1;
        tatext.scrollTop    = itop;
    }
    else if(this.selection 
            && this.selection.createRange) {
        // For IE up to version 8
        var selectionRange  = this.selection.createRange();
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

/**
 *
 * @param start int
 * @param end int
 * @return void
 */
function SetSelection(start, end) {
    var textInputRange;
    if(typeof tatext.selectionStart == "number" 
        && typeof tatext.selectionEnd == "number") {
        // Non-IE browsers and IE 9

        // Move the cursor
        tatext.selectionStart   = start;
        tatext.selectionEnd     = end;
    }
    else if(this.selection && this.selection.createRange) {
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
    document.getElementById("scanimage")
        .src = url;
}

function eReplacedImageFile() {
    //noinspection SillyAssignmentJS
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
    top.eHideUpload();
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

/**
 * @return {boolean}
 */
function IsSelectedText() {
    return (tatext ? tatext.value.length > 0 : false);
}

/* return whatever text is selected in textarea*/
/**
 * @return {string}
 * @return {string}
 */
function SelectedText() {
    var sel;
    var ierange;
    var seltext;

    if(!tatext || tatext.value.length == 0) {
        return '';
    }

    if(this.selection) {
        sel = this.selection;
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
    sel = sel.replace(/"?([^"]*)"?/, '“$1„');
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
    ReplaceText('/#\n' + sel + '\n#/');
    return false;
}

function eLinkLayout() {
    ajaxSwitchLayout();
    return false;
}

function eLinkFind() {
    if($('divFandR').style.display != "block") {
        $('divFandR').style.display = "block";
        if(SelectedText() != "") {
            $("txtfind").value = SelectedText();
            $("txtrepl").value = "";
        }
        imgpage.style.top = px($('divFandR').clientHeight+2);
        if(formedit.layout.value == "vertical") {
            divfratext.style.top = px($('divFandR').clientHeight+2);
        }
    }
}

function eCloseFandR() {
    if($('divFandR').style.display == "block") {
        $('divFandR').style.display = "none";
        imgpage.style.top = "0";
        if(formedit.layout.value == 'vertical') {
            divfratext.style.top = 0;
        }
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
    ajaxSetFontFace();
    return false;
}

function FontSize() {
    return $('selfontsize') 
        .item($('selfontsize').selectedIndex) 
            .value;
}

function eSetFontSize() {
    divpreview.style.fontSize =
    tatext.style.fontSize     = FontSize();
    tatext_match_divpreview();
    ajaxSetFontSize($('selfontsize').selectedIndex);
    return false;
}

/**
 * @return {boolean}
 */
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
    var w = formedit.zoom.value;
    w = w ? w * 1.03 : 100;
    if(w > 400) {
        w = 400;
    }
    imgpage.style.width = w + "%";
    formedit.zoom.value = w.toString();
    ajaxSetZoom(w);
    return false;
}

function eZoomOut() {
    var w = formedit.zoom.value;
    w = w ? w * 0.97 : 90;
    if(w < 25) {
        w = 25;
    }
    imgpage.style.width = w + "%";
    formedit.zoom.value = w.toString();
    ajaxSetZoom(w);
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
function eSaveTemp() {
    accepts_to_form();
    var qry = {};
    qry['querycode']    = "savetemp";
    qry['token']        = ++_wc_token;
    qry['projectid']    = formedit.projectid.value;
    qry['pagename']     = formedit.pagename.value;
    qry['langcode']     = formedit.langcode.value;
    // submit accepted words - saved in formedit
    qry['acceptwords']  = formedit.acceptwords.value;
    qry['text']         = tatext.value;

    writeAjax(qry);
    return false;
}

function checkBeforeSaving(type) {
    var qry = {};
    qry['querycode']    = type;

    writeAjax(qry);
    return false;
}

function eSaveNext() {
    checkBeforeSaving('savenext');
}

function doSaveNext() {
    accepts_to_form();
    formedit.todo.value = 'savenext';
    formedit.submit();
}

function eSaveQuit() {
    checkBeforeSaving('savequit');
}

function doSaveQuit() {
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


function eTextFocus() {
    _scroll_focus = divfratext;
}

function eTextBlur() {
    _scroll_focus = null;
}

function eImgFocus() {
    _scroll_focus = divimage;
}

function eImgBlur() {
    _scroll_focus = null;
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

function eMouseMove(e) {
    if(!e) { e = window.event; }
    // var _sync_x = e.clientX;
    // var _sync_y = e.clientY;
    // dbg("mouse x,y:" + e.clientX + e.clientY);
    if(_event_in_div(e, divfratext)) {
    // if(xy_in_divfratext(_sync_x, _sync_y)) {
        if(_active_scroll_id != "divfratext") {
            _active_scroll_id = "divfratext";
            // dbg("mouse position to divfratext");
        }
    }
    // else if(xy_in_divimage(_sync_x, _sync_y)) {
    else if(_event_in_div(e, divimage)) {
        if(_active_scroll_id != "divimage") {
            _active_scroll_id = "divimage";
            // dbg("mouse position to divimage");
        }
    }
    else {
        if(_active_scroll_id != null) {
            _active_scroll_id = null;
            // dbg("mouse position to null");
        }
    }
}

function _event_in_div(e, div) {
    var x = e.clientX;
    var y = e.clientY;
    var top = div.offsetTop;
    var left = div.offsetLeft;
    var p = div;
    while(p.parentNode) {
        p = p.parentNode;
        if(p.offsetTop)
            top += p.offsetTop;
        if(p.offsetLeft)
            left += p.offsetLeft;
    }
    var bottom = top + div.offsetHeight;
    var right = left + div.offsetWidth;
    return x >= left && x <= right && y >= top && y <= bottom;
}


// copy scroll psn from one element to another
function apply_scroll_pct(fromctl, toctl) {
    if(! issync()) {
        return;
    }
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

function eScroll(e) {
    if(! issync()) {
        return; 
    }
    if(! e) {e = window.event;}
    var tgt = e.currentTarget ? e.currentTarget : e.srcElement;
    if(tgt.id == "divfratext") {
        if(_active_scroll_id == "divfratext") {
            // source is divfratext, also scroller, apply to divimage
            apply_scroll_pct(divfratext, divimage);
        }
    }
    else if(tgt.id == "divimage") {
        if(_active_scroll_id == "divimage") {
            apply_scroll_pct(divimage, divfratext);
        }
    }
    return true;
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
    if(! _is_wordchecking) {
        return; 
    }
    if(! text_has_changed()) { return; }
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

function eImgClick(e) {
    if(!e) { e = window.event; }
    var t = e.target ? e.target : e.srcElement;
    //if(t.nodeName.toLowerCase() != "img") {
    //    return;
    //}
}

// when hovering over the image +/- buttons,
// the mouse wheel zooms the image
// up (> 0) zoom in, down (< 0) zoom out

function eWheel(e) {
    var t;
    if(!e) { e = window.event; }

    var tgt = e.currentTarget ? e.currentTarget : e.srcElement;

    return true;
}

function eImgCtlWheel(e) {
    if(!e) { e = window.event; }
    if(e.wheelDelta) {
        if(e.wheelDelta > 0)
        {
           top.ZoomIn();
        } else {
            top.ZoomOut();
        }
    }
    else if(e.detail) {
        if(e.detail > 0) {
           top.ZoomOut();
        } else {
            top.ZoomIn();
        }
    }
    return false;
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
    qry = {};
    qry['querycode'] = 'wordcontext';
    qry['projectid'] = $("projectid").value;
    qry['word']      = w;
    writeAjax(qry);
}

// json response handler for wccontext
// builds the left-column word list for context selection
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
    formcontext.tblcontext.innerHTML = str;
}

// json response handler for wordcontext
// fills top right box with contexts from json
// response has array of pagename, lineindex, linecount, context
function ajxDisplayWordContextList(rsp) {
    var i;
    var str = "";
 
    if(! $("div_context_list")) {
        return;
    }
    _contexts = rsp.contextinfo.contexts;
    if(_contexts.length < 1) {
        //TODO? ((get the number of lines used to show the message from the same place the context producer takes it))
        _contexts[0] = [];
        _contexts[0]['context'] = 
            "<div class='ctxt'><div class='ctxt-null' id='divctxt_0'>"
            + "<br/>There is no occurrence of<div class='ctxt-right'>"
            + rsp.word + "</div> in the active text.<br/><br/></div></div>";
        _contexts[0]['imageurl'] = "";
        _contexts[0]['pagename'] = "___";
        _contexts[0]['lineindex'] = "__";
        _contexts[0]['linecount'] = "__";
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
                  + "<div class='ctxt-right'>"
                  + ctxt.context + "</div>  <!-- ctxt-right -->"
                  + "</div>\n";
    }
    $("div_context_list").innerHTML = str;
    $("div_context_list").scrollTop = 0;
    eSetContextWordIndex(0);
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
    _contexts = rsp.contextinfo.contexts;
    if(_contexts.length < 1) {
        return;
    }
    // DisplayRegexList(_rsp.word, _contexts.length);
    for(i = 0; i < _contexts.length; i++) {
        var ctxt = _contexts[i];
        var id = i.toString();
        str = str + "<div class='ctxt' id='divctxt_" + id + "'"
                  + " onclick='eSetContextWordIndex(" + id + ")'>"
                  + "<div class='ctxt-left'>"
                  + "page " + ctxt.pagename
                  + "<br>line " + ctxt.lineindex.toString()
                  + " of " + ctxt.linecount.toString()
                  + "</div><div class='ctxt-right'>"
                  + ctxt.context + "</div></div></div>\n";
    }
    $("div_context_list").innerHTML = str;
    $("div_context_list").scrollTop = 0;
    eSetContextWordIndex(0);
}

function eLangcode(e) {
    if(!e) { e = window.event; }
    var tgt = e.target ? e.target : e.srcElement;
    formedit.langcode.value = tgt.value;
    requestWordcheck();
}

function eWordList() {
    requestWordList();
}

function SetAllCheck(val) {
    var i, c;
    var row;
    var tbl = $('tblcontext');
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

// body load event for wordcontext page
// we have projectid, language, and which list type.
// need to pull list and select first word and context.
function eContextInit() {
    formcontext = $("formcontext");

    addEvent(formcontext.btnremove,  "click", eRemoveWordClick);
    addEvent(formcontext.btngood,    "click", eGoodWordClick);
    addEvent(formcontext.btnbad,     "click", eBadWordClick);

    formcontext.btnremove.style.display = 
        ['good', 'bad', 'suggested']
            .indexOf($("mode").value) >= 0
        ? "inline-block" 
        : "none";
    formcontext.btngood.style.display = 
        ['bad', 'suggested', 'adhoc', 'flagged', 'regex']
            .indexOf($("mode").value) >= 0
        ? "inline-block"
        : "none";
    formcontext.btnbad.style.display = 
        ['good', 'suggested', 'adhoc', 'flagged', 'regex']
            .indexOf($("mode").value) >= 0
        ? "inline-block"
        : "none";

    sync_context();
}

function eGoodWordClick() {
    var i = formcontext.tblcontext.selectedIndex;
    if(i >= 0) {
        switch($("mode").value) {
            case "good":
                break;
            case "bad":
                awcBadToGoodWord(i);
                break;
            case "suggested":
                awcSuggestedToGoodWord(i);
                break;
            case "flagged":
            case "adhoc":
                awcAddGoodWord(i);
                break;
        }
        formcontext.tblcontext.remove(i);
        formcontext.tblcontext.selectedIndex = i;
        sync_context();
    }
}

function eBadWordClick() {
    var i = formcontext.tblcontext.selectedIndex;
    if(i >= 0) {
        switch($("mode").value) {
            case "good":
                awcGoodToBadWord(i);
                break;
            case "bad":
                break;
            case "suggested":
                awcSuggestedToBadWord(i);
                break;
            case "flagged":
            case "adhoc":
                awcAddBadWord(i);
                break;
        }
        formcontext.tblcontext.remove(i);
        formcontext.tblcontext.selectedIndex = i;
        sync_context();
    }
}

function eRemoveWordClick() {
    var i = formcontext.tblcontext.selectedIndex;
    if(i < 0) {
        return;
    }
    switch($("mode").value) {
        case "good":
            awcRemoveGoodWord(i);
            break;
        case "bad":
            awcRemoveBadWord(i);
            break;
        case "suggested":
            awcRemoveSuggestedWord(i);
            break;
        case "flagged":     // Remove meaningless
            return;
            break;
        case "adhoc":
            break;
    }
    formcontext.tblcontext.remove(i);
    formcontext.tblcontext.selectedIndex = i;
    sync_context();
}

// select another language
function eLangPick() {
    requestContext();
}

// select another wordlist type
function eListPick() {
    requestContext();
}

function active_context() {
    return _contexts.length > 0
        ? _contexts[_active_context_index] 
        : null;
}

function eSetImageScroll() {
    var context_image_scroll_pct =
        (active_context() && (active_context().lineindex > 0))
            ? 100 * active_context().lineindex 
                                / active_context().linecount 
            : -1;
    set_scroll_pct($('div_context_image'), context_image_scroll_pct);
}

// consequence of word table item onclick
function eSetContextWordIndex(index) {
    // check for current page == new page

    // if a div for the context exists, set background color
    if($('divctxt_'+_active_context_index.toString())) {
        $('divctxt_'+_active_context_index.toString())
                    .style.backgroundColor = "#EEEEEE";
    }
    _active_context_index = index;
    $('divctxt_'+index).style.backgroundColor = "white";
    // if index is different from current, replace the image
    if($('imgcontext').src != active_context().imageurl) {
        $('imgcontext').style.display = "none";
        $('imgcontext').src = (active_context())
                        ? active_context().imageurl
                            .replace(/\s/g, "")
                            .replace(/&amp;/g, "&")
                        : "";
    }
    else {
        eSetImageScroll();
    }
    $('imgcontext').style.display = "block";
}

function requestContext() {
    awcContext();
}

function requestWordcheck() {
    _accept_tags = accept_tags();
    awcWordcheck();
}

function requestWordList() {
    awcWordList();
}

function awcContext() {
    var qry = {};
    qry['querycode']    = "wccontext";
    qry['projectid']    = formcontext.projectid.value;
    qry['langcode']     = formcontext.langcode.value;
    qry['mode']         = $("mode").value;
    writeAjax(qry);

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

function ajaxSwitchLayout() {
    var qry = {};
    qry['querycode']    = "switchlayout";

    writeAjax(qry);
    return false;
}

function ajaxSetFontFace() {
    var qry = {};
    qry['querycode']    = "setfontface";
    qry['data']         = FontFace();

    writeAjax(qry);
    return false;
}

function ajaxSetFontSize(size) {
    var qry = {};
    qry['querycode']    = "setfontsize";
    qry['data']         = size;

    writeAjax(qry);
    return false;
}

function ajaxSetZoom(value) {
    var qry = {};
    qry['querycode']    = "setzoom";
    qry['data']         = value;

    writeAjax(qry);
    return false;
}




// user clicks wc button
// 1. initial wordcheck,
// 2. wordchecking, and wants to edit
// 3. editing, and wants to (resume) wordcheck.

function eWordCheckClick() {
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
    var wccount, bwcount;
    var i, id;
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

    wccount = wc_count();
    bwcount = bw_count();

    $("span_wccount").innerHTML = (wccount + bwcount).toString();

    if(divpreview.innerHTML.substr(-1) != "\n") {
        divpreview.innerHTML += "\n";
    }

    //divpreview.style.visibility     = "visible";
}

function eWCMonitor(msg) {
    _rsp = JSON.parse(msg);
    switch (_rsp.querycode) {

        // page editing functions

    case 'dosavetemp':
        show_wordcheck(_rsp);
        alert(_rsp.alert);
        break;

    case 'dosavenext':
	doSaveNext();
        break;

    case 'dosavequit':
	doSaveQuit();
        break;

    case 'wctext':
        show_wordcheck(_rsp);
        break;

    // response from submitting accepted words leaving wc mode
    case 'wcaccept':
        clear_wordchecking();
        break;

    case 'regexcontext':
        ajxDisplayRegexContextList(_rsp);
        break;

    case 'setfontsize':
    case 'setfontface':
    case 'setzoom':
    case 'acceptwords':
    case 'addgoodword':
    case 'addbadword':
    case 'removegoodword':
    case 'removebadword':
    case 'removesuggestedword':
    case 'goodtobadword':
    case 'badtogoodword':
    case 'suggestedtobadword':
    case 'suggestedtogoodword':
    //case 'suspecttobadword':
        break;

    case 'switchlayout':
        formedit.layout.value = _rsp.layout;
        formedit.imgpct = _rsp.imgpct;
        $('switchlayout').src = 
            (_rsp.layout == "horizontal")
                ? "gfx/horiz.png"
                : "gfx/vert.png";
        applylayout(_rsp);
        break;

    case 'popupalert':
        window.alert(_rsp.alert);
        break;

    default:
        window.alert("unknown querycode: " + _rsp.querycode);
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
            break;
        }
    }
}

function SetFontFaceSelector(size) {
    var i, o;
    var sel = $('selfontface');
    for (i = 0; i < sel.options.length; i++) {
        o = sel.options[i];
        if(o.value == size) {
            sel.selectedIndex = o.index;
            break;
        }
    }
}

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

function eTextScroll(e) {
    if(! e) { e = window.event; }
    var tgt = e.target ? e.target : e.srcElement;
    // dbg("textscroll", tgt.id);
    switch(tgt.id) {
        case "tatext":
            divpreview.scrollTop  = tatext.scrollTop;
            divpreview.scrollLeft = tatext.scrollLeft;
            break;
    }
    return true;
}

// only called when textarea loses focus
function eTextChange() {
    // divpreview.innerHTML = tatext.value
        // .replace(/&/g, '&amp;')
        // .replace(/</g, "&lt;")
        // .replace(/>/g, "&gt;");
    // if(divpreview.innerHTML.substr(-1) != "\n") {
        // divpreview.innerHTML += "\n";
    // }
    consider_wordchecking();
    // clear_wordchecking();
}

function eTextLoad(e) {
    if(! e) { e = window.event; }
    var tgt = e.target ? e.target : e.srcElement;
}

function eTextReset(e) {
    if(! e) { e = window.event; }
    var tgt = e.target ? e.target : e.srcElement;
}

// rules
// from http://stackoverflow.com/questions/263743/how-to-get-cursor-position-in-textarea/3373056#3373056

/**
 *
 * @returns {{start: number, end: number}}
 * @
 */
function SelectionBounds() {
    var start = 0;
    var end = 0;
    var normalizedValue;
    var range;
    var textInputRange;
    var len;
    var endRange;
    var el = tatext;

    if(typeof el.selectionStart == "number" 
                && typeof el.selectionEnd == "number") {
        start = el.selectionStart;
        end   = el.selectionEnd;
    } else {
        range = this.selection.createRange();

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
    // return $("is_sync").value == "1";
    return getsync();
}

function setSyncButton() {
    $('icosync').src = issync()
        ? "../../graphics/blusync.png"
        : "../../graphics/brnsync.png";
}

function eToggleSync() {
    // $("is_sync").value = (issync() ? "0" : "1");
    // setnamevalue("sync", ! issync());
    setsync(! issync());
    setSyncButton();
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

