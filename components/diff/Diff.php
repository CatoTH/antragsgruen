<?php /** @noinspection PhpMissingReturnTypeInspection */

namespace app\components\diff;

use app\components\diff\DataTypes\DiffWord;
use app\components\HashedStaticCache;
use app\models\exceptions\Internal;
use app\models\SectionedParagraph;

/*
 * Hint: type declarations are missing on purpose in this class, as they unfortunately slow down PHP.
 */

class Diff
{
    private const MAX_LINE_CHANGE_RATIO_MIN_LEN = 100;
    private const MAX_LINE_CHANGE_RATIO         = 0.6;
    private const MAX_LINE_CHANGE_RATIO_PART    = 0.4;

    // # is necessary for placeholders like ###LINENUMBER###
    private const WORD_BREAKING_CHARS = [' ', ',', '.', '#', '-', '?', '!', ':', '<', '>'];

    private const LINENUMBER_MARKER = '###LINENUMBER###';

    private Engine $engine;

    public function __construct()
    {
        $this->engine = new Engine();
    }

    public function setIgnoreStr(string $str): void
    {
        $this->engine->setIgnoreStr($str);
    }

    public static function wrapWithInsert(string $str): string
    {
        if ($str === '') {
            return '';
        } else {
            return DiffRenderer::INS_START . $str . DiffRenderer::INS_END;
        }
    }

    public static function wrapWithDelete(string $str): string
    {
        if ($str === '') {
            return '';
        } else {
            return DiffRenderer::DEL_START . $str . DiffRenderer::DEL_END;
        }
    }

    public static function normalizeForDiff(string $line): string
    {
        return preg_replace("/<br>\\n+/siu", "<br>", $line);
    }

