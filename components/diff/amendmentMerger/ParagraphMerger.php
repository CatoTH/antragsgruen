<?php /** @noinspection PhpMissingReturnTypeInspection */

namespace app\components\diff\amendmentMerger;

use app\components\diff\{DataTypes\DiffWord, DataTypes\GroupedParagraphData, DataTypes\ParagraphMergerWord, Diff, DiffRenderer};
use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

class ParagraphMerger
{
    private ParagraphOriginalData $paraData;

    /** @var ParagraphDiff[] */
    private array $diffs;

    private bool $merged = false;

    // If set to true, then collisions will be merged into the text, preferring ease of editing over consistency
    private bool $mergeCollisions;

    // Sets the limit, how long a collision may be in character for it to be merged.
    // If the collision (deletion + insertion) is longer than this limit, it will fall back into separating out the collisions
    private int $collisionMergingLimit = 100;

    public function __construct(string $paragraphStr, bool $mergeCollisions)
    {
        $origTokenized = Diff::tokenizeLine($paragraphStr);
        $words         = [];
        foreach ($origTokenized as $x) {
            $word = new ParagraphMergerWord();
            $word->orig = $x;
            $words[] = $word;
        }
        $this->paraData = new ParagraphOriginalData($paragraphStr, $origTokenized, $words);
        $this->diffs    = [];

        $this->mergeCollisions = $mergeCollisions;
    }

    /**
     * @param DiffWord[] $wordArr
     */
    public function addAmendmentParagraph(int $amendmentId, array $wordArr): void
    {
        $hasChanges = false;
        $firstDiff  = null;
        for ($i = 0; $i < count($wordArr); $i++) {
            if ($wordArr[$i]->amendmentId !== null) {
                $hasChanges = true;
                if ($firstDiff === null) {
                    $firstDiff = $i;
                }
            }
        }

        if ($hasChanges) {
            $this->diffs[] = new ParagraphDiff($amendmentId, $firstDiff, $wordArr);
        }
    }

    /*
     * Sort the amendment paragraphs by the first affected line/word descendingly.
     * This is an attempt to minimize the number of collisions when merging the paragraphs later on,
     * as amendments changing a lot and therefore colliding more frequently tend to start at earlier lines.
     */
    private function sortDiffParagraphsFromLastToFirst(): void
    {
        usort($this->diffs, function (ParagraphDiff $val1, ParagraphDiff $val2) {
            return $val2->firstDiff <=> $val1->firstDiff;
        });
    }

    private function sortCollisionsFromFirstToLast(): void
    {
        usort($this->paraData->collidingParagraphs, function (CollidingParagraphDiff $para1, CollidingParagraphDiff $para2) {
            return $para1->firstDiff <=> $para2->firstDiff;
        });
    }

    private function moveInsertIntoOwnWord(int $amendingNo, int $wordNo, string $insert): void
    {
        $insertArr = function ($arr, $pos, $insertedEl) {
            return array_merge(array_slice($arr, 0, $pos + 1), [$insertedEl], array_slice($arr, $pos + 1));
        };

        // Figures out if the blank element is to be inserted in the middle of a deletion block.
        // If so, the "amendmentId"-Attribute needs to be set to trigger a collision
        $pendingDeleteAmendment = function ($locAmendNo, $wordNo) {
            if ($wordNo == 0) {
                return null;
            }

            while ($wordNo >= 0) {
                $str = explode("###DEL_", $this->diffs[$locAmendNo]->diff[$wordNo]->diff);
                if (count($str) > 1 && str_starts_with($str[count($str) - 1], 'START')) {
                    return $this->diffs[$locAmendNo]->diff[$wordNo]->amendmentId;
                }
                if (count($str) > 1 && str_starts_with($str[count($str) - 1], 'END')) {
                    return null;
                }
                $wordNo--;
            }

            return null;
        };

        $this->paraData->origTokenized = $insertArr($this->paraData->origTokenized, $wordNo, '');
        $this->paraData->words         = $insertArr($this->paraData->words, $wordNo, new ParagraphMergerWord());

        foreach ($this->diffs as $locAmendNo => $changeSet) {
            if ($locAmendNo == $amendingNo) {
                $amendmentId                    = $changeSet->diff[$wordNo]->amendmentId;
                $changeSet->diff[$wordNo]->diff = $changeSet->diff[$wordNo]->word;
                $changeSet->diff[$wordNo]->amendmentId = null;

                $toInsert = new DiffWord();
                $toInsert->diff = $insert;
                $toInsert->amendmentId = $amendmentId;
                $changeSet->diff = $insertArr($changeSet->diff, $wordNo, $toInsert);
            } else {
                $insertArrEl = new DiffWord();
                $preAm       = $pendingDeleteAmendment($locAmendNo, $wordNo);
                if ($preAm !== null) {
                    $insertArrEl->amendmentId = $preAm;
                }
                $changeSet->diff = $insertArr($changeSet->diff, $wordNo, $insertArrEl);
            }
            $this->diffs[$locAmendNo] = $changeSet;
        }
    }

