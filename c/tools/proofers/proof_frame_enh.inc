<?PHP
global $relPath;
$relPath = "../../pinc/";
include_once($relPath.'http_headers.inc');
include_once($relPath.'dpinit.php');
include_once($relPath.'slim_header.inc');

global $User;

// set image and text height and width
if($User->IsVerticalLayout()) {
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
  color:black;
  background-color:#CDCDC1;
  text-align:center;
  overflow:hidden;
  }
A:link {
  color: blue;
  text-decoration : none;
  }
A:visited {
  color: blue;
  text-decoration : none;
  }
A:hover {
  color: blue;
  font-weight: bold;
  text-decoration : none;
  }
A:active {
  color: blue;
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
#scanimage {
<?php
echo "width: ".$imageWidth."%;";
 ?>
}

.nowrap {
    overflow: scroll;
}

.wrap {
    white-space: pre-wrap;
    overflow: auto;
    width: 100%;
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

<?PHP
    echo "position:absolute;\r\n".
      "top:25px;\r\n".
      "left:25px;\r\n";
    echo "width:".($imageWidth-3)."%;\r\n";
    echo "height:".($imageHeight-3)."%;\r\n";
?>

  clip:rect(0, 100%, 100%, 0);
  z-index:2;
  overflow:auto;
  text-align:center;
  }
#controlframe {

<?PHP
echo " position:absolute;\r\n";
echo "left:".$textLeft.";\r\n";
echo "top:".$textTop.";\r\n";
echo "width:".$textWidth."%;\r\n";
echo "height:".$textHeight."%;\r\n";

?>
  clip:rect(0, 100%, 100%, 0);
  background-color:#CE928C;
  overflow:auto;
  z-index:6;
  text-align:center;
  }
#tbtext {
  border:1px solid black;
  text-align:center;
  overflow:auto;
  }
#tdtop {
  text-align: left;
  border:1px solid black;
  background-color:#CDC0B0;
  padding:2px;
  }
#tdtext {
  width: 100%;
  border:1px solid black;
  background-color:#CE928C;
  padding:2px;
  }
#tdbottom {
  border:1px solid black;
  background-color:#EEDFCC;
  padding:2px;
  }
#text_data {
  padding:2px;
  background-color:#FFF8DC;
  color:black;
  }
#fntFace {
  font-size: 75%;
  background-color:#FFF8DC;
  width: 6em;
}
.dropsmall {
  vertical-align: top;
  width: 4em;
  font-size: 75%;
  background-color:#FFF8DC;
  }
.dropnormal {
  background-color:#FFF8DC;
  }
.boxnormal {
  vertical-align: top;
  background-color:#FFF8DC;
  }
</style>
</head>
<body onload="top.initializeStuff()">
<div id="imagehorz">
    <table id="tbhorz" style="width: 100%">
        <tr><td>
         </td>
        <td>
            </a>
        </td>
        </tr>
    </table>
</div>
<div id="imagevert">
    <table id="tbvert">
        <tr><td>
            </p>
        </td></tr>
        <tr><td>
            </p>
        </td></tr>
    </table>
</div>

<?php

$imgurl = $page->ImageUrl();

// draw image frame

echo "
<div id='imageframe'>
<div id='imagedisplay'>
    <img id='scanimage' title='' alt='' src='" 
            . $imgurl 
            . "' border='0'>
</div>
</div>\n";

// draw text frame

echo "
<div id='controlframe'>     <!-- width or height from v_tframe or h_frame -->
<form name='editform' id='editform' method='POST' action='processtext.php'>

<input type='hidden' value='{$page->ImageFile()}' name='imagefile' id='imagefile'>
<input type='hidden' value='{$page->ProjectId()}' name='projectid' id='projectid'>

<table id='tbtext'>
<!-- first row is controls -->
<tr> <td id='tdtop'>\n";

include('button_menu.inc');

echo "
</td> </tr>

<!-- second row is text -->
<tr> <td id='tdtext'>\n";

echo_proofing_textarea($page);

echo " </td></tr>

<!-- third row is page name etc. -->
<tr><td id='tdbottom'>\n";

echo_info($page);


echo "
</td> </tr>
</table>
</form>
</div>
</body>
</html>";

exit;


function echo_info($page) {
    /** @var DpPage $page */
        $proofers = array();
        foreach(array("P1", "P2", "P3", "F1", "F2") as $rnd) {
            if($rnd == $page->Phase()) {
                break;
            }
            $proofer = $page->RoundUser($rnd);
            $proofers[] = $rnd . ": " .link_to_pm($proofer);
        }
        $str = _("Page: ").$page->PageName()." &mdash; " . implode(", ", $proofers);
        echo "<p style='font-size: .8em;'>$str</p>\n";
        return;
}

function echo_proofing_textarea( $page ) {
    /** @var DpPage $page */
    global $userP, $f_f, $f_s;
    global $User;

     $page_text = maybe_convert($page->Text());

    $n_cols         = $User->TextChars();
    $n_rows         = $User->TextLines();
    $font_face_i    = $User->FontFace();
    $font_size_i    = $User->FontSize();
    $text_wrap      = $User->TextWrap();
    

    /*
    if($User->IsVerticalLayout()) {
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
        $font_size_i = $userP['h_fnts']; }
        */


    $font_face = $f_f[$font_face_i];
    $font_size = $f_s[$font_size_i];

    echo "<textarea name='text_data' id='text_data' cols='$n_cols' rows='$n_rows'\n";

    echo $User->TextWrap() 
        ? "class='wrap' wrap='soft'\n" 
        : "class='nowrap' wrap='off'\n"; 
    echo "style='";
    if ( $font_face != '' && $font_face != BROWSER_DEFAULT_STR ) {
        echo "font-family: $font_face; ";
    }
    if ( $font_size != '' && $font_size != BROWSER_DEFAULT_STR ) {
        echo "font-size: $font_size; ";
    }
    echo "
        padding-left: 0.25em;' 
        accept-charset='utf-8'>\n";


    // SENDING PAGE-TEXT TO USER
    // We're sending it in an HTML document, so encode special characters.
    echo h( $page_text);

    echo "</textarea>";
}



