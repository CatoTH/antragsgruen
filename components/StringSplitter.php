<?php

namespace app\components;

class StringSplitter
{
    /**
     * Split string at any of the given delimiters
     *
     * @static
     * @param string[] $delimiters
     * @param string $string
     * @return string[]
     */

    public static function split ($delimiters,$string) {
        $pieces = [$string];
        foreach ($delimiters as $delimiter) {
            $newPieces = [];
            foreach ($pieces as $piece)
                foreach (explode ($delimiter,$piece) as $newPiece)
                    $newPieces[] = trim ($newPiece);
            $pieces = $newPieces;
        }
        return $pieces;
    }

    /**
     * Return first piece before any of the given delimiters
     *
     * @static
     * @param string[] delimiters
     * @param string $string
     * @return string
     */

    public static function first ($delimiters,$string) {
        foreach ($delimiters as $delimiter)
            $string = explode ($delimiter,$string) [0];
        return trim ($string);
    }
}