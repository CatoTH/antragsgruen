<?php

declare(strict_types=1);

namespace app\models\events;

use app\models\db\MotionSupporter;
use yii\base\Event;

class MotionSupporterEvent extends Event
{
    public function __construct(
        public MotionSupporter $supporter,
        public bool $hadEnoughSupportersBefore
    ) {
        parent::__construct([]);
    }
}
