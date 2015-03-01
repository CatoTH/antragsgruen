<?php

namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @property int $position
 * @property int $userId
 * @property string $role
 * @property string $comment
 * @property int $personType
 * @property string $name
 * @property string $organization
 * @property string $resolutionDate
 * @property string $contactEmail
 * @property string $contactPhone
 */
abstract class ISupporter extends ActiveRecord
{
    const ROLE_INITIATOR = 'initiates';
    const ROLE_SUPPORTER = 'supports';
    const ROLE_LIKE      = 'likes';
    const ROLE_DISLIKE   = 'dislikes';

    const PERSON_NATURAL      = 0;
    const PERSON_ORGANIZATION = 1;

    /**
     * @return string[]
     */
    public static function getRoles()
    {
        return [
            static::ROLE_INITIATOR => 'InitiatorIn',
            static::ROLE_SUPPORTER => 'UnterstützerIn',
            static::ROLE_LIKE      => 'Mag',
            static::ROLE_DISLIKE   => 'Mag nicht',
        ];
    }

    /**
     * @return string[]
     */
    public static function getPersonTypes()
    {
        return [
            static::PERSON_NATURAL      => 'Natürliche Person',
            static::PERSON_ORGANIZATION => 'Organisation / Gremium',
        ];
    }
}
