<?php

declare(strict_types=1);

namespace app\components\view;

use app\components\StaticResourceTools;

/**
 * Copies asset directories of plugins to web/assets/, where they can be served by the web server.
 *
 * Each directory is copied once into a subdirectory whose name is derived from its path, modification time and
 * the Antragsgrün version, so a new version or added files lead to a new copy. Copying goes to a temporary
 * directory first, which is then renamed, so concurrent requests never see a partially copied directory.
 *
 * If static resources are served from a CDN (resourceBase is an absolute URL), the files are expected to be
 * deployed there under plugins/<pluginId>/; they are still copied locally, but linked from the CDN.
 */
final class AssetPublisher
{
    /** @var array<string, string> Source path => published base URL */
    private static array $published = [];

    public static function publish(string $sourcePath): string
    {
        $sourcePath = rtrim($sourcePath, '/' . DIRECTORY_SEPARATOR);
        if (isset(self::$published[$sourcePath])) {
            return self::$published[$sourcePath];
        }
        if (!is_dir($sourcePath)) {
            throw new \InvalidArgumentException('Asset directory does not exist: ' . $sourcePath);
        }

        $resourceBase = StaticResourceTools::getResolvedResourceBase();
        $isCdn = !str_starts_with($resourceBase, '/');
        $dirName = ($isCdn ? self::getCdnDirectoryName($sourcePath) : self::hash($sourcePath));

        $targetPath = View::getAppPath() . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $dirName;
        if (!is_dir($targetPath)) {
            self::copyAtomically($sourcePath, $targetPath);
        }

        self::$published[$sourcePath] = ($isCdn ? rtrim($resourceBase, '/') . '/' . $dirName : $resourceBase . 'assets/' . $dirName);

        return self::$published[$sourcePath];
    }

    private static function hash(string $sourcePath): string
    {
        return sprintf('%x', crc32($sourcePath . '|' . filemtime($sourcePath) . '|' . ANTRAGSGRUEN_VERSION));
    }

    /**
     * "plugins/<pluginId>": two directory levels, so relative links in CSS files (like ../../fonts) work
     * both for CDN and local deployments.
     */
    private static function getCdnDirectoryName(string $sourcePath): string
    {
        $parts = explode('plugins' . DIRECTORY_SEPARATOR, $sourcePath, 2);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('Only plugin assets can be served from a CDN: ' . $sourcePath);
        }

        return 'plugins/' . explode(DIRECTORY_SEPARATOR, $parts[1])[0];
    }

    private static function copyAtomically(string $sourcePath, string $targetPath): void
    {
        $parentDir = dirname($targetPath);
        if (!is_dir($parentDir) && !@mkdir($parentDir, 0775, true) && !is_dir($parentDir)) {
            throw new \RuntimeException('Could not create the asset directory: ' . $parentDir);
        }

        $tmpPath = $targetPath . '.tmp-' . bin2hex(random_bytes(4));
        self::copyDirectory($sourcePath, $tmpPath);
        if (!@rename($tmpPath, $targetPath)) {
            // Another request published the same directory in the meantime
            self::removeDirectory($tmpPath);
            if (!is_dir($targetPath)) {
                throw new \RuntimeException('Could not publish the asset directory: ' . $targetPath);
            }
        }
    }

    private static function copyDirectory(string $source, string $target): void
    {
        if (!@mkdir($target, 0775, true) && !is_dir($target)) {
            throw new \RuntimeException('Could not create the asset directory: ' . $target);
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        /** @var \SplFileInfo $item */
        foreach ($iterator as $item) {
            $targetItem = $target . DIRECTORY_SEPARATOR . substr($item->getPathname(), strlen($source) + 1);
            if ($item->isDir()) {
                if (!@mkdir($targetItem, 0775) && !is_dir($targetItem)) {
                    throw new \RuntimeException('Could not create the asset directory: ' . $targetItem);
                }
            } elseif (!copy($item->getPathname(), $targetItem)) {
                throw new \RuntimeException('Could not copy the asset file: ' . $item->getPathname());
            }
        }
    }

    private static function removeDirectory(string $path): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        /** @var \SplFileInfo $item */
        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($path);
    }
}
