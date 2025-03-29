<?php

declare(strict_types=1);

namespace app\models\events;

use app\models\db\User;
use yii\base\Event;

class UserEvent extends Event
{
    public function __construct(
        public User $user
    ) {
        parent::__construct([]);
    }
}
