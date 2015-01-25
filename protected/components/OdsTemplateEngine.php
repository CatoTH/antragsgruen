<?php


class OdsTemplateEngine extends OOfficeTemplateEngine
{

    public static $TYPE_TEXT   = 0;
    public static $TYPE_NUMBER = 1;
    public static $TYPE_HTML   = 2;

    /** @var DOMDocument */
    protected $doc = null;

    private $matrix      = array();
    private $matrix_rows = 0;
    private $matrix_cols = 0;

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
     * @return string
     * @throws Exception
     */
    public function create()
    {
        $this->appendStyleNode("Antragsgruen_fett", array(
            "fo:font-weight"            => "bold",
            "style:font-weight-asian"   => "bold",
            "style:font-weight-complex" => "bold",
        ));
        $this->appendStyleNode("Antragsgruen_kursiv", array(
            "fo:font-style"            => "italic",
            "style:font-style-asian"   => "italic",
            "style:font-style-complex" => "italic",
        ));
        $this->appendStyleNode("Antragsgruen_unterstrichen", array(
            "style:text-underline-width" => "auto",
            "style:text-underline-color" => "font-color",
            "style:text-underline-style" => "solid",
        ));
        $this->appendStyleNode("Antragsgruen_gruen", array(
            "fo:color" => "#00ff00",
        ));
        $this->appendStyleNode("Antragsgruen_rot", array(
            "fo:color" => "#ff0000",
        ));

        $dom_tables = $this->doc->getElementsByTagNameNS(static::$NS_TABLE, 'table');
        if ($dom_tables->length != 1) throw new Exception("Could not parse ODS template");

        /** @var DOMElement $dom_table */
        $dom_table = $dom_tables->item(0);

        $children = $dom_table->childNodes;
        for ($i = $children->length - 1; $i >= 0; $i--) $dom_table->removeChild($children->item($i));

        for ($row = 0; $row <= $this->matrix_rows; $row++) {
            $current_row = $this->doc->createElementNS(static::$NS_TABLE, 'table-row');
            for ($col = 0; $col <= $this->matrix_cols; $col++) {
                $current_cell = $this->doc->createElementNS(static::$NS_TABLE, 'table-cell');
                if (isset($this->matrix[$row][$col])) {
                    $p = $this->doc->createElementNS(static::$NS_TEXT, 'p');
                    $p->textContent = $this->matrix[$row][$col]["content"];
                    $current_cell->appendChild($p);
                }
                $current_row->appendChild($current_cell);
            }
            $dom_table->appendChild($current_row);
        }

        return $this->doc->saveXML();
    }
}
