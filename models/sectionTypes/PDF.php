<?php

namespace app\models\sectionTypes;

use app\components\latex\Content;
use app\components\opendocument\Text;
use app\components\UrlHelper;
use app\models\db\MotionSection;
use app\models\exceptions\FormError;
use app\views\pdfLayouts\IPDFLayout;
use yii\helpers\Html;

class PDF extends ISectionType
{
    /**
     * @return string
     */
    public function getPdfUrl()
    {
        /** @var MotionSection $section */
        $section = $this->section;
        $url     = UrlHelper::createUrl(
            [
                'motion/viewpdf',
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
            $str .= '<a href="' . Html::encode($this->getPdfUrl()) . '" alt="Current PDF">Current PDF</a>';
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
        if (!in_array($mime, ['application/pdf'])) {
            throw new FormError('Please only upload PDFs.');
        }
        $metadata                = [
            'filesize' => filesize($data['tmp_name']),
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
     */
    public function getSimple($isRight)
    {
        if ($this->isEmpty()) {
            return '';
        }

        $str = '<div class="content">';
        $str .= '<a href="' . Html::encode($this->getPdfUrl()) . '" alt="Current PDF">PDF herunterladen</a>';
        $str .= '</div>';
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
     * @param IPDFLayout $pdfLayout
     * @param \TCPDF $pdf
     */
    public function printMotionToPDF(IPDFLayout $pdfLayout, \TCPDF $pdf)
    {
        if ($this->isEmpty()) {
            return;
        }

        $pdf->writeHTML('<p>[PDF]</p>'); // @TODO
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

        return '[PDF]';
    }

    /**
     * @return string
     */
    public function getAmendmentPlainText()
    {
        return '[PDF]';
    }

    /**
     * @param bool $isRight
     * @param Content $content
     */
    public function printMotionTeX($isRight, Content $content)
    {
        // @TODO
    }

    /**
     * @param bool $isRight
     * @param Content $content
     */
    public function printAmendmentTeX($isRight, Content $content)
    {
        // @TODO
    }

    /**
     * @return string
     */
    public function getMotionODS()
    {
        return '<p>[PDF]</p>';
    }

    /**
     * @return string
     */
    public function getAmendmentODS()
    {
        return '<p>[PDF]</p>';
    }

    /**
     * @param Text $odt
     * @return mixed
     */
    public function printMotionToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[PDF]', false);
    }

    /**
     * @param Text $odt
     * @return mixed
     */
    public function printAmendmentToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[PDF]', false);
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
