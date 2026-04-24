#!/usr/bin/env php
<?php

$localDir  = './web';
$cdnBase   = 'https://cdn.motion.tools';
$integrity = [];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($localDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($files as $file) {
    if (!$file->isFile()) {
        continue;
    }
    if ($file->getExtension() !== 'js') {
        continue;
    }

    $localPath = $file->getPathname();
    if (str_contains($localPath, "ckeditor")) {
        continue;
    }
    $contents  = file_get_contents($localPath);

    if ($contents === false) {
        fwrite(STDERR, "Warning: could not read {$localPath}\n");
        continue;
    }

    $hash = 'sha384-' . base64_encode(hash('sha384', $contents, binary: true));

    // Build the CDN URL: strip the local base dir prefix and normalise separators
    $relative = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', substr($localPath, strlen($localDir))), '/');
    $url      = $cdnBase . '/' . $relative;

    $integrity[$url] = $hash;
}

ksort($integrity);

echo json_encode(['integrity' => $integrity], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
