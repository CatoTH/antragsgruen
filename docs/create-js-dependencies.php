#!/usr/bin/env php
<?php

$localDir  = './web';
$cdnBase   = 'https://cdn.motion.tools';
$integrity = [];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($localDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

// --- Pass 1: collect integrity hashes and direct imports ---

/** @var array<string, string[]> $directDeps  relative-path → list of relative-path deps */
$directDeps = [];

$absLocalDir = realpath($localDir);

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
    if (str_contains($localPath, "assets")) {
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
    if (!empty($deps)) {
        sort($deps);
        $transitiveDeps[$file] = $deps;
    }
}

ksort($transitiveDeps);

// --- Output ---

file_put_contents(__DIR__ . '/../assets/js-dependencies.json', json_encode([
    'integrity'    => $integrity,
    'dependencies' => $transitiveDeps,
], JSON_PRETTY_PRINT));
