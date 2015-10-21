<?php

namespace app\models\sectionTypes;

use app\components\latex\Exporter;
use app\components\opendocument\Text;
use app\models\db\AmendmentSection;
use app\models\exceptions\FormError;
use app\views\pdfLayouts\IPDFLayout;
use yii\helpers\Html;

class Title extends ISectionType
{

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        $type = $this->section->consultationSetting;
        $str  = '<div class="form-group plain-text" data-max-len="' . $type->maxLen . '">
            <label for="sections_' . $type->id . '">' . Html::encode($type->title) . '</label>';

        if ($type->maxLen != 0) {
            $len = abs($type->maxLen);
            $str .= '<div class="maxLenHint"><span class="glyphicon glyphicon-info-sign icon"></span> ';
            $str .= str_replace('%LEN%', $len, 'Max. %LEN% Zeichen (Aktuell: <span class="counter"></span>)');
            $str .= '</div>';
        }

        $str .= '<input type="text" class="form-control" id="sections_' . $type->id . '"' .
            ' name="sections[' . $type->id . ']" value="' . Html::encode($this->section->data) . '">';

        if ($type->maxLen != 0) {
            $str .= '<div class="alert alert-danger maxLenTooLong hidden" role="alert">';
            $str .= '<span class="glyphicon glyphicon-alert"></span> ' . 'Der Text ist zu lang!';
            $str .= '</div>';
        }

        $str .= '</div>';

        return $str;
    }

    /**
     * @return string
     */
    public function getAmendmentFormField()
    {
        $this->section->consultationSetting->maxLen = 0; // @TODO Dirty Hack
        return $this->getMotionFormField();
    }

    /**
     * @param $data
     * @throws FormError
     */
    public function setMotionData($data)
    {
        $this->section->data = $data;
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
    public function getSimple()
    {
        return Html::encode($this->section->data);
    }

    /**
     * @return string
     */
    public function getAmendmentFormatted()
    {
        /** @var AmendmentSection $section */
        $section = $this->section;

        if (!$section->getOriginalMotionSection()) {
            return '';
        }
        if ($section->data == $section->getOriginalMotionSection()->data) {
            return '';
        }
        $str = '<section id="section_title" class="motionTextHolder">';
        $str .= '<h3 class="green">' . Html::encode($section->consultationSetting->title) . '</h3>';
        $str .= '<div id="section_title_0" class="paragraph"><div class="text">';
        $str .= '<h4 class="lineSummary">' . 'Ändern in' . ':</h4>';
        $str .= '<p>' . Html::encode($section->data) . '</p>';
        $str .= '</div></div></section>';

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
        // TODO: Implement printMotionToPDF() method.
    }

    /**
     * @param IPDFLayout $pdfLayout
     * @param \TCPDF $pdf
     */
    public function printAmendmentToPDF(IPDFLayout $pdfLayout, \TCPDF $pdf)
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->data == $section->getOriginalMotionSection()->data) {
            return;
        }

        if (!$pdfLayout->isSkippingSectionTitles($this->section)) {
            $pdfLayout->printSectionHeading($this->section->consultationSetting->title);
        }

        $pdf->SetFont('Courier', '', 11);
        $pdf->Ln(7);

        $html = '<p><strong>Ändern in:</strong><br>' . Html::encode($section->data) . '</p>';
        $pdf->writeHTMLCell(170, '', 24, '', $html, 0, 1, 0, true, '', true);
        $pdf->Ln(7);
    }

    /**
     * @return string
     */
    public function getMotionPlainText()
    {
        return $this->section->data;
    }

    /**
     * @return string
     */
    public function getAmendmentPlainText()
    {
        return $this->section->data;
    }

    /**
     * @return string
     */
    public function getMotionTeX()
    {
        return Exporter::encodePlainString($this->section->data);
    }

    /**
     * @return string
     */
    public function getAmendmentTeX()
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->data == $section->getOriginalMotionSection()->data) {
            return '';
        }
        $title = Exporter::encodePlainString($section->consultationSetting->title);
        $tex   = '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
        $html  = '<p><strong>Ändern in:</strong><br>' . Html::encode($this->section->data) . '</p>';
        $tex .= Exporter::encodeHTMLString($html);
        return $tex;
    }


    /**
     * @return string
     */
    public function getMotionODS()
    {
        return '<p>' . Html::encode($this->section->data) . '</p>';
    }

    /**
     * @return string
     */
    public function getAmendmentODS()
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->data == $section->getOriginalMotionSection()->data) {
            return '';
        }
        return '<strong>Neuer Titel:</strong><br>' . Html::encode($section->data) . '<br><br>';
    }

    /**
     * @param Text $odt
     * @return mixed
     */
    public function printMotionToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h1>' . Html::encode($this->section->data) . '</h1>', false);
    }

    /**
     * @param Text $odt
     * @return mixed
     */
    public function printAmendmentToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h1>' . Html::encode($this->section->data) . '</h1>', false);
    }

    /**
     * @param $text
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function matchesFulltextSearch($text)
    {
        return (mb_stripos($this->section->data, $text) !== false);
    }
}
