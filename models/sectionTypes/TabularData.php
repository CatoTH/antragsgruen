<?php

namespace app\models\sectionTypes;

use app\components\latex\{Content, Exporter};
use app\models\db\Consultation;
use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};
use yii\helpers\Html;
use \CatoTH\HTML2OpenDocument\Text;

class TabularData extends ISectionType
{
    public function getMotionFormField(): string
    {
        $type = $this->section->getSettings();

        $rows = static::getTabularDataRowsFromData($type->data);
        $data = json_decode($this->section->getData(), true);

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

    public function getAmendmentFormField(): string
    {
        return $this->getMotionFormField();
    }

    /**
     * @param array $data
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

        $this->section->setData(json_encode($dataOut));
    }

    public function deleteMotionData()
    {
        $this->section->setData('');
    }

    /**
     * @param array $data
     */
    public function setAmendmentData($data)
    {
        $this->setMotionData($data);
    }

    public function getSimple(bool $isRight, bool $showToken = false): string
    {
        if ($this->isEmpty()) {
            return '';
        }
        $rows = static::getTabularDataRowsFromData($this->section->getSettings()->data);
        $data = json_decode($this->section->getData(), true);
        $str  = '<dl class="tabularData table' . (!$isRight ? ' dl-horizontal' : '') . '">';
        foreach ($data['rows'] as $rowId => $rowData) {
            if (!isset($rows[$rowId]) || $rows[$rowId]->formatRow($rowData) === '') {
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

    public function getAmendmentFormatted(string $sectionTitlePrefix = ''): string
    {
        return ''; // @TODO
    }

    public function isEmpty(): bool
    {
        if ($this->section->getData() === '') {
            return true;
        }
        $data = json_decode($this->section->getData(), true);
        return !(isset($data['rows']) && count($data['rows']) > 0);
    }

    public function printMotionToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        if ($this->isEmpty()) {
            return;
        }

        if ($this->section->getSettings()->printTitle) {
            $pdfLayout->printSectionHeading($this->section->getSettings()->title);
        }

        $pdf->SetFont('Courier', '', 11);
        $pdf->Ln(7);

        $rows = static::getTabularDataRowsFromData($this->section->getSettings()->data);
        $data = json_decode($this->section->getData(), true);

        foreach ($data['rows'] as $rowId => $rowData) {
            if (!isset($rows[$rowId]) || $rows[$rowId]->formatRow($rowData) === '') {
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

    public function printAmendmentToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
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

    public function getMotionPlainText(): string
    {
        $data   = json_decode($this->section->getData(), true);
        $type = $this->section->getSettings();
        $rows = static::getTabularDataRowsFromData($type->data);

        $return = '';
        foreach ($data['rows'] as $rowId => $rowData) {
            if (!isset($rows[$rowId]) || $rows[$rowId]->formatRow($rowData) == '') {
                continue;
            }
            $return .= $rows[$rowId]->title . ': ';
            $return .= Exporter::encodeHTMLString($rows[$rowId]->formatRow($rowData));
            $return .= "\n";
        }
        return $return;
    }

    public function getAmendmentPlainText(): string
    {
        return '@TODO'; // @TODO
    }

    public function printMotionTeX(bool $isRight, Content $content, Consultation $consultation): void
    {
        $data = json_decode($this->section->getData(), true);
        if (!isset($data['rows'])) {
            return;
        }
        $type = $this->section->getSettings();
        $rows = static::getTabularDataRowsFromData($type->data);
        /*
        if ($isRight) {
            // This breaks the LaTeX, if there is no preceding content for the newline. Without the newline, however, the second line gets moved up.
            $content->textRight .= '\vspace{-0.3cm}\newline';
        }
        */
        foreach ($data['rows'] as $rowId => $rowData) {
            if (!isset($rows[$rowId]) || $rows[$rowId]->formatRow($rowData) === '') {
                continue;
            }
            if ($isRight) {
                $content->textRight .= '\textbf{' . Exporter::encodePlainString($rows[$rowId]->title) . ':}';
                $content->textRight .= "\\newline\n";
                $content->textRight .= Exporter::encodePlainString($rowData);
                $content->textRight .= '\vspace{0.2cm}';
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

    public function printAmendmentTeX(bool $isRight, Content $content): void
    {
        if ($isRight) {
            $content->textRight .= '[TEST DATA]'; // @TODO
        } else {
            $content->textMain .= '[TEST DATA]'; // @TODO
        }
    }

    public function getMotionODS(): string
    {
        return 'Test'; //  @TODO
    }

    public function getAmendmentODS(): string
    {
        return 'Test'; //  @TODO
    }

    public function printMotionToODT(Text $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[TABELLE]', false); // @TODO
    }

    public function printAmendmentToODT(Text $odt): void
    {
        $odt->addHtmlTextBlock('<h2>' . Html::encode($this->section->getSettings()->title) . '</h2>', false);
        $odt->addHtmlTextBlock('[TABELLE]', false); // @TODO
    }

    public function matchesFulltextSearch(string $text): bool
    {
        $type   = $this->section->getSettings();
        $data   = json_decode($this->section->getData(), true);
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
