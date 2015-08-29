<?PHP

ini_set("report_errors", 1);
error_reporting(E_ALL);

$relPath = '../../pinc/';
include_once $relPath.'dpinit.php';


if ( ! $User->IsSiteManager() ) {
    die( "You are not allowed to run this script." );
}

$mode = Arg("mode", "optpm");
if(Arg("add")){
    $do = "add";
}
else if(Arg("subtract")) {
    $do = "subtract";
}


$pmchk = $pfchk = "";
if($mode == "optpm") {
    $pmchk = " checked";
    $sql = "SELECT username FROM users WHERE manager = 'yes'";
}
else {
    $pfchk = " checked";
    $sql = "SELECT username FROM usersettings
            WHERE setting = 'proj_facilitator'
            AND value = 'yes'";
}
$values = $dpdb->SqlValues($sql);

$names = array();
foreach($values as $value ) {
    $names[] = $value;
}
$namestr = implode(", ", $values);


echo "
<!doctype html>
<html>
<style type='text/css'>
.lfloat { float: left; }
</style>
<script type='text/javascript'>
function $(a) {
    return document.getElementById(a);
}
function eOpt(event) {
    frmhr.submit();
};

function eclick(event) {
    $('answer').innerHTML = this.value;
}
</script>

<h2>Personnel Managment</h2>

<form id='frmhr' name='frmhr' onclick='eclick(event)' echange='echange(event)'>
<div>
<div><input type='radio' name='opt' id='optpm' value='pm' onchange='eOpt() $pmchk'/>pm</div>
<br/>
<div><input type='radio' name='opt' id='optpf' value='pf' onchange='eOpt()'/>pf $pfchk</div>
<br/>
<div id='answer' style='width: 50em; height: 2em;'>
</div>
</div>

<div class='lfloat' id='namelist' name='namelist' style='width: 500px; height: 299px;'>$namestr</div>

<textarea class='lfloat' id='txt' name='txt' style='width: 500px; height: 299px;'></textarea>
<div class='lfloat'>
<input type='submit' name='cmdadd'      value='add'/><br/>
<input type='submit' name='cmdsubtract' value='subtract'/>
</div>

</form>
</html>
";
