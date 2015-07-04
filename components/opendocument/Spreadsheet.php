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
    private $dom_table;

    private $matrix             = [];
    private $matrix_rows        = 0;
    private $matrix_cols        = 0;
    private $matrix_col_widths  = [];
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
        if ($row > $this->matrix_rows) {
            $this->matrix_rows = $row;
        }
        if ($col > $this->matrix_cols) {
            $this->matrix_cols = $col;
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
        $this->matrix_col_widths[$col] = $cm;
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
        $dom_tables = $this->doc->getElementsByTagNameNS(static::NS_TABLE, 'table');
        if ($dom_tables->length != 1) {
            throw new \Exception('Could not parse ODS template');
        }

        $this->dom_table = $dom_tables->item(0);

        $children = $this->dom_table->childNodes;
        for ($i = $children->length - 1; $i >= 0; $i--) {
            $this->dom_table->removeChild($children->item($i));
        }
        return $this->dom_table;
    }


    /**
     */
    private function setColStyles()
    {
        for ($col = 0; $col <= $this->matrix_cols; $col++) {
            $el = $this->doc->createElementNS(static::NS_TABLE, 'table-column');
            if (isset($this->matrix_col_widths[$col])) {
                $el->setAttribute('table:style-name', 'Antragsgruen_col_' . $col);
                $this->appendColStyleNode('Antragsgruen_col_' . $col, [
                    'style:column-width' => $this->matrix_col_widths[$col] . 'cm',
                ]);
            }
            $this->dom_table->appendChild($el);
        }
    }


    /**
     */
    private function setCellContent()
    {
        for ($row = 0; $row <= $this->matrix_rows; $row++) {
            $this->cellNodeMatrix[$row] = [];
            $current_row                = $this->doc->createElementNS(static::NS_TABLE, 'table-row');
            for ($col = 0; $col <= $this->matrix_cols; $col++) {
                $this->cellNodeMatrix[$row][$col] = [];
                $current_cell                     = $this->doc->createElementNS(static::NS_TABLE, 'table-cell');
                if (isset($this->matrix[$row][$col])) {
                    switch ($this->matrix[$row][$col]["type"]) {
                        case static::TYPE_TEXT:
                            $p              = $this->doc->createElementNS(static::NS_TEXT, 'p');
                            $p->textContent = $this->matrix[$row][$col]["content"];
                            $current_cell->appendChild($p);
                            break;
                        case static::TYPE_NUMBER:
                            $p              = $this->doc->createElementNS(static::NS_TEXT, 'p');
                            $p->textContent = $this->matrix[$row][$col]["content"];
                            $current_cell->appendChild($p);
                            $current_cell->setAttribute('calctext:value-type', 'float');
                            $current_cell->setAttribute('office:value-type', 'float');
                            $current_cell->setAttribute('office:value', (string)$this->matrix[$row][$col]["content"]);
                            break;
                        case static::TYPE_HTML:
                            $ps = $this->html2ooNodes($this->matrix[$row][$col]["content"], null);
                            foreach ($ps as $p) {
                                $current_cell->appendChild($p);
                            }
                            $this->setMinRowHeight($row, count($ps));
                            $this->setCellStyle($row, $col, [
                                "fo:wrap-option" => "wrap",
                            ], [
                                "fo:hyphenate" => "true",
                            ]);

                            $width  = (isset($this->matrix_col_widths[$col]) ? $this->matrix_col_widths[$col] : 2);
                            $height = (mb_strlen(strip_tags($this->matrix[$row][$col]['content'])) / ($width * 6));
                            $this->setMinRowHeight($row, $height);
                            break;
                    }
                }
                $current_row->appendChild($current_cell);
                $this->cellNodeMatrix[$row][$col] = $current_cell;
            }
            $this->dom_table->appendChild($current_row);
            $this->rowNodes[$row] = $current_row;
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
        for ($row = 0; $row <= $this->matrix_rows; $row++) {
            for ($col = 0; $col <= $this->matrix_cols; $col++) {
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
                /** @var \DOMElement $current_cell */
                $current_cell = $this->cellNodeMatrix[$row][$col];
                $current_cell->setAttribute('table:style-name', $styleId);
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
            $style_name = 'Antragsgruen_row_' . $row;
            $this->appendRowStyleNode($style_name, [
                'style:row-height' => ($height * 0.45) . 'cm',
            ]);

            /** @var \DOMElement $node */
            $node = $this->rowNodes[$row];
            $node->setAttribute('table:style-name', $style_name);
        }
    }

    /**
     * @param int $from_row
     * @param int $from_col
     * @param int $to_row
     * @param int $to_col
     * @param float $width
     */
    public function drawBorder($from_row, $from_col, $to_row, $to_col, $width)
    {
        for ($i = $from_row; $i <= $to_row; $i++) {
            $this->setCellStyle($i, $from_col, [
                'fo:border-left' => $width . 'pt solid #000000',
            ], []);
            $this->setCellStyle($i, $to_col, [
                'fo:border-right' => $width . 'pt solid #000000',
            ], []);
        }

        for ($i = $from_col; $i <= $to_col; $i++) {
            $this->setCellStyle($from_row, $i, [
                'fo:border-top' => $width . 'pt solid #000000',
            ], []);
            $this->setCellStyle($to_row, $i, [
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
     * @param int
     * @throws \Exception
     * @return \DOMNode
     */
    protected function getNextNodeTemplate($template_type)
    {
        return $this->doc->createElement('text:span');
    }
}
