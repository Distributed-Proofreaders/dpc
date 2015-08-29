(function () {
    // -- digraphs
    var doc;
    var ta;
    var buf1 = "";
    var buf2 = "";
    var key;
    var trigger = false;
    var kNum;
    var text;
    var itop, start, end;
    var selectionRange, textInputRange, precedingRange, bookmark;

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

    function $(str) {
        return doc.getElementById(str);
    }

    function addEvent(obj, evType, fn) {
        if(obj && obj.addEventListener) {
            obj.addEventListener(evType, fn, false);
        }
        if(obj && obj.attachEvent) {
            obj.attachEvent("on" + evType, fn);
        }
    }

    if(proofframe.textframe) {
        doc = proofframe.textframe.document;
    }
    else {
        doc = proofframe.document;
    }
    ta = $('text_data');

    addEvent(doc, "keydown",  eKeyDown);
    addEvent(doc, "keyup",    eKeyUp);
    addEvent(doc, "keypress", eKeyPress);

    function digraph(c) {
        return digraphs[c] ? digraphs[c] : null;
    }

    function eKeyDown(e) {
        if(!e) { e = window.event; }

        kNum = (e.which && typeof e.which == "number") 
            ? e.which 
            : e.keyCode;

        // console.debug("keydown " + kCode);

        if(kNum == 8) {
            trigger = (buf1 && ! trigger);
        }
        return true;
    }

    function eKeyPress(e) {
        if(!e) { e = window.event; }
        kNum = (e.which && typeof e.which == "number") 
                    ? e.which : e.keyCode;

        switch (kNum) {
            case 8:  // backspace - needs to be trapped by keydown
            case 16: // IE passes the shift key, ignore it
                return true;
        }

        if(! trigger) {
            buf1 = String.fromCharCode(kNum);
            return true;
        }
                
        trigger    = false;
        buf2       = String.fromCharCode(kNum);
        key        = buf1 + buf2;
        mappedChar = digraph(key);

        if(! mappedChar) {
            return true;
        }

        e.preventDefault();
        e.returnValue = false;

        text = tatext.value;
        if(typeof tatext.selectionStart == "number" 
            && typeof tatext.selectionEnd == "number") {
            // Non-IE browsers and IE 9
            itop         = tatext.scrollTop;
            start        = tatext.selectionStart;
            end          = tatext.selectionEnd;
            text         = val.slice(0, start) 
                            + mappedChar 
                            + val.slice(end);
            tatext.value = text;

            // Move the cursor
            tatext.selectionStart   = start + 1;
            tatext.selectionEnd     = start + 1;
            tatext.scrollTop        = itop;
        }
        else if(proofdoc.selection 
                && proofdoc.selection.createRange) {
            // For IE up to version 8
            selectionRange  = proofdoc.selection.createRange();
            textInputRange  = tatext.createTextRange();
            precedingRange  = tatext.createTextRange();
            bookmark        = selectionRange.getBookmark();
            textInputRange.moveToBookmark(bookmark);
            precedingRange.setEndPoint("EndToStart", textInputRange);
            start           = precedingRange.text.length;
            end             = start + selectionRange.text.length;
            text            = val.slice(0, start) 
                                + mappedChar 
                                + val.slice(end);
            start++;

            // Move the cursor
            textInputRange      = tatext.createTextRange();
            textInputRange.collapse(true);
            textInputRange.move("character", 
                start - (tatext.value.slice(0, start)
                    .split(/\r\n/).length - 1));
            textInputRange.select();
        }

        return false;
    }
})()
