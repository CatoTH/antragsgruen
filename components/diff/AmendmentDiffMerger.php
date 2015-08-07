<?php

namespace app\components\diff;

use app\models\db\AmendmentSection;
use app\models\db\MotionSection;

class AmendmentDiffMerger
{
    private $paras    = null;
    private $paraData = null;

    /**
     * @return array
     */
    public function getParaData()
    {
        return $this->paraData;
    }

    /**
     * @return array
     */
    public function getParagraphs()
    {
        return $this->paras;
    }


    /**
     * @param MotionSection $section
     * @throws \app\models\exceptions\Internal
     */
    public function initByMotionSection(MotionSection $section)
    {
        $paras = $section->getTextParagraphs();
        $this->initByMotionParagraphs($paras);
    }

    /**
     * @param array $paras
     */
    public function initByMotionParagraphs($paras)
    {
        $this->paras    = $paras;
        $this->paraData = [];
        foreach ($paras as $paraNo => $paraStr) {
            $origTokenized = \app\components\diff\Diff::tokenizeLine($paraStr);
            $origArr       = preg_split('/\R/', $origTokenized);
            $words         = [];
            foreach ($origArr as $x) {
                $words[] = [
                    'orig'          => $x,
                    'modifications' => [],
                ];
            }
            $this->paraData[$paraNo] = [
                'orig'              => $paraStr,
                'origTokenized'     => $origTokenized,
                'words'             => $words,
                'amendmentSections' => [],
            ];
        }
    }

    /**
     * @param AmendmentSection $section
     */
    public function addAmendmentSection(AmendmentSection $section)
    {
        $affectedParas = $section->getAffectedParagraphs($this->paras);
        $this->addAmendmentParagraphs($section->amendmentId, $affectedParas);
    }

    /**
     * @param int $amendId
     * @param array $paragraphs
     */
    public function addAmendmentParagraphs($amendId, $paragraphs)
    {
        $diffEngine = new \app\components\diff\Engine();
        foreach ($paragraphs as $amendPara => $amendText) {
            $newTokens  = \app\components\diff\Diff::tokenizeLine($amendText);
            $diffTokens = $diffEngine->compareStrings($this->paraData[$amendPara]['origTokenized'], $newTokens);
            $diffTokens = $diffEngine->shiftMisplacedHTMLTags($diffTokens);

            $origNo = 0;
            foreach ($diffTokens as $token) {
                if ($token[1] == \app\components\diff\Engine::INSERTED) {
                    if ($token[0] == '') {
                        continue;
                    }
                    $insStr = '<ins>' . $token[0] . '</ins>';
                    if ($origNo == 0) {
                        // @TODO
                    } else {
                        $pre = $origNo - 1;
                        if (!isset($this->paraData[$amendPara]['words'][$pre]['modifications'][$amendId])) {
                            $orig = $this->paraData[$amendPara]['words'][$pre]['orig'];
                            //
                            $this->paraData[$amendPara]['words'][$pre]['modifications'][$amendId] = $orig;
                        }
                        $this->paraData[$amendPara]['words'][$pre]['modifications'][$amendId] .= $insStr;
                    }
                }
                if ($token[1] == \app\components\diff\Engine::DELETED) {
                    if ($token[0] != '') {
                        $delStr = '<del>' . $token[0] . '</del>';
                        if (!isset($this->paraData[$amendPara]['words'][$origNo]['modifications'][$amendId])) {
                            $this->paraData[$amendPara]['words'][$origNo]['modifications'][$amendId] = '';
                        }
                        $this->paraData[$amendPara]['words'][$origNo]['modifications'][$amendId] .= $delStr;
                    }
                    $origNo++;
                }
                if ($token[1] == \app\components\diff\Engine::UNMODIFIED) {
                    $origNo++;
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getGroupedParagraphData()
    {
        $groupedParaData = [];
        foreach ($this->paraData as $paraNo => $para) {
            $paraG            = [];
            $pending          = '';
            $pendingCurrAmend = 0;

            $addToParaG = function ($pendingCurrAmend, $text) use (&$paraG) {
                $paraG[] = [
                    'amendment' => $pendingCurrAmend,
                    'text'      => static::cleanupParagraphData($text),
                ];
            };

            foreach ($para['words'] as $word) {
                if (count($word['modifications']) > 1) {
                    echo 'PROBLEM: ';
                    var_dump($word);
                } elseif (count($word['modifications']) == 1) {
                    $keys = array_keys($word['modifications']);
                    if ($pendingCurrAmend == 0 && $word['orig'] != '') {
                        $modiKey = array_keys($word['modifications'])[0];
                        if (mb_strpos($word['modifications'][$modiKey], $word['orig']) === 0) {
                            $shortened = mb_substr($word['modifications'][$modiKey], mb_strlen($word['orig']));
                            $pending .= $word['orig'];
                            $word['modifications'][$modiKey] = $shortened;
                        }
                    }
                    if ($keys[0] != $pendingCurrAmend) {
                        $addToParaG($pendingCurrAmend, $pending);
                        $pending          = '';
                        $pendingCurrAmend = $keys[0];
                    }
                    $pending .= $word['modifications'][$keys[0]];
                } else {
                    if (0 != $pendingCurrAmend) {
                        $addToParaG($pendingCurrAmend, $pending);
                        $pending          = '';
                        $pendingCurrAmend = 0;
                    }
                    $pending .= $word['orig'];
                }
            }
            $addToParaG($pendingCurrAmend, $pending);
            $groupedParaData[$paraNo] = $paraG;
        }
        return $groupedParaData;
    }

    /**
     * @param string $text
     * @return string
     */
    public static function cleanupParagraphData($text)
    {
        $text = preg_replace('/<(del|ins)>(<\/?(li|ul|ol)>)<\/(del|ins)>/siu', '\2', $text);
        $text = str_replace('</ins><ins>', '', $text);
        $text = str_replace('</del><del>', '', $text);
        $text = str_replace('<ins><p>', '<p><ins>', $text);
        $text = str_replace('<del><p>', '<p><del>', $text);
        $text = str_replace('</p></ins>', '</ins></p>', $text);
        $text = str_replace('</p></del>', '</del></p>', $text);
        return $text;
    }

    /**
     * @param array $paras
     * @return array
     */
    public static function filterChangingGroupedParagraphs($paras)
    {
        $return = [];
        foreach ($paras as $para) {
            $currBlock = [];
            foreach ($para as $paraBlock) {
                if ($paraBlock['amendment'] > 0) {
                    $currBlock[] = $paraBlock;
                }
            }
            $return[] = $currBlock;
        }
        return $return;
    }
}
