<?php

namespace app\models\events;

use app\models\db\User;
use yii\base\Event;

class UserEvent extends Event
{
    /** @var User */
    public $user;

    public function __construct(User $user)
    {
        parent::__construct([]);
        $this->user = $user;
    }
}
