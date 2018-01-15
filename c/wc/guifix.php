//<?php
///**
// * Created by PhpStorm.
// * User: don
// * Date: 9/11/2015
// * Time: 10:14 PM
// */

$guifix = array(
	'  '  =>  ' ',    // 0
	' $'  =>  '',     // 1
	' ?- ?'  =>  '-',     // 2
	' ?-- '  =>  '--',     // 3
	' \.'=>  '.',     // 4
	' !' >  '!',     // 5
	' \?'=>  '?',     // 6
	' ;' >  ';',     // 7
	' :' >  ':',     // 7
	' ,' >  ',',     // 8
	'(\s"\s)' => '$1',   // spaceyquote
    "(\s'\s)" => '$1',   // spaceyquote
    '(?<!\.)\.{3}(?!\.)' => ' \.\.\.',     // 9
	"''" => '"',          // 10
    '(?<=\b)tii(\w*)' => 'th$1',     // 11
	'(?<=\b)Tii(\w*)' => 'Th$1',     // 11
	'(?<=\b)tli(\w*)' => 'th$1',     // 16
	'(?<=\b)Tli(\w*)' => 'Th$1',     // 16
	'(?<=[\'" ])1\b(?!\.)' => 'I',    // 12
	'(?<=[\n\'" ])0\b' => 'O',     // 13
	'(?<=\b)rn(\w*)' => 'm$1',       // 17
	'\n\n\n+' => '\r\r\r',           // 19
	'(?<=\b)tb(\w*)' => 'th$1',     // 25
	'(?<=\b)Tb(\w*)' => 'Th$1',     // 25
	'(?<=\b)(\w*)tb$' => '$1th',   //25
	'(?<=\b)wli(\w*)' => 'wh$1',   // 26
	'(?<=\b)Wli(\w*)' => 'Wh$1',   // 26
	'(?<=\b)wb(\w*)' => 'wh$1',   // 27
	'(?<=\b)Wb(\w*)' => 'Wh$1',   // 27
	'(?<=\b)hl(\w*)' => 'bl$1',     // 34
	'(?<=\b)hr(\w*)' => 'br$1',     // 35
	' -$' => '--',   // 36
	' - ' => '--',   // 36
    '(?<=[\'" ])l\b' => 'I',    // 37
    '(?<=(\(|\{|\[)) ' => '',   // 41
    ' (?=(\)|\}|\]))' => '',   // 41
	'__+' => '--',   // 42
    '(\w*?)rnp(\w*)' => '$1mp$2',  // 43
    '(?<!(\W))\/(?=\W)' => ',\'',   // 45
	'(?<=\')\/\/(?=\W)' => 'll',    // 45
    '(?<![ainu])j(?=\s)' => ';',    // 46
    '(\w*)cb\b' => '$1ch',   // 55
    '\bcb(\w*)\b' => 'ch$1',   // 55
    '(\w*?)gbt(\w*)' => '$1ght$2',   // 56
    '(\w*?)([ai])hle(\w*)' => '$1$2ble$3',    // 57
    '\bto he\b' => 'to be',    // 58
    '\\\\v' => 'w',    // 59
	'\\\\' => 'w',    // 59
	',,' => '""',   // 60
    '^" ' => '"',    // 62
	' "$' => '"',    // 62
    '(\w*?)cl(?=\b)' => '$1d',   // 63
    '(\w*?)pbt(\w*)' => '$1pht$2',   // 64
	'(\w*?)mrn(\w*)' => '$1mm$2',   // 65
    '(?<=\b)VV(\w*)' => 'W$1',    // 68
    '(?<=\b)[vV]{2}(\w*)' => 'w$1',   // 68
	'\!\!(\w+)' => 'H$1',   // 69
    '(\w+)\!(\w+)' => '$1l$2'  ,   // 71
    '(\w*)\'11\b' => '$1\'ll'   // 72
);
//
// $opt[0] = Convert multiple spaces to single space.
// $opt[1] = Remove end of line spaces.
// $opt[2] = Remove space on either side of hyphens.
// $opt[3] = Remove space on either side of emdashes.
// $opt[4] = Remove space before periods.
// $opt[5] = Remove space before exclamation points.
// $opt[6] = Remove space before question marks.
// $opt[7] = Remove space before colons and semicolons.
// $opt[8] = Remove space before commas.
// $opt[9] = Ensure space before ellipsis(except after period).
// $opt[10] = Convert two single quotes to one double.
// $opt[11] = Convert tii at the beginning of a word to th.
// $opt[12] = Convert solitary 1 to I.
// $opt[13] = Convert solitary 0 to O.
// $opt[14] = Convert vulgar fractions (¼  ,  ½,¾) to written out.
// $opt[15] = Convert ² and ³ to ^2 and ^3.
// $opt[16] = Convert tli at the beginning of a word to th.
// $opt[17] = Convert rn at the beginning of a word to m.
// $opt[18] = Remove empty lines at top of page.
// $opt[19] = Convert multi consecutive blank lines to single.
// $opt[20] = Remove top line if number.
// $opt[21] = Remove bottom line if number.
// $opt[22] = Remove empty lines from bottom of page.
// $opt[23] = $extractbold
// $opt[24] = Convert degree symbol to written
// $opt[25] = Convert tb at the beginning of a word to th.
// $opt[26] = Convert wli at the beginning of a word to wh.
// $opt[27] = Convert wb at the beginning of a word to wh.
// $opt[28] = Batch Extract
// $opt[29] = Batch Dehyphenate
// $opt[30] = Batch Filter
// $opt[31] = Batch Spellcheck
// $opt[32] = Batch Rename
// $opt[33] = Batch Check Zeros
// $opt[34] = Convert hl at the beginning of a word to bl.
// $opt[35] = Convert hr at the beginning of a word to br.
// $opt[36] = Convert unlikly hyphens to em dashes
// $opt[37] = Convert a solitary l to I if proceeded by ' or " or space
// $opt[38] = Convert £ to "Pounds
// $opt[39] = Convert ¢ to cents intelligently
// $opt[40] = Convert § to "Section
// $opt[41] = Remove space after open  ,   before closing brackets.
// $opt[42] = Convert multiple consecutive underscores to em dash
// $opt[43] = Convert rnp in a word to mp.
// $opt[44] = Move punctuation outside of markup
// $opt[45] = Convert forward slash to comma apostrophe.
// $opt[46] = Convert solitary j to semicolon.
// $opt[47] = Batch Rename Pngs
// $opt[48] = Save FTP User & Password
// $opt[49] = Batch pngcrush
// $opt[50] = Convert Windows codepage 1252 glyphs 80-9F to Latin1 equivalents
// $opt[51] = Search case insensitive
// $opt[52] = Automatically Remove Headers during batch processing.
// $opt[53] = Search whole word
// $opt[54] = Build a standard upload batch and zip it to the project directory
// $opt[55] = Convert cb in a word to ch.
// $opt[56] = Convert gbt in a word to ght.
// $opt[57] = Convert [ai]hle in a word to [ai]ble.
// $opt[58] = Convert he to be if it follows to.
// $opt[59] = Convert \v or \\\\ to w.
// $opt[60] = Convert double commas to double quote.
// $opt[61] = Insert cell delimiters  ,   "|" in tables.
// $opt[62] = Strip space after start & before end doublequotes.
// $opt[63] = Convert cl at the end of a word to d.
// $opt[64] = Convert pbt in a word to pht.
// $opt[65] = Convert rnm in a word to mm.
// $opt[66] = Batch run Englifh function
// $opt[67] = Extract sub/superscript markup
// $opt[68] = Convert vv at the beginning of a word to W
// $opt[69] = Convert !! at the beginning of a word to H
// $opt[70] = Convert X at the beginning of a word not followed by e to N
// $opt[71] = Convert ! in the middle of a word to l
// $opt[72] = Convert '11 to 'll
// $opt[73] = Use German style hyphens; "="
// $opt[74] = Convert to ISO 8859-1
// $opt[75] = Strip garbage punctuation from beginning of line.
// $opt[76] = Strip garbage punctuation from end of line.
// $opt[77] = Save files containing hyphenated and dehyphenated words
// $opt[78] = Extract <sc> </sc> maarkup for small caps

