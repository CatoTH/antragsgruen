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

    public static $FORMAT_NAMES = [
        0 => 'linebreak',
        1 => 'bold',
        2 => 'italic',
        3 => 'underlined',
        4 => 'strike',
        5 => 'ins',
        6 => 'del',
        7 => 'link',
        8 => 'indented',
    ];

    /** @var \DOMDocument */
    protected $doc = null;

    /** @var \DOMElement */
    protected $domTable;

    protected $matrix           = [];
    protected $matrixRows       = 0;
    protected $matrixCols       = 0;
    protected $matrixColWidths  = [];
    protected $matrixRowHeights = [];

    protected $rowNodes         = [];
    protected $cellNodeMatrix   = [];
    protected $cellStylesMatrix = [];

    protected $classCache = [];


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
     */
    protected function initRow($row)
    {
        if (!isset($this->matrix[$row])) {
            $this->matrix[$row] = [];
        }
        if ($row > $this->matrixRows) {
            $this->matrixRows = $row;
        }
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
        $this->initRow($row);
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
        $this->initRow($row);
        $rowHeight = (isset($this->matrixRowHeights[$row]) ? $this->matrixRowHeights[$row] : 1);
        if ($cm > $rowHeight) {
            $rowHeight = $cm;
        }
        $this->matrixRowHeights[$row] = $rowHeight;
    }

    /**
     * @return \DOMElement
     * @throws \Exception
     */
    protected function getCleanDomTable()
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
    protected function setColStyles()
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
    protected function setCellContent()
    {
        for ($row = 0; $row <= $this->matrixRows; $row++) {
            $this->cellNodeMatrix[$row] = [];
            $currentRow                 = $this->doc->createElementNS(static::NS_TABLE, 'table-row');
            for ($col = 0; $col <= $this->matrixCols; $col++) {
                $this->cellNodeMatrix[$row][$col] = [];
                $currentCell                      = $this->doc->createElementNS(static::NS_TABLE, 'table-cell');
                if (isset($this->matrix[$row][$col])) {
                    $cell = $this->matrix[$row][$col];
                    switch ($cell["type"]) {
                        case static::TYPE_TEXT:
                            $p              = $this->doc->createElementNS(static::NS_TEXT, 'p');
                            $p->textContent = $cell['content'];
                            $currentCell->appendChild($p);
                            break;
                        case static::TYPE_NUMBER:
                            $p              = $this->doc->createElementNS(static::NS_TEXT, 'p');
                            $p->textContent = $cell['content'];
                            $currentCell->appendChild($p);
                            $currentCell->setAttribute('calctext:value-type', 'float');
                            $currentCell->setAttribute('office:value-type', 'float');
                            $currentCell->setAttribute('office:value', (string)$cell['content']);
                            break;
                        case static::TYPE_HTML:
                            $nodes = $this->html2OdsNodes($cell['content']);
                            foreach ($nodes as $node) {
                                $currentCell->appendChild($node);
                            }

                            //$this->setMinRowHeight($row, count($ps));
                            $styles = $cell['styles'];
                            if (isset($styles['fo:wrap-option']) && $styles['fo:wrap-option'] == 'no-wrap') {
                                $wrap   = 'no-wrap';
                                $height = 1;
                            } else {
                                $wrap   = 'wrap';
                                $width  = (isset($this->matrixColWidths[$col]) ? $this->matrixColWidths[$col] : 2);
                                $height = (mb_strlen(strip_tags($this->matrix[$row][$col]['content'])) / ($width * 6));
                            }
                            $this->setCellStyle($row, $col, [
                                'fo:wrap-option' => $wrap,
                            ], [
                                'fo:hyphenate' => 'true',
                            ]);
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
    protected function node2Formatting(\DOMElement $node, $currentFormats)
    {
        switch ($node->nodeName) {
            case 'span':
                if ($node->hasAttribute('class')) {
                    $classes = explode(' ', $node->getAttribute('class'));
                    if (in_array('underline', $classes)) {
                        $currentFormats[] = static::FORMAT_UNDERLINED;
                    }
                    if (in_array('strike', $classes)) {
                        $currentFormats[] = static::FORMAT_STRIKE;
                    }
                    if (in_array('ins', $classes)) {
                        $currentFormats[] = static::FORMAT_INS;
                    }
                    if (in_array('inserted', $classes)) {
                        $currentFormats[] = static::FORMAT_INS;
                    }
                    if (in_array('del', $classes)) {
                        $currentFormats[] = static::FORMAT_DEL;
                    }
                    if (in_array('deleted', $classes)) {
                        $currentFormats[] = static::FORMAT_DEL;
                    }
                }
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
                if ($node->hasAttribute('class')) {
                    $classes          = explode(' ', $node->getAttribute('class'));
                    $currentFormats[] = static::FORMAT_INDENTED;
                    if (in_array('ins', $classes)) {
                        $currentFormats[] = static::FORMAT_INS;
                    }
                    if (in_array('inserted', $classes)) {
                        $currentFormats[] = static::FORMAT_INS;
                    }
                    if (in_array('del', $classes)) {
                        $currentFormats[] = static::FORMAT_DEL;
                    }
                    if (in_array('deleted', $classes)) {
                        $currentFormats[] = static::FORMAT_DEL;
                    }
                }
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
    protected function tokenizeFlattenHtml(\DOMNode $node, $currentFormats)
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
     * @param array $formats
     * @return string
     */
    protected function getClassByFormats($formats)
    {
        sort($formats);
        $key = implode('_', $formats);
        if (!isset($this->classCache[$key])) {
            $name   = 'Antragsgruen';
            $styles = [];
            foreach ($formats as $format) {
                $name .= '_' . static::$FORMAT_NAMES[$format];
                switch ($format) {
                    case static::FORMAT_INS:
                        $styles['fo:color']                   = '#00ff00';
                        $styles['style:text-underline-style'] = 'solid';
                        $styles['style:text-underline-width'] = 'auto';
                        $styles['style:text-underline-color'] = 'font-color';
                        break;
                    case static::FORMAT_DEL:
                        $styles['fo:color']                     = '#ff0000';
                        $styles['style:text-line-through-type'] = 'single';
                        break;
                    case static::FORMAT_BOLD:
                        $styles['fo:font-weight']            = 'bold';
                        $styles['style:font-weight-asian']   = 'bold';
                        $styles['style:font-weight-complex'] = 'bold';
                        break;
                    case static::FORMAT_UNDERLINED:
                        $styles['style:text-underline-width'] = 'auto';
                        $styles['style:text-underline-color'] = 'font-color';
                        $styles['style:text-underline-style'] = 'solid';
                        break;
                    case static::FORMAT_STRIKE:
                        $styles['style:text-line-through-type'] = 'single';
                        break;
                    case static::FORMAT_ITALIC:
                        $styles['fo:font-style']            = 'italic';
                        $styles['style:font-style-asian']   = 'italic';
                        $styles['style:font-style-complex'] = 'italic';
                        break;
                }
            }
            $this->appendTextStyleNode($name, $styles);
            $this->classCache[$key] = $name;
        }
        return $this->classCache[$key];
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
                $node = $this->doc->createElement('text:span');
                if (count($token['formattings']) > 0) {
                    $className = $this->getClassByFormats($token['formattings']);
                    $node->setAttribute('text:style-name', $className);
                }
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
