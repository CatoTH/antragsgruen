<?php

namespace app\models\events;

use app\models\db\MotionSupporter;
use yii\base\Event;

class MotionSupporterEvent extends Event
{
    /** @var MotionSupporter */
    public $supporter;

    public function __construct(MotionSupporter $supporter)
    {
        parent::__construct([]);
        $this->supporter = $supporter;
    }
}
