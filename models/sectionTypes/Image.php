<?php

namespace app\models\sectionTypes;

use app\components\{latex\Content, Tools, UrlHelper};
use app\models\db\{Consultation, MotionSection};
use app\models\exceptions\{FormError, Internal};
use app\models\settings\AntragsgruenApp;
use app\views\pdfLayouts\IPDFLayout;
use setasign\Fpdi\Tcpdf\Fpdi;
use yii\helpers\Html;
use CatoTH\HTML2OpenDocument\Text;

class Image extends ISectionType
{
    /**
     * @param bool $absolute
     * @param bool $showAlways
     * @return null|string
     */
    public function getImageUrl($absolute = false, $showAlways = false)
    {
        /** @var MotionSection $section */
        $section = $this->section;
        $motion  = $section->getMotion();
        if (!$motion || !$section->data) {
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

    /**
     * @param string $mime
     * @return string|null
     */
    public static function getFileExtensionFromMimeType($mime)
    {
        switch ($mime) {
            case 'image/png':
                return 'png';
            case 'image/jpg':
            case 'image/jpeg':
                return 'jpeg';
            case 'image/gif':
                return 'gif';
            default:
                return null;
        }
    }

    public function getMotionFormField(): string
    {
        /** @var MotionSection $section */
        $type = $this->section->getSettings();
        $url  = $this->getImageUrl();
        $str  = '<section class="section' . $this->section->sectionId . ' type' . static::TYPE_IMAGE . '">';
        if ($url) {
            $str      .= '<img src="' . Html::encode($this->getImageUrl()) . '" alt="Current Image" class="currentImage">';
            $required = false;
        } else {
            $required = ($type->required ? 'required' : '');
        }
        $str .= '<div class="form-group">';
        $str .= $this->getFormLabel();

        $maxSize = floor(Tools::getMaxUploadSize() / 1024 / 1024);
        $str     .= '<div class="maxLenHint"><span class="icon glyphicon glyphicon-info-sign"></span> ';
        $str     .= str_replace('%MB%', $maxSize, \Yii::t('motion', 'max_size_hint'));
        $str     .= '</div>';

        $str .= '<input type="file" class="form-control" id="sections_' . $type->id . '" ' . $required .
            ' name="sections[' . $type->id . ']">';
        if ($url && !$type->required) {
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

    /**
     * @param string $filename
     * @param string $targetType
     * @return string
     * @throws Internal
     */
    public static function getOptimizedImage($filename, $targetType)
    {
        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;
        if ($app->imageMagickPath === null) {
            return file_get_contents($filename);
        } elseif (!file_exists($app->imageMagickPath)) {
            throw new Internal('ImageMagick not correctly set up');
        }

        $tmpfile = $app->getTmpDir() . uniqid('image-conv-') . "." . $targetType;
        exec($app->imageMagickPath . ' -strip ' . escapeshellarg($filename) . ' ' . escapeshellarg($tmpfile));
        $converted = (file_exists($tmpfile) ? file_get_contents($tmpfile) : '');
        unlink($tmpfile);
        return $converted;
    }

    /**
     * If the image is more than twice as big as the specified size, it is reduced to this size.
     * A slightly exceeding size is tolerated, as reducing the size is rather comptation intensive.
     *
     * Hint: this function returns the raw image data, not the base64-encoded version.
     *
     * This only is performed if ImageMagick is installed and configured.
     *
     * @param int $width
     * @param int $height
     * @param string $fileExtension
     * @return string
     */
    public function resizeIfMassivelyTooBig($width, $height, $fileExtension)
    {
        $metadata = json_decode($this->section->metadata, true);
        if ($metadata['width'] < $width * 2 && $metadata['height'] < $height * 2) {
            return base64_decode($this->section->data);
        }

        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;
        if ($app->imageMagickPath === null) {
            return base64_decode($this->section->data);
        } elseif (!file_exists($app->imageMagickPath)) {
            throw new Internal('ImageMagick not correctly set up');
        }

        $tmpfile1 = $app->getTmpDir() . uniqid('image-conv-') . "." . $fileExtension;
        $tmpfile2 = $app->getTmpDir() . uniqid('image-conv-') . "." . $fileExtension;
        file_put_contents($tmpfile1, base64_decode($this->section->data));

        exec($app->imageMagickPath . ' -strip -geometry ' . IntVal($width) . 'x' . IntVal($height) . ' '
            . escapeshellarg($tmpfile1) . ' ' . escapeshellarg($tmpfile2));

        $converted = (file_exists($tmpfile2) ? file_get_contents($tmpfile2) : '');
        unlink($tmpfile1);
        unlink($tmpfile2);

        return $converted;
    }

    /**
     * @param array $data
     * @throws FormError
     */
    public function setMotionData($data)
    {
        if (!isset($data['tmp_name'])) {
            throw new FormError('Invalid Image');
        }
        $mime      = mime_content_type($data['tmp_name']);
        $imagedata = getimagesize($data['tmp_name']);
        if (!$imagedata) {
            throw new FormError('Could not read image.');
        }

        $fileExt = static::getFileExtensionFromMimeType($mime);
        if ($fileExt === null) {
            throw new FormError('Image type not supported. Supported formats are: JPEG, PNG and GIF.');
        }
        $optimized = static::getOptimizedImage($data['tmp_name'], $fileExt);

        $metadata                = [
            'width'    => $imagedata[0],
            'height'   => $imagedata[1],
            'filesize' => strlen($optimized),
            'mime'     => $mime
        ];
        $this->section->data     = base64_encode($optimized);
        $this->section->metadata = json_encode($metadata);
    }

    public function deleteMotionData()
    {
        $this->section->data     = '';
        $this->section->metadata = '';
    }

    /**
     * @param array $data
     * @throws FormError
     */
    public function setAmendmentData($data)
    {
        $this->setMotionData($data);
    }

    public function getAmendmentFormatted(string $sectionTitlePrefix = ''): string
    {
        return ''; // @TODO
    }

    public function getSimple(bool $isRight, bool $showAlways = false): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        /** @var MotionSection $section */
        $section = $this->section;
        $type    = $section->getSettings();
        $url     = $this->getImageUrl($this->absolutizeLinks, $showAlways);

        return '<img src="' . Html::encode($url) . '" alt="' . Html::encode($type->title) . '">';
    }

    public function isEmpty(): bool
    {
        return ($this->section->data === '');
    }

    /**
     * @param float $width
     * @param float $height
     * @param float $maxX
     * @param float $maxY
     * @return float[]
     */
    private function scaleSize($width, $height, $maxX, $maxY)
    {
        $scaleX = $maxX / $width;
        $scaleY = $maxY / $height;
        $scale  = ($scaleX < $scaleY ? $scaleX : $scaleY);
        return [$scale * $width, $scale * $height];
    }

    public function printMotionToPDF(IPDFLayout $pdfLayout, Fpdi $pdf): void
    {
        if ($this->isEmpty()) {
            return;
        }

        if ($this->section->getSettings()->printTitle) {
            $pdfLayout->printSectionHeading($this->section->getSettings()->title);
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
            $size     = $this->scaleSize($metadata['width'], $metadata['height'], $maxWidth, $maxHeight);
            $imageData = $this->resizeIfMassivelyTooBig(500, 1000, $fileExt);
        } else {
            $size     = $this->scaleSize($metadata['width'], $metadata['height'], $maxWidth, $maxHeight);
            $imageData = $this->resizeIfMassivelyTooBig(1500, 3000, $fileExt);
        }

        $img = '@' . $imageData;
        switch ($metadata['mime']) {
            case 'image/png':
                $type = 'PNG';
                break;
            case 'image/jpg':
            case 'image/jpeg':
                $type = 'JPEG';
                break;
            default:
                $type = '';
        }
        $pdf->Image($img, '', '', $size[0], $size[1], $type, '', '', true, 300, 'C');
        $pdf->Ln($size[1] + 7);
    }

    public function printAmendmentToPDF(IPDFLayout $pdfLayout, Fpdi $pdf): void
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

    public function printMotionTeX(bool $isRight, Content $content, Consultation $consultation): void
    {
        if ($this->isEmpty()) {
            return;
        }

        /** @var AntragsgruenApp $params */
        $params   = \Yii::$app->params;
        $metadata = json_decode($this->section->metadata, true);

        $fileExt      = static::getFileExtensionFromMimeType($metadata['mime']);
        $filenameBase = uniqid('motion-pdf-image') . '.' . $fileExt;

        $extraSettings = $this->section->getSettings()->getSettingsObj();
        $maxHeight     = ($extraSettings->imgMaxHeight > 0 ? $extraSettings->imgMaxHeight : null);

        if ($isRight) {
            $maxWidth           = ($extraSettings->imgMaxWidth > 0 ? $extraSettings->imgMaxWidth : 4.9);
            $maxStr             = ($maxHeight ? 'max width=' . $maxWidth . 'cm,max height=' . $maxHeight . 'cm' : 'width=' . $maxWidth . 'cm');
            $content->textRight .= '\includegraphics[' . $maxStr . ']{' . $params->getTmpDir() . $filenameBase . '}' . "\n";
            $content->textRight .= '\newline' . "\n" . '\newline' . "\n";
            $imageData          = $this->resizeIfMassivelyTooBig(500, 1000, $fileExt);
        } else {
            $maxWidth          = ($extraSettings->imgMaxWidth > 0 ? $extraSettings->imgMaxWidth : 10);
            $maxStr            = ($maxHeight ? 'max width=' . $maxWidth . 'cm,max height=' . $maxHeight . 'cm' : 'width=' . $maxWidth . 'cm');
            $content->textMain .= '\includegraphics[' . $maxStr . ']{' . $params->getTmpDir() . $filenameBase . '}' . "\n";
            $content->textMain .= '\newline' . "\n" . '\newline' . "\n";
            $imageData         = $this->resizeIfMassivelyTooBig(1500, 3000, $fileExt);
        }

        $content->imageData[$filenameBase] = $imageData;
    }

    public function printAmendmentTeX(bool $isRight, Content $content): void
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
            $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
            $odt->addHtmlTextBlock('[IMAGE]', false);
        }
    }

    public function printAmendmentToODT(Text $odt): void
    {
        if (!$this->isEmpty()) {
            $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
            $odt->addHtmlTextBlock('[IMAGE]', false);
        }
    }

    /**
     * @param $text
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function matchesFulltextSearch($text)
    {
        return false;
    }
}
