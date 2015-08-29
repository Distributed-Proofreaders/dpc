<?PHP
global $relPath;
include_once($relPath.'http_headers.inc');
include_once($relPath.'slim_header.inc');

// set image and text height and width
if($User->IsEnhancedLayout()) {
    $textWidth = $userP['v_tframe'];
    $imageWidth = (100 - $userP['v_tframe']) - 1;
    $textHeight = 99;
    $imageHeight = 99;
    $textTop = "0";
    $textLeft = (100 - $userP['v_tframe'])."%";
}
else {
    $textWidth = 99;
    $imageWidth = 99;
    $textHeight = $userP['h_tframe'];
    $imageHeight = (100 - $userP['h_tframe']) - 1;
    $textTop = (100 - $userP['h_tframe'])."%";
    $textLeft = "1%";
}

slim_header("Proofreading Page", true, false);

?>
<script language="JavaScript" type="text/javascript">

function ldAll() {
    top.initializeStuff(1);
}
function scrollImageUp() { top.scrollImage('up'); }
function scrollImageDown() { top.scrollImage('down'); }
function scrollImageLeft() { top.scrollImage('left'); }
function scrollImageRight() { top.scrollImage('right'); }
function getCurSel() {top.getCurSel();}
function getCurCaret() {top.getCurCaret();}
function showIZ() {
    top.showIZ();
    return false;
}
function showActual() { return top.showActual(); }

</script>
<style type="text/css">

body {
  font-family: verdana, arial, helvetica, sans-serif;
  font-size: 12px;
  color:#000000;
  background-color:#CDCDC1;
  text-align:center;
  overflow:hidden;
  }
A:link {
  color:#000000;
  text-decoration : none;
  }
A:visited {
  color:#000000;
  text-decoration : none;
  }
A:hover {
  color:#003300;
  font-weight: bold;
  text-decoration : none;
  }
A:active {
  color:#000033;
  font-weight: bold;
  text-decoration : none;
  }
#imagehorz {
  position:absolute;
  left:25px;
  top:0;
  <?PHP
    echo "width:".($imageWidth-3)."%;\r\n";
  ?>
  height:25px;
  z-index:3;
  }
#imagevert {
  position:absolute;
  left:0;
  top:25px;
  width:25px;
  <?PHP
    echo "height:".($imageHeight-3)."%;\r\n";
  ?>
  z-index:4;
  }
#imageframe {
    position:absolute;
      "top: 25px;
      "left: 25px;
    width: <? echo $imageWidth-3; ?> ;
    height:<?echo $imageHeight-3; ?> ;

    clip:rect(0, 100%, 100%, 0);
    z-index:2;
    overflow:auto;
    text-align:center;
}
#controlframe {
     position:absolute;
    left: <? echo $textLeft; ?> ;
    top: <? echo $textTop; ?> ;
    width: <? echo $textWidth; ?>%;
    height: <? echo $textHeight; ?>%;
    clip:rect(0, 100%, 100%, 0);
    background-color:#CE928C;
    overflow:auto;
    z-index:6;
    text-align:center;
}
#tbtext {
  border:1px solid #000000;
  text-align:center;
  overflow:auto;
  }
#tdtop {
  border:1px solid #000000;
  background-color:#CDC0B0;
  padding:2px;
  }
#tdtext {
  border:1px solid #000000;
  background-color:#CE928C;
  padding:2px;
  }
#tdbottom {
  border:1px solid #000000;
  background-color:#EEDFCC;
  padding:2px;
  }
#text_data {
  padding:2px;
  background-color:#FFF8DC;
  color:#000000;
  }
.dropsmall {
  font-size: 75%;
  background-color:#FFF8DC;
  }
.dropnormal {
  background-color:#FFF8DC;
  }
.boxnormal {
  background-color:#FFF8DC;
  }

</style>
</head>
<body onload="ldAll()">
<div id="imagehorz">
    <table id="tbhorz" style="width: 100%">
        <tr><td>
            <a onclick="scrollImageLeft()">
                <img src="gfx/a1_left.png" width="11" height="11" alt="Move Left" title="Move Left" border="0">
            </a>&nbsp;&nbsp;&nbsp;
            <img src="gfx/a2_left.png" width="11" height="11" alt="Scroll Left" title="Scroll Left" border="0">
         </td>
        <td>
            <img src="gfx/a2_right.png" width="11" height="11" alt="Scroll Right" title="Scroll Right" border="0">
            &nbsp;&nbsp;&nbsp;
            <a onclick="scrollImageRight()">
                <img src="gfx/a1_right.png" width="11" height="11" alt="Move Right" title="Move Right" border="0">
            </a>
        </td>
        </tr>
    </table>
