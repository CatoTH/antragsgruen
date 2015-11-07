<?php
namespace app\models\db;

class MotionSectionParagraph
{
    /** @var int */
    public $paragraphNo;

    /** @var string */
    public $origStr;

    /** @var string[] */
    public $lines;

    /** MotionComment[] */
    public $comments;

    /** @var MotionSectionParagraphAmendment[] */
    public $amendmentSections;

    /**
     * @param bool $screeningAdmin
     * @return MotionComment[]
     */
    public function getVisibleComments($screeningAdmin)
    {
        $visibleStati = [MotionComment::STATUS_VISIBLE];
        if ($screeningAdmin) {
            $visibleStati[] = MotionComment::STATUS_SCREENING;
        }
        $comments = [];
        foreach ($this->comments as $comment) {
            if (in_array($comment->status, $visibleStati)) {
                $comments[] = $comment;
            }
        }
        return $comments;
    }
}
