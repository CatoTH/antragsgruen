<?php

namespace app\components\opendocument;

use app\components\HTMLTools;

class Spreadsheet extends Base
{
    const TYPE_TEXT   = 0;
    const TYPE_NUMBER = 1;
    const TYPE_HTML   = 2;

    const FORMAT_LINEBREAK  = 0;
    const FORMAT_BOLD       = 1;
    const FORMAT_ITALIC     = 2;
    const FORMAT_UNDERLINED = 3;
    const FORMAT_STRIKE     = 4;
    const FORMAT_INS        = 5;
    const FORMAT_DEL        = 6;
    const FORMAT_LINK       = 7;
    const FORMAT_INDENTED   = 8;

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
     * @param string $styleName
     * @param array $cellAttributes
     * @param array $textAttributes
     */
    protected function appendCellStyleNode($styleName, $cellAttributes, $textAttributes)
    {
        $node = $this->doc->createElementNS(static::NS_STYLE, "style");
        $node->setAttribute("style:name", $styleName);
        $node->setAttribute("style:family", 'table-cell');
        $node->setAttribute("style:parent-style-name", "Default");

        if (count($cellAttributes) > 0) {
            $style = $this->doc->createElementNS(static::NS_STYLE, 'table-cell-properties');
            foreach ($cellAttributes as $att_name => $att_val) {
                $style->setAttribute($att_name, $att_val);
            }
            $node->appendChild($style);
        }
        if (count($textAttributes) > 0) {
            $style = $this->doc->createElementNS(static::NS_STYLE, 'text-properties');
            foreach ($textAttributes as $att_name => $att_val) {
                $style->setAttribute($att_name, $att_val);
            }
            $node->appendChild($style);
        }

        foreach ($this->doc->getElementsByTagNameNS(static::NS_OFFICE, 'automatic-styles') as $element) {
            /** @var \DOMElement $element */
            $element->appendChild($node);
        }
    }


    /**
     * @param string $styleName
     * @param array $attributes
     */
    protected function appendColStyleNode($styleName, $attributes)
    {
        $this->appendStyleNode($styleName, 'table-column', 'table-column-properties', $attributes);
    }

    /**
     * @param string $styleName
     * @param array $attributes
     */
    protected function appendRowStyleNode($styleName, $attributes)
    {
        $this->appendStyleNode($styleName, 'table-row', 'table-row-properties', $attributes);
    }

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
            $currentRow                 = $this->doc->createElementNS(static::NS_TABLE, 'table-row');
            for ($col = 0; $col <= $this->matrixCols; $col++) {
                $this->cellNodeMatrix[$row][$col] = [];
                $currentCell                      = $this->doc->createElementNS(static::NS_TABLE, 'table-cell');
                if (isset($this->matrix[$row][$col])) {
                    switch ($this->matrix[$row][$col]["type"]) {
                        case static::TYPE_TEXT:
                            $p              = $this->doc->createElementNS(static::NS_TEXT, 'p');
                            $p->textContent = $this->matrix[$row][$col]['content'];
                            $currentCell->appendChild($p);
                            break;
                        case static::TYPE_NUMBER:
                            $p              = $this->doc->createElementNS(static::NS_TEXT, 'p');
                            $p->textContent = $this->matrix[$row][$col]['content'];
                            $currentCell->appendChild($p);
                            $currentCell->setAttribute('calctext:value-type', 'float');
                            $currentCell->setAttribute('office:value-type', 'float');
                            $currentCell->setAttribute('office:value', (string)$this->matrix[$row][$col]['content']);
                            break;
                        case static::TYPE_HTML:
                            $nodes = $this->html2OdsNodes($this->matrix[$row][$col]['content']);
                            foreach ($nodes as $node) {
                                $currentCell->appendChild($node);
                            }

                            //$this->setMinRowHeight($row, count($ps));
                            $this->setCellStyle($row, $col, [
                                'fo:wrap-option' => 'wrap',
                            ], [
                                'fo:hyphenate' => 'true',
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
     * @param \DOMElement $node
     * @param array $currentFormats
     * @return array
     */
    private function node2Formatting(\DOMElement $node, $currentFormats)
    {
        switch ($node->nodeName) {
            case 'span':
                // @TODO Formattings
                break;
            case 'b':
            case 'strong':
                $currentFormats[] = static::FORMAT_BOLD;
                break;
            case 'i':
            case 'em':
                $currentFormats[] = static::FORMAT_ITALIC;
                break;
            case 'u':
                $currentFormats[] = static::FORMAT_UNDERLINED;
                break;
            case 'br':
                break;
            case 'p':
            case 'div':
            case 'blockquote':
                break;
            case 'ul':
            case 'ol':
                $currentFormats[] = static::FORMAT_INDENTED;
                break;
            case 'li':
                break;
            case 'del':
                $currentFormats[] = static::FORMAT_DEL;
                break;
            case 'ins':
                $currentFormats[] = static::FORMAT_INS;
                break;
            case 'a':
                $currentFormats[] = static::FORMAT_LINK;
                try {
                    $attr = $node->getAttribute('href');
                    if ($attr) {
                        $currentFormats['href'] = $attr;
                    }
                } catch (\Exception $e) {
                }
                break;
            default:
                die('Unknown Tag: ' . $node->nodeName);
        }
        return $currentFormats;
    }

    /**
     * @param \DOMNode $node
     * @param array $currentFormats
     * @return array
     */
    private function tokenizeFlattenHtml(\DOMNode $node, $currentFormats)
    {
        $return = [];
        foreach ($node->childNodes as $child) {
            switch ($child->nodeType) {
                case XML_ELEMENT_NODE:
                    /** @var \DOMElement $child */
                    $formattings = $this->node2Formatting($child, $currentFormats);
                    $children    = $this->tokenizeFlattenHtml($child, $formattings);
                    $return      = array_merge($return, $children);
                    if (in_array($child->nodeName, ['br', 'div', 'p', 'li', 'blockquote'])) {
                        $return[] = [
                            'text'        => '',
                            'formattings' => [static::FORMAT_LINEBREAK],
                        ];
                    }
                    break;
                case XML_TEXT_NODE:
                    /** @var \DOMText $child */
                    $return[] = [
                        'text'        => $child->data,
                        'formattings' => $currentFormats,
                    ];
                    break;
                default:
            }
        }

        return $return;
    }

    /**
     * @param string $html
     * @return array
     */
    public function html2OdsNodes($html)
    {
        $body     = HTMLTools::html2DOM($html);
        $tokens   = $this->tokenizeFlattenHtml($body, []);
        $nodes    = [];
        $currentP = $this->doc->createElementNS(static::NS_TEXT, 'p');
        foreach ($tokens as $token) {
            if (trim($token['text']) != '') {
                $node     = $this->doc->createElement('text:span');
                $textNode = $this->doc->createTextNode($token['text']);
                $node->appendChild($textNode);
                $currentP->appendChild($node);
            }

            if (in_array(static::FORMAT_LINEBREAK, $token['formattings'])) {
                $nodes[]  = $currentP;
                $currentP = $this->doc->createElementNS(static::NS_TEXT, 'p');
            }
        }
        $nodes[] = $currentP;
        return $nodes;
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
        $rows[0] .= "\n";
        return implode('', $rows) . "\n";
    }
}