    /*
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
     * AmendmentRewriter::moveInsertsIntoTheirOwnWords does about the same and should behave similarly
     */
    private function moveInsertsIntoTheirOwnWords(): void
    {
        foreach ($this->diffs as $changeSetNo => $changeSet) {
            $changeSet = $this->diffs[$changeSetNo];
            $words     = count($changeSet->diff);
            for ($wordNo = 0; $wordNo < $words; $wordNo++) {
                $word  = $changeSet->diff[$wordNo];
                $split = explode('###INS_START###', $word->diff);

                // INS_START appears, is an attachment to the original word without changig it.
                // However, do NOT do this if it's a plain insertion into plain text and we are merging collisions as we have better handling in those routines.
                if (count($split) === 2 && $split[0] === $word->word && !(!str_contains($split[1], '<')   && $this->mergeCollisions)) {
                    $this->moveInsertIntoOwnWord($changeSetNo, $wordNo, '###INS_START###' . $split[1]);
                    $changeSet = $this->diffs[$changeSetNo];
                    $wordNo++;
                    $words++;
                }
            }
        }
    }


    /**
     * Identify adjacent tokens that are about to be changed and check if any of the changes leads to a collision.
     *
     * @return ParagraphDiffGroup[]
     */
    private function groupChangeSet(ParagraphDiff $changeSet): array
    {
        /** @var ParagraphDiffGroup[] $foundGroups */
        $foundGroups = [];

        /** @var DiffWord[]|null $currTokens */
        $currTokens = null;
        $currGroupCollides = null;
        $currGroupFirstCollision = null;
        $currGroupLastCollision = null;

        /** @var int[] $currCollisionIds */
        $currCollisionIds = [];

        foreach ($changeSet->diff as $i => $token) {
            if ($token->amendmentId !== null) {
                if ($currTokens === null) {
                    $currGroupCollides = false;
                    $currCollisionIds = [];
                    $currTokens = [];
                    $currGroupFirstCollision = null;
                    $currGroupLastCollision = null;
                }
                $currTokens[$i] = $token;
                if ($this->paraData->words[$i]->modifiedBy > 0) {
                    $currGroupCollides = true;
                    if (!in_array($this->paraData->words[$i]->modifiedBy, $currCollisionIds)) {
                        $currCollisionIds[] = $this->paraData->words[$i]->modifiedBy;
                    }
                    if ($currGroupFirstCollision === null) {
                        $currGroupFirstCollision = (int) $i;
                    }
                    $currGroupLastCollision = (int) $i;
                }
            } else {
                if ($currTokens !== null) {
                    $foundGroup = new ParagraphDiffGroup();
                    $foundGroup->tokens = $currTokens;
                    $foundGroup->collides = $currGroupCollides;
                    $foundGroup->collisionIds = $currCollisionIds;
                    $foundGroup->firstCollisionPos = $currGroupFirstCollision;
                    $foundGroup->lastCollisionPos = $currGroupLastCollision;
                    $foundGroups[] = $foundGroup;

                    $currTokens = null;
                    $currGroupCollides = null;
                    $currCollisionIds = [];
                }
            }
        }
        if ($currTokens !== null) {
            $foundGroup = new ParagraphDiffGroup();
            $foundGroup->tokens = $currTokens;
            $foundGroup->collides = $currGroupCollides;
            $foundGroup->collisionIds = $currCollisionIds;
            $foundGroup->firstCollisionPos = $currGroupFirstCollision;
            $foundGroup->lastCollisionPos = $currGroupLastCollision;
            $foundGroups[] = $foundGroup;
        }

        return $foundGroups;
    }