</div>
<div id="imagevert">
    <table id="tbvert">
        <tr><td>
            <a onclick="scrollImageUp()">
                <img src="gfx/a1_up.png" width="11" height="11" alt="Move Up" title="Move Up" border="0"></a>
            <p>
                <img src="gfx/a2_up.png" width="11" height="11" alt="Scroll Up" title="Scroll Up" border="0">
            </p>
        </td></tr>
        <tr><td>
                <img src="gfx/a2_down.png" width="11" height="11" alt="Scroll Down" title="Scroll Down" border="0">
            <p>
            <a onclick="scrollImageDown()">
                <img src="gfx/a1_down.png" width="11" height="11" alt="Move Down" title="Move Down" border="0">
            </a>
            </p>
        </td></tr>
    </table>
</div>

<?php

  $imgurl  = $page->ImageUrl();
  $process_url = $User->Username() == 'dkretz' ? "proctext.php" : 'processtext.php';

echo "
<div id='imageframe'>
<div id='imagedisplay'>
    <img id='scanimage' title='' alt='' src='" 
            . $imgurl 
            . "' border='0'>
</div>
</div>
<div id='controlframe'>
<form name='editform' id='editform' method='POST' action='$process_url'>\n";

echo_hidden_fields($page);

echo "
<table id='tbtext'>
<tr>
<td id='tdtop'>\n";

include('button_menu.inc');

echo "
</td>
</tr>
<tr>
<td id='tdtext'>\n";

echo_proofing_textarea($page);

echo "
</td></tr>
<tr><td id='tdbottom'>\n";

echo_info();

echo "</td>
</tr>
</table>
</form>
</div>
</body>
</html>";

exit;

function echo_hidden_fields($page) {
    /** @var DpPage $page */
    $projectid = $page->ProjectId();
    $imagefile = $page->ImageFile();
    echo "
    <input type='hidden' value='$projectid' name='projectid' id='projectid'>
    <input type='hidden' value='$imagefile' name='imagefile' id='imagefile'>\n";
}

function echo_proofing_textarea( $page) {
    /** @var DpPage $page */
    global $userP, $f_f, $f_s;
    global $User;

    $page_text = maybe_convert($page->Text());
    
    if ( $User->IsEnhancedLayout()) {
        // "vertical"
        $n_cols      = $userP['v_tchars'];
        $n_rows      = $userP['v_tlines'];
        $font_face_i = $userP['v_fntf'];
        $font_size_i = $userP['v_fnts']; }
    else {
        // "horizontal"
        $n_cols      = $userP['h_tchars'];
        $n_rows      = $userP['h_tlines'];
        $font_face_i = $userP['h_fntf'];
        $font_size_i = $userP['h_fnts'];
    }

    $font_face = $f_f[$font_face_i];
    $font_size = $f_s[$font_size_i];

    echo "
    <textarea name='text_data' id='text_data' cols='$n_cols' rows='$n_rows'
          style='font-family: $font_face;font-size: $font_size; 
                padding-left: 0.25em;' ";

    if ( $User->IsEnhancedLayout() ) {
        echo "
            onselect='getCurSel()'
            onclick='getCurCaret()'
            onkeyup='getCurCaret()'\n";
    }

    echo  "accept-charset='utf-8'>\n";

    // SENDING PAGE-TEXT TO USER
    // We're sending it in an HTML document, so encode special characters.
    echo htmlspecialchars( $page_text, ENT_NOQUOTES );

    echo "</textarea>";
}

function echo_info() {
    /** @var DpPage $page */
    echo _("Page: ") . $page->PageName() . " -- ";

    $other = array();
    foreach(array("P1", "P2", "P3", "P4", "P5") as $rnd) {
        if($rnd == $this->_page->RoundId()) {
            continue;
        }
        $proofer = $this->_page->RoundUser($rnd);
        if($proofer) {
            $other[] = $proofer;
        }
    }
    echo implode(", ", $other);
}


