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
            $modifications = [];
            foreach ($origArr as $x) {
                $modifications[] = [
                    'orig'          => $x,
                    'modifications' => [],
                ];
            }
            $this->paraData[$paraNo] = [
                'orig'              => $paraStr,
                'origTokenized'     => $origTokenized,
                'modifications'     => $modifications,
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
            $origNo     = 0;
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
                        if (!isset($this->paraData[$amendPara]['modifications'][$pre]['modifications'][$amendId])) {
                            $orig = $this->paraData[$amendPara]['modifications'][$pre]['orig'];
                            //
                            $this->paraData[$amendPara]['modifications'][$pre]['modifications'][$amendId] = $orig;
                        }
                        $this->paraData[$amendPara]['modifications'][$pre]['modifications'][$amendId] .= $insStr;
                    }
                }
                if ($token[1] == \app\components\diff\Engine::DELETED) {
                    if ($token[0] != '') {
                        $delStr = '<del>' . $token[0] . '</del>';
                        if (!isset($this->paraData[$amendPara]['modifications'][$origNo]['modifications'][$amendId])) {
                            $this->paraData[$amendPara]['modifications'][$origNo]['modifications'][$amendId] = '';
                        }
                        $this->paraData[$amendPara]['modifications'][$origNo]['modifications'][$amendId] .= $delStr;
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
            foreach ($para['modifications'] as $modi) {
                if (count($modi['modifications']) > 1) {
                    echo 'PROBLEM: ';
                    var_dump($modi);
                } elseif (count($modi['modifications']) == 1) {
                    $keys = array_keys($modi['modifications']);
                    if ($keys[0] != $pendingCurrAmend) {
                        $paraG[]          = [
                            'amendment' => $pendingCurrAmend,
                            'text'      => $pending,
                        ];
                        $pending          = '';
                        $pendingCurrAmend = $keys[0];
                    }
                    $pending .= $modi['modifications'][$keys[0]];
                } else {
                    if (0 != $pendingCurrAmend) {
                        $paraG[]          = [
                            'amendment' => $pendingCurrAmend,
                            'text'      => $pending,
                        ];
                        $pending          = '';
                        $pendingCurrAmend = 0;
                    }
                    $pending .= $modi['orig'];
                }
            }
            $paraG[]                  = [
                'amendment' => $pendingCurrAmend,
                'text'      => $pending,
            ];
            $groupedParaData[$paraNo] = $paraG;
        }
        return $groupedParaData;
    }
}
