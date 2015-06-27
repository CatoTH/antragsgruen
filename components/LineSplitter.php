<?php

namespace app\components;

class LineSplitter
{

    private $lineLength;
    private $text;

    /**
     * @param string $text
     * @param int $lineLength
     */
    public function __construct($text, $lineLength)
    {
        $this->text       = $text;
        $this->lineLength = $lineLength;
    }


    /**
     * @static
     * @return string[]
     */
    public function splitLines()
    {
        $lines              = [];
        $lastSeparator      = -1;
        $lastSeparatorCount = 0;
        $inHtml             = false;
        $inEscaped          = false;
        $currLine           = "";
        $currLineCount      = 0;

        for ($i = 0; $i < mb_strlen($this->text); $i++) {
            $currChar = mb_substr($this->text, $i, 1);
            $currLine .= $currChar;
            if ($inHtml) {
                if ($currChar == ">") {
                    $inHtml = false;
                }
            } elseif ($inEscaped) {
                if ($currChar == ';') {
                    $inEscaped = false;
                }
            } else {
                if (mb_substr($this->text, $i, 4) == '<br>') {
                    $lines[] = mb_substr($currLine, 0, mb_strlen($currLine) - 1) . '###FORCELINEBREAK###';
                    $i += 3;
                    if (mb_substr($this->text, $i + 1, 1) == "\n") {
                        $i++;
                    }
                    $currLine      = '';
                    $currLineCount = 1;
                    continue;
                }
                if ($currChar == '<') {
                    $inHtml = true;
                    continue;
                }
                if ($currChar == '&') {
                    $inEscaped = true;
                }

                $currLineCount++;
                if ($currLineCount > $this->lineLength) {
                    /*
                    if ($debug) {
                        echo "Aktuelle Zeile: \"" . $currLine . "\"\n";
                        echo "Count: \"" . $currLineCount . "\"\n";
                        echo "Letztes Leerzeichen: \"" . $lastSeparator . "\"\n";
                    }
                    */
                    if ($lastSeparator == -1) {
                        $lines[]       = mb_substr($currLine, 0, mb_strlen($currLine) - 1) . '-';
                        $currLine      = $currChar;
                        $currLineCount = 1;
                    } else {
                        /*
                        if ($debug) {
                            echo "Aktuelles Zeichen: \"" . mb_substr($this->text, $i, 1) . "\"\n";
                        }
                        */
                        if (mb_substr($this->text, $i, 1) == ' ') {
                            $lines[] = mb_substr($currLine, 0, mb_strlen($currLine) - 1);

                            $currLine      = '';
                            $currLineCount = 0;
                        } else {
                            $ueberhang   = mb_substr($currLine, $lastSeparator + 1);
                            $lastIsSpace = (mb_substr($currLine, $lastSeparator, 1) == " ");
                            /*
                            if ($debug) {
                                echo "Überhang: \"" . $ueberhang . "\"\n";
                                echo "Letztes ist Leerzeichen: " . $lastIsSpace . "\n";
                            }
                            */
                            $lines[] = mb_substr($currLine, 0, $lastSeparator + ($lastIsSpace ? 0 : 1));

                            $currLine      = $ueberhang;
                            $currLineCount = $this->lineLength - $lastSeparatorCount + 1;
                        }

                        $lastSeparator      = -1;
                        $lastSeparatorCount = 0;
                    }
                    /*
                    if ($debug) {
                        echo "Neue aktuelle Zeile: \"" . $currLine . "\"\n";
                        echo "Count: \"" . $currLineCount . "\"\n\n";
                    }
                    */
                } elseif (in_array($currChar, [' ', '-'])) {
                    $lastSeparator      = mb_strlen($currLine) - 1;
                    $lastSeparatorCount = $currLineCount;
                }
            }
        }
        if (mb_strlen(trim($currLine)) > 0) {
            $lines[] = $currLine;
        }
        return $lines;
    }


    /**
     * @param string $para
     * @param bool $lineNumbers
     * @param int $lineLength
     * @return string[]
     */
    public static function motionPara2lines($para, $lineNumbers, $lineLength)
    {
        if (mb_stripos($para, '<ul>') === 0 || mb_stripos($para, '<ol>') === 0 ||
            mb_stripos($para, '<blockquote>') === 0
        ) {
            $lineLength -= 6;
        }
        $splitter = new LineSplitter($para, $lineLength);
        $linesIn  = $splitter->splitLines();

        if ($lineNumbers) {
            $linesOut = [];
            $pres     = ['<p>', '<ul><li>', '<ol( start="[0-9]+")?><li>', '<blockquote><p>'];
            $linePre  = '###LINENUMBER###';
            foreach ($linesIn as $line) {
                $inserted = false;
                foreach ($pres as $pre) {
                    if (preg_match("/^" . $pre . "/siu", $line, $matches)) {
                        $inserted = true;
                        $line     = str_ireplace($matches[0], $matches[0] . $linePre, $line);
                    }
                }
                if (!$inserted) {
                    $line = $linePre . $line;
                }
                $linesOut[] = $line;
            }
        } else {
            $linesOut = $linesIn;
        }
        return $linesOut;
    }
}
