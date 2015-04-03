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
     * @param bool $debug
     * @return string[]
     */
    public function splitLines($debug = false)
    {
        $lines              = array();
        $lastSeparator      = -1;
        $lastSeparatorCount = 0;
        $inHtml             = false;
        $inEscaped          = false;
        $currLine           = "";
        $currLineCount      = 0;

        for ($i = 0; $i < mb_strlen($this->text); $i++) {
            $currChar = mb_substr($this->text, $i, 1);
            if ($inHtml) {
                if ($currChar == ">") {
                    $inHtml = false;
                }
                $currLine .= $currChar;
            } elseif ($inEscaped) {
                if ($currChar == ";") {
                    $inEscaped = false;
                }
                $currLine .= $currChar;
            } else {
                $currLine .= $currChar;

                if (mb_substr($this->text, $i, 4) == '<br>') {
                    $lines[]       = mb_substr($currLine, 0, mb_strlen($currLine) - 1);
                    $i += 3;
                    if (mb_substr($this->text, $i + 1, 1) == "\n") {
                        $i++;
                    }
                    $currLine      = '';
                    $currLineCount = 1;
                    continue;
                }
                if ($currChar == "<") {
                    $inHtml = true;
                    continue;
                }
                if ($currChar == "&") {
                    $inEscaped = true;
                }

                $currLineCount++;

                if ($debug) {
                    echo $currLineCount . ": " . $currChar . "\n";
                }

                if ($currLineCount > $this->lineLength) {
                    if ($debug) {
                        echo "Aktuelle Zeile: \"" . $currLine . "\"\n";
                        echo "Count: \"" . $currLineCount . "\"\n";
                        echo "Letztes Leerzeichen: \"" . $lastSeparator . "\"\n";
                    }

                    if ($lastSeparator == -1) {
                        if ($debug) {
                            echo "Umbruch forcieren\n";
                        }
                        $lines[]       = mb_substr($currLine, 0, mb_strlen($currLine) - 1) . "-";
                        $currLine      = $currChar;
                        $currLineCount = 1;
                    } else {
                        if ($debug) {
                            echo "Aktuelles Zeichen: \"" . mb_substr($this->text, $i, 1) . "\"\n";
                        }
                        if (mb_substr($this->text, $i, 1) == " ") {
                            $lines[] = mb_substr($currLine, 0, mb_strlen($currLine) - 1);

                            $currLine      = "";
                            $currLineCount = 0;
                        } else {
                            $ueberhang               = mb_substr($currLine, $lastSeparator + 1);
                            $letztes_ist_leerzeichen = (mb_substr($currLine, $lastSeparator, 1) == " ");
                            if ($debug) {
                                echo "Überhang: \"" . $ueberhang . "\"\n";
                                echo "Letztes ist Leerzeichen: " . $letztes_ist_leerzeichen . "\n";
                            }
                            $lines[] = mb_substr($currLine, 0, $lastSeparator + ($letztes_ist_leerzeichen ? 0 : 1));

                            $currLine      = $ueberhang;
                            $currLineCount = $this->lineLength - $lastSeparatorCount + 1;
                        }

                        $lastSeparator      = -1;
                        $lastSeparatorCount = 0;
                    }
                    if ($debug) {
                        echo "Neue aktuelle Zeile: \"" . $currLine . "\"\n";
                        echo "Count: \"" . $currLineCount . "\"\n\n";
                    }
                } elseif (in_array($currChar, array(" ", "-"))) {
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
}
