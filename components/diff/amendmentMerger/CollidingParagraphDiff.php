<?php

namespace app\components\diff\amendmentMerger;

class CollidingParagraphDiff extends ParagraphDiff
{
    /** @var int[] */
    public $collidingAmendmentIds;

    public function __construct(int $amendment, int $firstDiff, array $diff, array $collidingAmendmentIds) {
        parent::__construct($amendment, $firstDiff, $diff);
        $this->collidingAmendmentIds = $collidingAmendmentIds;
    }
}
