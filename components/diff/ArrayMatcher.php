<?php

namespace app\components\diff;

class ArrayMatcher
{
    private $ignoredStrings = ['###EMPTYINSERTED###'];

    /**
     * @param string $str
     */
    public function addIgnoredString($str)
    {
        $this->ignoredStrings[] = $str;
    }

    /**
     * @param string[] $reference
     * @param string[] $toMatchArr
     * @return string[][]
     */
    public static function calcVariants($reference, $toMatchArr)
    {
        $emptyArray  = function ($num) {
            $arr = [];
            for ($i = 0; $i < $num; $i++) {
                $arr[] = '###EMPTYINSERTED###';
            }
            return $arr;
        };
        $spaceToFill = count($reference) - count($toMatchArr);

        if ($spaceToFill == 0) {
            return [$toMatchArr];
        }
        if (count($toMatchArr) == 0) {
            return [$emptyArray($spaceToFill)];
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
            $recVariants = static::calcVariants($tmpReference, $tmpMatchArr);
            foreach ($recVariants as $recVariant) {
                $mergedVariant = array_merge($begin, $recVariant);
                $variants[]    = $mergedVariant;
            }
        }
        return $variants;
    }

    /**
     * @param string[] $arr1
     * @param string[] $arr2
     * @return int
     * @throws
     */
    public function calcSimilarity($arr1, $arr2)
    {
        if (count($arr1) != count($arr2)) {
            throw new \Exception('calcSimilarity: The number of elements does not match');
        }
        $replaces = [];
        for ($i = 0; $i < count($this->ignoredStrings); $i++) {
            $replaces[] = '';
        }
        $similarity = 0;
        for ($i = 0; $i < count($arr1); $i++) {
            $val1 = str_replace($this->ignoredStrings, $replaces, $arr1[$i]);
            $val2 = str_replace($this->ignoredStrings, $replaces, $arr2[$i]);
            $similarity += similar_text($val1, $val2);
        }
        return $similarity;
    }

    /**
     * @param string[] $reference
     * @param string[][] $variants
     * @return string[]
     */
    public function getBestFit($reference, $variants)
    {
        $bestVariant           = null;
        $bestVariantSimilarity = 0;
        foreach ($variants as $variant) {
            $similarity = static::calcSimilarity($reference, $variant);
            if ($similarity > $bestVariantSimilarity) {
                $bestVariantSimilarity = $similarity;
                $bestVariant           = $variant;
            }
        }
        return $bestVariant;
    }

    /**
     * @param string[] $referenceArr
     * @param string[] $toMatchArr
     * @return string[]
     */
    public function matchArrayWithPlaceholder($referenceArr, $toMatchArr)
    {
        $variants = $this->calcVariants($referenceArr, $toMatchArr);
        $bestFit  = $this->getBestFit($referenceArr, $variants);
        return $bestFit;
    }

    /**
     * @param string[] $referenceArr
     * @param string[] $toMatchArr
     * @return string[]
     */
    public function matchArray($referenceArr, $toMatchArr)
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
            $return = [];
            while (count($matchRef) > 0 && $matchRef[0] == '###EMPTYINSERTED###') {
                array_shift($matchRef);
                $firstl = array_shift($toMatchArr);
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
}
