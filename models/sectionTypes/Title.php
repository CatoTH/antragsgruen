<?php

namespace app\models\sectionTypes;

use app\components\latex\Exporter;
use app\components\opendocument\Text;
use app\models\db\AmendmentSection;
use app\models\exceptions\FormError;
use yii\helpers\Html;

class Title extends ISectionType
{

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        // @TODO Max Length
        $type = $this->section->consultationSetting;
        return '<div class="form-group">
            <label for="sections_' . $type->id . '">' . Html::encode($type->title) . '</label>
            <input type="text" class="form-control" id="sections_' . $type->id . '"' .
        ' name="sections[' . $type->id . ']" value="' . Html::encode($this->section->data) . '">
        </div>';
    }

    /**
     * @return string
     */
    public function getAmendmentFormField()
    {
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
     * @return bool
     */
    public function isEmpty()
    {
        return ($this->section->data == '');
    }

    /**
     * @param \TCPDF $pdf
     */
    public function printMotionToPDF(\TCPDF $pdf)
    {
        // TODO: Implement printMotionToPDF() method.
    }

    /**
     * @param \TCPDF $pdf
     */
    public function printAmendmentToPDF(\TCPDF $pdf)
    {
        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->data == $section->getOriginalMotionSection()->data) {
            return;
        }

        $pdf->SetFont("helvetica", "", 12);
        $pdf->writeHTML("<h3>" . HTml::encode($this->section->consultationSetting->title) . "</h3>");

        $pdf->SetFont("Courier", "", 11);
        $pdf->Ln(7);

        $html = '<p><strong>Ändern in:</strong><br>' . Html::encode($section->data) . '</p>';
        $pdf->writeHTMLCell(170, '', 27, '', $html, 0, 1, 0, true, '', true);
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