    private function mergeParagraphRegularily(ParagraphDiff $changeSet): void
    {
        $words = $this->paraData->words;

        $paragraphHadCollisions = false;
        $collisionIds = [];
        $collidingGroups = [];

        $groups = $this->groupChangeSet($changeSet);
        foreach ($groups as $group) {
            // Transfer the diff from the non-colliding groups to the merged diff and remove it from the changeset.
            // The changeset that remains will contain the un-mergable collisions

            if ($group->collides) {
                $paragraphHadCollisions = true;
                $collisionIds = array_merge($collisionIds, $group->collisionIds);
                $collidingGroups[] = $group;
                continue;
            }

            foreach ($group->tokens as $i => $token) {
                // Apply the changes to the paragraph
                $words[$i]->modification = $token->diff;
                $words[$i]->modifiedBy = $token->amendmentId;

                // Only the colliding changes are left in the changeset
                $changeSet->diff[$i]->amendmentId = null;
                $changeSet->diff[$i]->diff = $changeSet->diff[$i]->word;
            }
        }

        $this->paraData->words = $words;
        if ($paragraphHadCollisions) {
            $collisionIds = array_unique($collisionIds);
            $this->paraData->collidingParagraphs[] = new CollidingParagraphDiff(
                $changeSet->amendment,
                $changeSet->firstDiff,
                $changeSet->diff,
                $collisionIds,
                $collidingGroups
            );
        }
    }

    /*
     * Must only be return true if:
     * - this first affected word of the colliding group is also the first word affected by the already merged amendment
     */
    private function mergeCollidedParagraphGroupIsToBePrepended(ParagraphDiffGroup $paragraphDiffGroup): bool
    {
        $firstPos = $paragraphDiffGroup->firstCollisionPos;
        if ($firstPos > 0 && $this->paraData->words[$firstPos]->modifiedBy === $this->paraData->words[$firstPos - 1]->modifiedBy) {
            return false;
        }

        $hasInsert = (str_contains($paragraphDiffGroup->tokens[$firstPos]->diff, '###INS_START###')  );
        $hasDelete = (str_contains($paragraphDiffGroup->tokens[$firstPos]->diff, '###DEL_START###')  );
        return ($hasInsert && !$hasDelete);
    }

    /*
     * The diff group should be saved into the appendCollisionGroups property of the last word of the first changeset that it collides with.
     * (An alternative would be to store it to the last word of the last changeset it collides with)
     */
    private function mergeCollidedParagraphGroup(ParagraphDiffGroup $paragraphDiffGroup): void {
        $affectedAmendmentId = $this->paraData->words[$paragraphDiffGroup->firstCollisionPos]->modifiedBy;
        $merged = false;
        if ($this->mergeCollidedParagraphGroupIsToBePrepended($paragraphDiffGroup)) {
            $firstPos = $paragraphDiffGroup->firstCollisionPos;
            if ($this->paraData->words[$firstPos]->prependCollisionGroups === null) {
                $this->paraData->words[$firstPos]->prependCollisionGroups = [];
            }
            $this->paraData->words[$firstPos]->prependCollisionGroups[] = $paragraphDiffGroup;
        } else {
            for ($i = $paragraphDiffGroup->firstCollisionPos; $i < count($this->paraData->words) && !$merged; $i++) {
                if ($i === count($this->paraData->words) - 1 || $this->paraData->words[$i + 1]->modifiedBy !== $affectedAmendmentId) {
                    if ($this->paraData->words[$i]->appendCollisionGroups === null) {
                        $this->paraData->words[$i]->appendCollisionGroups = [];
                    }
                    $this->paraData->words[$i]->appendCollisionGroups[] = $paragraphDiffGroup;
                    $merged = true;
                }
            }
        }
    }

