<?php

namespace app\components\diff\amendmentMerger;

class ParagraphOriginalData
{
    /** @var string */
    public $original;

    /** @var string[] */
    public $origTokenized;

    /** @var array */
    public $words;

    /** @var ParagraphDiff[] */
    public $collidingParagraphs = [];

    /**
     * ParagraphOriginalData constructor.
     * @param string $original
     * @param string[] $origTokenized
     * @param array $words
     */
    public function __construct($original, array $origTokenized, array $words)
    {
        $this->original      = $original;
        $this->origTokenized = $origTokenized;
        $this->words         = $words;
    }
}
