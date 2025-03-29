<?php

declare(strict_types=1);

namespace app\models\events;

use app\models\db\Motion;
use yii\base\Event;

class MotionEvent extends Event
{
    public function __construct(
        public Motion $motion
    ) {
        parent::__construct([]);
    }
}
