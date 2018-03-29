<?php

namespace app\models\events;

use app\models\db\Motion;
use yii\base\Event;

class MotionEvent extends Event
{
    /** @var Motion */
    public $motion;

    public function __construct(Motion $motion)
    {
        parent::__construct([]);
        $this->motion = $motion;
    }
}
