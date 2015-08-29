<?php

ini_set("display_errors", true);
error_reporting(E_ALL);

$relPath="./../pinc/";
include_once($relPath . 'dpinit.php');

$which      = Arg("which");
$name       = Arg("name");

// ----------------------------------
// handle transactions
// ----------------------------------

if($which && $name) {
    $which = ($which == "pper" ? "postproofer" : "ppverifier");

    $sql = "SELECT nameofwork, authorsname, postednum,
                postproofer, ppverifier, language, FROM_UNIXTIME(modifieddate)
            FROM projects
            WHERE $which = '$name'";

    $rows = $dpdb->SqlRows($sql);

    $tbl = new Dptable();
    $tbl->AddColumn("<Title", "nameofwork");
    $tbl->AddColumn("<Author", "authorsname");
    $tbl->AddColumn("<Last modified", "modifieddate");
    $tbl->AddColumn("<Posted #", "postednum");
    $tbl->AddColumn("<PPer", "postproofer");
    $tbl->AddColumn("<PPVer", "ppverifier");
    $tbl->AddColumn("^Language", "language");

    $tbl->SetRows($rows);
}

// ----------------------------------
// counts    
// ----------------------------------

$no_stats = 1;

theme("PP and PPV", "header");

// -----------------------------------------------------------------------------

if($which) {
    $tbl->EchoTable();
}

echo "<div>
    <form name='ppform' id='ppform' method='post'>
        PPer <input type='radio' name='which' value='pper' />
        <br/> or PPVer: <input type='radio' name='which' value='ppver' />
        <br/>Name: <input type='text' name='name' size='10' />
        <br/><input type='submit'/>
    </form>
</div>\n";

theme("", "footer");
exit;


