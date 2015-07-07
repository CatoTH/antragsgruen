<?php

namespace app\models\db;

class MotionSectionParagraphAmendment
{
    /**
     * @param AmendmentSection $amSec
     * @param $paragraphNo
     * @param $diff
     */
    public function __construct(AmendmentSection $amSec, $paragraphNo, $diff)
    {
        $this->amendmentSection = $amSec;
        $this->origParagraphNo  = $paragraphNo;
        $this->strDiff          = $diff;
    }

    /** @var AmendmentSection */
    public $amendmentSection;

    /** @var string */
    public $strDiff;

    /** @var int */
    public $origParagraphNo;
}
