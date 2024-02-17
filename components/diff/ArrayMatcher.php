<?php

namespace app\components\diff;

use app\models\exceptions\Internal;
use app\models\SectionedParagraph;

class ArrayMatcher
{
    /** @var string[] */
    private array $ignoredStrings = ['###EMPTYINSERTED###'];

    private Engine $diffEngine;

    public function __construct()
    {
        $this->diffEngine = new Engine();
    }

    public function addIgnoredString(string $str): void
    {
        $this->ignoredStrings[] = $str;
        $this->diffEngine->setIgnoreStr($str); // @TODO does not work with multiple strings yet
    }

    /**
     * @internal
     * @param string[] $reference
     * @param string[] $toMatchArr
     * @return string[][]
     */
    public static function calcVariants(array $reference, array $toMatchArr): array
    {
        $emptyArray  = function ($num) {
            $arr = [];
            for ($i = 0; $i < $num; $i++) {
                $arr[] = '###EMPTYINSERTED###';
            }
            return $arr;
        };
        $spaceToFill = count($reference) - count($toMatchArr);

        if ($spaceToFill === 0) {
            return [$toMatchArr];
        }
        if (count($toMatchArr) === 0) {
            return [$emptyArray($spaceToFill)];
        }
        if ($spaceToFill >= 10) {
            // @TODO Find a better solution for this
            // As the number of variants grows exponentially with the number of elements to fill, we need
            // a fallback for this kind of situation. Better a suboptimal solution than a broken site.
            // This usually happens when a lot of paragraphs are deleted or inserted.
            for ($i = 0; $i < $spaceToFill; $i++) {
                $toMatchArr[] = '###EMPTYINSERTED###';
            }
            return [$toMatchArr];
        }
        $variants = [];
        for ($trailingSpaces = 0; $trailingSpaces <= $spaceToFill; $trailingSpaces++) {
            $tmpMatchArr  = $toMatchArr;
            $tmpReference = $reference;
            $begin        = [];
            for ($i = 0; $i < $trailingSpaces; $i++) {
                $begin[] = '###EMPTYINSERTED###';
                array_shift($tmpReference);
            }
            $begin[] = array_shift($tmpMatchArr);
            array_shift($tmpReference);
            $recVariants = self::calcVariants($tmpReference, $tmpMatchArr);
            foreach ($recVariants as $recVariant) {
                $mergedVariant = array_merge($begin, $recVariant);
                $variants[]    = $mergedVariant;
            }
        }
        return $variants;
    }


    private static array $calcSimilarityCache = [];

    /**
     * @internal
     * @param string[] $arr1
     * @param string[] $arr2
     * @throws Internal
     */
    public function calcSimilarity(array $arr1, array $arr2): int
    {
        if (count($arr1) !== count($arr2)) {
            throw new Internal('calcSimilarity: The number of elements does not match');
        }
        $cacheKey = md5(serialize($arr1) . serialize($arr2));
        if (isset(self::$calcSimilarityCache[$cacheKey])) {
            return self::$calcSimilarityCache[$cacheKey];
        }

        $replaces = [];
        for ($i = 0; $i < count($this->ignoredStrings); $i++) {
            $replaces[] = '';
        }
        $similarity = 0;
        for ($i = 0; $i < count($arr1); $i++) {
            $val1 = str_replace($this->ignoredStrings, $replaces, $arr1[$i]);
            $val2 = str_replace($this->ignoredStrings, $replaces, $arr2[$i]);

            $cacheKey2 = md5("1" . $val1 . "2" . $val2);
            if (!isset(self::$calcSimilarityCache[$cacheKey2])) {
                self::$calcSimilarityCache[$cacheKey2] = similar_text($val1, $val2);
            }
            $similarity += self::$calcSimilarityCache[$cacheKey2];
        }
        self::$calcSimilarityCache[$cacheKey] = $similarity;
        return $similarity;
    }

    /**
     * @internal
     * @param string[] $reference
     * @param string[][] $variants
     * @return string[]
     * @throws Internal
     */
    public function getBestFit(array $reference, array $variants): array
    {
        $bestVariant           = null;
        $bestVariantSimilarity = 0;
        foreach ($variants as $variant) {
            $similarity = self::calcSimilarity($reference, $variant);
            if ($similarity > $bestVariantSimilarity) {
                $bestVariantSimilarity = $similarity;
                $bestVariant           = $variant;
            }
        }
        return $bestVariant;
    }

    /**
     * $referenceArr is guaranteed to always have more elements than $toMatchArr
     *
     * @internal
     * @param string[] $referenceArr
     * @param string[] $toMatchArr
     * @return string[]
     * @throws Internal
     */
    public function matchArrayWithPlaceholder(array $referenceArr, array $toMatchArr): array
    {
        if (count($toMatchArr) == 0) {
            $bestFit = [];
            for ($i = 0; $i < count($referenceArr); $i++) {
                $bestFit[] = '';
            }
            return $bestFit;
        }
        $variants = $this->calcVariants($referenceArr, $toMatchArr);
        $bestFit  = $this->getBestFit($referenceArr, $variants);
        return $bestFit;
    }

