<?php

namespace app\models\sectionTypes;

use app\components\latex\Content;
use app\components\opendocument\Text;
use app\components\UrlHelper;
use app\models\db\MotionSection;
use app\models\exceptions\FormError;
use app\models\settings\AntragsgruenApp;
use app\views\pdfLayouts\IPDFLayout;
use yii\helpers\Html;

class Image extends ISectionType
{
    /**
     * @return string
     */
    public function getImageUrl()
    {
        /** @var MotionSection $section */
        $section = $this->section;
        $url     = UrlHelper::createUrl(
            [
                'motion/viewimage',
                'motionId'  => $section->motionId,
                'sectionId' => $section->sectionId
            ]
        );
        return $url;
    }

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        /** @var MotionSection $section */
        $type = $this->section->getSettings();
        $str  = '';
        if ($this->section->data) {
            $str .= '<img src="' . Html::encode($this->getImageUrl()) . '" alt="Current Image"
            style="float: right; max-width: 100px; max-height: 100px; margin-left: 20px;">';
            $required = false;
        } else {
            $required = ($type->required ? 'required' : '');
        }
        $str .= '<div class="form-group" style="overflow: auto;">';
        $str .= '
            <label for="sections_' . $type->id . '">' . Html::encode($type->title) . '</label>
            <input type="file" class="form-control" id="sections_' . $type->id . '" ' . $required .
            ' name="sections[' . $type->id . ']">
        </div>';
        if ($this->section->data) {
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
     * @param string $data
     * @throws FormError
     */
    public function setMotionData($data)
    {
        if (!isset($data['tmp_name'])) {
            throw new FormError('Invalid Image');
        }
        $mime = mime_content_type($data['tmp_name']);
        if (!in_array($mime, ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'])) {
            throw new FormError('Image type not supported. Supported formats are: JPEG, PNG and GIF.');
        }
        $imagedata = getimagesize($data['tmp_name']);
        if (!$imagedata) {
            throw new FormError('Could not read image.');
        }
        $metadata                = [
            'width'    => $imagedata[0],
            'height'   => $imagedata[1],
            'filesize' => filesize($data['tmp_name']),
            'mime'     => $mime
        ];
        $this->section->data     = base64_encode(file_get_contents($data['tmp_name']));
        $this->section->metadata = json_encode($metadata);
    }

    /**
     * @param string $data
     * @throws FormError
     */
    public function setAmendmentData($data)
    {
        $this->setMotionData($data);
    }

    /**
     * @return string
     */
    public function getAmendmentFormatted()
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
        $str     = '<img src="' . Html::encode($this->getImageUrl()) . '" alt="' . Html::encode($type->title) . '">';
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
     * @param \TCPDF $pdf
     */
    public function printMotionToPDF(IPDFLayout $pdfLayout, \TCPDF $pdf)
    {
        if ($this->isEmpty()) {
            return;
        }

        if (!$pdfLayout->isSkippingSectionTitles($this->section)) {
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
     * @param \TCPDF $pdf
     */
    public function printAmendmentToPDF(IPDFLayout $pdfLayout, \TCPDF $pdf)
    {
        $this->printMotionToPDF($pdfLayout, $pdf);
    }

    /**
     * @return string
     */
    public function getMotionPlainText()
    {

        return '[BILD]';
    }

    /**
     * @return string
     */
    public function getAmendmentPlainText()
    {
        return '[BILD]';
    }

    /**
     * @param bool $isRight
     * @param Content $content
     */
    public function printMotionTeX($isRight, Content $content)
    {
        /** @var AntragsgruenApp $params */
        $params       = \Yii::$app->params;
        $filenameBase = uniqid('motion-pdf-image');

        $content->imageData[$filenameBase] = base64_decode($this->section->data);
        if ($isRight) {
            $content->textRight .= '\includegraphics[width=4cm]{' . $params->tmpDir . $filenameBase . '}' . "\n";
            $content->textRight .= '\newline' . "\n";
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
            $content->textRight .= '[BILD]';
        } else {
            $content->textMain .= '[BILD]';
        }
    }

    /**
     * @return string
     */
    public function getMotionODS()
    {
        return '<p>[BILD]</p>';
    }

    /**
     * @return string
     */
    public function getAmendmentODS()
    {
        return '<p>[BILD]</p>';
    }

    /**
     * @param Text $odt
     * @return mixed
     */
    public function printMotionToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[BILD]', false);
    }

    /**
     * @param Text $odt
     * @return mixed
     */
    public function printAmendmentToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[BILD]', false);
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
