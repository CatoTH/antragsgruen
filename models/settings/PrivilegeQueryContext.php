<?php

declare(strict_types=1);

namespace app\models\settings;

use app\models\db\{Amendment, IMotion, Motion};

class PrivilegeQueryContext
{
    private ?Motion $motion = null;
    private ?Amendment $amendment= null;
    private ?int $agendaItemId = null;
    private ?int $tagId = null;
    private ?int $motionTypeId = null;
    private bool $anyRestriction = false;

    public static function motion(Motion $motion): self
    {
        $obj = new self();
        $obj->motion = $motion;
        return $obj;
    }

    public static function amendment(Amendment $amendment): self
    {
        $obj = new self();
        $obj->amendment = $amendment;
        return $obj;
    }

    public static function imotion(IMotion $IMotion): self
    {
        $obj = new self();
        if (is_a($IMotion, Amendment::class)) {
            $obj->amendment = $IMotion;
        }
        if (is_a($IMotion, Motion::class)) {
            $obj->motion = $IMotion;
        }
        return $obj;
    }

    public static function motionType(int $motionTypeId): self
    {
        $obj = new self();
        $obj->motionTypeId = $motionTypeId;
        return $obj;
    }

    public static function tag(int $tagId): self
    {
        $obj = new self();
        $obj->tagId = $tagId;
        return $obj;
    }

    public static function agendaItem(int $agendaItemId): self
    {
        $obj = new self();
        $obj->agendaItemId = $agendaItemId;
        return $obj;
    }

    public static function anyRestriction(): self
    {
        $obj = new self();
        $obj->anyRestriction = true;

        return $obj;
    }

    public function matchesAgendaItemId(int $agendaItemId): bool
    {
        if ($this->anyRestriction) {
            return true;
        }
        if ($this->agendaItemId) {
            return $this->agendaItemId === $agendaItemId;
        }
        if ($this->motion) {
            return $this->motion->agendaItemId === $agendaItemId;
        }
        if ($this->amendment) {
            if ($this->amendment->agendaItemId) {
                // If the amendment is explicitly assigned to an agenda item, then we compare that
                return $this->amendment->agendaItemId === $agendaItemId;
            } else {
                // otherwise, we compare it against the agenda item set to the parent motion
                return $this->amendment->getMyMotion()->agendaItemId === $agendaItemId;
            }
        }
        return false;
    }

    public function matchesMotionTypeId(int $motionTypeId): bool
    {
        if ($this->anyRestriction) {
            return true;
        }
        if ($this->motionTypeId) {
            return $this->motionTypeId === $motionTypeId;
        }
        if ($this->motion) {
            return $this->motion->motionTypeId === $motionTypeId;
        }
        if ($this->amendment) {
            return $this->amendment->getMyMotion()->motionTypeId === $motionTypeId;
        }
        return false;
    }

    public function matchesTagId(int $tagId): bool
    {
        if ($this->anyRestriction) {
            return true;
        }
        if ($this->tagId) {
            return $this->tagId === $tagId;
        }
        if ($this->motion) {
            foreach ($this->motion->tags as $tag) {
                if ($tag->id === $tagId) {
                    return true;
                }
            }
            return false;
        }
        if ($this->amendment) {
            foreach ($this->amendment->tags as $tag) {
                if ($tag->id === $tagId) {
                    return true;
                }
            }
            return false;
        }
        return false;
    }
}
