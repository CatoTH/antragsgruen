<?php

namespace app\models\db;

class MotionSectionParagraphAmendment
{
    public string $strDiff;
    public int $amendmentId;
    public int $sectionId;
    public int $origParagraphNo;
    public int $firstAffectedLine;

    public function __construct(int $amendmentId, int $sectionId, int $paragraphNo, string $diff, int $firstLine)
    {
        $this->amendmentId       = $amendmentId;
        $this->sectionId         = $sectionId;
        $this->origParagraphNo   = $paragraphNo;
        $this->strDiff           = $diff;
        $this->firstAffectedLine = $firstLine;
    }
}