    /**
     * @return string[]
     */
    public static function tokenizeLine(string $line): array
    {
        $line    = static::normalizeForDiff($line);
        $htmlTag = '/(<br>\n+|<[^>]+>)/siu';
        $arr     = preg_split($htmlTag, $line, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($arr === false) {
            throw new \RuntimeException('Failed to parse line: ' . $line);
        }
        $out     = [];
        foreach ($arr as $arr2) {
            if (preg_match($htmlTag, $arr2)) {
                $out[] = $arr2;
            } else {
                $tokens = preg_split('/([ \-.:])/', $arr2, -1, PREG_SPLIT_DELIM_CAPTURE);
                if ($tokens === false) {
                    throw new \RuntimeException('Failed to parse line: ' . $arr2);
                }
                foreach ($tokens as $tok) {
                    if ($tok === ' ' || $tok === '-') {
                        if (count($out) == 0) {
                            $out[] = $tok;
                        } else {
                            $out[count($out) - 1] .= $tok;
                        }
                    } else {
                        $out[] = $tok;
                    }
                }
            }
        }
        $out2 = [];
        foreach ($out as $word) {
            if ($word !== '') {
                $out2[] = $word;
            }
        }
        return $out2;
    }

    /**
     * @param string[] $lines
     */
    public static function untokenizeLine(array $lines): string
    {
        return implode('', $lines);
    }

    public function groupOperations(array $operations, string $groupBy): array
    {
        $return = [];

        $preOp        = null;
        $preList      = false;
        $currentSpool = [];
        foreach ($operations as $operation) {
            $firstfour = grapheme_substr($operation[0], 0, 4);
            $isList    = $firstfour === '<ul>' || $firstfour === '<ol>';
            if (preg_match('/^<[^>]*>$/siu', $operation[0]) && $operation[0] !== '</pre>') {
                if (count($currentSpool) > 0) {
                    $return[] = [implode($groupBy, $currentSpool), $preOp];
                }
                $return[]     = [
                    $operation[0],
                    $operation[1],
                ];
                $preOp        = null;
                $currentSpool = [];
            } elseif ($operation[1] !== $preOp || $isList || $preList) {
                if (count($currentSpool) > 0) {
                    $return[] = [
                        implode($groupBy, $currentSpool),
                        $preOp
                    ];
                }
                $preOp        = $operation[1];
                $currentSpool = [$operation[0]];
            } else {
                $currentSpool[] = $operation[0];
            }
            $preList = $isList;
        }
        if (count($currentSpool) > 0) {
            $return[] = [
                implode($groupBy, $currentSpool),
                $preOp
            ];
        }

        return $return;
    }

    private function getCommonPrefix(string $word1, string $word2): string
    {
        $len1 = grapheme_strlen($word1);
        $len2 = grapheme_strlen($word2);
        $min  = min($len1, $len2);
        $str  = '';
        for ($i = 0; $i <= $min; $i++) {
            $char1 = grapheme_substr($word1, $i, 1);
            $char2 = grapheme_substr($word2, $i, 1);
            if ($char1 != $char2) {
                return $str;
            } else {
                $str .= $char1;
            }
        }
        return $str;
    }

    private function getCommonSuffix(string $word1, string $word2): string
    {
        $len1 = grapheme_strlen($word1);
        $len2 = grapheme_strlen($word2);
        $min  = min($len1, $len2);
        $str  = '';
        for ($i = 0; $i <= $min; $i++) {
            $char1 = grapheme_substr($word1, $len1 - $i, 1);
            $char2 = grapheme_substr($word2, $len2 - $i, 1);
            if ($char1 != $char2) {
                return $str;
            } else {
                $str = $char1 . $str;
            }
        }
        return $str;
    }


    private function getCommonWordPrefix(string $word1, string $word2): string
    {
        $prefix      = $this->getCommonPrefix($word1, $word2);
        $len         = (int)grapheme_strlen($prefix);
        $preLen      = (int)grapheme_strlen($prefix);
        $endsInWords = false;
        if (grapheme_strlen($word1) > $preLen && !in_array(grapheme_substr($word1, $preLen, 1), self::WORD_BREAKING_CHARS)) {
            $endsInWords = true;
        }
        if (grapheme_strlen($word2) > $preLen && !in_array(grapheme_substr($word2, $preLen, 1), self::WORD_BREAKING_CHARS)) {
            $endsInWords = true;
        }
        if ($endsInWords) {
            for ($i = 0; $i <= $len; $i++) {
                $char1 = grapheme_substr($prefix, $len - $i, 1);
                if (in_array($char1, self::WORD_BREAKING_CHARS)) {
                    return (string)grapheme_substr($prefix, 0, $len - $i + 1);
                }
            }
            return '';
        } else {
            return $prefix;
        }
    }

    private function getCommonWordSuffix(string $word1, string $word2): string
    {
        $suffix       = $this->getCommonSuffix($word1, $word2);
        $w1len        = grapheme_strlen($word1);
        $w2len        = grapheme_strlen($word2);
        $postLen      = grapheme_strlen($suffix);
        $startsInWord = false;
        if ($w1len > $postLen && !in_array(grapheme_substr($word1, $w1len - $postLen - 1, 1), self::WORD_BREAKING_CHARS)) {
            $startsInWord = true;
        }
        if ($w2len > $postLen && !in_array(grapheme_substr($word2, $w2len - $postLen - 1, 1), self::WORD_BREAKING_CHARS)) {
            $startsInWord = true;
        }
        if ($startsInWord) {
            $len = grapheme_strlen($suffix);
            for ($i = 0; $i < $len; $i++) {
                $char1 = grapheme_substr($suffix, $i, 1);
                if (in_array($char1, self::WORD_BREAKING_CHARS)) {
                    return (string)grapheme_substr($suffix, $i);
                }
            }
            return '';
        } else {
            return $suffix;
        }
    }

    public function computeWordDiff(string $wordDel, string $wordInsert): string
    {
        if (str_starts_with($wordDel, self::LINENUMBER_MARKER) && !str_starts_with($wordInsert, self::LINENUMBER_MARKER)) {
            $linenumber = self::LINENUMBER_MARKER;
            $wordDel    = substr($wordDel, strlen(self::LINENUMBER_MARKER));
        } else {
            $linenumber = '';
        }
        $preWords = $this->getCommonWordPrefix($wordDel, $wordInsert);
        $restDel  = (string)grapheme_substr($wordDel, (int)grapheme_strlen($preWords));
        $restIns  = (string)grapheme_substr($wordInsert, (int)grapheme_strlen($preWords));

        $postWords = $this->getCommonWordSuffix($restDel, $restIns);
        $restDel   = (string)grapheme_substr($restDel, 0, (int)grapheme_strlen($restDel) - (int)grapheme_strlen($postWords));
        $restIns   = (string)grapheme_substr($restIns, 0, (int)grapheme_strlen($restIns) - (int)grapheme_strlen($postWords));


        $preChars = $this->getCommonPrefix($restDel, $restIns);
        if ((int)grapheme_strlen($preChars) < 3) {
            $preChars = '';
        }
        $restDelC = (string)grapheme_substr($restDel, (int)grapheme_strlen($preChars));
        $restInsC = (string)grapheme_substr($restIns, (int)grapheme_strlen($preChars));

        $postChars = $this->getCommonSuffix($restDelC, $restInsC);
        if ((int)grapheme_strlen($postChars) < 3) {
            $postChars = '';
        }
        $restDelC = (string)grapheme_substr($restDelC, 0, (int)grapheme_strlen($restDelC) - (int)grapheme_strlen($postChars));
        $restInsC = (string)grapheme_substr($restInsC, 0, (int)grapheme_strlen($restInsC) - (int)grapheme_strlen($postChars));

        if ((int)grapheme_strlen($restDelC) <= 3 && (int)grapheme_strlen($restInsC) <= 3) {
            return $linenumber . $preWords . $preChars .
                $this->wrapWithDelete($restDelC) . $this->wrapWithInsert($restInsC) .
                $postChars . $postWords;
        }
        return $linenumber . $preWords . $this->wrapWithDelete($preChars . $restDelC . $postChars) .
            $this->wrapWithInsert($preChars . $restInsC . $postChars) . $postWords;
    }

    public function htmlParagraphTypeChanges(array $lineOldArr, array $lineNewArr): bool
    {
        if (count($lineOldArr) === 0 || count($lineNewArr) === 0) {
            return false;
        }
        if (!preg_match('/^<(?<nodeType>\w+)[ >]/siu', $lineOldArr[0], $matches)) {
            return false;
        } else {
            $nodeType1 = $matches['nodeType'];
        }
        if (!preg_match('/^<(?<nodeType>\w+)[ >]/siu', $lineNewArr[0], $matches)) {
            return false;
        } else {
            $nodeType2 = $matches['nodeType'];
        }
        return ($nodeType1 !== $nodeType2);
    }

    public function computeLineDiff(string $lineOld, string $lineNew): string
    {
        $computedStrs = [];
        $lineOld      = static::normalizeForDiff($lineOld);
        $lineNew      = static::normalizeForDiff($lineNew);
        $lineOldArr   = static::tokenizeLine($lineOld);
        $lineNewArr   = static::tokenizeLine($lineNew);

        if ($this->htmlParagraphTypeChanges($lineOldArr, $lineNewArr)) {
            return $this->wrapWithDelete($lineOld) . $this->wrapWithInsert($lineNew);
        }

        $return = $this->engine->compareArrays($lineOldArr, $lineNewArr, false);
        $return = $this->engine->moveWordOpsToMatchSentenceStructure($return);

        $return = $this->groupOperations($return, '');

        for ($i = 0; $i < count($return); $i++) {
            if ($return[$i][1] == Engine::UNMODIFIED) {
                $computedStrs[] = $return[$i][0];
            } elseif ($return[$i][1] == Engine::DELETED) {
                if (isset($return[$i + 1]) && $return[$i + 1][1] == Engine::INSERTED &&
                    strlen($return[$i + 1][0]) > 0 && $return[$i + 1][0][0] != '<'
                ) {
                    $computedStrs[] = $this->computeWordDiff($return[$i][0], $return[$i + 1][0]);
                    $i++;
                } else {
                    $delParts = explode(' ', $return[$i][0]);
                    for ($j = 0; $j < count($delParts) - 1; $j++) {
                        $delParts[$j] .= ' ';
                    }
                    foreach ($delParts as $delPart) {
                        $computedStrs[] = $this->wrapWithDelete($delPart);
                    }
                }
            } elseif ($return[$i][1] == Engine::INSERTED) {
                $insParts = explode(' ', $return[$i][0]);
                for ($j = 0; $j < count($insParts) - 1; $j++) {
                    $insParts[$j] .= ' ';
                }
                foreach ($insParts as $insPart) {
                    $computedStrs[] = $this->wrapWithInsert($insPart);
                }
            } else {
                throw new Internal('Unknown type: ' . $return[$i][1]);
            }
        }

        $combined = static::untokenizeLine($computedStrs);
        $combined = str_replace(DiffRenderer::DEL_END . DiffRenderer::DEL_START, '', $combined);
        $combined = str_replace(DiffRenderer::INS_END . DiffRenderer::INS_START, '', $combined);

        // If too much of the whole paragraph changes, then we don't display an inline diff anymore
        if (str_contains($combined, DiffRenderer::DEL_START)   &&
            str_contains($combined, DiffRenderer::INS_START)
        ) {
            $changeRatio = $this->computeLineDiffChangeRatio($lineOld, $combined);
            if ($changeRatio > self::MAX_LINE_CHANGE_RATIO) {
                return $this->wrapWithDelete($lineOld) . $this->wrapWithInsert($lineNew);
            }
        }

        $split = $this->getUnchangedPrefixPostfix($lineOld, $lineNew, $combined);
        list($prefix, $middleOrig, $middleNew, $middleDiff, $postfix) = $split;

        $middleLen  = grapheme_strlen(str_replace(self::LINENUMBER_MARKER, '', $middleOrig));
        $breaksList = (grapheme_stripos($middleDiff, '</li>') !== false);
        if ($middleLen > self::MAX_LINE_CHANGE_RATIO_MIN_LEN && !$breaksList) {
            $changeRatio = $this->computeLineDiffChangeRatio($middleOrig, $middleDiff);
            if ($changeRatio > self::MAX_LINE_CHANGE_RATIO_PART) {
                $combined = $prefix;
                $combined .= $this->wrapWithDelete($middleOrig);
                $combined .= $this->wrapWithInsert($middleNew);
                $combined .= $postfix;
            }
        }

        return $combined;
    }


    /**
     * @throws Internal
     */
    public static function computeSubsequentInsertsDeletes(array $arr, int $idx): ?array
    {
        $numDeletes = 0;
        $deleteStrs = [];
        $insertStrs = [];
        while ($idx < count($arr) && $arr[$idx][1] == Engine::DELETED) {
            $deleteStrs[] = $arr[$idx][0];
            $numDeletes++;
            $idx++;
        }
        $goon = true;
        for ($i = 0; $i < $numDeletes && $goon; $i++) {
            if (!isset($arr[$idx + $i]) || $arr[$idx + $i][1] != Engine::INSERTED) {
                $goon = false;
            } else {
                $insertStrs[] = $arr[$idx + $i][0];
            }
        }
        $matcher = new ArrayMatcher();
        $matcher->addIgnoredString(self::LINENUMBER_MARKER);
        $newInserts = $matcher->matchArrayResolved($deleteStrs, $insertStrs);
        return [$deleteStrs, $newInserts, count($deleteStrs) + count($insertStrs)];
    }

    /**
     * @return int|false
     */
    public static function findFirstOccurrenceIgnoringTags(string $haystack, string $needle)
    {
        $first = grapheme_strpos($haystack, $needle);
        if ($first === false) {
            return false;
        }
        $firstTag = grapheme_strpos($haystack, '<');
        if ($firstTag === false || $firstTag > $first) {
            return $first;
        }
        $parts = preg_split('/(<[^>]*>)/', $haystack, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            throw new \RuntimeException('Failed to parse line: ' . $haystack);
        }
        $pos   = 0;
        for ($i = 0; $i < count($parts); $i++) {
            if (($i % 2) === 0) {
                $occ = grapheme_strpos($parts[$i], $needle);
                if ($occ !== false) {
                    return $pos + $occ;
                }
            }
            $pos += grapheme_strlen($parts[$i]);
        }
        return false;
    }

    /**
     * @internal
     * @return string[]
     */
    public function getUnchangedPrefixPostfix(string $orig, string $new, string $diff): array
    {
        $firstTagOrig = (preg_match('/^<[^>]+>/siu', $orig, $matchesOrig) ? $matchesOrig[0] : '');
        $firstTagNew  = (preg_match('/^<[^>]+>/siu', $new, $matchesNew) ? $matchesNew[0] : '');
        if ($firstTagOrig !== $firstTagNew) {
            return ['', $orig, $new, $diff, ''];
        }

        $parts = preg_split('/###(INS|DEL)_(START|END)###/siuU', $diff);
        if ($parts === false) {
            throw new \RuntimeException('Failed to parse line: ' . $diff);
        }
        $prefix     = $parts[0];
        $postfix    = $parts[(int)count($parts) - 1];
        $prefixLen  = (int)grapheme_strlen($prefix);
        $postfixLen = (int)grapheme_strlen($postfix);

        $prefixPre = $prefix;
        if ($prefixLen < 40) {
            $prefix = '';
        } else {
            /** @noinspection PhpStatementHasEmptyBodyInspection */
            if ($prefixLen > 0 && grapheme_substr($prefix, $prefixLen - 1, 1) === '.') {
                // Leave it unchanged
            } elseif ($prefixLen > 40 && grapheme_strrpos($prefix, '. ') > $prefixLen - 40) {
                $prefix = (string)grapheme_substr($prefix, 0, grapheme_strrpos($prefix, '. ') + 2);
            } elseif ($prefixLen > 40 && grapheme_strrpos($prefix, '.') > $prefixLen - 40) {
                $prefix = (string)grapheme_substr($prefix, 0, grapheme_strrpos($prefix, '.') + 1);
            }
        }
        if ($prefix === '') {
            if (preg_match('/^(<(p|blockquote|ul|ol|li|pre)>)+/siu', $prefixPre, $matches)) {
                $prefix = (string)$matches[0];
            }
        }

        $postfixPre = $postfix;
        if ($postfixLen < 40) {
            $postfix = '';
        } else {
            $firstDot = static::findFirstOccurrenceIgnoringTags($postfix, '. ');
            if ($postfixLen > 40 && $firstDot !== false && $firstDot < 40) {
                $postfix = (string)grapheme_substr($postfix, $firstDot + 1);
            } else {
                $firstDot = static::findFirstOccurrenceIgnoringTags($postfix, '.');
                if ($postfixLen > 40 && $firstDot !== false && $firstDot < 40) {
                    $postfix = (string)grapheme_substr($postfix, $firstDot + 1);
                }
            }
        }

        if ($postfix === '') {
            if (preg_match('/(<\/(p|blockquote|ul|ol|li|pre)>)+$/siu', $postfixPre, $matches)) {
                $postfix = (string)$matches[0];
            }
        }

        // The old and the new version might have different attributes in the HTML tags, like a start="1" attribute. We need to normalize that
        $prefixNew               = str_replace(self::LINENUMBER_MARKER, '', $prefix);
        $postfixNew              = str_replace(self::LINENUMBER_MARKER, '', $postfix);
        $prefixNewNormalized     = preg_replace("/<(ul|ol|li)[^>]+>/siu", '<\1>', $prefixNew); // remove start/class-attributes from tags
        $postfixNewNormalized    = preg_replace("/<(ul|ol|li)[^>]+>/siu", '<\1>', $postfixNew); // remove start/class-attributes from tags
        $newNormalized           = preg_replace("/<(ul|ol|li)[^>]+>/siu", '<\1>', $new);
        $prefixNewNormalizedLen  = (int)grapheme_strlen($prefixNewNormalized);
        $postfixNewNormalizedLen = (int)grapheme_strlen($postfixNewNormalized);

        $prefixLen     = (int)grapheme_strlen($prefix);
        $postfixLen    = (int)grapheme_strlen($postfix);
        $middleDiff    = (string)grapheme_substr($diff, $prefixLen, (int)grapheme_strlen($diff) - $prefixLen - $postfixLen);
        $middleOrig    = (string)grapheme_substr($orig, $prefixLen, (int)grapheme_strlen($orig) - $prefixLen - $postfixLen);

        $middleNew     = (string)grapheme_substr($newNormalized, $prefixNewNormalizedLen, grapheme_strlen($newNormalized) - $prefixNewNormalizedLen - $postfixNewNormalizedLen);

        return [$prefix, $middleOrig, $middleNew, $middleDiff, $postfix];
    }

    /**
     * @internal
     */
    public function computeLineDiffChangeRatio(string $orig, string $diff): float
    {
        $orig       = str_replace([self::LINENUMBER_MARKER], [''], $orig);
        $diff       = str_replace([self::LINENUMBER_MARKER], [''], $diff);
        $origLength = grapheme_strlen(strip_tags($orig));
        if ($origLength === 0) {
            return 0.0;
        }
        $strippedDiff = preg_replace('/###INS_START###(.*)###INS_END###/siuU', '', $diff);
        $strippedDiff = preg_replace('/###DEL_START###(.*)###DEL_END###/siuU', '', $strippedDiff);

        $strippedDiffLength = grapheme_strlen(strip_tags($strippedDiff));

        return 1.0 - ($strippedDiffLength / $origLength);
    }

    /**
     * @param SectionedParagraph[] $referenceParas
     * @param SectionedParagraph[] $newParas
     * @return string[]
     * @throws Internal
     */
    public function compareHtmlParagraphs(array $referenceParas, array $newParas, int $diffFormatting): array
    {
        $referenceParas = array_map(fn(SectionedParagraph $par) => $par->html, $referenceParas);
        $newParas = array_map(fn(SectionedParagraph $par) => $par->html, $newParas);

        $cache_deps = [$referenceParas, $newParas, $diffFormatting];
        $cache = HashedStaticCache::getInstance('compareHtmlParagraphs', $cache_deps);

        return $cache->getCached(function () use ($referenceParas, $newParas, $diffFormatting) {
            $matcher = new ArrayMatcher();
            $matcher->addIgnoredString(self::LINENUMBER_MARKER);
            $this->setIgnoreStr(self::LINENUMBER_MARKER);
            $renderer = new DiffRenderer();
            $renderer->setFormatting($diffFormatting);

            list($adjustedRef, $adjustedMatching) = $matcher->matchForDiff($referenceParas, $newParas);
            if (count($adjustedRef) !== count($adjustedMatching)) {
                throw new Internal('compareSectionedHtml: number of sections does not match');
            }
            $diffSections = [];
            for ($i = 0; $i < count($adjustedRef); $i++) {
                $diffLine       = $this->computeLineDiff($adjustedRef[$i], $adjustedMatching[$i]);
                $diffSections[] = $renderer->renderHtmlWithPlaceholders($diffLine);
            }

            $pendingInsert = '';
            $resolved      = [];
            foreach ($diffSections as $diffS) {
                $diffS = preg_replace('/<del( [^>]*)?>' . self::LINENUMBER_MARKER . '<\/del>/siu', self::LINENUMBER_MARKER, $diffS);
                if (preg_match('/<del( [^>]*)?>###EMPTYINSERTED###<\/del>/siu', $diffS)) {
                    $str = preg_replace('/<del( [^>]*)?>###EMPTYINSERTED###<\/del>/siu', '', $diffS);
                    if (count($resolved) > 0) {
                        $resolved[count($resolved) - 1] .= $str;
                    } else {
                        $pendingInsert .= $str;
                    }
                } else {
                    $str           = preg_replace('/<ins( [^>]*)?>###EMPTYINSERTED###<\/ins>/siu', '', $diffS);
                    $resolved[]    = $pendingInsert . $str;
                    $pendingInsert = '';
                }
            }
            if ($pendingInsert) {
                $resolved[] = $pendingInsert;
            }

            return MovingParagraphDetector::markupMovedParagraphs($resolved);
        });
    }

    /**
     * @return DiffWord[]
     */
    public function convertToWordArray(string $diff, ?int $amendmentId = null): array
    {
        $splitChars        = [' ', '-', '>', '<', ':', '.'];
        $words             = [
            0 => new DiffWord(),
        ];
        $diffPartArr = preg_split('/(###(?:INS|DEL)_(?:START|END)###)/siu', $diff, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($diffPartArr === false) {
            throw new \RuntimeException('Failed to parse line: ' . $diff);
        }
        $inDel             = $inIns = false;
        $originalWordPos   = 0;
        $pendingOpeningDel = false;
        foreach ($diffPartArr as $diffPart) {
            if ($diffPart === '###INS_START###') {
                $words[$originalWordPos]->diff .= $diffPart;
                $words[$originalWordPos]->amendmentId = $amendmentId;
                $inIns = true;
            } elseif ($diffPart === '###INS_END###') {
                $words[$originalWordPos]->diff .= $diffPart;
                $words[$originalWordPos]->amendmentId = $amendmentId;
                $inIns = false;
            } elseif ($diffPart === '###DEL_START###') {
                $inDel             = true;
                $pendingOpeningDel = true;
            } elseif ($diffPart === '###DEL_END###') {
                $words[$originalWordPos]->diff .= $diffPart;
                $words[$originalWordPos]->amendmentId = $amendmentId;
                $inDel = false;
            } else {
                $diffPartWords = static::tokenizeLine($diffPart);
                if ($inIns) {
                    $words[$originalWordPos]->diff .= implode('', $diffPartWords);
                    $words[$originalWordPos]->amendmentId = $amendmentId;
                } elseif ($inDel) {
                    foreach ($diffPartWords as $diffPartWord) {
                        $prevLastChar = grapheme_substr($words[$originalWordPos]->word, -1, 1);
                        $isNewWord    = (
                            in_array($prevLastChar, $splitChars) ||
                            (in_array($diffPartWord, $splitChars) && $diffPartWord != ' ' && $diffPartWord != '-') ||
                            $diffPartWord[0] == '<'
                        );
                        if ($isNewWord || $originalWordPos === 0) {
                            $originalWordPos++;
                            $words[$originalWordPos] = new DiffWord();
                        }
                        $words[$originalWordPos]->word .= $diffPartWord;
                        if ($pendingOpeningDel) {
                            $words[$originalWordPos]->diff .= '###DEL_START###';
                            $pendingOpeningDel              = false;
                        }
                        $words[$originalWordPos]->diff .= $diffPartWord;
                        $words[$originalWordPos]->amendmentId = $amendmentId;
                    }
                } else {
                    foreach ($diffPartWords as $diffPartWord) {
                        $prevLastChar = grapheme_substr($words[$originalWordPos]->word, -1, 1);
                        $isNewWord    = (
                            in_array($prevLastChar, $splitChars) ||
                            (in_array($diffPartWord, $splitChars) && $diffPartWord !== ' ' && $diffPartWord !== '-') ||
                            $diffPartWord[0] === '<'
                        );

                        if ($isNewWord || $originalWordPos === 0) {
                            $originalWordPos++;
                            $words[$originalWordPos] = new DiffWord();
                        }
                        $words[$originalWordPos]->word .= $diffPartWord;
                        $words[$originalWordPos]->diff .= $diffPartWord;
                    }
                }
            }
        }

        $first = array_shift($words);
        if (count($words) === 0) {
            return [$first];
        } else {
            $words[0]->diff = $first->diff . $words[0]->diff;
            return $words;
        }
    }

    /**
     * @param DiffWord[] $wordArr
     * @throws Internal
     */
    public function checkWordArrayConsistency(string $orig, array $wordArr): void
    {
        $origArr = self::tokenizeLine($orig);
        if (count($origArr) === 0 && count($wordArr) === 1) {
            return;
        }
        for ($i = 0; $i < count($wordArr); $i++) {
            if (!isset($origArr[$i])) {
                var_dump($wordArr);
                var_dump($origArr);
                throw new Internal('Only exists in Diff-wordArray: ' . print_r($wordArr[$i], true) . ' (Pos: ' . $i . ')');
            }
            if ($origArr[$i] !== $wordArr[$i]->word) {
                var_dump($wordArr);
                var_dump($origArr);
                throw new Internal('Inconsistency; first difference at pos: ' . $i .
                    ' ("' . $origArr[$i] . '" vs. "' . $wordArr[$i]->word . '")');
            }
        }
        if (count($wordArr) !== count($origArr)) {
            var_dump($wordArr);
            var_dump($origArr);
            throw new Internal('Unequal size of arrays, but equal at beginning');
        }
    }

    /**
     * @param string[] $referenceParas
     * @param string[] $newParas
     * @throws Internal
     * @return DiffWord[][]
     */
    public function compareHtmlParagraphsToWordArray(array $referenceParas, array $newParas, ?int $amendmentId = null): array
    {
        $matcher = new ArrayMatcher();
        $matcher->addIgnoredString(self::LINENUMBER_MARKER);
        list($adjustedRef, $adjustedMatching) = $matcher->matchForDiff($referenceParas, $newParas);
        if (count($adjustedRef) !== count($adjustedMatching)) {
            throw new Internal('compareSectionedHtml: number of sections does not match');
        }

        /** @var DiffWord[][] $diffSections */
        $diffSections  = [];
        $pendingInsert = '';
        for ($i = 0; $i < count($adjustedRef); $i++) {
            if ($adjustedRef[$i] === '###EMPTYINSERTED###') {
                $diffLine  = $this->computeLineDiff('', $adjustedMatching[$i]);
                $wordArray = $this->convertToWordArray($diffLine, $amendmentId);
                if (count($wordArray) !== 1 || $wordArray[0]->word !== '') {
                    throw new Internal('Inserted Paragraph Incosistency');
                }
                if (count($diffSections) === 0) {
                    $pendingInsert .= $wordArray[0]->diff;
                } else {
                    $last                                 = count($diffSections) - 1;
                    $lastEl                               = count($diffSections[$last]) - 1;
                    $diffSections[$last][$lastEl]->diff .= $wordArray[0]->diff;
                    $diffSections[$last][$lastEl]->amendmentId = $amendmentId;
                }
            } else {
                $origLine    = $adjustedRef[$i];
                $matchingRow = str_replace('###EMPTYINSERTED###', '', $adjustedMatching[$i]);
                $diffLine    = $this->computeLineDiff($origLine, $matchingRow);
                $wordArray   = $this->convertToWordArray($diffLine, $amendmentId);

                $this->checkWordArrayConsistency($origLine, $wordArray);
                if ($pendingInsert !== '') {
                    $wordArray[0]->diff = $pendingInsert . $wordArray[0]->diff;
                    $wordArray[0]->amendmentId = $amendmentId;
                    $pendingInsert      = '';
                }
                $diffSections[] = $wordArray;
            }
        }
        return $diffSections;
    }

    /**
     * @param SectionedParagraph[] $referenceParas
     * @param SectionedParagraph[] $newParas
     * @throws Internal
     */
    public static function computeAffectedParagraphs(array $referenceParas, array $newParas, int $diffFormatting): array
    {
        $diff          = new Diff();
        $diffParas     = $diff->compareHtmlParagraphs($referenceParas, $newParas, $diffFormatting);
        $affectedParas = [];
        foreach ($diffParas as $paraNo => $para) {
            if (DiffRenderer::paragraphContainsDiff($para) !== null) {
                $affectedParas[$paraNo] = $para;
            }
        }
        return $affectedParas;
    }
}
