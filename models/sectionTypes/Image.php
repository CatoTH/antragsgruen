<?php

namespace app\models\sectionTypes;

use app\components\latex\Content;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\MotionSection;
use app\models\exceptions\FormError;
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use app\views\pdfLayouts\IPDFLayout;
use setasign\Fpdi\TcpdfFpdi;
use yii\helpers\Html;
use CatoTH\HTML2OpenDocument\Text;

class Image extends ISectionType
{
    /**
     * @param bool $absolute
     * @return null|string
     */
    public function getImageUrl($absolute = false)
    {
        /** @var MotionSection $section */
        $section = $this->section;
        $motion  = $section->getMotion();
        if (!$motion || !$section->data) {
            return null;
        }

        $url = UrlHelper::createUrl(
            [
                '/motion/viewimage',
                'motionSlug' => $section->getMotion()->getMotionSlug(),
                'sectionId'  => $section->sectionId
            ],
            $motion->getMyConsultation()
        );

        if ($absolute) {
            $url = UrlHelper::absolutizeLink($url);
        }

        return $url;
    }

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        /** @var MotionSection $section */
        $type = $this->section->getSettings();
        $url  = $this->getImageUrl();
        $str  = '';
        if ($url) {
            $str      .= '<img src="' . Html::encode($this->getImageUrl()) . '" alt="Current Image"
            style="float: right; max-width: 100px; max-height: 100px; margin-left: 20px;">';
            $required = false;
        } else {
            $required = ($type->required ? 'required' : '');
        }
        $str .= '<div class="form-group" style="overflow: auto;">';
        $str .= $this->getFormLabel();

        $maxSize = floor(Tools::getMaxUploadSize() / 1024 / 1024);
        $str     .= '<div class="maxLenHint"><span class="icon glyphicon glyphicon-info-sign"></span> ';
        $str     .= str_replace('%MB%', $maxSize, \Yii::t('motion', 'max_size_hint'));
        $str     .= '</div>';

        $str .= '<input type="file" class="form-control" id="sections_' . $type->id . '" ' . $required .
            ' name="sections[' . $type->id . ']">
        </div>';
        if ($url) {
            $str .= '<br style="clear: both;">';
        }
        return $str;
    }

    /**
     * @return string
     */
    public function getAmendmentFormField()
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

        $tmpfile = $app->tmpDir . uniqid('image-conv-') . "." . $targetType;
        exec($app->imageMagickPath . ' -strip ' . escapeshellarg($filename) . ' ' . escapeshellarg($tmpfile));
        $converted = (file_exists($tmpfile) ? file_get_contents($tmpfile) : '');
        unlink($tmpfile);
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

        switch ($mime) {
            case 'image/png':
                $fileExt = 'png';
                break;
            case 'image/jpg':
            case 'image/jpeg':
                $fileExt = 'jpeg';
                break;
            case 'image/gif':
                $fileExt = 'gif';
                break;
            default:
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

    /**
     * @param array $data
     * @throws FormError
     */
    public function setAmendmentData($data)
    {
        $this->setMotionData($data);
    }

    /**
     * @param string $sectionTitlePrefix
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAmendmentFormatted($sectionTitlePrefix = '')
    {
        return ''; // @TODO
    }

    /**
     * @param bool $isRight
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSimple($isRight)
    {
        if ($this->isEmpty()) {
            return '';
        }

        /** @var MotionSection $section */
        $section = $this->section;
        $type    = $section->getSettings();
        $url     = $this->getImageUrl($this->absolutizeLinks);
        $str     = '<img src="' . Html::encode($url) . '" alt="' . Html::encode($type->title) . '">';
        return $str;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return ($this->section->data == '');
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

    /**
     * @param IPDFLayout $pdfLayout
     * @param TcpdfFpdi $pdf
     */
    public function printMotionToPDF(IPDFLayout $pdfLayout, TcpdfFpdi $pdf)
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
        $size     = $this->scaleSize($metadata['width'], $metadata['height'], 80, 60);
        $img      = '@' . base64_decode($this->section->data);
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

    /**
     * @param IPDFLayout $pdfLayout
     * @param TcpdfFpdi $pdf
     */
    public function printAmendmentToPDF(IPDFLayout $pdfLayout, TcpdfFpdi $pdf)
    {
        $this->printMotionToPDF($pdfLayout, $pdf);
    }

    /**
     * @return string
     */
    public function getMotionPlainText()
    {
        return '[IMAGE]';
    }

    /**
     * @return string
     */
    public function getAmendmentPlainText()
    {
        return '[IMAGE]';
    }

    /**
     * @param bool $isRight
     * @param Content $content
     * @param Consultation $consultation
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function printMotionTeX($isRight, Content $content, Consultation $consultation)
    {
        /** @var AntragsgruenApp $params */
        $params       = \Yii::$app->params;
        $filenameBase = uniqid('motion-pdf-image');

        $metadata = json_decode($this->section->metadata, true);
        switch ($metadata['mime']) {
            case 'image/png':
                $filenameBase .= '.png';
                break;
            case 'image/jpg':
            case 'image/jpeg':
                $filenameBase .= '.jpg';
                break;
            case 'image/gif':
                $filenameBase .= '.gif';
        }

        $content->imageData[$filenameBase] = base64_decode($this->section->data);
        if ($isRight) {
            $content->textRight .= '\includegraphics[width=4.9cm]{' . $params->tmpDir . $filenameBase . '}' . "\n";
            $content->textRight .= '\newline' . "\n" . '\newline' . "\n";
        } else {
            $content->textMain .= '\includegraphics[width=10cm]{' . $params->tmpDir . $filenameBase . '}' . "\n";
        }
    }

    /**
     * @param bool $isRight
     * @param Content $content
     */
    public function printAmendmentTeX($isRight, Content $content)
    {
        if ($isRight) {
            $content->textRight .= '[IMAGE]';
        } else {
            $content->textMain .= '[IMAGE]';
        }
    }

    /**
     * @return string
     */
    public function getMotionODS()
    {
        return '<p>[IMAGE]</p>';
    }

    /**
     * @return string
     */
    public function getAmendmentODS()
    {
        return '<p>[IMAGE]</p>';
    }

    /**
     * @param Text $odt
     */
    public function printMotionToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[IMAGE]', false);
    }

    /**
     * @param Text $odt
     */
    public function printAmendmentToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[IMAGE]', false);
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
