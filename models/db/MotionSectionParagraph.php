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
     * @return int
     */
    public function getNumOfAllVisibleComments($screeningAdmin)
    {
        return count(array_filter($this->comments, function (IComment $comment) use ($screeningAdmin) {
            return ($comment->status === IComment::STATUS_VISIBLE ||
                ($screeningAdmin && $comment->status === IComment::STATUS_SCREENING));
        }));
    }

    /**
     * @param bool $screeningAdmin
     * @param null|int $parentId - null == only root level comments
     * @return MotionComment[]
     */
    public function getVisibleComments($screeningAdmin, $parentId)
    {
        $stati = [MotionComment::STATUS_VISIBLE];
        if ($screeningAdmin) {
            $stati[] = MotionComment::STATUS_SCREENING;
        }
        return array_filter($this->comments, function (MotionComment $comment) use ($stati, $parentId) {
            if (!in_array($comment->status, $stati)) {
                return false;
            }
            return ($parentId === $comment->parentCommentId);
        });
    }
}
