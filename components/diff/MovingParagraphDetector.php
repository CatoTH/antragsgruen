<?php

namespace app\components\diff;

use app\components\diff\DataTypes\DiffWord;

class MovingParagraphDetector
{
    private static int $MOVING_PARTNER_COUNT = 1;

    private static function compareMovedParagraphs(string $old, string $new): bool
    {
        $old = trim(strtolower($old));
        $new = trim(strtolower($new));
        return ($old == $new);
    }

    /**
     * @param string[] $paras
     */
    private static function findBlocksWithClasses(array $paras, string $class): array
    {
        $found = [];
        foreach ($paras as $paraNo => $para) {
            $pattern = '/<(?<tag>\w+) [^>]*class *= *["\'][^"\']*' . $class . '[^"\']*["\'][^>]*>' .
                '(?<content>.*)<\/\1>/siuU';
            if (preg_match_all($pattern, $para, $matches)) {
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $found[] = [
                        'tag'   => $matches['tag'][$i],
                        'para'  => $paraNo,
                        'full'  => $matches[0][$i],
                        'inner' => $matches['content'][$i],
                    ];
                }
            }
        }
        return $found;
    }

    private static function addMarkup(string $paragraph, string $matchFull, int $otherPara, string $pairId): string
    {
        $new = preg_replace_callback('/(?<tag><\w+)(?<atts>[^>]*)>/siu', function ($matches) use ($otherPara, $pairId) {
            $atts = $matches['atts'];

            if (preg_match('/class=["\'](?<classes>[^\']*)["\']/siu', $atts, $matches2)) {
                $classes   = explode(' ', $matches2['classes']);
                $classes[] = 'moved';
                $atts      = str_replace($matches2[0], 'class="' . implode(' ', $classes) . '"', $atts);
            } else {
                $atts .= ' class="moved"';
            }

            return $matches['tag'] . ' data-moving-partner-id="' . $pairId . '"' .
                ' data-moving-partner-paragraph="' . $otherPara . '"' . $atts . '>';
        }, $matchFull);

        return str_replace($matchFull, $new, $paragraph);
    }

    /**
     * @param string[] $paras
     * @return string[]
     */
    public static function markupMovedParagraphs(array $paras): array
    {
        $deleted  = self::findBlocksWithClasses($paras, 'deleted');
        $inserted = self::findBlocksWithClasses($paras, 'inserted');

        foreach ($inserted as $ins) {
            foreach ($deleted as $del) {
                if (self::compareMovedParagraphs($ins['inner'], $del['inner'])) {
                    $pairid              = self::$MOVING_PARTNER_COUNT++;
                    $paras[$ins['para']] = self::addMarkup($paras[$ins['para']], $ins['full'], $del['para'], (string)$pairid);
                    $paras[$del['para']] = self::addMarkup($paras[$del['para']], $del['full'], $ins['para'], (string)$pairid);
                }
            }
        }

        return $paras;
    }

    /**
     * @return string[]
     */
    private static function getBlocks(string $html): array
    {
        $found = [];
        preg_match_all('/<(?<tag>div|p|ul|li|blockquote|h1|h2|h3|h4|h5|h6)[^>]*>.*<\/\1>/siuU', $html, $matches);
        foreach ($matches[0] as $html) {
            $found[] = $html;
        }
        return $found;
    }

    /**
     * @param DiffWord[][] $paras
     */
    private static function extractInsertsFromArrays(array $paras): array
    {
        $inserts = [];
        foreach ($paras as $paraNo => $para) {
            foreach ($para as $wordNo => $word) {
                if (str_contains($word->diff, '###INS_START###')  ) {
                    $insBlocks = explode('###INS_START###', $word->diff);
                    for ($i = 1; $i < count($insBlocks); $i++) {
                        $txt = explode('###INS_END###', $insBlocks[$i]);
                        foreach (self::getBlocks($txt[0]) as $block) {
                            $inserts[] = [
                                'para'      => $paraNo,
                                'word_from' => $wordNo,
                                'word_to'   => $wordNo,
                                'html'      => $block,
                            ];
                        }
                    }
                }
            }
        }
        return $inserts;
    }

    /**
     * @param DiffWord[][] $paras
     */
    private static function extractDeletesFromArray(array $paras): array
    {
        $deletes = [];
        foreach ($paras as $paraNo => $para) {
            $currDelBlockStart = null;
            $currDelBlock      = null;
            foreach ($para as $wordNo => $word) {
                if ($currDelBlockStart === null) {
                    // If a DEL starts and ends at the same word, it only deletes a single word
                    // As we're only interested in larger deleted blocks, we can ignore those cases here
                    if (str_contains($word->diff, '###DEL_START###')   &&
                        !str_contains($word->diff, '###DEL_END###')  
                    ) {
                        $x                 = explode('###DEL_START###', $word->diff);
                        $currDelBlock      = $x[1];
                        $currDelBlockStart = $wordNo;
                    }
                } else {
                    if (str_contains($word->diff, '###DEL_END###')  ) {
                        $x            = explode('###DEL_END###', $word->diff);
                        $currDelBlock .= $x[0];
                        foreach (self::getBlocks($currDelBlock) as $block) {
                            $deletes[] = [
                                'para'      => $paraNo,
                                'word_from' => $currDelBlockStart,
                                'word_to'   => $wordNo,
                                'html'      => $block,
                            ];
                        }
                        $currDelBlock      = null;
                        $currDelBlockStart = null;
                    } else {
                        $currDelBlock .= $word->diff;
                    }
                }
            }
        }

        return $deletes;
    }

    /**
     * @param DiffWord[][] $paras
     */
    public static function markupWordArrays(array $paras): array
    {
        $inserts = self::extractInsertsFromArrays($paras);
        $deletes = self::extractDeletesFromArray($paras);

        foreach ($inserts as $insert) {
            foreach ($deletes as $delete) {
                if (self::compareMovedParagraphs($insert['html'], $delete['html'])) {
                    $pairid = self::$MOVING_PARTNER_COUNT++;
                    for ($i = $insert['word_from']; $i <= $insert['word_to']; $i++) {
                        $paras[$insert['para']][$i]->diff = self::addMarkup(
                            $paras[$insert['para']][$i]->diff,
                            $paras[$insert['para']][$i]->diff,
                            $delete['para'],
                            (string)$pairid
                        );
                    }
                    for ($i = $delete['word_from']; $i <= $delete['word_to']; $i++) {
                        $paras[$delete['para']][$i]->diff = self::addMarkup(
                            $paras[$delete['para']][$i]->diff,
                            $paras[$delete['para']][$i]->diff,
                            $insert['para'],
                            (string)$pairid
                        );
                    }
                }
            }
        }

        return $paras;
    }
}
