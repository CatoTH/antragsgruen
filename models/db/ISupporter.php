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
 * @property string $contactName
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
            static::ROLE_INITIATOR => \Yii::t('structure', 'role_initiator'),
            static::ROLE_SUPPORTER => \Yii::t('structure', 'role_supporter'),
            static::ROLE_LIKE      => \Yii::t('structure', 'role_likes'),
            static::ROLE_DISLIKE   => \Yii::t('structure', 'role_dislikes'),
        ];
    }

    /**
     * @return string[]
     */
    public static function getPersonTypes()
    {
        return [
            static::PERSON_NATURAL      => \Yii::t('structure', 'person_type_natural'),
            static::PERSON_ORGANIZATION => \Yii::t('structure', 'person_type_orga'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }

    /**
     * @return boolean
     */
    public function isDataFixed()
    {
        $user = $this->user;
        return ($user && $user->fixedData == 1);
    }

    /**
     * @return string
     */
    public function getNameWithOrga()
    {
        if ($this->personType == static::PERSON_NATURAL) {
            $name = $this->name;
            if ($name == '' && $this->user) {
                $name = $this->user->name;
            }
            if ($this->organization != '') {
                $name .= ' (' . trim($this->organization, " \t\n\r\0\x0B()") . ')';
            }
            return $name;
        } else {
            return trim($this->organization, " \t\n\r\0\x0B()");
        }
    }

    /**
     * @param bool $html
     * @return string
     */
    public function getNameWithResolutionDate($html = true)
    {
        if ($html) {
            $name = $this->name;
            $orga = trim($this->organization, " \t\n\r\0\x0B()");
            if ($name == '' && $this->user) {
                $name = $this->user->name;
            }
            if ($this->personType == static::PERSON_NATURAL) {
                if ($orga != '') {
                    $name .= ' <small style="font-weight: normal;">';
                    $name .= '(' . Html::encode($orga) . ')';
                    $name .= '</small>';
                }
                return $name;
            } else {
                if ($this->resolutionDate > 0) {
                    $orga .= ' <small style="font-weight: normal;">(';
                    $orga .= \Yii::t('motion', 'resolution_on') . ': ';
                    $orga .= Tools::formatMysqlDate($this->resolutionDate, null, false);
                    $orga .= ')</small>';
                }
                return $orga;
            }
        } else {
            $name = $this->name;
            $orga = trim($this->organization, " \t\n\r\0\x0B()");
            if ($name == '' && $this->user) {
                $name = $this->user->name;
            }
            if ($this->personType == static::PERSON_NATURAL) {
                if ($orga != '') {
                    $name .= ' (' . Html::encode($orga) . ')';
                }
                return $name;
            } else {
                if ($this->resolutionDate > 0) {
                    $orga .= ' (' . \Yii::t('motion', 'resolution_on') . ': ';
                    $orga .= Tools::formatMysqlDate($this->resolutionDate, null, false) . ')';
                }
                return $orga;
            }
        }
    }

    /**
     * @return string
     */
    public function getGivenNameOrFull()
    {
        if ($this->user && $this->personType == static::PERSON_NATURAL) {
            if ($this->user->nameGiven) {
                return $this->user->nameGiven;
            } else {
                return $this->name;
            }
        } else {
            return $this->name;
        }
    }
}
