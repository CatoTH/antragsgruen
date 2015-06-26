<?php

namespace app\components;

use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use app\models\settings\LaTeX;

class LaTeXExporter
{
    /**
     * @param string $str
     * @return string
     */
    public static function encodePlainString($str)
    {
        return $str; // @TODO
    }

    /**
     * @param \DOMNode $node
     * @return string
     */
    private static function encodeHTMLNode(\DOMNode $node)
    {
        // @TODO
        if ($node->nodeType == XML_TEXT_NODE) {
            /** @var \DOMText $node */
            return static::encodePlainString($node->data);
        } else {
            $content = '';
            foreach ($node->childNodes as $child) {
                /** @var \DOMNode $child */
                $content .= static::encodeHTMLNode($child);
            }
            switch ($node->nodeName) {
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
                    return '\begin{quotation}' . $content . '\end{quotation}' . "\n";
                case 'ul':
                    return '\begin{itemize}' . "\n" . $content . '\end{itemize}' . "\n";
                case 'ol':
                    /** @var \DOMElement $node */
                    $firstLine = '';
                    if ($node->hasAttribute('start')) {
                        $firstLine = '\setcounter{enumi}{' . ($node->getAttribute('start') - 1) . '}' . "\n";
                    }
                    return '\begin{enumerate}' . "\n" . $firstLine . $content . '\end{enumerate}' . "\n";
                case 'li':
                    return '\item ' . $content . "\n";
                case 'a':
                    /** @var \DOMElement $node */
                    if ($node->hasAttribute('href')) {
                        $content = '\href{' . $node->getAttribute('href') . '}{' . $content . '}';
                    }
                    return $content;
                case 'span':
                    /** @var \DOMElement $node */
                    if (!$node->hasAttribute('class')) {
                        return $content;
                    }
                    $classes = explode(' ', $node->getAttribute('class'));
                    if (in_array('underline', $classes)) {
                        $content = '\underline{' . $content . '}';
                    }
                    if (in_array('strike', $classes)) {
                        $content = '\sout{' . $content . '}';
                    }
                    return $content;
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
     * @param LaTeX $laTeX
     * @return string
     */
    public static function createLaTeXString(LaTeX $laTeX)
    {
        $template                         = file_get_contents($laTeX->templateFile);
        $replaces                         = [];
        $replaces['%LANGUAGE%']           = $laTeX->language;
        $replaces['%ASSETROOT%']          = $laTeX->assetRoot;
        $replaces['%TITLEPREFIX%']        = $laTeX->titlePrefix;
        $replaces['%TITLE%']              = $laTeX->title;
        $replaces['%TITLE_LONG%']         = $laTeX->titleLong;
        $replaces['%AUTHOR%']             = $laTeX->author;
        $replaces['%MOTION_DATA_TABLE%']  = $laTeX->motionDataTable;
        $replaces['%TEXT%']               = $laTeX->text;
        $replaces['%INTRODUCTION_BIG%']   = $laTeX->introductionBig;
        $replaces['%INTRODUCTION_SMALL%'] = $laTeX->introductionSmall;
        $template                         = str_replace(array_keys($replaces), array_values($replaces), $template);
        return $template;
    }

    /**
     * @param LaTeX $laTeX
     * @param AntragsgruenApp $app
     * @return string
     * @throws Internal
     */
    public static function createPDF(LaTeX $laTeX, AntragsgruenApp $app)
    {
        if (!$app->xelatexPath) {
            throw new Internal('LaTeX/XeTeX-Export is not enabled');
        }
        $str          = static::createLaTeXString($laTeX);

        if (YII_ENV_DEV && isset($_REQUEST["latex_src"])) {
            Header('Content-Type: text/plain');
            echo $str;
            die();
        }

        $filenameBase = $app->tmpDir . uniqid('motion-pdf');
        //echo nl2br(htmlentities($str));
        //die();
        file_put_contents($filenameBase . '.tex', $str);

        $cmd = $app->xelatexPath;
        $cmd .= ' -interaction=batchmode';
        $cmd .= ' -output-directory="' . $app->tmpDir . '"';
        if ($app->xdvipdfmx) {
            $cmd .= ' -output-driver="' . $app->xdvipdfmx . '"';
        }
        $cmd .= ' "' . $filenameBase . '.tex"';

        shell_exec($cmd);

        if (!file_exists($filenameBase . '.pdf')) {
            throw new Internal('An error occurred while creating the PDF: ' . $filenameBase);
        }
        $pdf = file_get_contents($filenameBase . '.pdf');

        unlink($filenameBase . '.aux');
        unlink($filenameBase . '.log');
        unlink($filenameBase . '.tex');
        unlink($filenameBase . '.pdf');
        return $pdf;
    }
}
