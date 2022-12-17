<?php

namespace app\components;

class StringSplitter
{
    /**
     * Split string at any of the given delimiters
     *
     * @param string[] $delimiters
     *
     * @return string[]
     */
    public static function split(array $delimiters, string $string): array
    {
        $pieces = [$string];
        foreach ($delimiters as $delimiter) {
            $newPieces = [];
            foreach ($pieces as $piece) {
                foreach (explode($delimiter, $piece) as $newPiece) {
                    $newPieces[] = trim($newPiece);
                }
            }
            $pieces = $newPieces;
        }

        return $pieces;
    }

    /**
     * Return first piece before any of the given delimiters
     *
     * @param string[] $delimiters
     */
    public static function first(array $delimiters, string $string): string
    {
        foreach ($delimiters as $delimiter) {
            /** @var string[] $parts */
            $parts = explode($delimiter, $string);
            $string = $parts[0];
        }

        return trim($string);
    }
}
