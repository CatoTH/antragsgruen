<?php

namespace app\models\db;

use app\components\Tools;
use yii\db\ActiveRecord;
use yii\helpers\Html;

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
 *
 * @property User|null $user
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userId']);
    }

    /**
     * @return string
     */
    public function getNameWithOrga()
    {
        $name = $this->name;
        if ($name == '' && $this->user) {
            $name = $this->user->name;
        }
        if ($this->organization != "") {
            $name .= " (" . trim($this->organization, " \t\n\r\0\x0B()") . ")";
        }
        return $name;
    }

    /**
     * @param bool $html
     * @return string
     */
    public function getNameWithResolutionDate($html = true)
    {
        if ($html) {
            $name = Html::encode($this->getNameWithOrga());
            if ($this->resolutionDate > 0) {
                $name .= " <small style='font-weight: normal;'>(Beschlossen: ";
                $name .= Tools::formatMysqlDate($this->resolutionDate) . ")</small>";
            }
        } else {
            $name = $this->getNameWithOrga();
            if ($this->resolutionDate > 0) {
                $name .= " (Beschlossen: " . Tools::formatMysqlDate($this->resolutionDate) . ")";
            }
        }
        return $name;
    }
}
