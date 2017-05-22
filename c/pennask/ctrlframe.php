<?php
    require_once "../../pinc/site_vars.php";
    require_once "../../pinc/links.inc";
?><!DOCTYPE HTML>
<html>
<head>
<title></title>
<meta charset='utf-8'>
<?php echo link_to_css("ctlbar.css"); ?>
</head>
<body id='bctl'>
<form accept-charset='UTF-8' name='formctls' target='_top'>
<div id='charpicker' 
                onmouseover='return top.ePickerOver(event)'
                onmouseout='return top.ePickerOut(event)' 
                onclick='return top.eCharClick(event)'>
    <div id='selectors'>
    </div>
    <div id='pickers'>
    </div>
</div>
<div id='charshow'> 
<div id='divchar'></div>
<div id='divdigraph'></div>
</div> <!-- charshow -->
<div>
    <div id='ctl_top' class='middle'>
        <div id='ctl_right'>
        <div id='ctl_tags_top' class='clear rfloat proofbutton'>
            <button title='italics' class='proofbutton'
                onclick='return top.eSetItalics();'>&lt;i&gt;</button>

            <button title='bold'
                onclick='return top.eSetBold();'>&lt;b&gt;</button>

            <button title='small-caps'
                onclick='return top.eSetSmallCaps()'>
                &lt;sc&gt; </button>

            <button title='gesperrt (spaced)'
                onclick='return top.eSetGesperrt()'>&lt;g&gt;</button>

            <button title='guillemets'
                onclick='return top.eSetGuillemets()'> « » </button>

            <button title='guillemetsR'
                onclick='return top.eSetGuillemetsR()'> » « </button>

            <button title='antiqua'
                onclick='return top.eSetAntiqua()'>&lt;f&gt;</button>

            <button title='dequotes'
                onclick='return top.eSetDeQuotes()'> „ “ </button>

            <button title='itquotes'
                onclick='return top.eSetItQuotes()'> “ „ </button>


            <button title='Remove markup'
                onclick='return top.eRemoveMarkup()'><span class="linethru">&lt;×&gt;</span></button>
        </div>
        <div class='rfloat clear proofbutton'>
            <button title='nowrap' onclick='return top.eSetNoWrap()'>
                 /* */ </button>

            <button title='note' onclick='return top.eSetNote()'>
                [** ]</button>

            <button title='blockquote'
                onclick='return top.eSetBlockQuote()'>
                 /# #/ </button>

            <button title='uppercase'
                onclick='return top.eSetUpperCase()'>
                 ABC </button>

            <button title='title case'
                onclick='return top.eSetTitleCase()'> Abc </button>

            <button title='lowercase'
                onclick='return top.eSetLowerCase()'> abc </button>
            <button title='brackets' 
                onclick='return top.eSetBrackets()'>[ ]</button>
            <button title='braces'
                onclick='return top.eSetBraces()'>{ }</button>
            <button title='curly quotes'
                onclick='return top.eCurlyQuotes()'>" “</button>
        </div> <!-- ctl_tags_top -->
        <div id='ctl_tags_bottom' class='rfloat clear proofbutton'>
            <button title='thought break'
                onclick='return top.eInsertThoughtBreak()'>
                &lt;tb&gt;</button>
            <button title='footnote'
                onclick='return top.eSetFootnote()'>
                [Footnote: ]</button>
            <button title='illustration'
                onclick='return top.eSetIllustration()'>
                               [Illustration: ]</button>
            <button title='sidenote'
                onclick='return top.eSetSidenote()'>
                               [Sidenote: ]</button>
            <button title='Blank Page'
                onclick='return top.eSetBlankPage()'>
                                        [Blank Page]</button>
        </div> <!-- ctl_tags_bottom -->
        </div> <!-- ctl_left -->
    </div> <!-- ctl_bottom -->
</div>
</form>

</body>
</html>
