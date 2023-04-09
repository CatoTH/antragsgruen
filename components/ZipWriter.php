<?php

namespace app\components;

use app\models\settings\AntragsgruenApp;

class ZipWriter
{
    private \ZipArchive $archive;
    private string $zipFile;

    public function __construct()
    {
        $this->zipFile = AntragsgruenApp::getInstance()->getTmpDir() . uniqid('zip-');
        $this->archive = new \ZipArchive();
        if ($this->archive->open($this->zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("cannot open <$this->zipFile>\n");
        }
    }

    public function addFile(string $filename, string $content): void
    {
        $this->archive->addFromString($filename, $content);
    }

    public function getContentAndFlush(): string
    {
        $this->archive->close();

        $content = (string)file_get_contents($this->zipFile);
        unlink($this->zipFile);
        return $content;
    }
}
