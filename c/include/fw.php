<?PHP
$metalinks = array(
    "<meta charset=utf-8>",
    "<link rel='shortcut icon' href='http://www.pgdpcanada.net/c/favicon.ico'>",
    "<link type='text/css' rel='Stylesheet' href='/c/css/dp.css'>"
}

$title = _("Activity Hub");
$slogan = "$pubcount titles preserved for the world!"

echo 
"<!DOCTYPE HTML>
<html lang='en'>
<head>
<title>$title</title>\n";

foreach($ameta as $meta) {
    echo $meta . "\n";
}

echo"
</head>\n";

echo ($body_on_load == ""
    ?  "<body>\n"
    : "<body $body_on_load>\n";
    
echo "
<div id='logobar' class='w100'>
  <div class='left'>
    <a href='$url_home'><img id='img_logo' src='$url_logo' width='336' height='68'></a>
  </div>
  <div class='right middle'>$slogan</div>
</div>\n";
