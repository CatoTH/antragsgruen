<?php

namespace app\models\db;

class MotionSectionParagraphAmendment
{
    /**
     * @param int $amendmentId
     * @param int $sectionId
     * @param int $paragraphNo
     * @param string $diff
     * @param int $firstLine
     */
    public function __construct($amendmentId, $sectionId, $paragraphNo, $diff, $firstLine)
    {
        $this->amendmentId       = $amendmentId;
        $this->sectionId         = $sectionId;
        $this->origParagraphNo   = $paragraphNo;
        $this->strDiff           = $diff;
        $this->firstAffectedLine = $firstLine;
    }

    /** @var string */
    public $strDiff;

    /** @var int */
    public $amendmentId;
    public $sectionId;
    public $origParagraphNo;
    public $firstAffectedLine;
}
