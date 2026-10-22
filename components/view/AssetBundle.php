<?php

declare(strict_types=1);

namespace app\components\view;

/**
 * A directory of static files of a plugin (CSS, JavaScript, images, fonts), made publicly accessible by copying
 * it to web/assets/ on first use (see AssetPublisher).
 *
 * Register a bundle with View::registerAssetBundle() to include its CSS and JavaScript files in the page.
 * To only link to its files (e.g. images), instantiate it and use getBaseUrl() / getFileUrl().
 */
abstract class AssetBundle
{
    /** @var string[] CSS files, relative to the source directory */
    public array $css = [];

    /** @var string[] JavaScript files, relative to the source directory */
    public array $js = [];

    private ?string $baseUrl = null;

    /**
     * The absolute path of the directory containing the files.
     */
    abstract public function getSourcePath(): string;

    /**
     * The public URL of the published directory, without trailing slash.
     */
    public function getBaseUrl(): string
    {
        if ($this->baseUrl === null) {
            $this->baseUrl = AssetPublisher::publish($this->getSourcePath());
        }

        return $this->baseUrl;
    }

    public function getFileUrl(string $file): string
    {
        return $this->getBaseUrl() . '/' . $file;
    }
}
