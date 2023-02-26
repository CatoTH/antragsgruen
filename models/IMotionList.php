<?php

declare(strict_types=1);

namespace app\models;

use app\models\db\{Amendment, IVotingItem, Motion, VotingQuestion};

class IMotionList
{
    /** @var int[] */
    public array $motionIds = [];

    /** @var int[] */
    public array $amendmentIds = [];

    /** @var int[] */
    public array $questionIds = [];

    public function addMotion(Motion $motion): void {
        $this->motionIds[] = $motion->id;
    }

    public function hasMotion(Motion $motion): bool {
        return in_array($motion->id, $this->motionIds);
    }

    public function addQuestion(VotingQuestion $question): void {
        $this->questionIds[] = $question->id;
    }

    public function hasQuestion(VotingQuestion $question): bool {
        return in_array($question->id, $this->questionIds);
    }

    public function addAmendment(Amendment $amendment): void {
        $this->amendmentIds[] = $amendment->id;
    }

    public function hasAmendment(Amendment $amendment): bool {
        return in_array($amendment->id, $this->amendmentIds);
    }

    public function addVotingItem(IVotingItem $item): void {
        if (is_a($item, Motion::class)) {
            $this->motionIds[] = $item->id;
        }
        if (is_a($item, Amendment::class)) {
            $this->amendmentIds[] = $item->id;
        }
        if (is_a($item, VotingQuestion::class)) {
            $this->questionIds[] = $item->id;
        }
    }

    public function hasVotingItem(IVotingItem $item): bool {
        if (is_a($item, Motion::class)) {
            return in_array($item->id, $this->motionIds);
        }
        if (is_a($item, Amendment::class)) {
            return in_array($item->id, $this->amendmentIds);
        }
        if (is_a($item, VotingQuestion::class)) {
            return in_array($item->id, $this->questionIds);
        }
        return false;
    }

    public function addIMotionList(IMotionList $list): void {
        $this->motionIds = array_unique(array_merge($this->motionIds, $list->motionIds));
        $this->amendmentIds = array_unique(array_merge($this->amendmentIds, $list->amendmentIds));
        $this->questionIds = array_unique(array_merge($this->questionIds, $list->questionIds));
    }
}
