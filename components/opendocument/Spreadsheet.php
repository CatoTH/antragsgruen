<?php

namespace app\components\opendocument;

class Spreadsheet extends Base
{
    const TYPE_TEXT   = 0;
    const TYPE_NUMBER = 1;
    const TYPE_HTML   = 2;

    /** @var \DOMDocument */
    protected $doc = null;

    /** @var \DOMElement */
    private $domTable;

    private $matrix           = [];
    private $matrixRows       = 0;
    private $matrixCols       = 0;
    private $matrixColWidths  = [];
    private $matrixRowHeights = [];

    private $rowNodes         = [];
    private $cellNodeMatrix   = [];
    private $cellStylesMatrix = [];

    /**
     * @param int $row
     * @param string $col
     * @param int $contentType
     * @param string $content
     * @param null|string $cssClass
     * @param null|string $styles
     */
    public function setCell($row, $col, $contentType, $content, $cssClass = null, $styles = null)
    {
        if (!isset($this->matrix[$row])) {
            $this->matrix[$row] = [];
        }
        if ($row > $this->matrixRows) {
            $this->matrixRows = $row;
        }
        if ($col > $this->matrixCols) {
            $this->matrixCols = $col;
        }
        $this->matrix[$row][$col] = [
            'type'    => $contentType,
            'content' => $content,
            'class'   => $cssClass,
            'styles'  => $styles,
        ];
    }

    /**
     * @param int $col
     * @param float $cm
     */
    public function setColumnWidth($col, $cm)
    {
        $this->matrixColWidths[$col] = $cm;
    }

    /**
     * @param int $row
     * @param float $cm
     */
    public function setMinRowHeight($row, $cm)
    {
        $rowHeight = (isset($this->matrixRowHeights[$row]) ? $this->matrixRowHeights[$row] : 1);
        if ($cm > $rowHeight) {
            $rowHeight = $cm;
        }
        $this->matrixRowHeights[$row] = $rowHeight;
    }

    /**
     * @return bool
     */
    protected function noNestedPs()
    {
        return true;
    }


    /**
     */
    private function appendBaseStyles()
    {
        $this->appendTextStyleNode('AntragsgruenBold', [
            'fo:font-weight'            => 'bold',
            'style:font-weight-asian'   => 'bold',
            'style:font-weight-complex' => 'bold',
        ]);
        $this->appendTextStyleNode('AntragsgruenItalic', [
            'fo:font-style'            => 'italic',
            'style:font-style-asian'   => 'italic',
            'style:font-style-complex' => 'italic',
        ]);
        $this->appendTextStyleNode('AntragsgruenUnderlined', [
            'style:text-underline-width' => 'auto',
            'style:text-underline-color' => 'font-color',
            'style:text-underline-style' => 'solid',
        ]);
        $this->appendTextStyleNode('AntragsgruenIns', [
            'fo:color'                   => '#00ff00',
            'style:text-underline-style' => 'solid',
            'style:text-underline-width' => 'auto',
            'style:text-underline-color' => 'font-color',
        ]);
        $this->appendTextStyleNode('AntragsgruenDel', [
            'fo:color'                     => '#ff0000',
            'style:text-line-through-type' => 'single',
        ]);
    }

    /**
     * @return \DOMElement
     * @throws \Exception
     */
    private function getCleanDomTable()
    {
        $domTables = $this->doc->getElementsByTagNameNS(static::NS_TABLE, 'table');
        if ($domTables->length != 1) {
            throw new \Exception('Could not parse ODS template');
        }

        $this->domTable = $domTables->item(0);

        $children = $this->domTable->childNodes;
        for ($i = $children->length - 1; $i >= 0; $i--) {
            $this->domTable->removeChild($children->item($i));
        }
        return $this->domTable;
    }


    /**
     */
    private function setColStyles()
    {
        for ($col = 0; $col <= $this->matrixCols; $col++) {
            $el = $this->doc->createElementNS(static::NS_TABLE, 'table-column');
            if (isset($this->matrixColWidths[$col])) {
                $el->setAttribute('table:style-name', 'Antragsgruen_col_' . $col);
                $this->appendColStyleNode('Antragsgruen_col_' . $col, [
                    'style:column-width' => $this->matrixColWidths[$col] . 'cm',
                ]);
            }
            $this->domTable->appendChild($el);
        }
    }