$myfix = array(
	array( '/\t+/'  ,   ' '),  // tabs to one space
	array( '/\iooo/i'  ,   '1000'),   // i000 to 1000
	array( '/\iooi/i'  ,   '1001'),   // iooi to 1001
	array( '/\ioo/i'  ,   '100'),     // ioo to 100
	array( '/\ioi/i'  ,   '101'),     // ioi to 101
	array( '/(\d)ooo/i'  ,   '$1000'),   // digit-ooo to thousand
	array( '/(\d)oo/i'  ,   '$100'),    // digit-oo to hundred
	array( '/\b(\d)o\b/'  ,   '$10'),    // digit-o to tens (stand-alone)
	array( '/\bist\b/'  ,   '1st'),      // ist to first
	array( '/\b(\d)ist\b/'  ,   '$11st'),   // digit-ist to umpty-first (stand-alone)
	array( '/\bioth\b/'  ,   '10th'),       // ioth to 10th (stand-alone)
	array( '/\bnth\b/'  ,   '11th'),    // nth to 11th (stand-alone)
	array( '/n(\d)/i'  ,   '11$1'),     // n-digit to 11+digit
	array( '/\( "/'  ,   '("'),         // lparen-space-quote to lparen-quote
	array( '/" \)/'  ,   '")'),          //quote-space-rparen to quote-rparen
	array( '/([0-9])  ,  ([0-9]{4})/', '$1, $2'),   // date with missing space
	array( '  ,  (?=\n\n)', '\.\n\n'),   // paragraph ending in comma
	array( '  ,  (\s+[A-HJ-Z])', '\.$1'),    // comma -space-uppercase (except I
);

$suspicious = array(
',(\n\s*"?[A-Z])' => '.$1'    // comma newline then uppercase
);