    /*
     * The collision will be merged IF:
     * - The setting is set (true by default)
     * - The length of the diff does not exceed the limit defined
     * - It does not smell like having HTML elements
     */
    private function tryMergingCollidedParagraph(CollidingParagraphDiff $paragraph): bool {
        if (!$this->mergeCollisions) {
            return false;
        }

        foreach ($paragraph->collidingGroups as $collidingGroup) {
            $combined = '';
            foreach (array_keys($collidingGroup->tokens) as $tokenKey) {
                $combined .= $collidingGroup->tokens[$tokenKey]->diff;
            }
            $normalized = str_replace(['###DEL_START###', '###DEL_END###', '###INS_START###', '###INS_END###'], ['', '', '', ''], $combined);
            if (grapheme_strpos($normalized, '<') !== false) {
                return false;
            }
            if (grapheme_strlen($normalized) > $this->collisionMergingLimit) {
                return false;
            }
        }

        foreach ($paragraph->collidingGroups as $collidingGroup) {
            $this->mergeCollidedParagraphGroup($collidingGroup);
        }

        return true;
    }

    private function merge(): void
    {
        if ($this->merged) {
            return;
        }

        $this->sortDiffParagraphsFromLastToFirst();
        $this->moveInsertsIntoTheirOwnWords();

        //echo "======== ORIGINAL DIFFS ========\n";
        //var_dump($this->diffs);

        foreach ($this->diffs as $changeSet) {
            $this->mergeParagraphRegularily($changeSet);
        }

        //echo "======== REGULARLY MERGED WORDS ========\n";
        //var_dump($this->paraData->words);
        //echo "======== COLLIDING PARAGRAPHS ========\n";
        //var_dump($this->paraData->collidingParagraphs);

        $this->sortCollisionsFromFirstToLast();

        $this->paraData->collidingParagraphs = array_values(array_filter(
            $this->paraData->collidingParagraphs,
            function (CollidingParagraphDiff $collidingParagraphDiff) {
                return !$this->tryMergingCollidedParagraph($collidingParagraphDiff);
            }
        ));

        //echo "======== MERGED ========\n";
        //var_dump($this->paraData->words);

        $this->merged = true;
    }

    private static function appendedOrPrependedGroupsToPending(?array $groups, ?int &$CHANGESET_COUNTER): string
    {
        if ($groups === null || count($groups) === 0) {
            return '';
        }
        $pending = '';
        foreach ($groups as $collisionGroup) {
            $appendedDiff = '';
            $amendmentId = null;
            foreach ($collisionGroup->tokens as $token) {
                $appendedDiff .= $token->diff;
                $amendmentId = $token->amendmentId;
            }

            $cid = $CHANGESET_COUNTER++;
            $mid = $cid . '-' . $amendmentId . '-COLLISION';
            $appendedDiff = str_replace('###INS_START###', '###INS_START' . $mid . '###', $appendedDiff);
            $appendedDiff = str_replace('###DEL_START###', '###DEL_START' . $mid . '###', $appendedDiff);

            $pending .= $appendedDiff;
        }
        return $pending;
    }

