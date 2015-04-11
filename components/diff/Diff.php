<?php

namespace app\components\diff;

use app\models\exceptions\Internal;

class Diff
{
    const ORIG_LINEBREAK = '###ORIGLINEBREAK###';

    private $debug = false;

    /** @var Engine */
    private $engine;

    public function __construct()
    {
        $this->engine = new Engine();
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * @param string $str
     */
    public function setIgnoreStr($str)
    {
        $this->engine->setIgnoreStr($str);
    }

    /**
     * @param string $str
     * @return string
     */
    private function wrapWithInsert($str)
    {
        if (preg_match('/^<[^>]*>$/siu', $str)) {
            return $str;
        }
        if ($str == static::ORIG_LINEBREAK) {
            return $str;
        }
        if (mb_stripos($str, '<ul>') === 0) {
            return '<ul class="inserted">' . mb_substr($str, 4);
        } elseif (mb_stripos($str, '<ol>') === 9) {
            return '<ol class="inserted">' . mb_substr($str, 4);
        } elseif (mb_stripos($str, '<ul>')) {
            return '<li class="inserted">' . mb_substr($str, 12);
        } elseif (mb_stripos($str, '<blockquote>')) {
            return '<blockquote class="inserted">' . $str;
        } else {
            return '<ins>' . $str . '</ins>';
        }
    }

    /**
     * @param string $str
     * @return string
     */
    private function wrapWithDelete($str)
    {
        if (preg_match('/^<[^>]*>$/siu', $str)) {
            return $str;
        }
        if ($str == static::ORIG_LINEBREAK) {
            return $str;
        }
        if (mb_stripos($str, '<ul>') === 0) {
            return '<ul class="deleted">' . mb_substr($str, 4);
        } elseif (mb_stripos($str, '<ol>') === 9) {
            return '<ol class="deleted">' . mb_substr($str, 4);
        } elseif (mb_stripos($str, '<ul>')) {
            return '<li class="deleted">' . mb_substr($str, 12);
        } elseif (mb_stripos($str, '<blockquote>')) {
            return '<blockquote class="deleted">' . $str;
        } else {
            return '<del>' . $str . '</del>';
        }
    }

    /**
     * @param string $line
     * @return string[]
     */
    private function tokenizeLine($line)
    {
        $line = preg_replace('/ /siu', "\n", $line);
        $line = preg_replace('/(<[^>]*>)/siu', "\n$1\n", $line);
        return $line;
    }

    /**
     * @param string $line
     * @return string
     */
    private function untokenizeLine($line)
    {
        $line = str_replace("\n", ' ', $line);
        $line = preg_replace('/ (<[^>]*>) /siu', "$1", $line);
        return $line;
    }

    /**
     * @param array $operations
     * @return array
     */
    private function groupOperations($operations)
    {
        $return = [];

        $preOp        = null;
        $currentSpool = [];
        foreach ($operations as $operation) {
            if ($operation[1] !== $preOp) {
                if (count($currentSpool) > 0) {
                    $return[] = [
                        implode(static::ORIG_LINEBREAK, $currentSpool),
                        $preOp
                    ];
                }
                $preOp        = $operation[1];
                $currentSpool = [$operation[0]];
            } else {
                $currentSpool[] = $operation[0];
            }
        }
        if (count($currentSpool) > 0) {
            $return[] = [
                implode(static::ORIG_LINEBREAK, $currentSpool),
                $preOp
            ];
        }
        return $return;
    }

    /**
     * @param string $lineOld
     * @param string $lineNew
     * @return string
     * @throws Internal
     */
    public function computeLineDiff($lineOld, $lineNew)
    {
        $computedStrs = [];
        $lineOld      = $this->tokenizeLine($lineOld);
        $lineNew      = $this->tokenizeLine($lineNew);

        $return = $this->engine->compare($lineOld, $lineNew);

        for ($i = 0; $i < count($return); $i++) {
            if ($return[$i][1] == Engine::UNMODIFIED) {
                $computedStrs[] = $return[$i][0];
            } elseif ($return[$i][1] == Engine::DELETED) {
                if (isset($return[$i + 1]) && $return[$i + 1][1] == Engine::INSERTED) {
                    $str            = $this->wrapWithDelete($return[$i][0]) . $this->wrapWithInsert($return[$i + 1][0]);
                    $computedStrs[] = $str;
                    $i++;
                } else {
                    $computedStrs[] = $this->wrapWithDelete($return[$i][0]);
                }
            } elseif ($return[$i][1] == Engine::INSERTED) {
                $computedStrs[] = $this->wrapWithInsert($return[$i][0]);
            } else {
                throw new Internal('Unknown type: ' . $return[$i][1]);
            }
        }
        $computedStr = implode("\n", $computedStrs);
        if ($this->debug) {
            var_dump($computedStr);
        }


        $combined = $this->untokenizeLine($computedStr);
        $combined = str_replace('</del> <del>', ' ', $combined);
        $combined = str_replace('</ins> <ins>', ' ', $combined);

        if ($this->debug) {
            var_dump($combined);
            die();
        }
        return $combined;
    }

    /**
     * @param string $strOld
     * @param string $strNew
     * @return string
     * @throws Internal
     */
    public function computeDiff($strOld, $strNew)
    {
        $computedStr = '';

        $return = $this->engine->compare($strOld, $strNew);
        if ($this->debug) {
            echo "==========\n";
            var_dump($return);
            echo "\n\n\n";
        }
        $return = $this->groupOperations($return);
        for ($i = 0; $i < count($return); $i++) {
            if ($return[$i][1] == Engine::UNMODIFIED) {
                $computedStr .= $return[$i][0] . "\n";
            } elseif ($return[$i][1] == Engine::DELETED) {
                if (isset($return[$i + 1]) && $return[$i + 1][1] == Engine::INSERTED) {
                    $computedStr .= $this->computeLineDiff($return[$i][0], $return[$i + 1][0]);
                    $i++;
                } else {
                    $computedStr .= $this->wrapWithDelete($return[$i][0]) . "\n";
                }
            } elseif ($return[$i][1] == Engine::INSERTED) {
                $computedStr .= $this->wrapWithInsert($return[$i][0]) . "\n";
            } else {
                throw new Internal('Unknown type: ' . $return[$i][1]);
            }
        }
        $computedStr = str_replace(static::ORIG_LINEBREAK, "\n", $computedStr);

        if ($this->debug) {
            die();
        }

        return trim($computedStr);
    }
}
