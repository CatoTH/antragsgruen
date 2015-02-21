<?php


class OdsTemplateEngine extends OOfficeTemplateEngine
{

    public static $TYPE_TEXT   = 0;
    public static $TYPE_NUMBER = 1;
    public static $TYPE_HTML   = 2;

    /** @var DOMDocument */
    protected $doc = null;

    /** @var DOMElement */
    private $dom_table;

    private $matrix             = array();
    private $matrix_rows        = 0;
    private $matrix_cols        = 0;
    private $matrix_col_widths  = array();
    private $matrix_row_heights = array();

    private $rowNodes         = array();
    private $cellNodeMatrix   = array();
    private $cellStylesMatrix = array();

    /**
     * @param int $row
     * @param string $col
     * @param int $content_type
     * @param string $content
     * @param null|string $css_class
     * @param null|string $styles
     */
    public function setCell($row, $col, $content_type, $content, $css_class = null, $styles = null)
    {
        if (!isset($this->matrix[$row])) {
            $this->matrix[$row] = array();
        }
        if ($row > $this->matrix_rows) {
            $this->matrix_rows = $row;
        }
        if ($col > $this->matrix_cols) {
            $this->matrix_cols = $col;
        }
        $this->matrix[$row][$col] = array(
            "type"    => $content_type,
            "content" => $content,
            "class"   => $css_class,
            "styles"  => $styles,
        );
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
        $row_height = (isset($this->matrix_row_heights[$row]) ? $this->matrix_row_heights[$row] : 1);
        if ($cm > $row_height) {
            $row_height = $cm;
        }
        $this->matrix_row_heights[$row] = $row_height;
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
        $this->appendTextStyleNode("Antragsgruen_fett", array(
            "fo:font-weight"            => "bold",
            "style:font-weight-asian"   => "bold",
            "style:font-weight-complex" => "bold",
        ));
        $this->appendTextStyleNode("Antragsgruen_kursiv", array(
            "fo:font-style"            => "italic",
            "style:font-style-asian"   => "italic",
            "style:font-style-complex" => "italic",
        ));
        $this->appendTextStyleNode("Antragsgruen_unterstrichen", array(
            "style:text-underline-width" => "auto",
            "style:text-underline-color" => "font-color",
            "style:text-underline-style" => "solid",
        ));
        $this->appendTextStyleNode("Antragsgruen_gruen", array(
            "fo:color" => "#00ff00",
        ));
        $this->appendTextStyleNode("Antragsgruen_rot", array(
            "fo:color" => "#ff0000",
        ));
    }

    /**
     * @return DOMElement
     * @throws Exception
     */
    private function getCleanDomTable()
    {
        $dom_tables = $this->doc->getElementsByTagNameNS(static::$NS_TABLE, 'table');
        if ($dom_tables->length != 1) {
            throw new Exception("Could not parse ODS template");
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
            $el = $this->doc->createElementNS(static::$NS_TABLE, 'table-column');
            if (isset($this->matrix_col_widths[$col])) {
                $el->setAttribute('table:style-name', 'Antragsgruen_col_' . $col);
                $this->appendColStyleNode('Antragsgruen_col_' . $col, array(
                    'style:column-width' => $this->matrix_col_widths[$col] . 'cm',
                ));
            }
            $this->dom_table->appendChild($el);
        }
    }


