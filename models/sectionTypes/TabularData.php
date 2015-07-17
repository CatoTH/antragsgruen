<?php

namespace app\models\sectionTypes;

use app\components\opendocument\Text;
use app\models\exceptions\FormError;
use yii\helpers\Html;

class TabularData extends ISectionType
{

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        $type = $this->section->consultationSetting;

        $rows = static::getTabularDataRowsFromData($type->data);
        $data = json_decode($this->section->data, true);

        $str  = '<fieldset class="form-horizontal tabularData">';
        $str .= '<div class="label">' . Html::encode($type->title) . '</div>';

        foreach ($rows as $row) {
            $id = 'sections_' . $type->id . '_' . $row->rowId;
            $str .= '<div class="form-group">';
            $str .= '<label for="' . $id . '" class="col-md-3 control-label">';
            $str .= Html::encode($row->title) . ':</label>';
            $str .= '<div class="col-md-9">';
            $nameId = 'name="sections[' . $type->id . '][' . $row->rowId . ']" id="' . $id . '"';
            $dat = (isset($data['rows'][$row->rowId]) ? $data['rows'][$row->rowId] : '');
            $str .= $row->getFormField($nameId, $dat, $type->required);
            $str .= '</div></div>';
        }
        $str .= '</table></fieldset>';
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
     * @param array $data
     * @throws FormError
     */
    public function setMotionData($data)
    {
        $type = $this->section->consultationSetting;
        $rows = static::getTabularDataRowsFromData($type->data);

        $dataOut = ['rows' => []];

        foreach ($rows as $row) {
            if (!isset($data[$row->rowId])) {
                continue;
            }
            $dat = $data[$row->rowId];
            $dataOut['rows'][$row->rowId] = $row->parseFormInput($dat);
        }

        $this->section->data = json_encode($dataOut);
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
        if ($this->isEmpty()) {
            return '';
        }
        $rows = static::getTabularDataRowsFromData($this->section->consultationSetting->data);
        $data = json_decode($this->section->data, true);
        $str  = '<div class="content"><table class="tabularData table">';
        foreach ($data['rows'] as $rowId => $rowData) {
            if (!isset($rows[$rowId])) {
                continue;
            }
            $str .= '<tr><th class="col-md-3">';
            $str .= Html::encode($rows[$rowId]->title) . ':';
            $str .= '</th><td class="col-md-9">';
            $str .= Html::encode($rows[$rowId]->formatRow($rowData));
            $str .= '</td></tr>';
        }
        $str .= '</table></div>';
        return $str;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        if ($this->section->data == '') {
            return true;
        }
        $data = json_decode($this->section->data, true);
        return !(isset($data['rows']) && count($data['rows']) > 0);
    }

    /**
     * @param \TCPDF $pdf
     */
    public function printMotionToPDF(\TCPDF $pdf)
    {
        if ($this->isEmpty()) {
            return;
        }

        $pdf->SetFont("helvetica", "", 12);
        $pdf->writeHTML("<h3>" . $this->section->consultationSetting->title . "</h3>");

        $pdf->SetFont("Courier", "", 11);
        $pdf->Ln(7);

        $rows = static::getTabularDataRowsFromData($this->section->consultationSetting->data);
        $data = json_decode($this->section->data, true);

        foreach ($data['rows'] as $rowId => $rowData) {
            if (!isset($rows[$rowId])) {
                continue;
            }
            $y     = $pdf->getY();
            $text1 = '<strong>' . Html::encode($rows[$rowId]->title) . ':</strong>';

            $text2 = Html::encode($rowData);  // @TODO sectionType-specific handling
            $pdf->writeHTMLCell(45, '', 25, $y, $text1, 0, 0, 0, true, '', true);
            $pdf->writeHTMLCell(111, '', 75, '', $text2, 0, 1, 0, true, '', true);
            $pdf->Ln(3);
        }
        $pdf->Ln(4);
    }

    /**
     * @param \TCPDF $pdf
     */
    public function printAmendmentToPDF(\TCPDF $pdf)
    {
        $this->printAmendmentToPDF($pdf);
    }

    /**
     * @param string|null $data
     * @return TabularDataType[]
     */
    public static function getTabularDataRowsFromData($data)
    {
        if ($data === null || $data == '') {
            return [];
        }
        $data = json_decode($data, true);
        if (!$data || !isset($data['rows'])) {
            return [];
        }
        $rows = [];
        foreach ($data['rows'] as $row) {
            $rows[$row['rowId']] = new TabularDataType($row);
        }
        return $rows;
    }

    /**
     * @param string $preData
     * @param array $post
     * @return null|string
     */
    public static function saveTabularDataSettingsFromPost($preData, $post)
    {
        if ($preData === null || $preData == '') {
            $newData = [
                'maxRowId' => 0,
                'rows'     => []
            ];
        } else {
            $preData = json_decode($preData, true);
            if (!$preData || !isset($preData['rows'])) {
                $newData = [
                    'maxRowId' => 0,
                    'rows'     => []
                ];
            } else {
                $newData         = $preData;
                $newData['rows'] = [];
            }
        }
        if (isset($post['tabular'])) {
            foreach ($post['tabular'] as $key => $val) {
                if (mb_substr($key, 0, 3) === 'new') {
                    $key = ++$newData['maxRowId'];
                }
                if (!is_numeric($key)) {
                    continue;
                }
                if ($key > $newData['maxRowId']) {
                    $newData['maxRowId'] = $key;
                }
                $val['rowId']          = $key;
                $newData['rows'][$key] = new TabularDataType($val);
            }
        } else {
            $newData['rows'] = [];
        }
        return json_encode($newData);
    }

    /**
     * @return string
     */
    public function getMotionPlainText()
    {
        $data = json_decode($this->section->data, true);
        $return = '';
        foreach ($data['rows'] as $rowId => $rowData) {
            if (!isset($rows[$rowId])) {
                continue;
            }
            $return .= $rows[$rowId]->title . ': ';
            $return .= $rowData . "\n"; // @TODO sectionType-specific handling
        }
        return $return;
    }

    /**
     * @return string
     */
    public function getAmendmentPlainText()
    {
        return '@TODO'; // @TODO
    }

    /**
     * @return string
     */
    public function getMotionTeX()
    {
        return 'Test'; //  @TODO
    }

    /**
     * @return string
     */
    public function getAmendmentTeX()
    {
        return 'Test'; //  @TODO
    }

    /**
     * @return string
     */
    public function getMotionODS()
    {
        return 'Test'; //  @TODO
    }

    /**
     * @return string
     */
    public function getAmendmentODS()
    {
        return 'Test'; //  @TODO
    }

    /**
     * @param Text $odt
     * @return mixed
     */
    public function printMotionToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->consultationSetting->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[TABELLE]', false); // @TODO
    }

    /**
     * @param Text $odt
     * @return mixed
     */
    public function printAmendmentToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->consultationSetting->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[TABELLE]', false); // @TODO
    }
}
