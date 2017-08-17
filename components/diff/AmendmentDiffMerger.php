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
            $origTokenized = Diff::tokenizeLine($paraStr);
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
     * @param string[] $amendingParas
     */
    public function addAmendingParagraphs($amendmentId, $amendingParas)
    {
        $diff     = new Diff();
        $amParams = ['amendmentId' => $amendmentId];
        $paraArr  = $diff->compareHtmlParagraphsToWordArray($this->sectionParagraphs, $amendingParas, $amParams);
        $paraArr  = MovingParagraphDetector::markupWordArrays($paraArr);

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
            $newParas = HTMLTools::sectionSimpleHTML($section->data);
            $this->addAmendingParagraphs($section->amendmentId, $newParas);
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
     * @param int $amendingNo
     * @param int $wordNo
     * @param string $insert
     */
    private function moveInsertIntoOwnWord($paraNo, $amendingNo, $wordNo, $insert)
    {
        $insertArr              = function ($arr, $pos, $insertedEl) {
            return array_merge(array_slice($arr, 0, $pos + 1), [$insertedEl], array_slice($arr, $pos + 1));
        };

        // Figures out if the blank element is to be inserted in the middle of a deletion block.
        // If so, the "amendmentId"-Attribute needs to be set to trigger a collission
        $pendingDeleteAmendment = function ($paraNo, $locAmendNo, $wordNo) {
            if ($wordNo == 0) {
                return null;
            }

            while ($wordNo >= 0) {
                $str = explode("###DEL_", $this->diffParagraphs[$paraNo][$locAmendNo]['diff'][$wordNo]['diff']);
                if (count($str) > 1 && strpos($str[count($str) - 1], 'START') === 0) {
                    return $this->diffParagraphs[$paraNo][$locAmendNo]['diff'][$wordNo]['amendmentId'];
                }
                if (count($str) > 1 && strpos($str[count($str) - 1], 'END') === 0) {
                    return null;
                }
                $wordNo--;
            };

            return null;
        };

        $this->paraData[$paraNo]['origTokenized'] = $insertArr($this->paraData[$paraNo]['origTokenized'], $wordNo, '');
        $this->paraData[$paraNo]['words']         = $insertArr($this->paraData[$paraNo]['words'], $wordNo, [
            'orig'         => '',
            'modification' => null,
            'modifiedBy'   => null,
        ]);

        foreach ($this->diffParagraphs[$paraNo] as $locAmendNo => $changeSet) {
            if ($locAmendNo == $amendingNo) {
                $amendmentId                        = $changeSet['diff'][$wordNo]['amendmentId'];
                $changeSet['diff'][$wordNo]['diff'] = $changeSet['diff'][$wordNo]['word'];
                unset($changeSet['diff'][$wordNo]['amendmentId']);
                $changeSet['diff'] = $insertArr($changeSet['diff'], $wordNo, [
                    'word'        => '',
                    'diff'        => $insert,
                    'amendmentId' => $amendmentId,
                ]);
            } else {
                $insertArrEl = ['word' => '', 'diff' => ''];
                $preAm       = $pendingDeleteAmendment($paraNo, $locAmendNo, $wordNo);
                if ($preAm !== null) {
                    $insertArrEl['amendmentId'] = $preAm;
                }
                $changeSet['diff'] = $insertArr($changeSet['diff'], $wordNo, $insertArrEl);
            }
            $this->diffParagraphs[$paraNo][$locAmendNo] = $changeSet;
        }
    }

    /**
     * Inserting new words / paragraphs is stored like "</p>###INS_START###...###INS_END###,
     * being assigned to the "</p>" token. This makes multiple insertions after </p> colliding with each other.
     * This workaround splits this up by inserting empty tokens in the original word array
     * and moving the insertion to this newly created index.
     * To maintain consistency, we need to insert the new token both in the original word array as well as in _all_
     * amendments affecting this paragraph.
     *
     * This isn't exactly very elegant, as the data structure mutates as we're iterating over it,
     * therefore we need to cancel out the side-effects.
     *
     * AmendmentRewriter::moveInsertsIntoTheirOwnWords does about the same and should behave similarily
     */
    private function moveInsertsIntoTheirOwnWords()
    {
        foreach ($this->diffParagraphs as $paraNo => $para) {
            $para = $this->diffParagraphs[$paraNo];
            foreach ($para as $changeSetNo => $changeSet) {
                $changeSet = $this->diffParagraphs[$paraNo][$changeSetNo];
                $words     = count($changeSet['diff']);
                for ($wordNo = 0; $wordNo < $words; $wordNo++) {
                    $word  = $changeSet['diff'][$wordNo];
                    $split = explode('###INS_START###', $word['diff']);
                    if (count($split) === 2 && $split[0] == $word['word']) {
                        $this->moveInsertIntoOwnWord($paraNo, $changeSetNo, $wordNo, '###INS_START###' . $split[1]);
                        $changeSet = $this->diffParagraphs[$paraNo][$changeSetNo];
                        $wordNo++;
                        $words++;
                    }
                }
            }
        }
    }

    /**
     * Identify adjacent tokens that are about to be changed and check if any of the changes leads to a collission.
     *
     * @param int $paraNo
     * @param array $changeSet
     * @return array
     */
    private function groupChangeSet($paraNo, $changeSet)
    {
        $foundGroups = [];

        $currTokens        = null;
        $currGroupCollides = null;

        foreach ($changeSet['diff'] as $i => $token) {
            if (isset($token['amendmentId'])) {
                if ($currTokens === null) {
                    $currGroupCollides = false;
                    $currTokens        = [];
                }
                $currTokens[$i] = $token;
                if ($this->paraData[$paraNo]['words'][$i]['modifiedBy'] > 0) {
                    $currGroupCollides = true;
                }
            } else {
                if ($currTokens !== null) {
                    $foundGroups[]     = [
                        'tokens'   => $currTokens,
                        'collides' => $currGroupCollides
                    ];
                    $currTokens        = null;
                    $currGroupCollides = null;
                }
            }
        }
        if ($currTokens !== null) {
            $foundGroups[] = [
                'tokens'   => $currTokens,
                'collides' => $currGroupCollides
            ];
        }

        return $foundGroups;
    }

    /**
     * @param int $paraNo
     * @param array $changeSet
     */
    public function mergeParagraph($paraNo, $changeSet)
    {
        $words = $this->paraData[$paraNo]['words'];

        $paragraphHadCollissions = false;
        $groups                  = $this->groupChangeSet($paraNo, $changeSet);
        foreach ($groups as $group) {
            // Transfer the diff from the non-colliding groups to the merged diff and remove the from the changeset.
            // The changeset that remains will contain the un-mergable collissions

            if ($group['collides']) {
                $paragraphHadCollissions = true;
                continue;
            }

            foreach ($group['tokens'] as $i => $token) {
                // Apply the changes to the paragraph
                $words[$i]['modification'] = $token['diff'];
                $words[$i]['modifiedBy']   = $token['amendmentId'];

                // Only the colliding changes are left in the changeset
                unset($changeSet['diff'][$i]['amendmentId']);
                $changeSet['diff'][$i]['diff'] = $changeSet['diff'][$i]['word'];
            }
        }

        $this->paraData[$paraNo]['words'] = $words;
        if ($paragraphHadCollissions) {
            $this->paraData[$paraNo]['collidingParagraphs'][] = $changeSet;
        }
    }

    /**
     */
    public function mergeParagraphs()
    {
        $this->sortDiffParagraphs();
        $this->moveInsertsIntoTheirOwnWords();

        foreach ($this->diffParagraphs as $paraNo => $para) {
            foreach ($para as $changeSet) {
                $this->mergeParagraph($paraNo, $changeSet);
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
                        $shortened            = mb_substr($word['modification'], mb_strlen($word['orig']));
                        $pending              .= $word['orig'];
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
     * Somewhat special case: if two amendments are inserting a bullet point at the same place,
     * they are colliding. We cannot change this fact right now, so at least
     * let's try not to print the previous line that wasn't actually changed twice.
     *
     * @param string $str
     * @return string
     */
    private function stripUnchangedLiFromColliding($str)
    {
        if (mb_substr($str, 0, 8) != '<ul><li>' && mb_substr($str, 0, 8) != '<ol><li>') {
            return $str;
        }
        if (mb_substr_count($str, '<li>') != 1 || mb_substr_count($str, '</li>') != 1) {
            return $str;
        }
        return preg_replace('/<li>.*<\/li>/siu', '', $str);
    }

    /**
     * @param array $words
     * @param int $maxDistance
     * @return array
     */
    public static function stripDistantUnchangedWords($words, $maxDistance)
    {
        $distance = null;
        $numWords = count($words);
        foreach ($words as $i => $word) {
            $words[$i]['distance'] = null;
        }
        for ($i = 0; $i < $numWords; $i++) {
            if ($words[$i]['modification']) {
                $distance = 0;
            } else {
                if ($distance === null) {
                    continue;
                }
                if (trim(strip_tags($words[$i]['orig'])) != '') {
                    $distance++;
                }
                $words[$i]['distance'] = $distance;
            }
        }
        for ($i = $numWords - 1; $i >= 0; $i--) {
            if ($words[$i]['modification']) {
                $distance = 0;
            } else {
                if ($distance === null) {
                    continue;
                }
                if (trim(strip_tags($words[$i]['orig'])) != '') {
                    $distance++;
                }
                if ($words[$i]['distance'] === null || $words[$i]['distance'] > $distance) {
                    $words[$i]['distance'] = $distance;
                }
            }
        }

        foreach ($words as $i => $word) {
            if (strpos($word['orig'], '<') === false && trim($word['orig']) != '') {
                if ($words[$i]['distance'] == ($maxDistance + 1)) {
                    $words[$i]['orig'] = ' … ';
                } elseif ($words[$i]['distance'] > ($maxDistance + 1)) {
                    $words[$i]['orig'] = '';
                }
            }
            unset($words[$i]['distance']);
        }

        return $words;
    }

    /**
     * @param int $paraNo
     * @param int|null $stripDistantUnchangedWords
     * @return array
     */
    public function getCollidingParagraphGroups($paraNo, $stripDistantUnchangedWords = null)
    {
        $grouped = [];

        foreach ($this->paraData[$paraNo]['collidingParagraphs'] as $changeSet) {
            $words = [];
            foreach ($this->paraData[$paraNo]['origTokenized'] as $token) {
                $words[] = [
                    'orig'         => $token,
                    'modification' => null,
                    'modifiedBy'   => null,
                ];
            }
            foreach ($changeSet['diff'] as $i => $token) {
                if (isset($token['amendmentId'])) {
                    $words[$i]['modification'] = $token['diff'];
                    $words[$i]['modifiedBy']   = $token['amendmentId'];
                }
            }
            if ($stripDistantUnchangedWords) {
                $words = $this->stripDistantUnchangedWords($words, $stripDistantUnchangedWords);
            }
            $data = $this->groupParagraphData(['words' => $words]);
            foreach ($data as $i => $dat) {
                if ($dat['amendment'] == 0) {
                    $data[$i]['text'] = $this->stripUnchangedLiFromColliding($dat['text']);
                }
            }
            $grouped[$changeSet['amendment']] = $data;
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
