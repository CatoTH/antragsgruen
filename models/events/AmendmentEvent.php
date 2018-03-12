<?php

namespace app\models\events;

use app\models\db\Amendment;
use yii\base\Event;

class AmendmentEvent extends Event
{
    /** @var Amendment */
    public $amendment;

    public function __construct(Amendment $amendment)
    {
        parent::__construct([]);
        $this->amendment = $amendment;
    }
}
