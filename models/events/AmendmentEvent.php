<?php

declare(strict_types=1);

namespace app\models\events;

use app\models\db\Amendment;
use yii\base\Event;

class AmendmentEvent extends Event
{
    public function __construct(
        public Amendment $amendment
    ) {
        parent::__construct([]);
    }
}
