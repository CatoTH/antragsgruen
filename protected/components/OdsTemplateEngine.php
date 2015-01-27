<?php


class OdsTemplateEngine extends OOfficeTemplateEngine
{

    public static $TYPE_TEXT   = 0;
    public static $TYPE_NUMBER = 1;
    public static $TYPE_HTML   = 2;

    /** @var DOMDocument */
    protected $doc = null;

    private $matrix            = array();
    private $matrix_rows       = 0;
    private $matrix_cols       = 0;
    private $matrix_col_widths = array();

    public function setCell($row, $col, $content_type, $content, $css_class, $styles)
    {
        if (!isset($this->matrix[$row])) $this->matrix[$row] = array();
        if ($row > $this->matrix_rows) $this->matrix_rows = $row;
        if ($col > $this->matrix_cols) $this->matrix_cols = $col;
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
     * @return string
     * @throws Exception
     */
    public function create()
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

        $dom_tables = $this->doc->getElementsByTagNameNS(static::$NS_TABLE, 'table');
        if ($dom_tables->length != 1) throw new Exception("Could not parse ODS template");

        /** @var DOMElement $dom_table */
        $dom_table = $dom_tables->item(0);

        $children = $dom_table->childNodes;
        for ($i = $children->length - 1; $i >= 0; $i--) $dom_table->removeChild($children->item($i));

        for ($col = 0; $col <= $this->matrix_cols; $col++) {
            $el = $this->doc->createElementNS(static::$NS_TABLE, 'table-column');
            if (isset($this->matrix_col_widths[$col])) {
                $el->setAttribute('table:style-name', 'Antragsgruen_col_' . $col);
                $this->appendColStyleNode('Antragsgruen_col_' . $col, array(
                    'style:column-width' => $this->matrix_col_widths[$col] . 'cm',
                ));
            }
            $dom_table->appendChild($el);
        }

        for ($row = 0; $row <= $this->matrix_rows; $row++) {
            $current_row = $this->doc->createElementNS(static::$NS_TABLE, 'table-row');
            $row_height  = 1;
            for ($col = 0; $col <= $this->matrix_cols; $col++) {
                $current_cell = $this->doc->createElementNS(static::$NS_TABLE, 'table-cell');
                if (isset($this->matrix[$row][$col])) switch ($this->matrix[$row][$col]["type"]) {
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
                        foreach ($ps as $p) $current_cell->appendChild($p);
                        if (count($ps) > $row_height) $row_height = count($ps);
                        break;
                }
                $current_row->appendChild($current_cell);
            }
            if ($row_height > 1) {
                $style_name = "Antragsgruen_row_" . $row;
                $this->appendRowStyleNode($style_name, array(
                    'style:row-height' => ($row_height * 0.45) . 'cm',
                ));
                $current_row->setAttribute('table:style-name', $style_name);
            }
            $dom_table->appendChild($current_row);
        }

        return $this->doc->saveXML();
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
