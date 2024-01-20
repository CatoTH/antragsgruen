<?php

declare(strict_types=1);

namespace app\components\html2pdf;

use app\models\exceptions\Internal;
use app\models\sectionTypes\Image;
use app\models\settings\AntragsgruenApp;
use yii\helpers\Html;

class Html2PdfConverter
{
    private const SUPPORTED_IMAGE_FORMATS = [
        'image/png',
        'image/jpg',
        'image/jpeg',
        'image/gif',
    ];

    public function __construct(
        private AntragsgruenApp $app,
    ) {
    }

    /**
     * @param Content[] $contents
     * @throws Internal
     */
    public function createPDF(array $contents): string
    {
        if (!$this->app->xelatexPath && !$this->app->lualatexPath) {
            throw new Internal('LaTeX/XeTeX-Export is not enabled');
        }

        $filenameBase = $this->app->getTmpDir() . uniqid('motion-html');

        $imageFiles  = [];
        $imageHashes = [];

        $html = '';
        foreach ($contents as $content) {
            $html .= self::createContentString($content);

            foreach ($content->imageData as $fileName => $fileData) {
                if (!preg_match('/^[a-z0-9_-]+(\.[a-z0-9_-]+)?$/siu', $fileName)) {
                    throw new Internal('Invalid image filename');
                }
                file_put_contents($this->app->getTmpDir() . $fileName, $fileData);
                $imageHashes[$this->app->getTmpDir() . $fileName] = md5($fileData);

                $imageFiles[] = $this->app->getTmpDir() . $fileName;
            }
        }

        file_put_contents($filenameBase . '.html', '<!doctype html>
            <html lang="de">
            <head>
                <meta charset="utf-8">
                <meta name="author" content="">
                <title>Test</title>
            </head>
        <body>' . $html . '</body></html>');

        $cmd = $this->app->weasyprintPath;
        $cmd .= ' ' . escapeshellarg($filenameBase . '.html');
        $cmd .= ' ' . escapeshellarg($filenameBase . '.pdf');
        $cmd .= ' -s ' . escapeshellarg(__DIR__ . '/../../assets/html2pdf/application.css');

        shell_exec($cmd);

        if (!file_exists($filenameBase . '.pdf')) {
            throw new Internal('An error occurred while creating the PDF: ' . $cmd);
        }
        $pdf = (string)file_get_contents($filenameBase . '.pdf');

        unlink($filenameBase . '.html');
        foreach ($imageFiles as $file) {
            unlink($file);
        }

        return $pdf;
    }

    public static function createContentString(Content $content): string
    {
        $template                         = $content->template;

        $replaces                         = [];
        $replaces['%TITLE%']              = Html::encode($content->title);
        $replaces['%TITLE_PREFIX%']        = Html::encode($content->titlePrefix);
        $replaces['%TITLE_LONG%']         = Html::encode($content->titleLong);
        $replaces['%TITLE_RAW%']          = Html::encode($content->titleRaw);
        $replaces['%AUTHOR%']             = Html::encode($content->author);
        $replaces['%MOTION_DATA_TABLE%']  = $content->motionDataTable;
        $replaces['%TEXT%']               = self::createTextWithRightString($content->textMain, $content->textRight);
        $replaces['%INTRODUCTION_BIG%']   = Html::encode($content->introductionBig);
        $replaces['%INTRODUCTION_SMALL%'] = Html::encode($content->introductionSmall);
        $replaces['%PAGE_LABEL%']         = Html::encode(\Yii::t('export', 'pdf_page_label'));
        $replaces['%INITIATOR_LABEL%']    = Html::encode(\Yii::t('export', 'Initiators'));
        $replaces['%PUBLICATION_DATE%']   = Html::encode($content->publicationDate);
        $replaces['%MOTION_TYPE%']        = Html::encode($content->typeName);
        $replaces['%TITLE_LABEL%']        = Html::encode(\Yii::t('export', 'title'));

        $replaces['%APP_TITLE%'] = Html::encode(\Yii::t('export', 'pdf_app_title'));
        if ($content->agendaItemName) {
            $replaces['%APP_TOP_LABEL%'] = Html::encode(\Yii::t('export', 'pdf_app_top_label'));
            $replaces['%APP_TOP%']       = Html::encode($content->agendaItemName);
        } else {
            $replaces['%APP_TOP_LABEL%'] = '';
            $replaces['%APP_TOP%']       = '';
        }
        if ($content->logoData && in_array($content->logoData[0], self::SUPPORTED_IMAGE_FORMATS)) {
            $fileExt = Image::getFileExtensionFromMimeType($content->logoData[0]);
            $filenameBase = uniqid('motion-pdf-image') . '.' . $fileExt;
            $tmpPath = AntragsgruenApp::getInstance()->getTmpDir() . $filenameBase;
            $replaces['%LOGO%'] = '\includegraphics[width=4.9cm]{' . $tmpPath . '}';
            $content->imageData[$filenameBase] = $content->logoData[1];
        } else {
            $replaces['%LOGO%'] = '';
        }
        $template = str_replace(array_keys($replaces), array_values($replaces), $template);
        return $template;
    }

    public static function createTextWithRightString(string $textMain, string $textRight): string
    {
        if (trim($textRight) === '' || trim($textRight) === '<br>') {
            return "<main>" . $textMain . "</main>\n";
        }

        return "<aside>" . $textRight . "</aside>\n<main>" . $textMain . "</main>\n";
    }
}
