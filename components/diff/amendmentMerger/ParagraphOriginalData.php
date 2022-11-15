<?php

namespace app\components\diff\amendmentMerger;

use app\components\diff\DataTypes\ParagraphMergerWord;

class ParagraphOriginalData
{
    public string $original;

    /** @var string[] */
    public array $origTokenized;

    /** @var ParagraphMergerWord[] */
    public array $words;

    /** @var CollidingParagraphDiff[] */
    public array $collidingParagraphs = [];

    /**
     * @param string[] $origTokenized
     * @param ParagraphMergerWord[] $words
     */
    public function __construct(string $original, array $origTokenized, array $words)
    {
        $this->original = $original;
        $this->origTokenized = $origTokenized;
        $this->words = $words;
    }
}
