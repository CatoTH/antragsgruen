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
     * @param array $match
     * @param array $other
     * @param string $parId
     * @return string
     */
    private static function addMarkup($paragraph, $match, $other, $parId)
    {
        $pattern = '/^<(?<tag>\w+)' .
            '(?<inbetween> [^>]*class *= *["\'])' .
            '(?<classes>[^"\']*)' .
            '(?<remainder>["\'][^>]*>)/siu';

        $new = preg_replace_callback($pattern, function ($matches) use ($other, $parId) {
            $classes   = explode(' ', $matches['classes']);
            $classes[] = 'moved';

            $repl = '<' . $matches['tag'] . ' data-moving-partner-id="' . $parId . '"';
            $repl .= ' data-moving-partner-paragraph="' . $other['para'] . '"';
            $repl .= $matches['inbetween'] . implode(' ', $classes) . $matches['remainder'];

            return $repl;
        }, $match['full']);

        return str_replace($match['full'], $new, $paragraph);
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
                    $paras[$ins['para']] = static::addMarkup($paras[$ins['para']], $ins, $del, $pairid);
                    $paras[$del['para']] = static::addMarkup($paras[$del['para']], $del, $ins, $pairid);
                }
            }
        }

        return $paras;
    }
}
