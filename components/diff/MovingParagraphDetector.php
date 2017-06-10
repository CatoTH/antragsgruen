<?php

namespace app\components\diff;

class MovingParagraphDetector
{
    private static $MOVING_PARTNER_COUNT = 1;

    /**
     * @param string $old
     * @param string $new
     * @return boolean
     */
    private static function compareMovedParagraphs($old, $new)
    {
        $old = trim(strtolower($old));
        $new = trim(strtolower($new));
        return ($old == $new);
    }

    /**
     * @param string[] $paras
     * @param string $class
     * @return array
     */
    private static function findBlocksWithClasses($paras, $class)
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

    /**
     * @param string $paragraph
     * @param string $matchFull
     * @param int $otherPara
     * @param string $pairId
     * @return string
     */
    private static function addMarkup($paragraph, $matchFull, $otherPara, $pairId)
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
    public static function markupMovedParagraphs($paras)
    {
        $deleted  = static::findBlocksWithClasses($paras, 'deleted');
        $inserted = static::findBlocksWithClasses($paras, 'inserted');

        foreach ($inserted as $ins) {
            foreach ($deleted as $del) {
                if (static::compareMovedParagraphs($ins['inner'], $del['inner'])) {
                    $pairid              = static::$MOVING_PARTNER_COUNT++;
                    $paras[$ins['para']] = static::addMarkup($paras[$ins['para']], $ins['full'], $del['para'], $pairid);
                    $paras[$del['para']] = static::addMarkup($paras[$del['para']], $del['full'], $ins['para'], $pairid);
                }
            }
        }

        return $paras;
    }

    /**
     * @param string $html
     * @return string[]
     */
    private static function getBlocks($html)
    {
        $found = [];
        preg_match_all('/<(?<tag>div|p|ul|li|blockquote|h1|h2|h3|h4|h5|h6)[^>]*>.*<\/\1>/siuU', $html, $matches);
        foreach ($matches[0] as $html) {
            $found[] = $html;
        }
        return $found;
    }

    /**
     * @param array $paras
     * @return array
     */
    private static function extractInsertsFromArrays($paras)
    {
        $inserts = [];
        foreach ($paras as $paraNo => $para) {
            foreach ($para as $wordNo => $word) {
                if (strpos($word['diff'], '###INS_START###') !== false) {
                    $insBlocks = explode('###INS_START###', $word['diff']);
                    for ($i = 1; $i < count($insBlocks); $i++) {
                        $txt = explode('###INS_END###', $insBlocks[$i]);
                        foreach (static::getBlocks($txt[0]) as $block) {
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
     * @param array $paras
     * @return array
     */
    private static function extractDeletesFromArray($paras)
    {
        $deletes = [];
        foreach ($paras as $paraNo => $para) {
            $currDelBlockStart = null;
            $currDelBlock      = null;
            foreach ($para as $wordNo => $word) {
                if ($currDelBlockStart === null) {
                    // If a DEL starts and ends at the same word, it only deletes a single word
                    // As we're only interested in larger deleted blocks, we can ignore those cases here
                    if (strpos($word['diff'], '###DEL_START###') !== false &&
                        strpos($word['diff'], '###DEL_END###') === false
                    ) {
                        $x                 = explode('###DEL_START###', $word['diff']);
                        $currDelBlock      = $x[1];
                        $currDelBlockStart = $wordNo;
                    }
                } else {
                    if (strpos($word['diff'], '###DEL_END###') !== false) {
                        $x            = explode('###DEL_END###', $word['diff']);
                        $currDelBlock .= $x[0];
                        foreach (static::getBlocks($currDelBlock) as $block) {
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
                        $currDelBlock .= $word['diff'];
                    }
                }
            }
        }

        return $deletes;
    }

    /**
     * @param array $paras
     * @return array
     */
    public static function markupWordArrays($paras)
    {
        $inserts = static::extractInsertsFromArrays($paras);
        $deletes = static::extractDeletesFromArray($paras);

        foreach ($inserts as $insert) {
            foreach ($deletes as $delete) {
                if (static::compareMovedParagraphs($insert['html'], $delete['html'])) {
                    $pairid = static::$MOVING_PARTNER_COUNT++;
                    for ($i = $insert['word_from']; $i <= $insert['word_to']; $i++) {
                        $paras[$insert['para']][$i]['diff'] = static::addMarkup(
                            $paras[$insert['para']][$i]['diff'],
                            $paras[$insert['para']][$i]['diff'],
                            $delete['para'],
                            $pairid
                        );
                    }
                    for ($i = $delete['word_from']; $i <= $delete['word_to']; $i++) {
                        $paras[$delete['para']][$i]['diff'] = static::addMarkup(
                            $paras[$delete['para']][$i]['diff'],
                            $paras[$delete['para']][$i]['diff'],
                            $insert['para'],
                            $pairid
                        );
                    }
                }
            }
        }

        return $paras;
    }
}