    /**
     * @param ParagraphMergerWord[] $words
     *
     * @return GroupedParagraphData[]
     */
    public static function groupParagraphData(array $words, ?int &$CHANGESET_COUNTER = null): array
    {
        /** @var GroupedParagraphData[] $groupedParaData */
        $groupedParaData  = [];
        $pending          = '';
        $pendingCurrAmend = 0;

        foreach ($words as $wordNo => $word) {
            if ($word->modifiedBy !== null) {
                if ($pendingCurrAmend === 0 && !in_array($word->orig, ['', '#', '##', '###'])) { // # would lead to conflicty with ###DEL_START### in the modification
                    if (grapheme_strpos($word->modification, $word->orig) === 0) {
                        // The current word has an unchanged beginning + an insertion or deletion
                        // => the unchanged part will be added to the $pending queue (which will be added to $groupedParaData in the next "if" statement
                        $shortened            = (string)grapheme_substr($word->modification, (int)grapheme_strlen($word->orig));
                        $pending              .= $word->orig;
                        $word->modification = $shortened;

                        foreach ($word->prependCollisionGroups ?? [] as $group) {
                            // If a prepended collision begins with the same original word, then we don't want to repeat it
                            // Relevant for the scenario:
                            //   - original:        "word "
                            //   - Regular diff:    "word ###INS_START###ins1###INS_END###"
                            //   - Collision:       "word ###INS_START###ins2###INS_END###"
                            //   - Desired outcome: "word ###INS_START###ins2###INS_END######INS_START###ins1###INS_END###"
                            if (grapheme_strpos($group->tokens[$wordNo]->diff, $word->orig) === 0) {
                                $group->tokens[$wordNo]->diff = (string)grapheme_substr($group->tokens[$wordNo]->diff, (int)grapheme_strlen($word->orig));
                            }
                        }
                    }
                }
                if ($word->modifiedBy !== $pendingCurrAmend) {
                    $data = new GroupedParagraphData();
                    $data->amendment = $pendingCurrAmend;
                    $data->text = $pending;
                    $groupedParaData[] = $data;

                    $pending          = '';
                    $pendingCurrAmend = $word->modifiedBy;
                }

                $toPrepend = self::appendedOrPrependedGroupsToPending($word->prependCollisionGroups, $CHANGESET_COUNTER);
                $pending .= $toPrepend;
                if (preg_match("/^(?<orig>.*)###(INS|DEL)_START/siuU", $toPrepend, $matches)) {
                    // Scenario
                    //   - original:        "word "
                    //   - Regular diff:    "word###DEL_START### "
                    //   - Collision:       "word ###INS_START###schena ###INS_END###"
                    //   - Desired outcome: "word ###INS_START###schena ###INS_END######DEL_START### "
                    $origPrepended = $matches['orig'];
                    while (grapheme_strlen($origPrepended) > 0 && grapheme_strlen($word->modification) > 0 && grapheme_substr($origPrepended, 0, 1) === grapheme_substr($word->modification, 0, 1)) {
                        $origPrepended = (string)grapheme_substr($origPrepended, 1);
                        $word->modification = (string)grapheme_substr($word->modification, 1);
                    }
                }
                $pending .= $word->modification;
                $pending .= self::appendedOrPrependedGroupsToPending($word->appendCollisionGroups, $CHANGESET_COUNTER);
            } else {
                if (0 !== $pendingCurrAmend) {
                    $data = new GroupedParagraphData();
                    $data->amendment = $pendingCurrAmend;
                    $data->text = $pending;
                    $groupedParaData[] = $data;

                    $pending          = '';
                    $pendingCurrAmend = 0;
                }
                $pending .= $word->orig;
            }
        }

        $data = new GroupedParagraphData();
        $data->amendment = $pendingCurrAmend;
        $data->text = $pending;
        $groupedParaData[] = $data;

        return $groupedParaData;
    }

    /**
     * @return GroupedParagraphData[]
     */
    public function getGroupedParagraphData(?int &$CHANGESET_COUNTER = null): array
    {
        $this->merge();

        $words = $this->paraData->words;

        return self::groupParagraphData($words, $CHANGESET_COUNTER);
    }

    /**
     * @param Amendment[] $amendmentsById
     */
    public function getFormattedDiffText(array $amendmentsById): string
    {
        $CHANGESET_COUNTER = 0;
        $changeset         = [];

        $groupedParaData = $this->getGroupedParagraphData($CHANGESET_COUNTER);

        $paragraphText   = '';
        foreach ($groupedParaData as $part) {
            $text = $part->text;

            if ($part->amendment > 0) {
                $amendmentId = $part->amendment;
                $cid         = $CHANGESET_COUNTER++;
                if (!isset($changeset[$amendmentId])) {
                    $changeset[$amendmentId] = [];
                }
                $changeset[$amendmentId][] = $cid;

                $mid  = $cid . '-' . $amendmentId;
                $text = str_replace('###INS_START###', '###INS_START' . $mid . '###', $text);
                $text = str_replace('###DEL_START###', '###DEL_START' . $mid . '###', $text);
            }

            $paragraphText .= $text;
        }

        return DiffRenderer::renderForInlineDiff($paragraphText, $amendmentsById);
    }


