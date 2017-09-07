<?php

namespace app\components\latex;

use app\components\HashedStaticCache;
use app\components\HTMLTools;
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
            '\\'                     => '\textbackslash{}',
            '&'                      => '\&',
            '%'                      => '\%',
            '$'                      => '\$',
            '#'                      => '\#',
            '_'                      => '\_',
            '{'                      => '\{',
            '}'                      => '\}',
            '\textbackslash\{\}'     => '\textbackslash{}',
            '~'                      => '\texttt{\~{}}',
            '^'                      => '\^{}',
            '\#\#\#LINENUMBER\#\#\#' => '###LINENUMBER###',
            '\#\#\#LINEBREAK\#\#\#'  => '###LINEBREAK###',
        ];
        return str_replace(array_keys($replaces), array_values($replaces), $str);
    }

    /**
     * @param string[] $lines
     * @return string
     */
    public static function getMotionLinesToTeX($lines)
    {
        $str = implode('###LINEBREAK###', $lines);
        $str = str_replace('<br>###LINEBREAK###', '###LINEBREAK###', $str);
        $str = str_replace('<br>' . "\n" . '###LINEBREAK###', '###LINEBREAK###', $str);
        $str = static::encodeHTMLString($str);
        $str = str_replace('###LINENUMBER###', '', $str);
        $str = str_replace('###LINEBREAK###', "\\linebreak\n", $str);

        // Some edge cases that occur in nested enumerated lists
        $str = str_replace('\linebreak' . "\n\n" . '\item', "\n" . '\item', $str);
        $str = str_replace('\newline' . "\n" . '\end{enumerate}', "\n" . '\end{enumerate}', $str);
        $str = str_replace('\linebreak' . "\n" . '\begin{enumerate}', "\n" . '\begin{enumerate}', $str);

        return $str;
    }

    /**
     * @param string $str
     * @return string
     */
    public static function encodePREString($str)
    {
        $out = "\n" . '\nolinenumbers' . "\n\n" . '\texttt{';
        $lines = explode("\n", $str);
        foreach ($lines as $line) {
            if (strlen($line) > 0 && $line[0] == ' ') {
                $out .= '\phantom{.}';
                $line = substr($line, 1);
            }
            $out .= str_replace(' ', '\ ', static::encodePlainString($line));
            $out .= '\linebreak' . "\n";
        }
        $out .= '}' . "\n\n" . '\linenumbers';
        return $out;
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
     * @param string $content
     * @param string[] $extraStyles
     * @return string
     */
    private static function addInsDelExtraStylesToLi($content, $extraStyles)
    {
        $items = explode('\item ', $content);
        $out   = [];
        foreach ($items as $item) {
            if (trim($item) == '') {
                continue;
            }
            if (in_array('ins', $extraStyles)) {
                $out[] = '\textcolor{Insert}{' . trim($item) . '}';
            } elseif (in_array('del', $extraStyles)) {
                $out[] = '\textcolor{Delete}{\sout{' . trim($item) . '}}';
            } else {
                $out[] = trim($item);
            }
        }

        return '\item ' . implode("\n" . '\item ', $out) . "\n";
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
            if (in_array('underlined', $extraStyles) || in_array('strike', $extraStyles)) {
                $words = explode(' ', $str);
                if (in_array('underlined', $extraStyles)) {
                    $words[0] = '\uline{' . $words[0] . '}';
                    for ($i = 1; $i < count($words); $i++) {
                        $words[$i] = '\uline{ ' . $words[$i] . '}';
                    }
                }
                if (in_array('strike', $extraStyles)) {
                    $words[0] = '\sout{' . $words[0] . '}';
                    for ($i = 1; $i < count($words); $i++) {
                        $words[$i] = '\sout{ ' . $words[$i] . '}';
                    }
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
            } elseif ($node->nodeName == 's') {
                $childStyles[] = 'strike';
            } elseif ($node->nodeName == 'del') {
                $childStyles[] = 'strike';
            } elseif ($node->nodeName == 'span') {
                if (in_array('underline', $classes)) {
                    $childStyles[] = 'underlined';
                }
                if (in_array('strike', $classes)) {
                    $childStyles[] = 'strike';
                }
                if (in_array('ins', $classes) || in_array('inserted', $classes)) {
                    $childStyles[] = 'underlined';
                }
            }
            if (in_array('underlined', $extraStyles)) {
                $childStyles[] = 'underlined';
            }
            if (in_array('strike', $extraStyles)) {
                $childStyles[] = 'strike';
            }

            foreach ($node->childNodes as $child) {
                /** @var \DOMNode $child */
                $content .= static::encodeHTMLNode($child, $childStyles);
            }

            switch ($node->nodeName) {
                case 'h4':
                    return '\textbf{' . $content . '}\\newline' . "\n";
                case 'h3':
                    return '\textbf{\large ' . $content . '}\\newline' . "\n";
                case 'h2':
                    return '\textbf{\Large ' . $content . '}\\newline' . "\n";
                case 'h1':
                    return '\textbf{\LARGE ' . $content . '}\\newline' . "\n";
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
                    $content = preg_replace("/\\n{2,}/siu", "\n", $content);
                    return '\textbf{' . $content . '}';
                case 'em':
                case 'i':
                    $content = preg_replace("/\\n{2,}/siu", "\n", $content);
                    return '\emph{' . $content . '}';
                case 'u':
                    // return '\uline{' . $content . '}';
                    return $content;
                case 's':
                    //return '\sout{' . $content . '}';
                    return $content;
                case 'sub':
                    return '\textsubscript{' . $content . '}';
                case 'sup':
                    return '\textsuperscript{' . $content . '}';
                case 'blockquote':
                    $content = static::addInsDelExtraStyles($content, $extraStyles);
                    return '\begin{quotation}\noindent' . "\n" . $content . '\end{quotation}' . "\n";
                case 'ul':
                    $content = static::addInsDelExtraStylesToLi($content, $extraStyles);
                    return '\begin{itemize}' . "\n" . $content . '\end{itemize}' . "\n";
                case 'ol':
                    $firstLine = '';
                    $content   = static::addInsDelExtraStylesToLi($content, $extraStyles);
                    if ($node->hasAttribute('start')) {
                        $firstLine = '\setcounter{enumi}{' . ($node->getAttribute('start') - 1) . '}' . "\n";
                    }
                    return '\begin{enumerate}[label=\arabic*.]' . "\n" .
                        $firstLine . $content .
                        '\end{enumerate}' . "\n";
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
                        // $content = '\sout{' . $content . '}';
                    }
                    if (in_array('ins', $classes)) {
                        //$content = '\textcolor{Insert}{\uline{' . $content . '}}';
                        $content = '\textcolor{Insert}{' . $content . '}';
                    }
                    if (in_array('inserted', $classes)) {
                        $content = '\textcolor{Insert}{' . $content . '}';
                    }
                    if (in_array('del', $classes)) {
                        $content = '\textcolor{Delete}{' . $content . '}';
                    }
                    if (in_array('deleted', $classes)) {
                        $content = '\textcolor{Delete}{' . $content . '}';
                    }
                    if (in_array('subscript', $classes)) {
                        $content = '\textsubscript{' . $content . '}';
                    }
                    if (in_array('superscript', $classes)) {
                        $content = '\textsuperscript{' . $content . '}';
                    }
                    return $content;
                case 'del':
                    return '\textcolor{Delete}{' . $content . '}';
                case 'ins':
                    //return '\textcolor{Insert}{\uline{' . $content . '}}';
                    return '\textcolor{Insert}{' . $content . '}';
                case 'pre':
                    return  static::encodePREString($content);
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
        $replaces['%TITLE%']     = static::encodePlainString($layout->title);
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
            return "\\raggedright\n" . $textMain;
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
        $replaces['%TITLE%']              = static::encodePlainString($content->title);
        $replaces['%TITLEPREFIX%']        = static::encodePlainString($content->titlePrefix);
        $replaces['%TITLE_LONG%']         = static::encodePlainString($content->titleLong);
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
        $layoutStr   = static::createLayoutString($this->layout);
        $contentStr  = '';
        $count       = 0;
        $imageFiles  = [];
        $imageHashes = [];
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
                $imageHashes[$this->app->tmpDir . $fileName] = md5($fileData);
                $imageFiles[]                                = $this->app->tmpDir . $fileName;
            }
            $count++;
        }
        $str = str_replace('%CONTENT%', $contentStr, $layoutStr);

        $filenameBase = $this->app->tmpDir . uniqid('motion-pdf');

        $cmd = $this->app->xelatexPath;
        $cmd .= ' -interaction=batchmode';
        $cmd .= ' -output-directory="' . $this->app->tmpDir . '"';
        if ($this->app->xdvipdfmx) {
            $cmd .= ' -output-driver="' . $this->app->xdvipdfmx . '"';
        }
        $cmd .= ' "' . $filenameBase . '.tex"';

        $cacheDepend = $str;
        foreach ($imageHashes as $file => $hash) {
            $cacheDepend = str_replace($file, $hash, $cacheDepend);
        }
        $cached = HashedStaticCache::getCache('latexCreatePDF', $cacheDepend);

        if (YII_ENV_DEV && isset($_REQUEST['latex_src'])) {
            Header('Content-Type: text/plain');
            echo $str;
            echo "\n\nIs in cache: " . ($cached ? "Yes" : "No") . "\n%" . $cmd;
            die();
        }

        if ($cached) {
            foreach ($imageFiles as $file) {
                unlink($file);
            }
            return $cached;
        }

        file_put_contents($filenameBase . '.tex', $str);
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

        HashedStaticCache::setCache('latexCreatePDF', $cacheDepend, $pdf);

        return $pdf;
    }
}
