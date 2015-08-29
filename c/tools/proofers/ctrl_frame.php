<?php
$relPath="./../../pinc/";
include_once($relPath.'http_headers.inc');
include_once($relPath.'dpinit.php');
include_once($relPath.'stages.inc');
include_once('toolbox.inc');

$round_id = Arg('round_id');
assert( !empty($round_id) );
$round = get_Round_for_round_id($round_id);
assert( !is_null($round) );

include_once($relPath.'slim_header.inc');
slim_header("Control Frame", true, false);

?>
<style type="text/css">
table { margin: 0; padding: 0; }
body {
  font-family: verdana, arial, helvetica, sans-serif; font-size: 12px; color:#000000;
  background-color:#CDC0B0; padding:0; margin: 2px 0 -2px; text-align:center; 
}
.dropsmall { font-size: 75%; background-color:#FFF8DC; }
.dropnormal { background-color:#FFF8DC; }
#markBoxChar { background-color:#FFF8DC; font-size: 1.5em; padding-left: .5em; width: 2em; }
.dropchars { background-color:#EEDFCC; }
.lfloat { float: left;}
.rfloat { float: right;}

.proofbutton {
    border:1px solid black;
    text-align: center;
    background: #FFF8DC;
    display:inline;
    margin: 0 1px 1px 0;
    <? if(!stristr($_SERVER['HTTP_USER_AGENT'],"msie")) echo "line-height:140%;\n"; ?>
    padding-top: 1px;
    cursor: pointer;
}
.ctlbox input {
    font-size: 80%;
    padding: 1px;
    margin: 1px;
    float: left;
}
.divguides {
    font-size: 80%;
    margin: auto;
}
</style>
</head>
<body>
<a
	href="#"
	accesskey="="
	onfocus="top.focusText()"
></a><form
	name="markform"
	id="markform"
	onsubmit="return(false);"
	action=""
><table
	cellpadding="0"
	cellspacing="0"
	align="center"
	width="99%"
	border="0"
><tr><td
	valign="top"
	align="right"
><?

echo_character_selectors_block();

?>
</td>
<td valign="top" align="center" >
<INPUT accesskey="[" TYPE="text" VALUE="" name="markBox" class="dropnormal" size="9" onclick="this.select();" >
<INPUT accesskey="]" TYPE="text" VALUE="" name="markBoxEnd" class="dropnormal" size="9" onclick="this.select()" >

<?PHP

echo_tool_buttons( $round->pi_tools['tool_buttons'] );

?></td>
<td align="right" valign="top">
<?PHP
echo "<b><font color='red'>"._("HELP")."---&gt;</font></b>";
?>
<a href="../../faq/<? echo lang_dir(); ?>prooffacehelp.php" accesskey="1" target="helpNewWin" >
<img src="gfx/tags/help.png" width="18" height="18" border="0" align="top"
    alt="<? echo _("Help"); ?>" title="<? echo _("Help"); ?>" ></a>
</td></tr>
<tr><td valign="top" colspan="3" align="center">

<div class='ctlbox lfloat'>
<input type="button" value="[Greek: ]" onclick="top.new_iMU('[Greek: ', ']')">
<input type='button' title='Note' value='[** ]' onclick='top.new_iMU("[** ", "]")'>
<?php
if($round_id == "F1" || $round_id == "F2" ) {
?>
<input type='button' value='[Sidenote: ]' onclick='top.new_iMU("[Sidenote: ", "]")'>
<input type='button' value='[Illustration: ]' onclick='top.new_iMU("[Illustration: ", "]")'>
<input type='button' value='[Footnote #: ]' onclick='top.new_iMU("[Footnote #: ", "]")'>
<input type='button' title='poetry/table' value='/* */' onclick='top.new_iMU("/*\n", "\n*/")'>
<input type='button' title='Block quote' value='/# #/' onclick='top.new_iMU("/#\n", "\n#/")'>
<input type='button' title='Thought break' value='&lt;tb&gt;'
                                        onclick='top.new_iMU("\n&lt;tb&gt;\n", "")'>
<?php
}
?>
<input type='button' title='[ ]' value='[ ]' onclick='top.new_iMU("[", "]")'>
<input type='button' title='{ }' value='{ }' onclick='top.new_iMU("{", "}")'>
<input type='button' title='Blank Page' value='[Blank Page]' onclick='top.iMUO(6)'>
</div>
<div class='lfloat divguides'>
<?php
echo "<a href='{$site_url}/wiki/index.php?title=Proofreading_Guidelines' target='_blank'>"
. _('Proofreading Guidelines') ." </a>
<br/>
<a href='{$site_url}/wiki/index.php?title=Formatting_Guidelines' target='_blank'>"
._("Formatting Guidelines")."</a>\n";
?>
</div>
<div class='ctlbox rfloat'>
<input type="button" title="Search and replace" value="Search/Replace" 
onClick="window.open('srchrep.php','dp_searchrepl','width=300,height=250,directories=0,location=0,menubar=0,resizable,scrollbars,status=0,toolbar=0'); return false;">

<input type="button" title="Table Maker" value="Make table" 
onClick="window.open('mktable.php','dp_mktable','width=600,height=500,directories=0,location=0,menubar=0,resizable,scrollbars,status=0,toolbar=0'); return false;">

<input type="button" title="Greek Transliterator" value="Greek transliterator" 
onClick="window.open('greek2ascii.php','gkasciiWin','width=640,height=210,directories=0,location=0,menubar=0,resizable,scrollbars,status=0,toolbar=0'); return false;">
</div>
<br/>
<?PHP

echo "
</td>
</tr>
</table>
</form>

</body>
</html>";
