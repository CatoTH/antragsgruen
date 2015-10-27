<?php

namespace app\models\db;

class MotionSectionParagraphAmendment
{
    /**
     * @param AmendmentSection $amSec
     * @param int $paragraphNo
     * @param string $diff
     * @param int $firstLine
     */
    public function __construct($amSec, $paragraphNo, $diff, $firstLine)
    {
        $this->amendmentSection  = $amSec;
        $this->origParagraphNo   = $paragraphNo;
        $this->strDiff           = $diff;
        $this->firstAffectedLine = $firstLine;
    }

    /** @var AmendmentSection */
    public $amendmentSection;

    /** @var string */
    public $strDiff;

    /** @var int */
    public $origParagraphNo;
    public $firstAffectedLine;
}