    /**
     * @param string[] $referenceArr
     * @param string[] $toMatchArr
     * @return string[]
     * @throws Internal
     */
    public function matchArrayResolved(array $referenceArr, array $toMatchArr): array
    {
        if (count($referenceArr) == count($toMatchArr)) {
            return $toMatchArr;
        } elseif (count($referenceArr) > count($toMatchArr)) {
            $return = $this->matchArrayWithPlaceholder($referenceArr, $toMatchArr);
            for ($i = 0; $i < count($return); $i++) {
                if ($return[$i] == '###EMPTYINSERTED###') {
                    $return[$i] = '';
                }
            }
        } else {
            $matchRef = $this->matchArrayWithPlaceholder($toMatchArr, $referenceArr);
            $return   = [];
            while (count($matchRef) > 0 && $matchRef[0] == '###EMPTYINSERTED###') {
                array_shift($matchRef);
                $firstl        = array_shift($toMatchArr);
                $toMatchArr[0] = $firstl . $toMatchArr[0];
            }
            for ($i = 0; $i < count($matchRef); $i++) {
                if ($matchRef[$i] == '###EMPTYINSERTED###') {
                    $return[count($return) - 1] .= $toMatchArr[$i];
                } else {
                    $return[] = $toMatchArr[$i];
                }
            }
        }
        return $return;
    }

    /**
     * The reference array is returned as well and may contain more elements than before
     * Newly created elements contain ###EMPTYINSERTED### and have to be handled later on
     *
     * The purpose is to help the diff later on: e.g. if a list item is added, it makes more sense
     * not to merge two items
     *
     * @internal
     * @param string[] $referenceArr
     * @param string[] $toMatchArr
     * @return array
     * @throws Internal
     */
    public function matchArrayUnresolved(array $referenceArr, array $toMatchArr): array
    {
        if (count($referenceArr) == count($toMatchArr)) {
            return [$referenceArr, $toMatchArr];
        } elseif (count($referenceArr) > count($toMatchArr)) {
            $return = $this->matchArrayWithPlaceholder($referenceArr, $toMatchArr);
            return [$referenceArr, $return];
        } else {
            $matchRef = $this->matchArrayWithPlaceholder($toMatchArr, $referenceArr);
            return [$matchRef, $toMatchArr];
        }
    }

    /**
     * @param array $arr
     * @param int $idx
     * @return array
     */
    private function getSubsequentInsertsDeletes(array $arr, int $idx): array
    {
        $deleteStrs = [];
        $insertStrs = [];
        while ($idx < count($arr) && $arr[$idx][1] == Engine::DELETED) {
            $deleteStrs[] = $arr[$idx][0];
            $idx++;
        }
        while ($idx < count($arr) && $arr[$idx][1] == Engine::INSERTED) {
            $insertStrs[] = $arr[$idx][0];
            $idx++;
        }
        return [$deleteStrs, $insertStrs];
    }


    /**
     * Reference is usually the original motion, matching the amendment
     * It returns two new arrays of the same size
     * If new elements are inserted, they are marked by ###EMPTYINSERTED###
     *
     * @param string[] $referenceArr
     * @param string[] $toMatchArr
     * @return array
     * @throws Internal
     */
    public function matchForDiff(array $referenceArr, array $toMatchArr): array
    {
        $diff = $this->diffEngine->compareArrays($referenceArr, $toMatchArr, true);

        $newRef = $newMatching = [];

        for ($i = 0; $i < count($diff); $i++) {
            if ($diff[$i][1] == Engine::UNMODIFIED) {
                $newRef[]      = $diff[$i][0];
                $newMatching[] = $diff[$i][0];
            } elseif ($diff[$i][1] == Engine::DELETED) {
                list($deletes, $inserts) = $this->getSubsequentInsertsDeletes($diff, $i);
                list($tmpRef, $tmpMatch) = $this->matchArrayUnresolved($deletes, $inserts);
                $newRef      = array_merge($newRef, $tmpRef);
                $newMatching = array_merge($newMatching, $tmpMatch);
                $i += count($deletes) + count($inserts) - 1;
            } elseif ($diff[$i][1] == Engine::INSERTED) {
                $newRef[]      = '###EMPTYINSERTED###';
                $newMatching[] = $diff[$i][0];
            }
        }

        return [$newRef, $newMatching];
    }

    /**
     * @param SectionedParagraph[] $oldParagraphs
     * @param SectionedParagraph[] $newParagraphs
     * @return string[]
     * @throws Internal
     */
    public static function computeMatchingAffectedParagraphs(array $oldParagraphs, array $newParagraphs): array
    {
        $newParagraphs = array_map(fn(SectionedParagraph $par) => $par->html, $newParagraphs);
        $oldParagraphs = array_map(fn(SectionedParagraph $par) => $par->html, $oldParagraphs);

        $matcher = new ArrayMatcher();
        list($oldAdjusted, $newAdjusted) = $matcher->matchForDiff($oldParagraphs, $newParagraphs);
        if (count($oldAdjusted) != count($newAdjusted)) {
            throw new Internal('computeMatchingAffectedParagraphs: number of sections does not match');
        }

        $pendinginsert   = '';
        $oldWithoutEmpty = $newWithoutEmpty = [];
        for ($i = 0; $i < count($oldAdjusted); $i++) {
            if ($oldAdjusted[$i] == '###EMPTYINSERTED###') {
                if (count($newWithoutEmpty) == 0) {
                    $pendinginsert .= $newAdjusted[$i];
                } else {
                    $newWithoutEmpty[count($newWithoutEmpty) - 1] .= $newAdjusted[$i];
                }
            } else {
                if ($newAdjusted[$i] == '###EMPTYINSERTED###') {
                    $newWithoutEmpty[] = $pendinginsert . '';
                } else {
                    $newWithoutEmpty[] = $pendinginsert . $newAdjusted[$i];
                }
                $oldWithoutEmpty[] = $oldAdjusted[$i];
                $pendinginsert     = '';
            }
        }

        if (serialize($oldParagraphs) !== serialize($oldWithoutEmpty)) {
            throw new Internal("An internal error matching the paragraphs occurred");
        }

        return $newWithoutEmpty;
    }
}
