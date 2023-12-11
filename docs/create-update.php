#!/usr/bin/env php
<?php

# Call:
#  docs/create-update.php dist/antragsgruen-3.9.0a1 dist/antragsgruen-3.9.0a2 updates/ --skip-changelog

if (count($argv) < 4) {
    die("Call: ./create-update.php [directory old] [directory new] [update directory] [--skip-changelog]\n");
}

$dirOld        = $argv[1];
$dirNew        = $argv[2];
$dirUpdate     = $argv[3];
$skipChangelog = in_array('--skip-changelog', $argv);
if ($dirOld[strlen($dirOld) - 1] !== '/') {
    $dirOld .= '/';
}
if ($dirNew[strlen($dirNew) - 1] !== '/') {
    $dirNew .= '/';
}
if ($dirUpdate[strlen($dirUpdate) - 1] !== '/') {
    $dirUpdate .= '/';
}

if (!file_exists($dirOld) || !is_dir($dirOld)) {
    die("$dirOld is not a directory");
}
if (!file_exists($dirNew) || !is_dir($dirNew)) {
    die("$dirNew is not a directory");
}
if (!file_exists($dirUpdate) || !is_dir($dirUpdate)) {
    die("$dirUpdate is not a directory");
}

$GLOBALS["FILES_ADDED"]       = [];
$GLOBALS["FILES_ADDED_MD5"]   = [];
$GLOBALS["FILES_UPDATED"]     = [];
$GLOBALS["FILES_UPDATED_MD5"] = [];
$GLOBALS["FILES_DELETED"]     = [];