    /**
     */
    private function setCellContent()
    {
        for ($row = 0; $row <= $this->matrix_rows; $row++) {
            $this->cellNodeMatrix[$row] = array();
            $current_row                = $this->doc->createElementNS(static::$NS_TABLE, 'table-row');
            for ($col = 0; $col <= $this->matrix_cols; $col++) {
                $this->cellNodeMatrix[$row][$col] = array();
                $current_cell                     = $this->doc->createElementNS(static::$NS_TABLE, 'table-cell');
                if (isset($this->matrix[$row][$col])) {
                    switch ($this->matrix[$row][$col]["type"]) {
                        case static::$TYPE_TEXT:
                            $p              = $this->doc->createElementNS(static::$NS_TEXT, 'p');
                            $p->textContent = $this->matrix[$row][$col]["content"];
                            $current_cell->appendChild($p);
                            break;
                        case static::$TYPE_NUMBER:
                            $p              = $this->doc->createElementNS(static::$NS_TEXT, 'p');
                            $p->textContent = $this->matrix[$row][$col]["content"];
                            $current_cell->appendChild($p);
                            $current_cell->setAttribute('calctext:value-type', 'float');
                            $current_cell->setAttribute('office:value-type', 'float');
                            $current_cell->setAttribute('office:value', (string)$this->matrix[$row][$col]["content"]);
                            break;
                        case static::$TYPE_HTML:
                            $ps = $this->html2ooNodes($this->matrix[$row][$col]["content"], null);
                            foreach ($ps as $p) {
                                $current_cell->appendChild($p);
                            }
                            $this->setMinRowHeight($row, count($ps));
                            $this->setCellStyle($row, $col, array(
                                "fo:wrap-option" => "wrap",
                            ), array(
                                "fo:hyphenate" => "true",
                            ));

                            $width  = (isset($this->matrix_col_widths[$col]) ? $this->matrix_col_widths[$col] : 2);
                            $height = (mb_strlen(strip_tags($this->matrix[$row][$col]["content"])) / ($width * 6));
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
            $this->cellStylesMatrix[$row] = array();
        }
        if (!isset($this->cellStylesMatrix[$row][$col])) {
            $this->cellStylesMatrix[$row][$col] = array("cell" => array(), "text" => array());
        }
        if (is_array($cellAttributes)) {
            foreach ($cellAttributes as $key => $val) {
                $this->cellStylesMatrix[$row][$col]["cell"][$key] = $val;
            }
        }
        if (is_array($textAttribuges)) {
            foreach ($textAttribuges as $key => $val) {
                $this->cellStylesMatrix[$row][$col]["text"][$key] = $val;
            }
        }
    }

    /**
     */
    public function setCellStyles()
    {
        for ($row = 0; $row <= $this->matrix_rows; $row++) {
            for ($col = 0; $col <= $this->matrix_cols; $col++) {
                $cell = (isset($this->cellStylesMatrix[$row]) && isset($this->cellStylesMatrix[$row][$col]) ? $this->cellStylesMatrix[$row][$col] : array("cell" => array(), "text" => array()));

                $styleId    = 'Antragsgruen_cell_' . $row . '_' . $col;
                $cellStyles = array_merge(array(
                    "style:vertical-align" => "top"
                ), $cell["cell"]);
                $this->appendCellStyleNode($styleId, $cellStyles, $cell["text"]);
                /** @var DOMElement $current_cell */
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
        foreach ($this->matrix_row_heights as $row => $height) {
            $style_name = "Antragsgruen_row_" . $row;
            $this->appendRowStyleNode($style_name, array(
                'style:row-height' => ($height * 0.45) . 'cm',
            ));

            /** @var DOMElement $node */
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
            $this->setCellStyle($i, $from_col, array(
                "fo:border-left" => $width . "pt solid #000000",
            ), array());
            $this->setCellStyle($i, $to_col, array(
                "fo:border-right" => $width . "pt solid #000000",
            ), array());
        }

        for ($i = $from_col; $i <= $to_col; $i++) {
            $this->setCellStyle($from_row, $i, array(
                "fo:border-top" => $width . "pt solid #000000",
            ), array());
            $this->setCellStyle($to_row, $i, array(
                "fo:border-bottom" => $width . "pt solid #000000",
            ), array());
        }
    }


    /**
     * @return string
     * @throws Exception
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
     * @return DOMNode
     */
    protected function getNextNodeTemplate($template_type)
    {
        return $this->doc->createElement('text:span');
    }
}
