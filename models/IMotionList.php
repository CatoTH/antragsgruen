<?php

declare(strict_types=1);

namespace app\models;

use app\models\db\{Amendment, IMotion, Motion};

class IMotionList
{
    /** @var int[] */
    public $motionIds = [];

    /** @var int[] */
    public $amendmentIds = [];

    public function addMotion(Motion $motion): void {
        $this->motionIds[] = $motion->id;
    }

    public function hasMotion(Motion $motion): bool {
        return in_array($motion->id, $this->motionIds);
    }

    public function addAmendment(Amendment $amendment): void {
        $this->amendmentIds[] = $amendment->id;
    }

    public function hasAmendment(Amendment $amendment): bool {
        return in_array($amendment->id, $this->amendmentIds);
    }

    public function addIMotion(IMotion $IMotion): void {
        if (is_a($IMotion, Motion::class)) {
            $this->motionIds[] = $IMotion->id;
        }
        if (is_a($IMotion, Amendment::class)) {
            $this->amendmentIds[] = $IMotion->id;
        }
    }

    public function hasIMotion(IMotion $IMotion): bool {
        if (is_a($IMotion, Motion::class)) {
            return in_array($IMotion->id, $this->motionIds);
        }
        if (is_a($IMotion, Amendment::class)) {
            return in_array($IMotion->id, $this->amendmentIds);
        }
        return false;
    }

    public function addIMotionList(IMotionList $list): void {
        $this->motionIds = array_unique(array_merge($list->motionIds));
        $this->amendmentIds = array_unique(array_merge($list->amendmentIds));
    }
}