function getDirContent(string $dirBase, string $dirRelative): array
{
    if (!file_exists($dirBase . '/' . $dirRelative)) {
        return [[], []];
    }
    $dirh  = opendir($dirBase . '/' . $dirRelative);
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

function filesAreEqual(string $file1, string $file2): bool
{
    if (filesize($file1) !== filesize($file2)) {
        return false;
    }
    return file_get_contents($file1) === file_get_contents($file2);
}

function getFileHash(string $file): string
{
    $content    = file_get_contents($file);
    $binaryHash = sodium_crypto_generichash($content);
    return base64_encode($binaryHash);
}

function compareDirectories(string $dirOldBase, string $dirNewBase, string $dirRelative): void
{
    list($oldDirs, $oldFiles) = getDirContent($dirOldBase, $dirRelative);
    list($newDirs, $newFiles) = getDirContent($dirNewBase, $dirRelative);

    // Search for changed files
    foreach ($oldFiles as $filename) {
        if (!in_array($filename, $newFiles)) {
            $GLOBALS['FILES_DELETED'][] = $filename;
        } elseif (!filesAreEqual($dirOldBase . $filename, $dirNewBase . $filename)) {
            $GLOBALS['FILES_UPDATED'][$filename]     = getFileHash($dirNewBase . $filename);
            $GLOBALS['FILES_UPDATED_MD5'][$filename] = md5(file_get_contents($dirNewBase . $filename));
        }
    }
    foreach ($newFiles as $filename) {
        if (!in_array($filename, $oldFiles)) {
            $GLOBALS['FILES_ADDED'][$filename]     = getFileHash($dirNewBase . $filename);
            $GLOBALS['FILES_ADDED_MD5'][$filename] = md5(file_get_contents($dirNewBase . $filename));
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

function readVersionFromDirectory(string $dir): string
{
    $defines = file_get_contents($dir . 'config/defines.php');
    $parts   = explode('ANTRAGSGRUEN_VERSION', $defines);
    $parts   = explode('\'', $parts[1]);
    $version = $parts[2];
    if (strlen($version) < 4 || strlen($version) > 20 || $version < 3) {
        throw new \Exception('Could not read version from defines.php: ' . $defines);
    }
    return $version;
}

function readChangelog(string $dirNew, string $versionOld, string $versionNew): string
{
    $defines  = explode("\n", file_get_contents($dirNew . 'History.md'));
    $lines    = [];
    $foundNew = false;
    $foundOld = false;
    for ($i = 0; $i < count($defines) && !$foundOld; $i++) {
        $line = $defines[$i];
        if (str_contains($line, '## Version ' . $versionNew)  ) {
            $foundNew = true;
            continue;
        }
        if (str_contains($line, '## Version ' . $versionOld)  ) {
            $foundOld = true;
            continue;
        }
        if ($foundNew && !$foundOld && trim($line) !== '') {
            $lines[] = $line;
        }
    }
    if ($foundNew && $foundOld) {
        return implode("\n", $lines);
    } else {
        throw new \Exception("Could not read the changelog");
    }
}

/**
 * @param string $dirNew
 * @return string
 * @throws Exception
 */
function readRequiredPhpVersion($dirNew)
{
    require_once($dirNew . 'config/defines.php');
    return '>=' . ANTRAGSGRUEN_MIN_PHP_VERSION;
}

compareDirectories($dirOld, $dirNew, '');
$versionOld = readVersionFromDirectory($dirOld);
$versionNew = readVersionFromDirectory($dirNew);
if ($skipChangelog) {
    $changelog = '';
} else {
    $changelog = readChangelog($dirNew, $versionOld, $versionNew);
}

$phpRequirement = readRequiredPhpVersion($dirNew);
$requirements   = [
    "php" => $phpRequirement,
];

$updateFilename = $versionOld . "-" . $versionNew . ".zip";
$updateDefFilename = $versionOld;

$zipfile = new ZipArchive();
if ($zipfile->open($dirUpdate . $updateFilename, ZipArchive::CREATE) !== true) {
    die("Could not open the ZIP file");
}
foreach (array_keys($GLOBALS["FILES_ADDED"]) as $file) {
    $zipfile->addFile($dirNew . $file, $file);
}
foreach (array_keys($GLOBALS["FILES_UPDATED"]) as $file) {
    $zipfile->addFile($dirNew . $file, $file);
}

$updateJson = "
\"from_version\": " . json_encode($versionOld) . ",
\"to_version\": " . json_encode($versionNew) . ",
\"requirements\": " . json_encode($requirements, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT) . ",
\"changelog\": " . json_encode($changelog) . ",
\"files_updated\": " . json_encode($GLOBALS['FILES_UPDATED'], JSON_PRETTY_PRINT | JSON_FORCE_OBJECT) . ",
\"files_updated_md5\": " . json_encode($GLOBALS['FILES_UPDATED_MD5'], JSON_PRETTY_PRINT | JSON_FORCE_OBJECT) . ",
\"files_added\": " . json_encode($GLOBALS['FILES_ADDED'], JSON_PRETTY_PRINT | JSON_FORCE_OBJECT) . ",
\"files_added_md5\": " . json_encode($GLOBALS['FILES_ADDED_MD5'], JSON_PRETTY_PRINT | JSON_FORCE_OBJECT) . ",
\"files_deleted\": " . json_encode($GLOBALS['FILES_DELETED'], JSON_PRETTY_PRINT);
$updateJson = "{" . str_replace("\n", "\n    ", $updateJson) . "\n}";
$zipfile->addFromString('update.json', $updateJson);

$secretKey = base64_decode(file_get_contents($_SERVER['HOME'] . '/.local/antragsgruen-update-private.key'));
$signature = base64_encode(sodium_crypto_sign_detached($updateJson, $secretKey));
$zipfile->addFromString('update.json.signature', $signature);

$zipfile->close();

$zipContent = file_get_contents($dirUpdate . $updateFilename);
$updateJson = json_encode([
    [
        "type"      => "patch",
        "version"   => $versionNew,
        "changelog" => $changelog,
        "url"       => "https://antragsgruen.de/updates/" . $updateFilename,
        "filesize"  => strlen($zipContent),
        "signature" => base64_encode(sodium_crypto_generichash($zipContent)),
    ]
], JSON_PRETTY_PRINT);
echo "Template for the update definition:\n" . $updateJson . "\n";
file_put_contents($dirUpdate . $updateDefFilename, $updateJson);
