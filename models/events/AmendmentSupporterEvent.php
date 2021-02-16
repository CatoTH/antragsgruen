<?php

namespace app\models\events;

use app\models\db\AmendmentSupporter;
use yii\base\Event;

class AmendmentSupporterEvent extends Event
{
    /** @var AmendmentSupporter */
    public $supporter;

    /** @var bool */
    public $hadEnoughSupportersBefore;

    public function __construct(AmendmentSupporter $supporter, bool $hadEnoughSupportersBefore)
    {
        parent::__construct([]);
        $this->supporter = $supporter;
        $this->hadEnoughSupportersBefore = $hadEnoughSupportersBefore;
    }
}
