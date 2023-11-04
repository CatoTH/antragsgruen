<?php

declare(strict_types=1);

namespace app\models\db;

class MotionSectionParagraph
{
    public int $paragraphNo;
    public int $paragraphNoWithoutSplitLists;
    public string $origStr;

    /** @var string[] */
    public array $lines;

    /** @var MotionComment[] */
    public array $comments;

    /** @var MotionSectionParagraphAmendment[] */
    public array $amendmentSections;

    public function getNumOfAllVisibleComments(bool $screeningAdmin): int
    {
        return count(array_filter($this->comments, function (IComment $comment) use ($screeningAdmin) {
            return ($comment->status === IComment::STATUS_VISIBLE ||
                ($screeningAdmin && $comment->status === IComment::STATUS_SCREENING));
        }));
    }

    /**
     * @param null|int $parentId - null == only root level comments
     * @return MotionComment[]
     */
    public function getVisibleComments(bool $screeningAdmin, ?int $parentId): array
    {
        $statuses = [MotionComment::STATUS_VISIBLE];
        if ($screeningAdmin) {
            $statuses[] = MotionComment::STATUS_SCREENING;
        }
        return array_filter($this->comments, function (MotionComment $comment) use ($statuses, $parentId) {
            if (!in_array($comment->status, $statuses)) {
                return false;
            }
            return ($parentId === $comment->parentCommentId);
        });
    }
}
