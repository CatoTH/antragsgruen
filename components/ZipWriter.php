<?php

namespace app\components;

class ZipWriter
{
    /** @var \ZipArchive */
    private $archive;

    /** @var string */
    private $zipFile;

    public function __construct()
    {
        /** @var \app\models\settings\AntragsgruenApp $params */
        $params        = \yii::$app->params;
        $this->zipFile = $params->tmpDir . uniqid('zip-');
        $this->archive = new \ZipArchive();
        if ($this->archive->open($this->zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("cannot open <$this->zipFile>\n");
        }
    }

    /**
     * @param string $filename
     * @param string $content
     */
    public function addFile($filename, $content)
    {
        $this->archive->addFromString($filename, $content);
    }

    /**
     * @return string
     */
    public function getContentAndFlush()
    {
        $this->archive->close();

        $content = file_get_contents($this->zipFile);
        unlink($this->zipFile);
        return $content;
    }
}
