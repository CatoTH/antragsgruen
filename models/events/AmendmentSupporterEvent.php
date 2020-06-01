<?php

namespace app\models\events;

use app\models\db\AmendmentSupporter;
use yii\base\Event;

class AmendmentSupporterEvent extends Event
{
    /** @var AmendmentSupporter */
    public $supporter;

    public function __construct(AmendmentSupporter $supporter)
    {
        parent::__construct([]);
        $this->supporter = $supporter;
    }
}
