<?php

namespace app\models\sectionTypes;

use app\models\db\MotionSection;
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
        $str  = '<fieldset class="form-horizontal tabularData">';
        $str .= '<div class="label">' . Html::encode($type->title) . '</div>';
        $rows = static::getTabularDataRowsFromData($type->data);
        foreach ($rows as $rowId => $rowName) {
            $id = 'sections_' . $type->id . '_' . $rowId;
            $str .= '<div class="form-group">';
            $str .= '<label for="' . $id . '" class="col-md-3 control-label">' . Html::encode($rowName) . ':</label>';
            $str .= '<div class="col-md-9"><input type="text" name="sections[' . $type->id . '][' . $rowId . ']"';
            $str .= ' value=""';
            if ($type->required) {
                $str .= ' required';
            }
            $str .= ' id="' . $id . '" class="form-control"></div></div>';
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
        $dataOut = ['rows' => []];
        if (is_array($data)) {
            foreach ($data as $rowId => $rowData) {
                $dataOut['rows'][$rowId] = $rowData;
            }
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
    public function showSimple()
    {
        if ($this->isEmpty()) {
            return '';
        }
        $rows = static::getTabularDataRowsFromData($this->section->consultationSetting->data);
        $data = json_decode($this->section->data, true);
        $str = '<div class="content"><table class="tabularData table">';
        foreach ($data['rows'] as $rowId => $rowData) {
            if (!isset($rows[$rowId])) {
                continue;
            }
            $str .= '<tr><th class="col-md-3">';
            $str .= Html::encode($rows[$rowId]) . ':';
            $str .= '</th><td class="col-md-9">';
            $str .= Html::encode($rowData);
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
    public function printToPDF(\TCPDF $pdf)
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
            $y = $pdf->getY();
            $text1 = '<strong>' . Html::encode($rows[$rowId]) . ':</strong>';
            $text2 = Html::encode($rowData);
            $pdf->writeHTMLCell(45, '', 25, $y, $text1, 0, 0, 0, true, '', true);
            $pdf->writeHTMLCell(111, '', 75, '', $text2, 0, 1, 0, true, '', true);
            $pdf->Ln(7);
        }
    }

    /**
     * @param string|null $data
     * @return string[]
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
        return $data['rows'];
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
                if (!is_numeric($key)) {
                    continue;
                }
                if (trim($val) != '') {
                    if ($key > $newData['maxRowId']) {
                        $newData['maxRowId'] = $key;
                    }
                    $newData['rows'][$key] = $val;
                }
            }
            if (isset($post['tabular']['new'])) {
                foreach ($post['tabular']['new'] as $val) {
                    if (trim($val) != '') {
                        $newData['maxRowId']++;
                        $newData['rows'][$newData['maxRowId']] = $val;
                    }
                }
            }
        } else {
            $newData['rows'] = [];
        }
        return json_encode($newData);
    }
}