    /*
     * Somewhat special case: if two amendments are inserting a bullet point at the same place,
     * they are colliding. We cannot change this fact right now, so at least
     * let's try not to print the previous line that wasn't actually changed twice.
     */
    private static function stripUnchangedLiFromColliding(string $str): string
    {
        if (grapheme_substr($str, 0, 8) !== '<ul><li>' && grapheme_substr($str, 0, 8) !== '<ol><li>') {
            return $str;
        }
        if (mb_substr_count($str, '<li>') !== 1 || mb_substr_count($str, '</li>') !== 1) {
            return $str;
        }
        return preg_replace('/<li>.*<\/li>/siu', '', $str);
    }

    /**
     * @return CollidingParagraphDiff[]
     */
    public function getCollidingParagraphs(): array
    {
        $this->merge();
        return $this->paraData->collidingParagraphs;
    }

    /**
     * @return GroupedParagraphData[][]
     */
    public function getCollidingParagraphGroups(): array
    {
        $this->merge();

        $grouped = [];

        foreach ($this->paraData->collidingParagraphs as $changeSet) {
            /** @var ParagraphMergerWord[] $words */
            $words = [];
            foreach ($this->paraData->origTokenized as $token) {
                $mergerWord = new ParagraphMergerWord();
                $mergerWord->orig = $token;
                $words[] = $mergerWord;
            }

            foreach ($changeSet->diff as $i => $token) {
                if ($token->amendmentId !== null) {
                    $words[$i]->modification = $token->diff;
                    $words[$i]->modifiedBy   = $token->amendmentId;
                }
            }

            $data = self::groupParagraphData($words);
            foreach ($data as $i => $dat) {
                if ($dat->amendment == 0) {
                    $data[$i]->text = self::stripUnchangedLiFromColliding($dat->text);
                }
            }
            $grouped[$changeSet->amendment] = $data;
        }

        return $grouped;
    }

    /**
     * @param GroupedParagraphData[] $paraData
     * @param Amendment[] $amendmentsById
     */
    public static function getFormattedCollision(array $paraData, Amendment $amendment, array $amendmentsById, bool $includeControls): string
    {
        $amendmentUrl      = UrlHelper::createAmendmentUrl($amendment);
        $paragraphText     = '';
        $CHANGESET_COUNTER = 0;

        foreach ($paraData as $part) {
            $text = $part->text;

            if ($part->amendment > 0) {
                $amendment = $amendmentsById[$part->amendment];
                $cid       = $CHANGESET_COUNTER++;

                $mid  = $cid . '-' . $amendment->id;
                $text = str_replace('###INS_START###', '###INS_START' . $mid . '###', $text);
                $text = str_replace('###DEL_START###', '###DEL_START' . $mid . '###', $text);
            }

            $paragraphText .= $text;
        }

        $out = '<div class="collidingParagraph collidingParagraph' . $amendment->id . '"
                     data-link="' . Html::encode($amendmentUrl) . '"
                     data-amendment-id="' . $amendment->id . '"
                     data-username="' . Html::encode($amendment->getInitiatorsStr()) . '">';
        if ($includeControls) {
            $out .= '<button class="btn btn-link pull-right btn-xs hideCollision" type="button">' .
                    \Yii::t('amend', 'merge_colliding_hide') . ' <span class="glyphicon glyphicon-minus-sign"></span>' .
                    '</button>';
            $out .= '<p class="collidingParagraphHead"><strong>' .
                    \Yii::t('amend', 'merge_colliding') . ': ' .
                    Html::a(Html::encode($amendment->getFormattedTitlePrefix()), $amendmentUrl) .
                    '</strong></p>';
            $out .= '<div class="alert alert-danger"><p>' . \Yii::t('amend', 'merge_colliding_hint') . '</p></div>';
        } else {
            $out .= '<p class="collidingParagraphHead"><strong>' .
                    \Yii::t('amend', 'merge_colliding') . ': ' . Html::encode($amendment->getFormattedTitlePrefix()) .
                    '</strong></p>';
        }
        $out .= DiffRenderer::renderForInlineDiff($paragraphText, $amendmentsById);
        $out .= '</div>';

        return $out;
    }

    /**
     * @return int[]
     */
    public function getAffectingAmendmentIds(): array
    {
        return array_map(function (ParagraphDiff $diff) {
            return $diff->amendment;
        }, $this->diffs);
    }
}
