<?php

namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @property integer $id
 * @property integer $userId
 * @property integer $paragraph
 * @property string $text
 * @property string $dateCreated
 * @property integer $status
 * @property User $user
 */
abstract class IComment extends ActiveRecord
{

    const STATUS_SCREENING = 1;
    const STATUS_VISIBLE   = 0;
    const STATUS_DELETED   = -1;

    /**
     * @return string[]
     */
    public static function getStati()
    {
        return [
            static::STATUS_SCREENING => 'Nicht freigeschaltet',
            static::STATUS_VISIBLE => 'Sichtbar',
            static::STATUS_DELETED => 'Gelöscht',
        ];
    }


    /**
     * @return Consultation
     */
    abstract public function getConsultation();

    /**
     * @return string
     */
    abstract public function getMotionTitle();

    /**
     * @param bool $absolute
     * @return string
     */
    abstract public function getLink($absolute = false);

    /**
     * @param User $user
     * @return bool
     */
    public function canDelete($user)
    {
        if ($user->hasPrivilege($this->getConsultation(), User::PRIVILEGE_SCREENING)) {
            return true;
        }
        if (!is_null($this->user->auth) && $user->auth == $this->user->auth) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isVisibleCurrUser()
    {
        if ($this->status == static::STATUS_DELETED) {
            return false;
        }
        if ($this->status == static::STATUS_VISIBLE) {
            return true;
        }

        $user = \Yii::$app->user;
        if ($user->isGuest) {
            return false;
        }
        /** @var User $identity */
        $identity = $user->identity;
        if ($identity->hasPrivilege($this->getConsultation(), User::PRIVILEGE_SCREENING)) {
            return true;
        }

        return ($identity->id == $this->userId);
    }
}
