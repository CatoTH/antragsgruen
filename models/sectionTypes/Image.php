<?php

namespace app\models\sectionTypes;

use app\components\{latex\Content as LatexContent, html2pdf\Content as HtmlToPdfContent, Tools, UrlHelper};
use app\models\db\{AmendmentSection, Consultation, ConsultationSettingsMotionSection, MotionSection};
use app\models\exceptions\{FormError, Internal};
use app\models\settings\AntragsgruenApp;
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};
use yii\helpers\Html;
use CatoTH\HTML2OpenDocument\Text;

class Image extends ISectionType
{
    private function supportsPdf(): bool
    {
        return (AntragsgruenApp::getInstance()->imageMagickPath !== null && file_exists(AntragsgruenApp::getInstance()->imageMagickPath));
    }

    public function getImageUrl(bool $absolute = false, bool $showAlways = false): ?string
    {
        $app = AntragsgruenApp::getInstance();
        $externallySavedData = ($app->binaryFilePath !== null && trim($app->binaryFilePath) !== '');

        /** @var MotionSection $section */
        $section = $this->section;
        $motion  = $section->getMotion();
        if (!$motion) {
            return null;
        }
        if (!$externallySavedData && !$section->getData()) {
            // If the data IS saved externally, we always create an URL, as it might be saved on a path
            // not accessible by the current process.
            return null;
        }

        $params = [
            '/motion/viewimage',
            'motionSlug' => $section->getMotion()->getMotionSlug(),
            'sectionId'  => $section->sectionId
        ];
        if ($showAlways) {
            $params['showAlways'] = $section->getShowAlwaysToken();
        }
        $url = UrlHelper::createUrl($params, $motion->getMyConsultation());

        if ($absolute) {
            $url = UrlHelper::absolutizeLink($url);
        }

        return $url;
    }

