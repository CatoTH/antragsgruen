<?php

namespace app\components\latex;

use app\components\HTMLTools;
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;

class Exporter
{
    /**
     * @param string $str
     * @return string
     */
    public static function encodePlainString($str)
    {
        $replaces = [
            '\\'                         => '\textbackslash{}',
            '&'                          => '\&',
            '%'                          => '\%',
            '$'                          => '\$',
            '#'                          => '\#',
            '_'                          => '\_',
            '{'                          => '\{',
            '}'                          => '\}',
            '\textbackslash\{\}'         => '\textbackslash{}',
            '~'                          => '\texttt{\~{}}',
            '^'                          => '\^{}',
            '\#\#\#LINENUMBER\#\#\#'     => '###LINENUMBER###',
            '\#\#\#LINEBREAK\#\#\#'      => '###LINEBREAK###',
            '\#\#\#FORCELINEBREAK\#\#\#' => '###FORCELINEBREAK###',
        ];
        return str_replace(array_keys($replaces), array_values($replaces), $str);
    }

    /**
     * @param \DOMNode $node
     * @return string
     * @throws Internal
     */
    private static function encodeHTMLNode(\DOMNode $node)
    {
        if ($node->nodeType == XML_TEXT_NODE) {
            /** @var \DOMText $node */
            return static::encodePlainString($node->data);
        } else {
            $content = '';
            foreach ($node->childNodes as $child) {
                /** @var \DOMNode $child */
                $content .= static::encodeHTMLNode($child);
            }

            /** @var \DOMElement $node */
            if ($node->hasAttribute('class')) {
                $classes = explode(' ', $node->getAttribute('class'));
            } else {
                $classes = [];
            }

            switch ($node->nodeName) {
                case 'br':
                    return '\newline' . "\n";
                case 'p':
                    return $content . "\n";
                case 'strong':
                case 'b':
                    return '\textbf{' . $content . '}';
                case 'em':
                case 'i':
                    return '\emph{' . $content . '}';
                case 'u':
                    return '\underline{' . $content . '}';
                case 's':
                    return '\sout{' . $content . '}';
                case 'blockquote':
                    return '\begin{quotation}\noindent' . "\n" . $content . '\end{quotation}' . "\n";
                case 'ul':
                    if (in_array('ins', $classes)) {
                        $content = '\color{Insert}{' . $content . '}';
                    }
                    if (in_array('inserted', $classes)) {
                        $content = '\color{Insert}{' . $content . '}';
                    }
                    if (in_array('del', $classes)) {
                        $content = '\color{Delete}{' . $content . '}';
                    }
                    if (in_array('deleted', $classes)) {
                        $content = '\color{Delete}{' . $content . '}';
                    }
                    return '\begin{itemize}' . "\n" . $content . '\end{itemize}' . "\n";
                case 'ol':
                    $firstLine = '';
                    if ($node->hasAttribute('start')) {
                        $firstLine = '\setcounter{enumi}{' . ($node->getAttribute('start') - 1) . '}' . "\n";
                    }
                    if (in_array('ins', $classes)) {
                        $content = '\color{Insert}{' . $content . '}';
                    }
                    if (in_array('inserted', $classes)) {
                        $content = '\color{Insert}{' . $content . '}';
                    }
                    if (in_array('del', $classes)) {
                        $content = '\color{Delete}{' . $content . '}';
                    }
                    if (in_array('deleted', $classes)) {
                        $content = '\color{Delete}{' . $content . '}';
                    }
                    return '\begin{enumerate}' . "\n" . $firstLine . $content . '\end{enumerate}' . "\n";
                case 'li':
                    return '\item ' . $content . "\n";
                case 'a':
                    if ($node->hasAttribute('href')) {
                        $content = '\href{' . $node->getAttribute('href') . '}{' . $content . '}';
                    }
                    return $content;
                case 'span':
                    if (count($classes) == 0) {
                        return $content;
                    }
                    if (in_array('underline', $classes)) {
                        $content = '\underline{' . $content . '}';
                    }
                    if (in_array('strike', $classes)) {
                        $content = '\sout{' . $content . '}';
                    }
                    if (in_array('ins', $classes)) {
                        $content = '\color{Insert}{' . $content . '}';
                    }
                    if (in_array('inserted', $classes)) {
                        $content = '\color{Insert}{' . $content . '}';
                    }
                    if (in_array('del', $classes)) {
                        $content = '\color{Delete}{' . $content . '}';
                    }
                    if (in_array('deleted', $classes)) {
                        $content = '\color{Delete}{' . $content . '}';
                    }
                    return $content;
                case 'del':
                    return '\color{Delete}{' . $content . '}';
                case 'ins':
                    return '\color{Insert}{' . $content . '}';
                default:
                    //return $content;
                    throw new Internal('Unknown Tag: ' . $node->nodeName);
            }
        }
    }

