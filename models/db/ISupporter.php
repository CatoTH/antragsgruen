<?php

namespace app\models\db;

use yii\db\ActiveRecord;

abstract class ISupporter extends ActiveRecord
{
    const ROLE_INITIATOR = 'initiates';
    const ROLE_SUPPORTER = 'supports';
    const ROLE_LIKE      = 'likes';
    const ROLE_DISLIKE   = 'dislikes';

    public static function getRoles()
    {
        return [
            'initiates' => 'InitiatorIn',
            'supports'  => 'UnterstützerIn',
            'like'      => 'Mag',
            'dislikes'  => 'Mag nicht',
        ];
    }
}
