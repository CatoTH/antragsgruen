<?php

namespace app\components\diff;

use app\components\HTMLTools;
use app\models\db\AmendmentSection;
use app\models\db\MotionSection;

class AmendmentDiffMerger
{
    private $paras          = null;
    private $paraData       = null;
    private $diffParagraphs = null;

    private $sectionParagraphs = [];

    public static $BLOCK_LEVEL_ELEMENTS_OPENING = [
        '</ul>'         => '/^<ul[^>]*>$/siu',
        '</ol>'         => '/^<ol[^>]*>$/siu',
        '</li>'         => '/^<li[^>]*>$/siu',
        '</p>'          => '/^<p[^>]*>$/siu',
        '</pre>'        => '/^<pre[^>]*>$/siu',
        '</blockquote>' => '/^<blockquote[^>]*>$/siu',
        '</div>'        => '/^<div[^>]*>$/siu',
    ];

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
        $paras    = $section->getTextParagraphLines();
        $sections = [];
        foreach ($paras as $para) {
            $sections[] = str_replace('###LINENUMBER###', '', implode('', $para));
        }
        $this->initByMotionParagraphs($sections);
    }

    /**
     * @param array $paras
     */
    public function initByMotionParagraphs($paras)
    {
        $this->sectionParagraphs = $paras;

        $this->paras          = $paras;
        $this->paraData       = [];
        $this->diffParagraphs = [];
        foreach ($paras as $paraNo => $paraStr) {
            $origTokenized = Diff2::tokenizeLine($paraStr);
            $words         = [];
            foreach ($origTokenized as $x) {
                $words[] = [
                    'orig'         => $x,
                    'modification' => null,
                    'modifiedBy'   => null,
                ];
            }
            $this->paraData[$paraNo]       = [
                'orig'                => $paraStr,
                'origTokenized'       => $origTokenized,
                'words'               => $words,
                'collidingParagraphs' => [],
            ];
            $this->diffParagraphs[$paraNo] = [];
        }
    }

    /**
     * @param int $amendmentId
     * @param array $origParas
     * @param string[] $amendingParas
     */
    public function addAmendingParagraphs($amendmentId, $origParas, $amendingParas)
    {
        $diff     = new Diff2();
        $amParams = ['amendmentId' => $amendmentId];
        $paraArr  = $diff->compareHtmlParagraphsToWordArray($origParas, $amendingParas, $amParams);

        foreach ($paraArr as $paraNo => $wordArr) {
            $hasChanges = false;
            $firstDiff  = null;
            for ($i = 0; $i < count($wordArr); $i++) {
                if (isset($wordArr[$i]['amendmentId'])) {
                    $hasChanges = true;
                    if ($firstDiff === null) {
                        $firstDiff = $i;
                    }
                }
            }
            if ($hasChanges) {
                $this->diffParagraphs[$paraNo][] = [
                    'amendment' => $amendmentId,
                    'firstDiff' => $firstDiff,
                    'diff'      => $wordArr,
                ];
            }
        }
    }

    /**
     * @param AmendmentSection[] $sections
     */
    public function addAmendingSections($sections)
    {

        foreach ($sections as $section) {
            $newParas  = HTMLTools::sectionSimpleHTML($section->data);
            $origParas = HTMLTools::sectionSimpleHTML($section->getOriginalMotionSection()->data);
            $this->addAmendingParagraphs($section->amendmentId, $origParas, $newParas);
        }
    }

    /**
     * For testing
     *
     * @param array $data
     */
    public function setAmendingSectionData($data)
    {
        $this->diffParagraphs = $data;
    }

    /**
     * @param array $diff
     * @return array
     */
    public static function splitDiffToInsertDelete($diff)
    {
        $inserts = $deletes = [];
        foreach ($diff as $diffPart) {
            if ($diffPart[1] == Engine::INSERTED) {
                $inserts[] = $diffPart;
            } elseif ($diffPart[1] == Engine::DELETED) {
                $deletes[] = $diffPart;
            } else {
                $diffPart[1] = Engine::INSERTED;
                $inserts[]   = $diffPart;
                $diffPart[1] = Engine::DELETED;
                $deletes[]   = $diffPart;
            }
        }
        return [$deletes, $inserts];
    }

    /**
     * @param array $diffTokens
     * @param string $openingTagRegexp
     * @param string $closingTag
     * @return bool
     */
    public static function findPendingOpeningTag($diffTokens, $openingTagRegexp, $closingTag)
    {
        for ($i = count($diffTokens) - 1; $i >= 0; $i--) {
            if ($diffTokens[$i][0] == $closingTag) {
                return false;
            }
            if (preg_match($openingTagRegexp, $diffTokens[$i][0])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sort the amendment paragraphs by the last affected line/word.
     * This is an attempt to minimize the number of collissions when merging the paragraphs later on,
     * as amendments changing a lot and therefore colloding more frequently tend to start at earlier lines.
     */
    private function sortDiffParagraphs()
    {
        foreach (array_keys($this->diffParagraphs) as $paraId) {
            usort($this->diffParagraphs[$paraId], function ($val1, $val2) {
                if ($val1['firstDiff'] < $val2['firstDiff']) {
                    return 1;
                }
                if ($val2['firstDiff'] < $val1['firstDiff']) {
                    return -1;
                }
                return 0;
            });
        }
    }

    /**
     * @param int $paraNo
     * @param array $diff
     * @return bool;
     */
    private function checkIsDiffColliding($paraNo, $diff)
    {
        for ($i = 0; $i < count($diff); $i++) {
            if ($this->paraData[$paraNo]['words'][$i]['modifiedBy'] > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param int $paraNo
     * @param array $changeSet
     */
    public function mergeParagraph($paraNo, $changeSet)
    {
        $words = $this->paraData[$paraNo]['words'];
        foreach ($changeSet['diff'] as $i => $token) {
            if (isset($token['amendmentId'])) {
                $words[$i]['modification'] = $token['diff'];
                $words[$i]['modifiedBy']   = $token['amendmentId'];
            }
        }
        $this->paraData[$paraNo]['words'] = $words;
    }

    /**
     */
    public function mergeParagraphs()
    {
        $this->sortDiffParagraphs();

        foreach ($this->diffParagraphs as $paraNo => $para) {
            foreach ($para as $changeSet) {
                if ($this->checkIsDiffColliding($paraNo, $changeSet['diff'])) {
                    $this->paraData[$paraNo]['collidingParagraphs'][] = $changeSet;
                } else {
                    $this->mergeParagraph($paraNo, $changeSet);
                }
            }
        }
    }

    /**
     * @param array $para
     * @return array
     */
    private function groupParagraphData($para)
    {
        $groupedParaData  = [];
        $pending          = '';
        $pendingCurrAmend = 0;

        $addToGrouped = function ($pendingCurrAmend, $text) use (&$groupedParaData) {
            $groupedParaData[] = [
                'amendment' => $pendingCurrAmend,
                //'text'      => static::cleanupParagraphData($text),
                'text'      => $text,
            ];
        };

        foreach ($para['words'] as $word) {
            if ($word['modifiedBy'] !== null) {
                if ($pendingCurrAmend == 0 && $word['orig'] != '') {
                    if (mb_strpos($word['modification'], $word['orig']) === 0) {
                        $shortened = mb_substr($word['modification'], mb_strlen($word['orig']));
                        $pending .= $word['orig'];
                        $word['modification'] = $shortened;
                    }
                }
                if ($word['modifiedBy'] != $pendingCurrAmend) {
                    $addToGrouped($pendingCurrAmend, $pending);
                    $pending          = '';
                    $pendingCurrAmend = $word['modifiedBy'];
                }
                $pending .= $word['modification'];
            } else {
                if (0 != $pendingCurrAmend) {
                    $addToGrouped($pendingCurrAmend, $pending);
                    $pending          = '';
                    $pendingCurrAmend = 0;
                }
                $pending .= $word['orig'];
            }
        }
        $addToGrouped($pendingCurrAmend, $pending);
        return $groupedParaData;
    }

    /**
     * @param int $paraNo
     * @return array
     */
    public function getGroupedParagraphData($paraNo)
    {
        return $this->groupParagraphData($this->paraData[$paraNo]);
    }

    /**
     * @param int $paraNo
     * @return array
     */
    public function getGroupedCollidingSections($paraNo)
    {
        $grouped = [];
        foreach ($this->paraData[$paraNo]['collidingParagraphs'] as $section) {
            $groups        = [];
            $currOperation = Engine::UNMODIFIED;
            $currPending   = '';
            foreach ($section['diff'] as $token) {
                if ($token[1] != $currOperation) {
                    $groups[]      = [$currPending, $currOperation];
                    $currOperation = $token[1];
                    $currPending   = '';
                }
                $currPending .= $token[0];
            }
            if ($currPending != '') {
                $groups[] = [$currPending, $currOperation];
            }
            $grouped[$section['amendment']] = $groups;
        }
        return $grouped;
    }

    /**
     * @return boolean
     */
    public function hasCollodingParagraphs()
    {
        foreach ($this->paraData as $paragraph) {
            if (count($paragraph['collidingParagraphs']) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param int $paraNo
     * @return array
     */
    public function getCollidingParagraphGroups($paraNo)
    {
        $grouped = [];

        foreach ($this->paraData[$paraNo]['collidingParagraphs'] as $section) {
            $words = [];
            foreach ($this->paraData[$paraNo]['origTokenized'] as $token) {
                $words[] = [
                    'orig'         => $token,
                    'modification' => null,
                    'modifiedBy'   => null,
                ];
            }
            foreach ($section['diff'] as $i => $token) {
                if (isset($token['amendmentId'])) {
                    $words[$i]['modification'] = $token['diff'];
                    $words[$i]['modifiedBy']   = $token['amendmentId'];
                }
            }
            $grouped[$section['amendment']] = $this->groupParagraphData(['words' => $words]);
        }

        return $grouped;
    }

    /**
     * @param int $paraNo
     * @param int $wrapWords
     * @return array
     */
    public function getWrappedGroupedCollidingSections($paraNo, $wrapWords = 4)
    {
        $grouped = [];

        foreach ($this->paraData[$paraNo]['collidingParagraphs'] as $section) {
            $groups        = [];
            $currOperation = Engine::UNMODIFIED;
            $currPending   = [];
            foreach ($section['diff'] as $token) {
                if ($token[1] != $currOperation) {
                    $groups[]      = [$currPending, $currOperation];
                    $currOperation = $token[1];
                    $currPending   = [];
                }
                if ($token[0] != '') {
                    $currPending[] = $token[0];
                }
            }
            if (count($currPending) > 0) {
                $groups[] = [$currPending, $currOperation];
            }


            $max     = 2 * $wrapWords + 2;
            $wrapped = '';
            foreach ($groups as $i => $group) {
                if ($group[1] == Engine::UNMODIFIED) {
                    if (count($group[0]) <= $max) {
                        $wrapped .= strip_tags(implode('', $group[0]));
                    }
                } else {
                    if ($i > 0 && $groups[$i - 1][1] == Engine::UNMODIFIED && count($groups[$i - 1][0]) > $max) {
                        $lastWords = array_slice($groups[$i - 1][0], -1 * $wrapWords, $wrapWords, true);
                        $wrapped .= '...' . strip_tags(implode('', $lastWords));
                    }
                    if ($group[1] == Engine::INSERTED) {
                        $wrapped .= '<ins>' . implode('', $group[0]) . '</ins>';
                    }
                    if ($group[1] == Engine::DELETED) {
                        $wrapped .= '<del>' . implode('', $group[0]) . '</del>';
                    }
                    $last = ($i == (count($groups) - 1));
                    if (!$last && $groups[$i + 1][1] == Engine::UNMODIFIED && count($groups[$i + 1][0]) > $max) {
                        $firstWords = array_slice($groups[$i + 1][0], 0, $wrapWords, true);
                        $wrapped .= strip_tags(implode('', $firstWords)) . '...<br>';
                    }
                }
            }

            $grouped[$section['amendment']] = HTMLTools::correctHtmlErrors(static::cleanupParagraphData($wrapped));
        }
        return $grouped;
    }

    /**
     * @param array $section
     * @return string
     */
    public static function formatGroupedCollidingSection($section)
    {
        $out = '';
        foreach ($section as $token) {
            if ($token[1] == Engine::UNMODIFIED) {
                $out .= $token[0];
            } elseif ($token[1] == Engine::INSERTED) {
                $out .= '<ins>' . $token[0] . '</ins>';
            } elseif ($token[1] == Engine::DELETED) {
                $out .= '<del>' . $token[0] . '</del>';
            }
        }
        $out = static::cleanupParagraphData($out);
        return $out;
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

        $text = preg_replace_callback('/<ins>.*<\/ins>/siuU', function ($matches) {
            $html = $matches[0];
            $html = preg_replace('/<\/p>\s*<p>/siu', '</ins>\\0<ins>', $html);
            $html = preg_replace('/<\/blockquote>\s*<blockquote>/siu', '</ins>\\0<ins>', $html);
            $html = preg_replace('/<\/pre>\s*<pre>/siu', '</ins>\\0<ins>', $html);
            $html = preg_replace('/<\/li>\s*<\/ul>\s*<ul>\s*<li>/siu', '</ins>\\0<ins>', $html);
            $html = preg_replace('/<\/li>\s*<\/ol>\s*<ol>\s*<li>/siu', '</ins>\\0<ins>', $html);
            return $html;
        }, $text);
        $text = str_replace('<ins></ins>', '', $text);

        $text = preg_replace_callback('/<del>.*<\/del>/siuU', function ($matches) {
            $html = $matches[0];
            $html = preg_replace('/<\/p>\s*<p>/siu', '</del>\\0<del>', $html);
            $html = preg_replace('/<\/blockquote>\s*<blockquote>/siu', '</del>\\0<del>', $html);
            $html = preg_replace('/<\/pre>\s*<pre>/siu', '</del>\\0<del>', $html);
            $html = preg_replace('/<\/li>\s*<\/ul>\s*<ul>\s*<li>/siu', '</del>\\0<del>', $html);
            $html = preg_replace('/<\/li>\s*<\/ol>\s*<ol>\s*<li>/siu', '</del>\\0<del>', $html);
            return $html;
        }, $text);
        $text = str_replace('<del></del>', '', $text);

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
