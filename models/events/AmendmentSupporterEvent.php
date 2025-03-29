<?php

declare(strict_types=1);

namespace app\models\events;

use app\models\db\AmendmentSupporter;
use yii\base\Event;

class AmendmentSupporterEvent extends Event
{
    public function __construct(
        public AmendmentSupporter $supporter,
        public bool $hadEnoughSupportersBefore
    ) {
        parent::__construct([]);
    }
}
