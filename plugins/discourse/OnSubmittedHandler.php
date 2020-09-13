<?php

namespace app\plugins\discourse;

use app\models\events\{AmendmentEvent, MotionEvent};

class OnSubmittedHandler
{
    public static function onAmendmentPublished(AmendmentEvent $event): void
    {
        $amendment = $event->amendment;
    }

    public static function onAmendmentSubmitted(AmendmentEvent $event): void
    {
        // @TODO: Restrict to amendments with collection phase
        static::onAmendmentPublished($event);
    }

    public static function onMotionPublished(MotionEvent $event): void
    {
        $motion = $event->motion;
    }

    public static function onMotionSubmitted(MotionEvent $event): void
    {
        // @TODO: Restrict to motions with collection phase
        static::onMotionPublished($event);
    }
}
