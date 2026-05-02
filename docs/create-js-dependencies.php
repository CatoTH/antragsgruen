#!/usr/bin/env php
<?php

# Call:
#  docs/create-js-dependencies.php [cdn-tag]

if (count($argv) < 2) {
    die("Call: ./create-js-dependencies.php [cdn-tag]\n");
}

$cdnTag = $argv[1];
$localDir  = './web';
$integrity = [];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($localDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$relevantFiles = [];
foreach ($files as $file) {
    if (!$file->isFile()) {
        continue;
    }
    if ($file->getExtension() !== 'js') {
        continue;
    }
    if (str_contains($file->getPathname(), "ckeditor")) {
        continue;
    }
    if (str_contains($file->getPathname(), "assets")) {
        continue;
    }
    $relevantFiles[] = $file;
}

// --- Pass 1: collect integrity hashes and direct imports ---

/** @var array<string, string[]> $directDeps  relative-path → list of relative-path deps */
$directDeps = [];

$absLocalDir = realpath($localDir);

foreach ($relevantFiles as $file) {
    $localPath = $file->getPathname();
    $contents  = file_get_contents($localPath);

    if ($contents === false) {
        fwrite(STDERR, "Warning: could not read {$localPath}\n");
        continue;
    }

    $hash = 'sha384-' . base64_encode(hash('sha384', $contents, binary: true));

    // Build the CDN URL: strip the local base dir prefix and normalise separators
    $relative = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', substr($localPath, strlen($localDir))), '/');

    $integrity[$relative] = $hash;

    // Scan for static and dynamic ES module imports:
    //   import ... from './foo.js'
    //   import ... from '/foo.js'
    //   import('./foo.js')
    //   export ... from './foo.js'
    preg_match_all(
        '/(?:import\s*(?:[^"\']*?\s+from\s*)?|export\s+[^"\']*?\s+from\s*|import\s*\()\s*["\']([^"\']+\.js)["\']/',
        $contents,
        $matches
    );

    $fileDir      = dirname($localPath);
    $resolvedDeps = [];

    foreach ($matches[1] as $importPath) {
        // Handle relative imports (./foo, ../foo) and absolute imports (/foo).
        // Skip bare specifiers like 'lodash'.
        if (!str_starts_with($importPath, '.') && !str_starts_with($importPath, '/')) {
            continue;
        }

        // Absolute imports are resolved against $localDir;
        // relative ones are resolved against the importing file's directory.
        $base      = str_starts_with($importPath, '/') ? $absLocalDir : $fileDir;
        $absImport = realpath($base . '/' . $importPath);

        if ($absImport === false) {
            fwrite(STDERR, "Warning: could not resolve import '{$importPath}' in {$localPath}\n");
            continue;
        }

        // Make it relative to $localDir
        $relImport      = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', substr($absImport, strlen($absLocalDir))), '/');
        $resolvedDeps[] = $relImport;
    }

    $directDeps[$relative] = array_unique($resolvedDeps);
}

ksort($integrity);

// --- Pass 2: resolve transitive dependencies ---

/**
 * Recursively collects all transitive dependencies for a given file.
 *
 * @param  string                      $file       Relative path of the file to resolve.
 * @param  array<string, list<string>> $directDeps Map of direct dependencies.
 * @param  array<string, list<string>> &$cache     Memoisation cache.
 * @param  list<string>                $stack      Current resolution stack (cycle detection).
 * @return list<string> All transitive deps, excluding the file itself.
 */
function resolveTransitiveDeps(string $file, array $directDeps, array &$cache, array $stack = []): array
{
    if (isset($cache[$file])) {
        return $cache[$file];
    }

    // Cycle guard
    if (in_array($file, $stack, true)) {
        fwrite(STDERR, "Warning: circular dependency detected at '{$file}'\n");
        return [];
    }

    $stack[] = $file;
    $all     = [];

    foreach ($directDeps[$file] ?? [] as $dep) {
        $all[] = $dep;
        foreach (resolveTransitiveDeps($dep, $directDeps, $cache, $stack) as $transitive) {
            $all[] = $transitive;
        }
    }

    $result       = array_values(array_unique($all));
    $cache[$file] = $result;

    return $result;
}

$cache          = [];
$transitiveDeps = [];

foreach (array_keys($directDeps) as $file) {
    $deps = resolveTransitiveDeps($file, $directDeps, $cache);
    sort($deps);
    $transitiveDeps[$file] = $deps;
}

ksort($transitiveDeps);

// --- Pass 3: collect translation keys ---

$validBases = array_map(
    fn($f) => basename($f, '.php') === 'consultation' ? 'con' : basename($f, '.php'),
    glob(__DIR__ . '/../messages/en/*.php') ?: []
);

$translations = [];

foreach ($relevantFiles as $file) {
    $localPath = $file->getPathname();
    $contents  = file_get_contents($localPath);

    if (str_contains($file->getPathname(), "/npm/")) {
        continue;
    }
    if (str_contains($file->getPathname(), "pdfjs-viewer")) {
        continue;
    }
    if (str_contains($file->getPathname(), "bootstrap-datetimepicker")) {
        continue;
    }
    if (str_contains($file->getPathname(), "jquery-4")) {
        continue;
    }
    if (str_contains($file->getPathname(), "jscolor.js")) {
        continue;
    }

    if ($contents === false) {
        continue; // already warned in pass 1
    }

    $relative = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', substr($localPath, strlen($localDir))), '/');

    // Match array literals: ['base', 'key', ...optional...]
    // Captures the first two string arguments.
    preg_match_all(
        '/\[\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"](?:\s*,|\s*\])/',
        $contents,
        $matches,
        PREG_SET_ORDER
    );

    $keys = [];
    foreach ($matches as $match) {
        $base = $match[1];
        $key  = $match[2];

        if (!in_array($base, $validBases, true)) {
            continue;
        }

        $keys[] = [$base, $key];
    }

    if (!empty($keys)) {
        // Deduplicate while preserving order
        $seen   = [];
        $unique = [];
        foreach ($keys as $k) {
            $sig = $k[0] . "\0" . $k[1];
            if (!isset($seen[$sig])) {
                $seen[$sig] = true;
                $unique[]   = $k;
            }
        }
        $translations[$relative] = $unique;
    }
}

ksort($translations);

// --- Output ---

$data = [
    'cdn_tag'      => $cdnTag,
    'integrity'    => $integrity,
    'dependencies' => $transitiveDeps,
    'translations' => $translations,
];

file_put_contents(
    __DIR__ . '/../config/js-dependencies.php',
    '<?php return ' . var_export($data, true) . ';' . PHP_EOL
);
