<?php

namespace app\components\diff\amendmentMerger;

class CollidingParagraphDiff extends ParagraphDiff
{
    /** @var int[] */
    public $collidingAmendmentIds;

    /** @var ParagraphDiffGroup[] */
    public $collidingGroups;

    public function __construct(int $amendment, int $firstDiff, array $diff, array $collidingAmendmentIds, array $collidingGroups) {
        parent::__construct($amendment, $firstDiff, $diff);
        $this->collidingAmendmentIds = $collidingAmendmentIds;
        $this->collidingGroups = $collidingGroups;
    }
}
