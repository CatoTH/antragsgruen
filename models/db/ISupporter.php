<?php

namespace app\models\db;

use app\components\Tools;
use yii\db\ActiveRecord;
use yii\helpers\Html;

/**
 * @property int $id
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
 * @property string $dateCreation
 * @property string $extraData
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
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }

    public function getMyUser(): ?User
    {
        if ($this->userId) {
            return User::getCachedUser($this->userId);
        } else {
            return null;
        }
    }

    public function isDataFixed(): bool
    {
        return ($this->getMyUser() && $this->getMyUser()->fixedData == 1);
    }

    public function getNameWithOrga(): string
    {
        if ($this->personType === static::PERSON_NATURAL || $this->personType === null) {
            $name = $this->name;
            if ($name == '' && $this->getMyUser()) {
                $name = $this->getMyUser()->name;
            }
            if ($this->organization != '') {
                $name .= ' (' . trim($this->organization, " \t\n\r\0\x0B()") . ')';
            }
            return $name;
        } else {
            return trim($this->organization, " \t\n\r\0\x0B()");
        }
    }

    public function getNameWithResolutionDate(bool $html = true): string
    {
        if ($html) {
            $name = Html::encode($this->name);
            $orga = Html::encode(trim($this->organization, " \t\n\r\0\x0B"));
            if ($name == '' && $this->getMyUser()) {
                $name = Html::encode($this->getMyUser()->name);
            }
            if ($this->personType === static::PERSON_NATURAL || $this->personType === null) {
                if ($orga != '') {
                    $name .= ' <small style="font-weight: normal;">';
                    $name .= '(' . $orga . ')';
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
            $orga = trim($this->organization, " \t\n\r\0\x0B");
            if ($name == '' && $this->getMyUser()) {
                $name = $this->getMyUser()->name;
            }
            if ($this->personType === static::PERSON_NATURAL || $this->personType === null) {
                if ($orga !== '') {
                    $name .= ' (' . $orga . ')';
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

    public function getGivenNameOrFull(): string
    {
        if ($this->getMyUser() && $this->personType === static::PERSON_NATURAL || $this->personType === null) {
            if ($this->getMyUser()->nameGiven) {
                return $this->getMyUser()->nameGiven;
            } else {
                return $this->name;
            }
        } else {
            return $this->name;
        }
    }

    /**
     * @param array $values
     * @param bool $safeOnly
     */
    public function setAttributes($values, $safeOnly = true)
    {
        parent::setAttributes($values, $safeOnly);
        if (!isset($values['extraData']) || $values['extraData'] === null) {
            $this->setExtraDataEntry('gender', (isset($values['gender']) ? $values['gender'] : null));
        }
        $this->personType = IntVal($this->personType);
        $this->position   = IntVal($this->position);
        $this->userId     = ($this->userId === null ? null : IntVal($this->userId));
    }

    /**
     * @param string $name
     * @param null|mixed $default
     * @return mixed
     */
    public function getExtraDataEntry(string $name, $default = null)
    {
        $arr = json_decode($this->extraData, true);
        if ($arr && isset($arr[$name])) {
            return $arr[$name];
        } else {
            return $default;
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setExtraDataEntry(string $name, $value)
    {
        $arr = json_decode($this->extraData, true);
        if (!$arr) {
            $arr = [];
        }
        if ($value !== null) {
            $arr[$name] = $value;
        } else {
            unset($arr[$name]);
        }
        $this->extraData = json_encode($arr, JSON_PRETTY_PRINT);
    }

    /**
     * @return IMotion
     */
    abstract public function getIMotion();
}
