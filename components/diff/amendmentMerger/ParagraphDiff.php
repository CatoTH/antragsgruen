<?php

namespace app\components\diff\amendmentMerger;

class ParagraphDiff
{
    /** @var integer */
    public $firstDiff;

    /** @var integer */
    public $amendment;

    /** @var array */
    public $diff;

    /**
     * ParagraphDiff constructor.
     * @param int $amendment
     * @param int $firstDiff
     * @param array $diff
     */
    public function __construct($amendment, $firstDiff, array $diff)
    {
        $this->firstDiff = $firstDiff;
        $this->amendment = $amendment;
        $this->diff      = $diff;
    }
}
