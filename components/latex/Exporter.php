<?php

namespace app\components\latex;

use app\components\HTMLTools;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;

class Exporter
{
    /** @var Layout */
    private $layout;

    /** @var  AntragsgruenApp */
    private $app;

    /**
     * @param Layout $layout
     * @param AntragsgruenApp $app
     */
    public function __construct(Layout $layout, AntragsgruenApp $app)
    {
        $this->layout = $layout;
        $this->app    = $app;
    }

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
     * @param string $content
     * @param string[] $extraStyles
     * @return string
     */
    private static function addInsDelExtraStyles($content, $extraStyles)
    {
        if (in_array('ins', $extraStyles)) {
            $content = '\textcolor{Insert}{' . $content . '}';
        }
        if (in_array('del', $extraStyles)) {
            $content = '\textcolor{Delete}{\sout{' . $content . '}}';
        }
        return $content;
    }

    /**
     * @param \DOMNode $node
     * @param array $extraStyles
     * @return string
     * @throws Internal
     */
    private static function encodeHTMLNode(\DOMNode $node, $extraStyles = [])
    {
        if ($node->nodeType == XML_TEXT_NODE) {
            /** @var \DOMText $node */
            $str = static::encodePlainString($node->data);
            if (in_array('underlined', $extraStyles)) {
                $words    = explode(' ', $str);
                $words[0] = '\uline{' . $words[0] . '}';
                for ($i = 1; $i < count($words); $i++) {
                    $words[$i] = '\uline{ ' . $words[$i] . '}';
                }
                $str = implode('', $words);
            }
            return $str;
        } else {
            $content = '';
            /** @var \DOMElement $node */
            if ($node->hasAttribute('class')) {
                $classes = explode(' ', $node->getAttribute('class'));
            } else {
                $classes = [];
            }

            $childStyles = [];
            if (in_array($node->nodeName, HTMLTools::$KNOWN_BLOCK_ELEMENTS)) {
                if (in_array('ins', $classes) || in_array('inserted', $classes)) {
                    $extraStyles[] = 'ins';
                    $childStyles[] = 'underlined';
                }
                if (in_array('del', $classes) || in_array('deleted', $classes)) {
                    $extraStyles[] = 'del';
                }
            } elseif ($node->nodeName == 'u') {
                $childStyles[] = 'underlined';
            } elseif ($node->nodeName == 'ins') {
                $childStyles[] = 'underlined';
            } elseif ($node->nodeName == 'span') {
                if (in_array('underline', $classes)) {
                    $childStyles[] = 'underlined';
                }
                if (in_array('ins', $classes) || in_array('inserted', $classes)) {
                    $childStyles[] = 'underlined';
                }
            }
            if (in_array('underlined', $extraStyles)) {
                $childStyles[] = 'underlined';
            }

            foreach ($node->childNodes as $child) {
                /** @var \DOMNode $child */
                $content .= static::encodeHTMLNode($child, $childStyles);
            }

            switch ($node->nodeName) {
                case 'h4':
                    return '\textbf{' . $content . '}' . "\n" . '\\newline' . "\n";
                case 'h3':
                    return '\textbf{\large ' . $content . '}' . "\n" . '\\newline' . "\n";
                case 'h2':
                    return '\textbf{\Large ' . $content . '}' . "\n" . '\\newline' . "\n";
                case 'h1':
                    return '\textbf{\LARGE ' . $content . '}' . "\n" . '\\newline' . "\n";
                case 'br':
                    return '\newline' . "\n";
                case 'p':
                    $content = static::addInsDelExtraStyles($content, $extraStyles);
                    return $content . "\n";
                case 'div':
                    $content = static::addInsDelExtraStyles($content, $extraStyles);
                    return $content . "\n";
                case 'strong':
                case 'b':
                    return '\textbf{' . $content . '}';
                case 'em':
                case 'i':
                    return '\emph{' . $content . '}';
                case 'u':
                    // return '\uline{' . $content . '}';
                    return $content;
                case 's':
                    return '\sout{' . $content . '}';
                case 'sub':
                    return '\textsubscript{' . $content . '}';
                case 'sup':
                    return '\textsuperscript{' . $content . '}';
                case 'blockquote':
                    $content = static::addInsDelExtraStyles($content, $extraStyles);
                    return '\begin{quotation}\noindent' . "\n" . $content . '\end{quotation}' . "\n";
                case 'ul':
                    $content = static::addInsDelExtraStyles($content, $extraStyles);
                    return '\begin{itemize}' . "\n" . $content . '\end{itemize}' . "\n";
                case 'ol':
                    $firstLine = '';
                    $content   = static::addInsDelExtraStyles($content, $extraStyles);
                    if ($node->hasAttribute('start')) {
                        $firstLine = '\setcounter{enumi}{' . ($node->getAttribute('start') - 1) . '}' . "\n";
                    }
                    return '\begin{enumerate}' . "\n" . $firstLine . $content . '\end{enumerate}' . "\n";
                case 'li':
                    $content = static::addInsDelExtraStyles($content, $extraStyles);
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
                        // $content = '\uline{' . $content . '}';
                    }
                    if (in_array('strike', $classes)) {
                        $content = '\sout{' . $content . '}';
                    }
                    if (in_array('ins', $classes)) {
                        //$content = '\textcolor{Insert}{\uline{' . $content . '}}';
                        $content = '\textcolor{Insert}{' . $content . '}';
                    }
                    if (in_array('inserted', $classes)) {
                        $content = '\textcolor{Insert}{' . $content . '}';
                    }
                    if (in_array('del', $classes)) {
                        $content = '\textcolor{Delete}{\sout{' . $content . '}}';
                    }
                    if (in_array('deleted', $classes)) {
                        $content = '\textcolor{Delete}{\sout{' . $content . '}}';
                    }
                    if (in_array('subscript', $classes)) {
                        $content = '\textsubscript{' . $content . '}';
                    }
                    if (in_array('superscript', $classes)) {
                        $content = '\textsuperscript{' . $content . '}';
                    }
                    return $content;
                case 'del':
                    return '\textcolor{Delete}{\sout{' . $content . '}}';
                case 'ins':
                    //return '\textcolor{Insert}{\uline{' . $content . '}}';
                    return '\textcolor{Insert}{' . $content . '}';
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
        $str     = HTMLTools::correctHtmlErrors($str);
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

        $out = str_replace('###FORCELINEBREAK###', "\n\\newline\n", $out);

        if (trim(str_replace('###LINENUMBER###', '', $out), "\n") == ' ') {
            $out = str_replace(' ', '{\color{white}.}', $out);
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
     * @param string $textMain
     * @param string $textRight
     * @return string
     */
    public static function createTextWithRightString($textMain, $textRight)
    {
        if ($textRight == '') {
            return $textMain;
        }

        $textMain = str_replace(
            ['\begin{itemize}', '\end{itemize}'],
            ['\parbox{12.5cm}{\raggedright\begin{itemize}', '\end{itemize}}'],
            $textMain
        );
        return '\begin{wrapfigure}{r}{0.23\textwidth}
\vspace{-0.5cm}
' . $textRight . '
\end{wrapfigure}
' . $textMain;
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
        $replaces['%TEXT%']               = static::createTextWithRightString($content->textMain, $content->textRight);
        $replaces['%INTRODUCTION_BIG%']   = $content->introductionBig;
        $replaces['%INTRODUCTION_SMALL%'] = $content->introductionSmall;
        $template                         = str_replace(array_keys($replaces), array_values($replaces), $template);
        return $template;
    }

    /**
     * @param Content[] $contents
     * @return string
     * @throws Internal
     */
    public function createPDF($contents)
    {
        if (!$this->app->xelatexPath) {
            throw new Internal('LaTeX/XeTeX-Export is not enabled');
        }
        $layoutStr  = static::createLayoutString($this->layout);
        $contentStr = '';
        $count      = 0;
        $imageFiles = [];
        foreach ($contents as $content) {
            if ($count > 0) {
                $contentStr .= "\n\\newpage\n";
            }
            $contentStr .= static::createContentString($content);
            foreach ($content->imageData as $fileName => $fileData) {
                if (preg_match('/[^a-z0-9_-]/siu', $fileName)) {
                    throw new Internal('Invalid image filename');
                }
                file_put_contents($this->app->tmpDir . $fileName, $fileData);
                $imageFiles[] = $this->app->tmpDir . $fileName;
            }
            $count++;
        }
        $str = str_replace('%CONTENT%', $contentStr, $layoutStr);

        $filenameBase = $this->app->tmpDir . uniqid('motion-pdf');
        file_put_contents($filenameBase . '.tex', $str);

        $cmd = $this->app->xelatexPath;
        $cmd .= ' -interaction=batchmode';
        $cmd .= ' -output-directory="' . $this->app->tmpDir . '"';
        if ($this->app->xdvipdfmx) {
            $cmd .= ' -output-driver="' . $this->app->xdvipdfmx . '"';
        }
        $cmd .= ' "' . $filenameBase . '.tex"';

        if (YII_ENV_DEV && isset($_REQUEST['latex_src'])) {
            Header('Content-Type: text/plain');
            echo $str;
            echo "\n\n%" . $cmd;
            unlink($filenameBase . '.tex');
            die();
        }

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
        unlink($filenameBase . '.out');

        foreach ($imageFiles as $file) {
            unlink($file);
        }

        return $pdf;
    }

    /**
     * @param $motion Motion
     * @return string
     */
    public static function createMotionPdf($motion)
    {
        $cache = \Yii::$app->cache->get($motion->getPdfCacheKey());
        if ($cache) {
            return $cache;
        }
        $texTemplate = $motion->motionType->texTemplate;

        $layout            = new Layout();
        $layout->assetRoot = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
        //$layout->templateFile = \yii::$app->basePath . DIRECTORY_SEPARATOR .
        //    'assets' . DIRECTORY_SEPARATOR . 'motion_std.tex';
        $layout->template = $texTemplate->texLayout;
        $layout->author   = $motion->getInitiatorsStr();
        $layout->title    = $motion->getTitleWithPrefix();

        /** @var AntragsgruenApp $params */
        $params   = \yii::$app->params;
        $exporter = new Exporter($layout, $params);
        $content  = \app\views\motion\LayoutHelper::renderTeX($motion);
        $pdf      = $exporter->createPDF([$content]);
        \Yii::$app->cache->set($motion->getPdfCacheKey(), $pdf);
        return $pdf;
    }

    /**
     * @param $amendment Amendment
     * @return string
     */
    public static function createAmendmentPdf($amendment)
    {
        $cache = \Yii::$app->cache->get($amendment->getPdfCacheKey());
        if ($cache) {
            return $cache;
        }
        $texTemplate = $amendment->getMyMotion()->motionType->texTemplate;

        $layout            = new Layout();
        $layout->assetRoot = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
        //$layout->templateFile = \yii::$app->basePath . DIRECTORY_SEPARATOR .
        //    'assets' . DIRECTORY_SEPARATOR . 'motion_std.tex';
        $layout->template = $texTemplate->texLayout;
        $layout->author   = $amendment->getInitiatorsStr();
        $layout->title    = $amendment->getTitle();

        /** @var AntragsgruenApp $params */
        $params   = \yii::$app->params;
        $exporter = new Exporter($layout, $params);
        $content  = \app\views\amendment\LayoutHelper::renderTeX($amendment);
        $pdf      = $exporter->createPDF([$content]);
        \Yii::$app->cache->set($amendment->getPdfCacheKey(), $pdf);
        return $pdf;
    }
}