    /**
     * @param string $str
     * @return string
     */
    public static function encodeHTMLString($str)
    {
        $str     = HTMLTools::cleanTrustedHtml($str);
        $src_doc = new \DOMDocument();

        $src_doc->loadHTML('<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head><body>' . $str . "</body></html>");
        $bodies = $src_doc->getElementsByTagName('body');
        $body   = $bodies->item(0);

        $out = '';
        for ($i = 0; $i < $body->childNodes->length; $i++) {
            /** @var \DOMNode $child */
            $child = $body->childNodes->item($i);
            $out .= static::encodeHTMLNode($child);
        }

        return $out;
    }

    /**
     * @param Layout $layout
     * @return string
     */
    public static function createLayoutString(Layout $layout)
    {
        $template                = $layout->template;
        $template                = str_replace("\r", "", $template);
        $replaces                = [];
        $replaces['%LANGUAGE%']  = $layout->language;
        $replaces['%ASSETROOT%'] = $layout->assetRoot;
        $replaces['%TITLE%']     = $layout->title;
        $replaces['%AUTHOR%']    = $layout->author;
        $template                = str_replace(array_keys($replaces), array_values($replaces), $template);
        return $template;
    }

    /**
     * @param Content $content
     * @return string
     */
    public static function createContentString(Content $content)
    {
        $template                         = $content->template;
        $template                         = str_replace("\r", "", $template);
        $replaces                         = [];
        $replaces['%TITLE%']              = $content->title;
        $replaces['%TITLEPREFIX%']        = $content->titlePrefix;
        $replaces['%TITLE_LONG%']         = $content->titleLong;
        $replaces['%AUTHOR%']             = $content->author;
        $replaces['%MOTION_DATA_TABLE%']  = $content->motionDataTable;
        $replaces['%TEXT%']               = $content->text;
        $replaces['%INTRODUCTION_BIG%']   = $content->introductionBig;
        $replaces['%INTRODUCTION_SMALL%'] = $content->introductionSmall;
        $template                         = str_replace(array_keys($replaces), array_values($replaces), $template);
        return $template;
    }

    /**
     * @param Layout $layout
     * @param Content[] $contents
     * @param AntragsgruenApp $app
     * @return string
     * @throws Internal
     */
    public static function createPDF(Layout $layout, $contents, AntragsgruenApp $app)
    {
        if (!$app->xelatexPath) {
            throw new Internal('LaTeX/XeTeX-Export is not enabled');
        }
        $layoutStr  = static::createLayoutString($layout);
        $contentStr = '';
        $count      = 0;
        foreach ($contents as $content) {
            if ($count > 0) {
                $contentStr .= "\n\\newpage\n";
            }
            $contentStr .= static::createContentString($content);
            $count++;
        }
        $str = str_replace('%CONTENT%', $contentStr, $layoutStr);

        if (YII_ENV_DEV && isset($_REQUEST['latex_src'])) {
            Header('Content-Type: text/plain');
            echo $str;
            die();
        }

        $filenameBase = $app->tmpDir . uniqid('motion-pdf');
        file_put_contents($filenameBase . '.tex', $str);

        $cmd = $app->xelatexPath;
        $cmd .= ' -interaction=batchmode';
        $cmd .= ' -output-directory="' . $app->tmpDir . '"';
        if ($app->xdvipdfmx) {
            $cmd .= ' -output-driver="' . $app->xdvipdfmx . '"';
        }
        $cmd .= ' "' . $filenameBase . '.tex"';

        shell_exec($cmd);
        shell_exec($cmd); // Do it twice, to get the LastPage-reference right

        if (!file_exists($filenameBase . '.pdf')) {
            throw new Internal('An error occurred while creating the PDF: ' . $cmd);
        }
        $pdf = file_get_contents($filenameBase . '.pdf');

        unlink($filenameBase . '.aux');
        unlink($filenameBase . '.log');
        unlink($filenameBase . '.tex');
        unlink($filenameBase . '.pdf');

        return $pdf;
    }
}
