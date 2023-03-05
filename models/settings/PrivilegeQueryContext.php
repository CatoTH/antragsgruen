<?php

declare(strict_types=1);

namespace app\models\settings;

use app\models\db\{Amendment, IMotion, Motion};

class PrivilegeQueryContext
{
    public ?Motion $motion = null;
    public ?Amendment $amendment= null;
    public ?int $agendaItemId = null;
    public ?int $tagId = null;
    public ?int $motionTypeId = null;

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
}
