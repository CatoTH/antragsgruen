#!/usr/bin/env php
<?php

if (count($argv) !== 4) {
    die("Call: ./create-update.php [directory old] [directory-new] [update-file.zip]\n");
}

$dirOld     = $argv[1];
$dirNew     = $argv[2];
$updateFile = $argv[3];
if ($dirOld[strlen($dirOld) - 1] !== '/') {
    $dirOld .= '/';
}
if ($dirNew[strlen($dirNew) - 1] !== '/') {
    $dirNew .= '/';
}

if (!file_exists($dirOld) || !is_dir($dirOld)) {
    die("$dirOld is not a directory");
}
if (!file_exists($dirNew) || !is_dir($dirNew)) {
    die("$dirNew is not a directory");
}


$GLOBALS["FILES_ADDED"]   = [];
$GLOBALS["FILES_UPDATED"] = [];
$GLOBALS["FILES_DELETED"] = [];

/**
 * @param string $dirBase
 * @param string $dirRelative
 * @return array
 * @throws Exception
 */
function getDirContent($dirBase, $dirRelative)
{
    $dirh = opendir($dirBase . '/' . $dirRelative);
    if (!$dirh) {
        return [[], []];
    }
    $dirs  = [];
    $files = [];
    while (($entry = readdir($dirh)) !== false) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $fullname     = $dirBase . '/' . $dirRelative . $entry;
        $relativeName = $dirRelative . $entry;
        if (is_dir($fullname)) {
            $dirs[] = $relativeName;
        } elseif (is_file($fullname)) {
            $files[] = $relativeName;
        } else {
            throw new \Exception("Unknown file type: $fullname");
        }
    }
    return [$dirs, $files];
}

/**
 * @param string $file1
 * @param string $file2
 * @return bool
 */
function filesAreEqual($file1, $file2)
{
    if (filesize($file1) !== filesize($file2)) {
        return false;
    }
    return file_get_contents($file1) === file_get_contents($file2);
}

/**
 * @param string $file
 * @return string
 */
function getFileHash($file)
{
    $content    = file_get_contents($file);
    $binaryHash = sodium_crypto_generichash($content);
    return base64_encode($binaryHash);
}

/**
 * @param string $dirOldBase
 * @param string $dirNewBase
 * @param string $dirRelative
 * @throws Exception
 */
function compareDirectories($dirOldBase, $dirNewBase, $dirRelative)
{
    list($oldDirs, $oldFiles) = getDirContent($dirOldBase, $dirRelative);
    list($newDirs, $newFiles) = getDirContent($dirNewBase, $dirRelative);

    // Search for changed files
    foreach ($oldFiles as $filename) {
        if (!in_array($filename, $newFiles)) {
            $GLOBALS['FILES_DELETED'][] = $filename;
        } elseif (!filesAreEqual($dirOldBase . $filename, $dirNewBase . $filename)) {
            $GLOBALS['FILES_UPDATED'][$filename] = getFileHash($dirNewBase . $filename);
        }
    }
    foreach ($newFiles as $filename) {
        if (!in_array($filename, $oldFiles)) {
            $GLOBALS['FILES_ADDED'][$filename] = getFileHash($dirNewBase . $filename);
        }
    }

    // Recursively go into the subdirectories
    foreach ($newDirs as $dirname) {
        compareDirectories($dirOldBase, $dirNewBase, $dirname . '/');
    }
    foreach ($oldDirs as $dirname) {
        if (!in_array($dirname, $newDirs)) {
            compareDirectories($dirOldBase, $dirNewBase, $dirname . '/');
        }
    }
}

compareDirectories($dirOld, $dirNew, '');

$zipfile = new ZipArchive();
if ($zipfile->open($updateFile, ZipArchive::CREATE) !== true) {
    die("Could not open the ZIP file");
}
foreach (array_keys($GLOBALS["FILES_ADDED"]) as $file) {
    $zipfile->addFile($dirNew . $file, $file);
}
foreach (array_keys($GLOBALS["FILES_UPDATED"]) as $file) {
    $zipfile->addFile($dirNew . $file, $file);
}

$updateJson = "
\"files_updated\": " . json_encode($GLOBALS['FILES_UPDATED'], JSON_PRETTY_PRINT | JSON_FORCE_OBJECT) . ",
\"files_added\": " . json_encode($GLOBALS['FILES_ADDED'], JSON_PRETTY_PRINT | JSON_FORCE_OBJECT) . ",
\"files_deleted\": " . json_encode($GLOBALS['FILES_DELETED']);
$updateJson = "{" . str_replace("\n", "\n    ", $updateJson) . "\n}";
$zipfile->addFromString('update.json', $updateJson);

$secretKey = base64_decode(file_get_contents(__DIR__ . '/../config/update-private.key'));
$signature = base64_encode(sodium_crypto_sign_detached($updateJson, $secretKey));
$zipfile->addFromString('update.json.signature', $signature);

$zipfile->close();

$zipContent = file_get_contents($updateFile);
echo "Template for the update definition:\n" .
    json_encode([
        [
            "type"      => "patch",
            "version"   => "",
            "changelog" => "",
            "url"       => "",
            "filesize"  => strlen($zipContent),
            "signature" => base64_encode(sodium_crypto_generichash($zipContent)),
        ]
    ], JSON_PRETTY_PRINT);