    public static function getFileExtensionFromMimeType(string $mime): ?string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/jpg', 'image/jpeg' => 'jpeg',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            default => null,
        };
    }

    public function getMotionFormField(): string
    {
        $type = $this->section->getSettings();
        $url  = $this->getImageUrl();
        $str  = '<section class="section' . $this->section->sectionId . ' type' . static::TYPE_IMAGE . '">';
        if ($url) {
            $str      .= '<img src="' . Html::encode($this->getImageUrl()) . '" alt="' . \Yii::t('motion', 'image_current') . '" class="currentImage">';
            $required = '';
        } else {
            $required = match ($type->required) {
                ConsultationSettingsMotionSection::REQUIRED_YES => 'required',
                ConsultationSettingsMotionSection::REQUIRED_ENCOURAGED => 'data-encouraged="true"',
                default => '',
            };
        }
        $str .= '<div class="form-group">';

        $str .= $this->getFormLabel();
        $str .= $this->getHintsAfterFormLabel();

        $maxSize = (string) floor(Tools::getMaxUploadSize() / 1024 / 1024);
        $str     .= '<div class="maxLenHint"><span class="icon glyphicon glyphicon-info-sign" aria-hidden="true"></span> ';
        $str     .= str_replace('%MB%', $maxSize, \Yii::t('motion', 'max_size_hint'));
        $str     .= '</div>';

        $inputTypes = ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'];
        if ($this->supportsPdf()) {
            $inputTypes[] = 'application/pdf';
        }

        $str .= '<input type="file" class="form-control" id="sections_' . $type->id . '" ' . $required .
                ' accept="' . implode(', ', $inputTypes) . '"' .
                ' name="sections[' . $type->id . ']">';
        if ($url && $type->required !== ConsultationSettingsMotionSection::REQUIRED_YES) {
            $str .= '<label class="deleteImage"><input type="checkbox" name="sectionDelete[' . $type->id . ']">';
            $str .= \Yii::t('motion', 'img_delete');
            $str .= '</label>';
        }
        $str .= '</div>';
        $str .= '</section>';
        return $str;
    }

    public function getAmendmentFormField(): string
    {
        return $this->getMotionFormField();
    }

    public static function getOptimizedImage(string $filename, string $targetType): string
    {
        $app = AntragsgruenApp::getInstance();
        if ($app->imageMagickPath === null) {
            return (file_exists($filename) ? (string)file_get_contents($filename) : '');
        } elseif (!file_exists($app->imageMagickPath)) {
            throw new Internal('ImageMagick not correctly set up');
        }

        $tmpfile = $app->getTmpDir() . uniqid('image-conv-') . "." . $targetType;
        exec($app->imageMagickPath . ' -strip ' . escapeshellarg($filename) . ' ' . escapeshellarg($tmpfile));
        $converted = (file_exists($tmpfile) ? (string)file_get_contents($tmpfile) : '');
        unlink($tmpfile);
        return $converted;
    }

    /*
     * If the image is more than twice as big as the specified size, it is reduced to this size.
     * A slightly exceeding size is tolerated, as reducing the size is rather comptation intensive.
     *
     * Hint: this function returns the raw image data, not the base64-encoded version.
     *
     * This only is performed if ImageMagick is installed and configured.
     */
    public function resizeIfMassivelyTooBig(int $width, int $height, string $fileExtension): string
    {
        $metadata = json_decode($this->section->metadata, true);
        if ($metadata['width'] < $width * 2 && $metadata['height'] < $height * 2) {
            return $this->section->getData();
        }

        $app = AntragsgruenApp::getInstance();
        if ($app->imageMagickPath === null) {
            return $this->section->getData();
        } elseif (!file_exists($app->imageMagickPath)) {
            throw new Internal('ImageMagick not correctly set up');
        }

        $tmpfile1 = $app->getTmpDir() . uniqid('image-conv-') . "." . $fileExtension;
        $tmpfile2 = $app->getTmpDir() . uniqid('image-conv-') . "." . $fileExtension;
        file_put_contents($tmpfile1, $this->section->getData());

        exec($app->imageMagickPath . ' -strip -geometry ' . IntVal($width) . 'x' . IntVal($height) . ' '
            . escapeshellarg($tmpfile1) . ' ' . escapeshellarg($tmpfile2));

        $converted = (file_exists($tmpfile2) ? (string)file_get_contents($tmpfile2) : '');
        unlink($tmpfile1);
        unlink($tmpfile2);

        return $converted;
    }

    private function convertPdfToTmpPng(string $pdfFilename): string
    {
        $app = AntragsgruenApp::getInstance();
        $pngFilename = $app->getTmpDir() . uniqid('pdf-') . '.png';
        exec($app->imageMagickPath . ' -density 150 ' . escapeshellarg($pdfFilename . '[0]') . ' ' . escapeshellarg($pngFilename));
        return $pngFilename;
    }

    private function convertGifToPngData(string $gifData): string
    {
        $app = AntragsgruenApp::getInstance();
        $gifFilename = $app->getTmpDir() . uniqid('pdf-') . '.gif';
        $pngFilename = $app->getTmpDir() . uniqid('pdf-') . '.png';
        file_put_contents($gifFilename, $gifData);
        exec($app->imageMagickPath . ' ' . escapeshellarg($gifFilename . '[0]') . ' ' . escapeshellarg($pngFilename));
        $data = (string)file_get_contents($pngFilename);
        unlink($pngFilename);
        unlink($gifFilename);
        return $data;
    }

    /**
     * @param array $data
     * @throws FormError
     */
    public function setMotionData($data): void
    {
        if (!isset($data['tmp_name'])) {
            throw new FormError('Invalid Image');
        }
        $mime = mime_content_type($data['tmp_name']);
        $toDeleteTmpFiles = [];
        if ($mime === 'application/pdf' && $this->supportsPdf()) {
            $pngFile = $this->convertPdfToTmpPng($data['tmp_name']);
            $data['tmp_name'] = $pngFile;
            $toDeleteTmpFiles[] = $pngFile;
            $mime = 'image/png';
        }

        $imagedata = getimagesize($data['tmp_name']);
        if (!$imagedata || !$mime) {
            throw new FormError('Could not read image.');
        }

        $fileExt = static::getFileExtensionFromMimeType($mime);
        if ($fileExt === null) {
            throw new FormError('Image type not supported. Supported formats are: JPEG, PNG and GIF.');
        }
        $optimized = static::getOptimizedImage($data['tmp_name'], $fileExt);

        $metadata = [
            'width'    => $imagedata[0],
            'height'   => $imagedata[1],
            'filesize' => strlen($optimized),
            'mime'     => $mime
        ];
        $this->section->setData($optimized);
        $this->section->metadata = json_encode($metadata, JSON_THROW_ON_ERROR);

        foreach ($toDeleteTmpFiles as $deleteTmpFile) {
            unlink($deleteTmpFile);
        }
    }

    public function deleteMotionData(): void
    {
        $this->section->setData('');
        $this->section->metadata = '';
    }

    /**
     * @param array $data
     * @throws FormError
     */
    public function setAmendmentData($data): void
    {
        $this->setMotionData($data);
    }

    public function getAmendmentFormatted(string $htmlIdPrefix = ''): string
    {
        return ''; // @TODO
    }

    public function getSimple(bool $isRight, bool $showAlways = false): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $url     = $this->getImageUrl($this->absolutizeLinks, $showAlways);

        return '<img src="' . Html::encode($url) . '" alt="' . Html::encode($this->getTitle()) . '">';
    }

    public function isEmpty(): bool
    {
        // Hint: when an image is set to amendable and no image is given for the amendment, then the data is copied
        // but no metadata is set. Don't show the image in this case, as nothing was to be changed anyway.
        $invalidAmendmentImageWorkaround = (is_a($this->section, AmendmentSection::class) && $this->section->metadata === null);
        return ($this->section->getData() === '' || $invalidAmendmentImageWorkaround);
    }

    public function showIfEmpty(): bool
    {
        return false;
    }

    public function isFileUploadType(): bool
    {
        return true;
    }

    /**
     * @return float[]
     */
    private function scaleSize(float $width, float $height, float $maxX, float $maxY): array
    {
        $scaleX = $maxX / $width;
        $scaleY = $maxY / $height;
        $scale  = min($scaleX, $scaleY);
        return [$scale * $width, $scale * $height];
    }

    public function printMotionToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        if ($this->isEmpty()) {
            return;
        }

        if ($this->section->getSettings()->printTitle) {
            $pdfLayout->printSectionHeading($this->getTitle());
        }

        $pdf->SetFont('Courier', '', 11);
        $pdf->Ln(7);

        $metadata = json_decode($this->section->metadata, true);
        $fileExt  = static::getFileExtensionFromMimeType($metadata['mime']);

        $extraSettings = $this->section->getSettings()->getSettingsObj();
        if ($extraSettings->imgMaxHeight > 0 && $extraSettings->imgMaxWidth > 0) {
            $maxHeight = $extraSettings->imgMaxHeight * 10;
            $maxWidth = $extraSettings->imgMaxWidth * 10;
        } else {
            $maxHeight = ($extraSettings->imgMaxHeight > 0 ? $extraSettings->imgMaxHeight * 10 : 60);
            $maxWidth  = ($extraSettings->imgMaxWidth > 0 ? $extraSettings->imgMaxWidth * 10 : 80);
        }

        if ($this->section->isLayoutRight()) {
            $size     = $this->scaleSize(floatval($metadata['width']), floatval($metadata['height']), floatval($maxWidth), floatval($maxHeight));
            $imageData = $this->resizeIfMassivelyTooBig(500, 1000, $fileExt);
        } else {
            $size     = $this->scaleSize(floatval($metadata['width']), floatval($metadata['height']), floatval($maxWidth), floatval($maxHeight));
            $imageData = $this->resizeIfMassivelyTooBig(1500, 3000, $fileExt);
        }

        $img = '@' . $imageData;
        $type = match ($metadata['mime']) {
            'image/png' => 'PNG',
            'image/jpg', 'image/jpeg' => 'JPEG',
            'image/gif' => 'GIF',
            default => '',
        };
        $pdf->Image($img, null, null, $size[0], $size[1], $type, '', '', true, 300, 'C');
        $pdf->Ln($size[1] + 7);
    }

    public function printAmendmentToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        $this->printMotionToPDF($pdfLayout, $pdf);
    }

    public function getMotionPlainText(): string
    {
        return ($this->isEmpty() ? '' : '[IMAGE]');
    }

    public function getAmendmentPlainText(): string
    {
        return ($this->isEmpty() ? '' : '[IMAGE]');
    }

    public function printMotionTeX(bool $isRight, LatexContent $content, Consultation $consultation): void
    {
        if ($this->isEmpty()) {
            return;
        }

        $params   = AntragsgruenApp::getInstance();
        $metadata = json_decode($this->section->metadata, true);

        $extraSettings = $this->section->getSettings()->getSettingsObj();
        $maxHeight     = ($extraSettings->imgMaxHeight > 0 ? $extraSettings->imgMaxHeight : null);

        $fileExt = static::getFileExtensionFromMimeType($metadata['mime']);
        if ($isRight) {
            $imageData = $this->resizeIfMassivelyTooBig(500, 1000, $fileExt);
        } else {
            $imageData = $this->resizeIfMassivelyTooBig(1500, 3000, $fileExt);
        }

        if ($fileExt === 'gif') {
            $imageData = $this->convertGifToPngData($imageData);
            $fileExt = 'png';
        }
        $filenameBase = uniqid('motion-pdf-image') . '.' . $fileExt;

        if ($isRight) {
            $maxWidth           = ($extraSettings->imgMaxWidth > 0 ? $extraSettings->imgMaxWidth : 4.9);
            $maxStr             = ($maxHeight ? 'max width=' . $maxWidth . 'cm,max height=' . $maxHeight . 'cm' : 'width=' . $maxWidth . 'cm');
            $content->textRight .= '\includegraphics[' . $maxStr . ']{' . $params->getTmpDir() . $filenameBase . '}' . "\n";
            $content->textRight .= '\newline' . "\n" . '\newline' . "\n";
        } else {
            $maxWidth          = ($extraSettings->imgMaxWidth > 0 ? $extraSettings->imgMaxWidth : 10);
            $maxStr            = ($maxHeight ? 'max width=' . $maxWidth . 'cm,max height=' . $maxHeight . 'cm' : 'width=' . $maxWidth . 'cm');
            $content->textMain .= '\includegraphics[' . $maxStr . ']{' . $params->getTmpDir() . $filenameBase . '}' . "\n";
            $content->textMain .= '\newline' . "\n" . '\newline' . "\n";
        }

        $content->imageData[$filenameBase] = $imageData;
    }

    public function printAmendmentTeX(bool $isRight, LatexContent $content): void
    {
        if ($isRight) {
            $content->textRight .= ($this->isEmpty() ? '' : '[IMAGE]');
        } else {
            $content->textMain .= ($this->isEmpty() ? '' : '[IMAGE]');
        }
    }

    public function printMotionHtml2Pdf(bool $isRight, HtmlToPdfContent $content, Consultation $consultation): void
    {
        if ($this->isEmpty()) {
            return;
        }

        $metadata = json_decode($this->section->metadata, true);

        $fileExt = static::getFileExtensionFromMimeType($metadata['mime']);
        if ($isRight) {
            $imageData = $this->resizeIfMassivelyTooBig(500, 1000, $fileExt);
        } else {
            $imageData = $this->resizeIfMassivelyTooBig(1500, 3000, $fileExt);
        }

        $params   = AntragsgruenApp::getInstance();
        $filenameBase = uniqid('motion-pdf-image') . '.' . $fileExt;
        $filenameHtml = $params->getTmpDir() . $filenameBase;

        if ($isRight) {
            $content->textRight .= '<img src="' . Html::encode($filenameHtml) . '" alt="image">';
        } else {
            $content->textMain .= '<img src="' . Html::encode($filenameHtml) . '" alt="image">';
        }

        $content->imageData[$filenameBase] = $imageData;
    }

    public function printAmendmentHtml2Pdf(bool $isRight, HtmlToPdfContent $content): void
    {
        if ($isRight) {
            $content->textRight .= ($this->isEmpty() ? '' : '[IMAGE]');
        } else {
            $content->textMain .= ($this->isEmpty() ? '' : '[IMAGE]');
        }
    }

    public function getMotionODS(): string
    {
        return ($this->isEmpty() ? '' : '<p>[IMAGE]</p>');
    }

    public function getAmendmentODS(): string
    {
        return ($this->isEmpty() ? '' : '<p>[IMAGE]</p>');
    }

    public function printMotionToODT(Text $odt): void
    {
        if (!$this->isEmpty()) {
            $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);
            $odt->addHtmlTextBlock('[IMAGE]', false);
        }
    }

    public function printAmendmentToODT(Text $odt): void
    {
        if (!$this->isEmpty()) {
            $odt->addHtmlTextBlock('<h2>' . Html::encode($this->getTitle()) . '</h2>', false);
            $odt->addHtmlTextBlock('[IMAGE]', false);
        }
    }

    public function matchesFulltextSearch(string $text): bool
    {
        return false;
    }
}