    /**
     */
    private function setCellContent()
    {
        for ($row = 0; $row <= $this->matrixRows; $row++) {
            $this->cellNodeMatrix[$row] = [];
            $currentRow                = $this->doc->createElementNS(static::NS_TABLE, 'table-row');
            for ($col = 0; $col <= $this->matrixCols; $col++) {
                $this->cellNodeMatrix[$row][$col] = [];
                $currentCell                     = $this->doc->createElementNS(static::NS_TABLE, 'table-cell');
                if (isset($this->matrix[$row][$col])) {
                    switch ($this->matrix[$row][$col]["type"]) {
                        case static::TYPE_TEXT:
                            $p              = $this->doc->createElementNS(static::NS_TEXT, 'p');
                            $p->textContent = $this->matrix[$row][$col]["content"];
                            $currentCell->appendChild($p);
                            break;
                        case static::TYPE_NUMBER:
                            $p              = $this->doc->createElementNS(static::NS_TEXT, 'p');
                            $p->textContent = $this->matrix[$row][$col]["content"];
                            $currentCell->appendChild($p);
                            $currentCell->setAttribute('calctext:value-type', 'float');
                            $currentCell->setAttribute('office:value-type', 'float');
                            $currentCell->setAttribute('office:value', (string)$this->matrix[$row][$col]["content"]);
                            break;
                        case static::TYPE_HTML:
                            $ps = $this->html2ooNodes($this->matrix[$row][$col]["content"], null);
                            foreach ($ps as $p) {
                                $currentCell->appendChild($p);
                            }
                            $this->setMinRowHeight($row, count($ps));
                            $this->setCellStyle($row, $col, [
                                "fo:wrap-option" => "wrap",
                            ], [
                                "fo:hyphenate" => "true",
                            ]);

                            $width  = (isset($this->matrixColWidths[$col]) ? $this->matrixColWidths[$col] : 2);
                            $height = (mb_strlen(strip_tags($this->matrix[$row][$col]['content'])) / ($width * 6));
                            $this->setMinRowHeight($row, $height);
                            break;
                    }
                }
                $currentRow->appendChild($currentCell);
                $this->cellNodeMatrix[$row][$col] = $currentCell;
            }
            $this->domTable->appendChild($currentRow);
            $this->rowNodes[$row] = $currentRow;
        }
    }

    /**
     * @param int $row
     * @param int $col
     * @param null|array $cellAttributes
     * @param null|array $textAttribuges
     */
    public function setCellStyle($row, $col, $cellAttributes, $textAttribuges)
    {
        if (!isset($this->cellStylesMatrix[$row])) {
            $this->cellStylesMatrix[$row] = [];
        }
        if (!isset($this->cellStylesMatrix[$row][$col])) {
            $this->cellStylesMatrix[$row][$col] = ['cell' => [], 'text' => []];
        }
        if (is_array($cellAttributes)) {
            foreach ($cellAttributes as $key => $val) {
                $this->cellStylesMatrix[$row][$col]['cell'][$key] = $val;
            }
        }
        if (is_array($textAttribuges)) {
            foreach ($textAttribuges as $key => $val) {
                $this->cellStylesMatrix[$row][$col]['text'][$key] = $val;
            }
        }
    }

    /**
     */
    public function setCellStyles()
    {
        for ($row = 0; $row <= $this->matrixRows; $row++) {
            for ($col = 0; $col <= $this->matrixCols; $col++) {
                if (isset($this->cellStylesMatrix[$row]) && isset($this->cellStylesMatrix[$row][$col])) {
                    $cell = $this->cellStylesMatrix[$row][$col];
                } else {
                    $cell = ['cell' => [], 'text' => []];
                }

                $styleId    = 'Antragsgruen_cell_' . $row . '_' . $col;
                $cellStyles = array_merge([
                    'style:vertical-align' => 'top'
                ], $cell['cell']);
                $this->appendCellStyleNode($styleId, $cellStyles, $cell['text']);
                /** @var \DOMElement $currentCell */
                $currentCell = $this->cellNodeMatrix[$row][$col];
                $currentCell->setAttribute('table:style-name', $styleId);
            }
        }

        foreach ($this->cellStylesMatrix as $rowNr => $row) {
            foreach ($row as $colNr => $cell) {
            }
        }
    }

    /**
     */
    public function setRowStyles()
    {
        foreach ($this->matrixRowHeights as $row => $height) {
            $styleName = 'Antragsgruen_row_' . $row;
            $this->appendRowStyleNode($styleName, [
                'style:row-height' => ($height * 0.45) . 'cm',
            ]);

            /** @var \DOMElement $node */
            $node = $this->rowNodes[$row];
            $node->setAttribute('table:style-name', $styleName);
        }
    }

    /**
     * @param int $fromRow
     * @param int $fromCol
     * @param int $toRow
     * @param int $toCol
     * @param float $width
     */
    public function drawBorder($fromRow, $fromCol, $toRow, $toCol, $width)
    {
        for ($i = $fromRow; $i <= $toRow; $i++) {
            $this->setCellStyle($i, $fromCol, [
                'fo:border-left' => $width . 'pt solid #000000',
            ], []);
            $this->setCellStyle($i, $toCol, [
                'fo:border-right' => $width . 'pt solid #000000',
            ], []);
        }

        for ($i = $fromCol; $i <= $toCol; $i++) {
            $this->setCellStyle($fromRow, $i, [
                'fo:border-top' => $width . 'pt solid #000000',
            ], []);
            $this->setCellStyle($toRow, $i, [
                'fo:border-bottom' => $width . 'pt solid #000000',
            ], []);
        }
    }


    /**
     * @return string
     * @throws \Exception
     */
    public function create()
    {
        $this->appendBaseStyles();
        $this->getCleanDomTable();
        $this->setColStyles();
        $this->setCellContent();
        $this->setRowStyles();
        $this->setCellStyles();

        $xml = $this->doc->saveXML();

        $rows = explode("\n", $xml);
        $rows[0] .= "\n"; // <?xml version="1.0" encoding="UTF-8"
        return implode("", $rows) . "\n";
    }

    /**
     * @param int $templateType
     * @throws \Exception
     * @return \DOMNode
     */
    protected function getNextNodeTemplate($templateType)
    {
        return $this->doc->createElement('text:span');
    }
}
