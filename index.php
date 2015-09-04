<?php

$suffix = explode('.', $_SERVER['REQUEST_URI']);
if (count($suffix) > 1) {
    $suffix = $suffix[count($suffix) - 1];
    if (in_array($suffix, ['jpg', 'js', 'css', 'png'])) {
        header('HTTP/1.0 404 Not Found');
        die();
    }
}

$newUrl = str_replace('index.php', 'web/', $_SERVER['SCRIPT_NAME']);
Header('Location: ' . $newUrl);
