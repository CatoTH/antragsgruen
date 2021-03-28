<?php

namespace app\components\diff\amendmentMerger;

use app\components\diff\DataTypes\ParagraphMergerWord;

class ParagraphOriginalData
{
    /** @var string */
    public $original;

    /** @var string[] */
    public $origTokenized;

    /** @var ParagraphMergerWord[] */
    public $words;

    /** @var ParagraphDiff[] */
    public $collidingParagraphs = [];

    /**
     * @param string $original
     * @param string[] $origTokenized
     * @param array $words
     */
    public function __construct($original, $origTokenized, $words)
    {
        $this->original      = $original;
        $this->origTokenized = $origTokenized;
        $this->words         = $words;
    }
}
