<?php
$src = '../web/dist/';
$dest = '/var/www/glpi/public/flow/';

function recursiveCopy($src, $dest)
{
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    $dir = opendir($src);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                recursiveCopy($src . '/' . $file, $dest . '/' . $file);
            } else {
                copy($src . '/' . $file, $dest . '/' . $file);
            }
        }
    }
    closedir($dir);
}

recursiveCopy($src, $dest);
echo "Deployment completed via PHP";
