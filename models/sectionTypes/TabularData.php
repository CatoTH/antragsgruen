<?php

namespace app\models\sectionTypes;

use app\components\latex\Content;
use app\components\latex\Exporter;
use app\models\exceptions\FormError;
use app\views\pdfLayouts\IPDFLayout;
use yii\helpers\Html;
use \CatoTH\HTML2OpenDocument\Text;

class TabularData extends ISectionType
{

    /**
     * @return string
     */
    public function getMotionFormField()
    {
        $type = $this->section->getSettings();

        $rows = static::getTabularDataRowsFromData($type->data);
        $data = json_decode($this->section->data, true);

        $str = '<div class="form-horizontal tabularData">';
        $str .= '<div class="label">' . Html::encode($type->title) . '</div>';

        foreach ($rows as $row) {
            $id = 'sections_' . $type->id . '_' . $row->rowId;
            $str .= '<div class="form-group">';
            $str .= '<label for="' . $id . '" class="col-md-3 control-label">';
            $str .= Html::encode($row->title) . ':</label>';
            $str .= '<div class="col-md-9">';
            $nameId = 'name="sections[' . $type->id . '][' . $row->rowId . ']" id="' . $id . '"';
            $dat    = (isset($data['rows'][$row->rowId]) ? $data['rows'][$row->rowId] : '');
            $str .= $row->getFormField($nameId, $dat, $type->required);
            $str .= '</div></div>';
        }
        $str .= '</table></div>';
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
        $type = $this->section->getSettings();
        $rows = static::getTabularDataRowsFromData($type->data);

        $dataOut = ['rows' => []];

        foreach ($rows as $row) {
            if (!isset($data[$row->rowId])) {
                continue;
            }
            $dat                          = $data[$row->rowId];
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
     * @param bool $isRight
     * @return string
     */
    public function getSimple($isRight)
    {
        if ($this->isEmpty()) {
            return '';
        }
        $rows = static::getTabularDataRowsFromData($this->section->getSettings()->data);
        $data = json_decode($this->section->data, true);
        $str  = '<dl class="tabularData table' . (!$isRight ? ' dl-horizontal' : '') . '">';
        foreach ($data['rows'] as $rowId => $rowData) {
            if (!isset($rows[$rowId])) {
                continue;
            }
            $str .= '<dt>';
            $str .= Html::encode($rows[$rowId]->title) . ':';
            $str .= '</dt><dd>';
            $str .= Html::encode($rows[$rowId]->formatRow($rowData));
            $str .= '</dd>';
        }
        $str .= '</dl>';
        return $str;
    }

    /**
     * @return string
     */
    public function getAmendmentFormatted()
    {
        return ''; // @TODO
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
     * @param IPDFLayout $pdfLayout
     * @param \FPDI $pdf
     */
    public function printMotionToPDF(IPDFLayout $pdfLayout, \FPDI $pdf)
    {
        if ($this->isEmpty()) {
            return;
        }

        if (!$pdfLayout->isSkippingSectionTitles($this->section)) {
            $pdfLayout->printSectionHeading($this->section->getSettings()->title);
        }

        $pdf->SetFont('Courier', '', 11);
        $pdf->Ln(7);

        $rows = static::getTabularDataRowsFromData($this->section->getSettings()->data);
        $data = json_decode($this->section->data, true);

        foreach ($data['rows'] as $rowId => $rowData) {
            if (!isset($rows[$rowId])) {
                continue;
            }
            $y     = $pdf->getY();
            $text1 = '<strong>' . Html::encode($rows[$rowId]->title) . ':</strong>';
            $text2 = Exporter::encodeHTMLString($rows[$rowId]->formatRow($rowData));
            
            $pdf->writeHTMLCell(45, '', 25, $y, $text1, 0, 0, 0, true, '', true);
            $pdf->writeHTMLCell(111, '', 75, '', $text2, 0, 1, 0, true, '', true);
            $pdf->Ln(3);
        }
        $pdf->Ln(4);
    }

    /**
     * @param IPDFLayout $pdfLayout
     * @param \FPDI $pdf
     */
    public function printAmendmentToPDF(IPDFLayout $pdfLayout, \FPDI $pdf)
    {
        $this->printAmendmentToPDF($pdfLayout, $pdf);
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
        $data   = json_decode($this->section->data, true);
        $type = $this->section->getSettings();
        $rows = static::getTabularDataRowsFromData($type->data);

        $return = '';
        foreach ($data['rows'] as $rowId => $rowData) {
            if (!isset($rows[$rowId])) {
                continue;
            }
            $return .= $rows[$rowId]->title . ': ';
            $return .= Exporter::encodeHTMLString($rows[$rowId]->formatRow($rowData));
            $return .= "\n";
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
     * @param bool $isRight
     * @param Content $content
     */
    public function printMotionTeX($isRight, Content $content)
    {
        $data = json_decode($this->section->data, true);
        $type = $this->section->getSettings();
        $rows = static::getTabularDataRowsFromData($type->data);
        if ($isRight) {
            $content->textRight .= '\vspace{-0.3cm}\newline';
        }
        foreach ($data['rows'] as $rowId => $rowData) {
            if (!isset($rows[$rowId])) {
                continue;
            }
            if ($isRight) {
                $content->textRight .= '\textbf{' . Exporter::encodePlainString($rows[$rowId]->title) . ':}';
                $content->textRight .= "\\newline\n";
                $content->textRight .= '\vspace{0.2cm}';
                $content->textRight .= Exporter::encodePlainString($rowData);
                $content->textRight .= "\\newline\n";
            } else {
                $content->textMain .= '\textbf{' . Exporter::encodePlainString($rows[$rowId]->title) . ':}';
                $content->textMain .= "\\newline\n";
                $content->textMain .= Exporter::encodeHTMLString($rows[$rowId]->formatRow($rowData));
                $content->textMain .= "\\newline\n";
            }
        }
        if ($isRight) {
            $content->textRight .= '\newline' . "\n";
        }
    }

    /**
     * @param bool $isRight
     * @param Content $content
     */
    public function printAmendmentTeX($isRight, Content $content)
    {
        if ($isRight) {
            $content->textRight .= '[TEST DATA]'; // @TODO
        } else {
            $content->textMain .= '[TEST DATA]'; // @TODO
        }
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
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[TABELLE]', false); // @TODO
    }

    /**
     * @param Text $odt
     * @return mixed
     */
    public function printAmendmentToODT(Text $odt)
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[TABELLE]', false); // @TODO
    }

    /**
     * @param $text
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function matchesFulltextSearch($text)
    {
        $type   = $this->section->getSettings();
        $data   = json_decode($this->section->data, true);
        $rows   = static::getTabularDataRowsFromData($type->data);
        $return = '';
        foreach ($data['rows'] as $rowId => $rowData) {
            if (!isset($rows[$rowId])) {
                continue;
            }
            if (mb_stripos($rowData, $text) !== false) {
                return false;
            }
        }
        return $return;
    }
}
