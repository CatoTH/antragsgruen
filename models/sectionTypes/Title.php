<?php

namespace app\models\sectionTypes;

use app\components\LaTeXExporter;
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
        return '<fieldset class="form-group">
            <label for="sections_' . $type->id . '">' . Html::encode($type->title) . '</label>
            <input type="text" class="form-control" id="sections_' . $type->id . '"' .
        ' name="sections[' . $type->id . ']" value="' . Html::encode($this->section->data) . '">
        </fieldset>';
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
     * @param \TCPDF $pdf
     */
    public function printToPDF(\TCPDF $pdf)
    {
        $pdf->SetFont("helvetica", "", 12);
        $pdf->writeHTML("<h3>" . $this->section->data . "</h3>");
        $pdf->Ln(5);
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
        // TODO: Implement printAmendmentToPDF() method.
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
        return LaTeXExporter::encodePlainString($this->section->data);
    }

    /**
     * @return string
     */
    public function getAmendmentTeX()
    {
        return LaTeXExporter::encodePlainString($this->section->data);
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
        return '<p>' . Html::encode($this->section->data) . '</p>';
    }
}
