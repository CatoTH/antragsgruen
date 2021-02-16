<?php

namespace app\models\events;

use app\models\db\MotionSupporter;
use yii\base\Event;

class MotionSupporterEvent extends Event
{
    /** @var MotionSupporter */
    public $supporter;

    /** @var bool */
    public $hadEnoughSupportersBefore;

    public function __construct(MotionSupporter $supporter, bool $hadEnoughSupportersBefore)
    {
        parent::__construct([]);
        $this->supporter = $supporter;
        $this->hadEnoughSupportersBefore = $hadEnoughSupportersBefore;
    }
}
