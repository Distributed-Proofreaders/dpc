<?php

function bytesToSize1024($bytes, $precision = 2) {
    $unit = array('B','KB','MB');
    return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision).' '.$unit[$i];
}

$filename = $_FILES['zipfile']['name'];
$filetype = $_FILES['zipfile']['type'];
$filesize = bytesToSize1024($_FILES['zipfile']['size'], 1);

echo <<<EOF
<p>Your file: {$filename} has been successfully received.</p>
<p>Type: {$filetype}</p>
<p>Size: {$filesize}</p>
EOF;

