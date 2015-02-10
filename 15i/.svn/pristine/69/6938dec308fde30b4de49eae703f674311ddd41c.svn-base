<?php
die('developer only');

s(dirname(dirname(__FILE__)).'/');

function s($target_dir) {
    chdir($target_dir);
    $files = glob('*');
    if (!is_array($files)||count($files)==0) return;
    foreach ($files as $file) {
        $path = $target_dir.$file;
        if (is_dir($path)) {
            s($path.'/');
        } else {
            $contents = file_get_contents($path);
            if (hasbom($contents)) {
                echo "<p>$path</p>";
            }
        }
    }
}

function hasbom($contents) {
    return preg_match("/^efbbbf/", bin2hex($contents[0] . $contents[1] . $contents[2])) === 1;
}
